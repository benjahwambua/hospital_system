<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'insurance', 'view');

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];
$message = '';
$error = '';
$userId = (int)($_SESSION['user_id'] ?? 0);

$baseTable = $conn->query("SHOW TABLES LIKE 'claim_denials'");
$requiredColumns = ['appeal_reference','appeal_submitted_at','appeal_notes','resolution_outcome','resolution_notes','resolved_at','created_by','updated_by'];
$columnCount = 0;
if ($baseTable && $baseTable->num_rows > 0) {
    $check = $conn->query("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='claim_denials' AND column_name IN ('appeal_reference','appeal_submitted_at','appeal_notes','resolution_outcome','resolution_notes','resolved_at','created_by','updated_by')");
    if ($check) $columnCount = (int)($check->fetch_assoc()['c'] ?? 0);
}
$schemaReady = $baseTable && $baseTable->num_rows > 0 && $columnCount === count($requiredColumns);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    require_module_access($conn, 'insurance', in_array($action, ['submit_appeal','resolve_appeal'], true) ? 'approve' : 'create');
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Refresh the page and try again.';
    } elseif (!$schemaReady) {
        $error = 'The denial/appeal database migration is not installed. Apply database/insurance_denial_appeals_migration.sql after database/insurance_migration.sql.';
    } else {
        try {
            if ($action === 'record_denial') {
                $claimId = (int)($_POST['claim_id'] ?? 0);
                $itemId = (int)($_POST['claim_item_id'] ?? 0);
                $itemId = $itemId > 0 ? $itemId : null;
                $code = trim((string)($_POST['denial_code'] ?? ''));
                $reason = trim((string)($_POST['denial_reason'] ?? ''));
                $category = (string)($_POST['denial_category'] ?? 'Other');
                $amount = filter_var($_POST['denied_amount'] ?? null, FILTER_VALIDATE_FLOAT);
                $notes = trim((string)($_POST['notes'] ?? ''));
                $categories = ['Eligibility','Authorization','Benefit Limit','Documentation','Coding','Duplicate','Pricing','Other'];
                if ($claimId <= 0 || $reason === '' || !in_array($category, $categories, true) || $amount === false || $amount <= 0) {
                    throw new Exception('Select a claim and denial category, enter a reason, and provide a denied amount greater than zero.');
                }
                $s = $conn->prepare("SELECT id,total_claim_amount,claim_status FROM claim_headers WHERE id=? AND claim_status<>'Draft' LIMIT 1");
                if (!$s) throw new Exception('Unable to validate the claim.');
                $s->bind_param('i', $claimId); $s->execute(); $claim = $s->get_result()->fetch_assoc(); $s->close();
                if (!$claim) throw new Exception('Select an existing non-draft claim.');
                if ($itemId !== null) {
                    $s = $conn->prepare("SELECT id,gross_amount FROM claim_items WHERE id=? AND claim_id=? LIMIT 1");
                    if (!$s) throw new Exception('Unable to validate the claim item.');
                    $s->bind_param('ii', $itemId, $claimId); $s->execute(); $item = $s->get_result()->fetch_assoc(); $s->close();
                    if (!$item) throw new Exception('The selected claim item does not belong to this claim.');
                    if ($amount > (float)$item['gross_amount']) throw new Exception('Denied amount cannot exceed the selected claim item gross amount.');
                } elseif ($amount > (float)$claim['total_claim_amount']) {
                    throw new Exception('Denied amount cannot exceed the total claim amount.');
                }
                $s = $conn->prepare("INSERT INTO claim_denials(claim_id,claim_item_id,denial_code,denial_reason,denial_category,denied_amount,appeal_status,notes,created_by,updated_by) VALUES(?,?,?,?,?,?,'Not Appealed',?,?,?)");
                if (!$s) throw new Exception('Unable to prepare the denial record.');
                $s->bind_param('iisssdsii', $claimId, $itemId, $code, $reason, $category, $amount, $notes, $userId, $userId);
                if (!$s->execute()) throw new Exception('Unable to save the denial record.');
                $denialId = (int)$s->insert_id; $s->close();
                if (function_exists('audit')) audit('insurance_denial_recorded', "denial_id={$denialId},claim_id={$claimId},category={$category}");
                $message = 'Denial record saved.';
            } elseif ($action === 'submit_appeal') {
                $id = (int)($_POST['denial_id'] ?? 0);
                $reference = trim((string)($_POST['appeal_reference'] ?? ''));
                $appealNotes = trim((string)($_POST['appeal_notes'] ?? ''));
                if ($id <= 0 || $reference === '' || $appealNotes === '') throw new Exception('Appeal reference and appeal notes are required.');
                $s = $conn->prepare("UPDATE claim_denials SET appeal_status='Appealed',appeal_reference=?,appeal_submitted_at=NOW(),appeal_notes=?,updated_by=? WHERE id=? AND appeal_status='Not Appealed'");
                if (!$s) throw new Exception('Unable to prepare the appeal update.');
                $s->bind_param('ssii', $reference, $appealNotes, $userId, $id);
                if (!$s->execute() || $s->affected_rows !== 1) throw new Exception('This denial is not awaiting an appeal, or it was updated by another user.');
                $s->close();
                if (function_exists('audit')) audit('insurance_appeal_submitted', "denial_id={$id},reference={$reference}");
                $message = 'Appeal recorded as submitted. Keep the payer acknowledgement with the claim file.';
            } elseif ($action === 'resolve_appeal') {
                $id = (int)($_POST['denial_id'] ?? 0);
                $outcome = (string)($_POST['resolution_outcome'] ?? '');
                $resolutionNotes = trim((string)($_POST['resolution_notes'] ?? ''));
                $outcomes = ['Approved','Partially Approved','Upheld','Written Off'];
                if ($id <= 0 || !in_array($outcome, $outcomes, true) || $resolutionNotes === '') throw new Exception('Choose an appeal outcome and record resolution notes.');
                $appealStatus = $outcome === 'Written Off' ? 'Written Off' : 'Resolved';
                $s = $conn->prepare("UPDATE claim_denials SET appeal_status=?,resolution_outcome=?,resolution_notes=?,resolved_at=NOW(),updated_by=? WHERE id=? AND appeal_status='Appealed'");
                if (!$s) throw new Exception('Unable to prepare the resolution update.');
                $s->bind_param('sssii', $appealStatus, $outcome, $resolutionNotes, $userId, $id);
                if (!$s->execute() || $s->affected_rows !== 1) throw new Exception('Only an appeal currently marked Appealed can be resolved.');
                $s->close();
                if (function_exists('audit')) audit('insurance_appeal_resolved', "denial_id={$id},outcome={$outcome}");
                $message = 'Appeal resolution recorded. Review and reconcile financial allocations separately; this record does not automatically alter invoice or claim balances.';
            } else {
                throw new Exception('Unknown denial/appeal action.');
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$claims = [];
$itemsByClaim = [];
$denials = [];
if ($schemaReady) {
    $q = $conn->query("SELECT ch.id,ch.claim_number,ch.claim_status,ch.total_claim_amount,p.full_name,py.payer_name FROM claim_headers ch JOIN patients p ON p.id=ch.patient_id JOIN payers py ON py.id=ch.payer_id WHERE ch.claim_status<>'Draft' ORDER BY ch.created_at DESC LIMIT 250");
    if ($q) while ($row = $q->fetch_assoc()) $claims[] = $row;
    $q = $conn->query("SELECT ci.id,ci.claim_id,ci.item_name,ci.gross_amount FROM claim_items ci JOIN claim_headers ch ON ch.id=ci.claim_id WHERE ch.claim_status<>'Draft' ORDER BY ci.claim_id DESC,ci.id ASC LIMIT 1000");
    if ($q) while ($row = $q->fetch_assoc()) $itemsByClaim[(int)$row['claim_id']][] = $row;
    $q = $conn->query("SELECT d.*,ch.claim_number,ch.claim_status,p.full_name,py.payer_name,ci.item_name FROM claim_denials d JOIN claim_headers ch ON ch.id=d.claim_id JOIN patients p ON p.id=ch.patient_id JOIN payers py ON py.id=ch.payer_id LEFT JOIN claim_items ci ON ci.id=d.claim_item_id ORDER BY d.created_at DESC LIMIT 250");
    if ($q) while ($row = $q->fetch_assoc()) $denials[] = $row;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid hms-module-page"><div class="hms-module-shell">
<section class="hms-module-hero"><div><div class="hms-module-kicker">Insurance &amp; SHA · Revenue Cycle</div><h1>Denials &amp; Appeals</h1><p>Record payer denials, track appeal submissions and document outcomes with an auditable history.</p></div><a class="btn" href="index.php"><i class="fas fa-arrow-left mr-2"></i>Insurance Dashboard</a></section>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if (!$schemaReady): ?><div class="alert alert-warning">The denial/appeal workflow migration is not installed. After backing up the database, apply <code>database/insurance_denial_appeals_migration.sql</code> after <code>database/insurance_migration.sql</code>. Actions are disabled until the required fields exist.</div><?php else: ?>
<div class="row">
<div class="col-xl-5 mb-4"><?php if (can_module_action($conn, 'insurance', 'create')): ?><div class="card h-100"><div class="card-header"><strong>Record a payer denial</strong></div><div class="card-body">
<form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="record_denial">
<div class="form-group"><label>Claim</label><select class="form-control" id="claim_id" name="claim_id" required><option value="">Select non-draft claim</option><?php foreach($claims as $claim): ?><option value="<?=(int)$claim['id']?>"> <?=htmlspecialchars($claim['claim_number'])?> · <?=htmlspecialchars($claim['full_name'])?> · <?=htmlspecialchars($claim['payer_name'])?> · <?=htmlspecialchars($claim['claim_status'])?> · KES <?=number_format((float)$claim['total_claim_amount'],2)?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Claim item <span class="text-muted">(optional)</span></label><select class="form-control" id="claim_item_id" name="claim_item_id"><option value="0">Claim-level denial</option><?php foreach($itemsByClaim as $claimId=>$items): foreach($items as $item): ?><option data-claim="<?=(int)$claimId?>" value="<?=(int)$item['id']?>"><?=htmlspecialchars($item['item_name'])?> · KES <?=number_format((float)$item['gross_amount'],2)?></option><?php endforeach; endforeach; ?></select><small class="form-text text-muted">Select a specific item when the payer denied only part of a claim.</small></div>
<div class="form-group"><label>Denial category</label><select class="form-control" name="denial_category" required><?php foreach(['Eligibility','Authorization','Benefit Limit','Documentation','Coding','Duplicate','Pricing','Other'] as $cat): ?><option value="<?=htmlspecialchars($cat)?>"><?=htmlspecialchars($cat)?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Denial code <span class="text-muted">(optional)</span></label><input class="form-control" name="denial_code" maxlength="50"></div>
<div class="form-group"><label>Denial reason</label><input class="form-control" name="denial_reason" maxlength="255" required></div>
<div class="form-group"><label>Denied amount (KES)</label><input class="form-control" name="denied_amount" type="number" min="0.01" step="0.01" required></div>
<div class="form-group"><label>Notes / evidence reference</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
<button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save denial</button></form>
</div></div><?php else: ?><div class="alert alert-info">You have read-only access to this module. Denial creation requires Insurance Create permission.</div><?php endif; ?></div>
<div class="col-xl-7 mb-4"><div class="card h-100"><div class="card-header"><strong>Denial and appeal register</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Claim / Patient</th><th>Denial</th><th>Amount</th><th>Appeal</th><th>Actions</th></tr></thead><tbody>
<?php if (!$denials): ?><tr><td colspan="5" class="text-center text-muted py-4">No denial records have been added.</td></tr><?php else: foreach($denials as $d): ?><tr><td><strong><?=htmlspecialchars($d['claim_number'])?></strong><br><?=htmlspecialchars($d['full_name'])?><br><small><?=htmlspecialchars($d['payer_name'])?></small></td><td><?=htmlspecialchars($d['denial_category'])?>: <?=htmlspecialchars($d['denial_reason'])?><?php if(!empty($d['item_name'])): ?><br><small>Item: <?=htmlspecialchars($d['item_name'])?></small><?php endif; ?><?php if(!empty($d['appeal_reference'])): ?><br><small>Ref: <?=htmlspecialchars($d['appeal_reference'])?></small><?php endif; ?></td><td>KES <?=number_format((float)$d['denied_amount'],2)?></td><td><strong><?=htmlspecialchars($d['appeal_status'])?></strong><?php if(!empty($d['resolution_outcome'])): ?><br><small><?=htmlspecialchars($d['resolution_outcome'])?></small><?php endif; ?><?php if(!empty($d['appeal_submitted_at'])): ?><br><small>Submitted <?=htmlspecialchars($d['appeal_submitted_at'])?></small><?php endif; ?></td><td style="min-width:270px">
<?php if($d['appeal_status']==='Not Appealed' && can_module_action($conn,'insurance','approve')): ?><form method="post" class="mb-2"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="submit_appeal"><input type="hidden" name="denial_id" value="<?=(int)$d['id']?>"><input class="form-control form-control-sm mb-1" name="appeal_reference" maxlength="100" placeholder="Payer appeal reference" required><textarea class="form-control form-control-sm mb-1" name="appeal_notes" rows="2" placeholder="Appeal basis / documents sent" required></textarea><button class="btn btn-sm btn-outline-primary">Record appeal submission</button></form><?php elseif($d['appeal_status']==='Appealed' && can_module_action($conn,'insurance','approve')): ?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="resolve_appeal"><input type="hidden" name="denial_id" value="<?=(int)$d['id']?>"><select class="form-control form-control-sm mb-1" name="resolution_outcome" required><option value="">Outcome</option><?php foreach(['Approved','Partially Approved','Upheld','Written Off'] as $outcome): ?><option value="<?=htmlspecialchars($outcome)?>"><?=htmlspecialchars($outcome)?></option><?php endforeach; ?></select><textarea class="form-control form-control-sm mb-1" name="resolution_notes" rows="2" placeholder="Payer response / resolution notes" required></textarea><button class="btn btn-sm btn-outline-success">Record resolution</button></form><?php else: ?><span class="text-muted small">No action available for this status or permission.</span><?php endif; ?>
</td></tr><?php endforeach; endif; ?>
</tbody></table></div></div></div></div>
<div class="alert alert-info">This register records denial and appeal evidence. It does not automatically change claim approval totals, patient responsibility, invoice balances or remittance allocations; reconcile those separately after payer confirmation.</div>
<?php endif; ?>
</div></div>
<script>
(function(){
 var claim=document.getElementById('claim_id'), item=document.getElementById('claim_item_id');
 if(!claim||!item)return;
 function filterItems(){var selected=claim.value;Array.prototype.forEach.call(item.options,function(opt){if(!opt.value)return;var show=opt.getAttribute('data-claim')===selected;opt.hidden=!show;opt.disabled=!show;if(!show&&opt.selected)item.value='0';});}
 claim.addEventListener('change',filterItems);filterItems();
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
