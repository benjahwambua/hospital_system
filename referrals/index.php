<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'referrals', 'view');

function referral_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$canCreate = can_module_action($conn, 'referrals', 'create');
$canEdit = can_module_action($conn, 'referrals', 'edit');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'create') {
        if (!$canCreate) { http_response_code(403); exit('Forbidden: referral create permission required.'); }
        $patientId = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT) ?: 0;
        $visitId = filter_input(INPUT_POST, 'visit_id', FILTER_VALIDATE_INT) ?: 0;
        $direction = (string)($_POST['direction'] ?? 'Outgoing');
        $urgency = (string)($_POST['urgency'] ?? 'Routine');
        $destination = trim((string)($_POST['destination_facility'] ?? ''));
        $destinationProvider = trim((string)($_POST['destination_provider'] ?? ''));
        $reason = trim((string)($_POST['referral_reason'] ?? ''));
        $summary = trim((string)($_POST['clinical_summary'] ?? ''));
        $requested = trim((string)($_POST['requested_service'] ?? ''));
        $department = trim((string)($_POST['referring_department'] ?? ''));
        $provider = trim((string)($_POST['referring_provider'] ?? ''));
        if ($patientId <= 0 || $destination === '' || $reason === '') {
            $error = 'Patient, destination facility and referral reason are required.';
        } elseif (!in_array($direction, ['Outgoing','Incoming'], true) || !in_array($urgency, ['Routine','Urgent','Emergency'], true)) {
            $error = 'Select a valid referral direction and urgency.';
        } else {
            $p = $conn->prepare('SELECT id FROM patients WHERE id=? LIMIT 1');
            $p->bind_param('i', $patientId); $p->execute(); $exists = (bool)$p->get_result()->fetch_assoc(); $p->close();
            $visitValid = true;
            if ($visitId > 0) {
                $v = $conn->prepare('SELECT id FROM visits WHERE id=? AND patient_id=? LIMIT 1');
                if (!$v) { $visitValid = false; } else {
                    $v->bind_param('ii', $visitId, $patientId); $v->execute(); $visitValid = (bool)$v->get_result()->fetch_assoc(); $v->close();
                }
            } else { $visitId = null; }
            if (!$exists) {
                $error = 'The selected patient was not found.';
            } elseif (!$visitValid) {
                $error = 'The selected visit does not belong to this patient, or the visits schema is unavailable.';
            } else {
                $referralNumber = 'RF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $uid = (int)($_SESSION['user_id'] ?? 0);
                $stmt = $conn->prepare("INSERT INTO referral_cases (referral_number,patient_id,visit_id,direction,urgency,destination_facility,destination_provider,referral_reason,clinical_summary,requested_service,referring_department,referring_provider,status,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'Pending',?,?)");
                if (!$stmt) {
                    $error = 'Unable to prepare the referral. Confirm the referral migration has been applied.';
                } else {
                    $stmt->bind_param('siisssssssssii', $referralNumber,$patientId,$visitId,$direction,$urgency,$destination,$destinationProvider,$reason,$summary,$requested,$department,$provider,$uid,$uid);
                    if ($stmt->execute()) {
                        $newId = (int)$stmt->insert_id;
                        if (function_exists('audit')) audit('referral_created', 'referral_id='.$newId.',number='.$referralNumber.',patient_id='.$patientId);
                        $message = 'Referral ' . $referralNumber . ' was registered.';
                    } else {
                        $error = 'Referral could not be saved. Confirm the migration and required fields.';
                    }
                    $stmt->close();
                }
            }
        }
    } elseif (($_POST['action'] ?? '') === 'update') {
        if (!$canEdit) { http_response_code(403); exit('Forbidden: referral edit permission required.'); }
        $id = filter_input(INPUT_POST, 'referral_id', FILTER_VALIDATE_INT) ?: 0;
        $status = (string)($_POST['status'] ?? 'Pending');
        $appointment = trim((string)($_POST['receiving_appointment_at'] ?? ''));
        $externalReference = trim((string)($_POST['external_reference'] ?? ''));
        $followup = trim((string)($_POST['follow_up_notes'] ?? ''));
        $closureReason = trim((string)($_POST['closure_reason'] ?? ''));
        $allowed = ['Pending','Accepted','Scheduled','Seen','Report Received','Closed','Cancelled'];
        if ($id <= 0 || !in_array($status, $allowed, true)) {
            $error = 'Invalid referral or status.';
        } elseif ($status === 'Closed' && $followup === '') {
            $error = 'Record follow-up / outcome notes before closing a referral.';
        } elseif ($status === 'Cancelled' && $closureReason === '') {
            $error = 'A reason is required to cancel a referral.';
        } else {
            $conn->begin_transaction();
            try {
                $lock = $conn->prepare('SELECT id,status FROM referral_cases WHERE id=? FOR UPDATE');
                $lock->bind_param('i', $id); $lock->execute(); $current = $lock->get_result()->fetch_assoc(); $lock->close();
                if (!$current) throw new RuntimeException('Referral was not found.');
                $old = (string)$current['status'];
                $transitions = [
                    'Pending'=>['Pending','Accepted','Scheduled','Cancelled'],
                    'Accepted'=>['Accepted','Scheduled','Seen','Report Received','Cancelled'],
                    'Scheduled'=>['Scheduled','Seen','Report Received','Cancelled'],
                    'Seen'=>['Seen','Report Received','Closed'],
                    'Report Received'=>['Report Received','Closed'],
                    'Closed'=>['Closed'],
                    'Cancelled'=>['Cancelled']
                ];
                if (!in_array($status, $transitions[$old] ?? [], true)) throw new RuntimeException('This referral status transition is not allowed.');
                $appointmentSql = null;
                if ($appointment !== '') {
                    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $appointment);
                    if (!$dt) throw new RuntimeException('Enter a valid receiving appointment date and time.');
                    $appointmentSql = $dt->format('Y-m-d H:i:s');
                }
                $followSql = $followup !== '' ? $followup : null;
                $refSql = $externalReference !== '' ? $externalReference : null;
                $reasonSql = $closureReason !== '' ? $closureReason : null;
                $closedAt = $status === 'Closed' ? date('Y-m-d H:i:s') : null;
                $uid = (int)($_SESSION['user_id'] ?? 0);
                $stmt = $conn->prepare("UPDATE referral_cases SET status=?,receiving_appointment_at=COALESCE(?,receiving_appointment_at),external_reference=COALESCE(?,external_reference),follow_up_notes=COALESCE(?,follow_up_notes),closure_reason=COALESCE(?,closure_reason),closed_at=COALESCE(?,closed_at),updated_by=?,updated_at=NOW() WHERE id=?");
                if (!$stmt) throw new RuntimeException('Unable to prepare the referral update.');
                $stmt->bind_param('ssssssii',$status,$appointmentSql,$refSql,$followSql,$reasonSql,$closedAt,$uid,$id);
                if (!$stmt->execute()) throw new RuntimeException('Referral update failed.');
                $stmt->close();
                $conn->commit();
                if (function_exists('audit')) audit('referral_updated', 'referral_id='.$id.',from='.$old.',to='.$status);
                $message = 'Referral updated successfully.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e instanceof RuntimeException ? $e->getMessage() : 'Referral could not be updated.';
            }
        }
    }
}

$patients = $conn->query('SELECT id,patient_number,full_name FROM patients ORDER BY full_name ASC LIMIT 2000');
$visits = $conn->query("SELECT v.id,v.visit_number,v.patient_id,p.patient_number,p.full_name,v.visit_date FROM visits v JOIN patients p ON p.id=v.patient_id ORDER BY v.visit_date DESC,v.id DESC LIMIT 1000");
$stats = ['pending'=>0,'active'=>0,'reports'=>0,'closed'=>0];
if ($q=$conn->query("SELECT SUM(status='Pending') pending_count,SUM(status IN ('Accepted','Scheduled','Seen')) active_count,SUM(status='Report Received') report_count,SUM(status='Closed') closed_count FROM referral_cases")) {
    $s=$q->fetch_assoc() ?: [];
    $stats=['pending'=>(int)($s['pending_count']??0),'active'=>(int)($s['active_count']??0),'reports'=>(int)($s['report_count']??0),'closed'=>(int)($s['closed_count']??0)];
}
$cases = $conn->query("SELECT r.*,p.full_name,p.patient_number,v.visit_number FROM referral_cases r JOIN patients p ON p.id=r.patient_id LEFT JOIN visits v ON v.id=r.visit_id ORDER BY FIELD(r.urgency,'Emergency','Urgent','Routine'),FIELD(r.status,'Pending','Accepted','Scheduled','Seen','Report Received','Closed','Cancelled'),r.created_at DESC LIMIT 250");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.referral-page{padding:26px;background:#f4f7fb;min-height:calc(100vh - 60px)}.referral-shell{max-width:1550px;margin:auto}.referral-hero{background:linear-gradient(135deg,#063b73,#075b9d);border-radius:20px;padding:28px 30px;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px;box-shadow:0 10px 28px rgba(6,59,115,.15)}.referral-hero h1{margin:4px 0 8px;font-size:28px;color:#fff}.referral-hero p{margin:0;color:#d9eaff;font-size:14px}.referral-kicker{font-size:10px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#9ee7ff}.referral-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.referral-stat,.referral-panel{background:#fff;border:1px solid #e3eaf3;border-radius:14px;padding:18px;box-shadow:0 3px 12px rgba(22,44,75,.04)}.referral-stat small{color:#758399;text-transform:uppercase;font-size:10px;letter-spacing:1px;font-weight:800}.referral-stat strong{display:block;font-size:28px;color:#12385f;margin-top:8px}.referral-layout{display:grid;grid-template-columns:minmax(300px,.8fr) minmax(0,1.8fr);gap:18px;align-items:start}.referral-panel h2{font-size:17px;color:#173b62;margin:0 0 15px}.referral-form label{display:block;font-size:12px;font-weight:700;color:#43536a;margin:12px 0 5px}.referral-form input,.referral-form select,.referral-form textarea{width:100%;border:1px solid #d7e0ec;border-radius:8px;padding:10px 11px;font:inherit;font-size:13px;background:#fff;box-sizing:border-box}.referral-form textarea{min-height:70px;resize:vertical}.referral-btn{border:0;border-radius:8px;padding:10px 14px;background:#075b9d;color:#fff;font-weight:800;cursor:pointer;margin-top:12px}.referral-table-wrap{overflow:auto}.referral-table{width:100%;border-collapse:collapse;font-size:12px;min-width:950px}.referral-table th,.referral-table td{padding:12px 10px;border-bottom:1px solid #e9eef5;text-align:left;vertical-align:top}.referral-table th{background:#f7f9fc;color:#66758b;text-transform:uppercase;letter-spacing:.6px;font-size:10px}.referral-muted{color:#758399;font-size:11px;margin-top:4px}.referral-pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#e8f1fb;color:#174e7e;font-size:10px;font-weight:800}.referral-pill.urgent{background:#fff0d7;color:#895700}.referral-pill.emergency{background:#fde4e4;color:#992d2d}.referral-alert{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:13px}.referral-alert.error{background:#fff0f0;color:#922b2b;border:1px solid #f4d0d0}.referral-alert.success{background:#e5f7ee;color:#176345;border:1px solid #c5ead7}.referral-edit{min-width:270px}.referral-edit details{border:1px solid #e2eaf3;border-radius:9px;padding:9px;margin-top:8px}.referral-edit summary{cursor:pointer;color:#075b9d;font-weight:800}.referral-empty{padding:28px;text-align:center;color:#758399}@media(max-width:1100px){.referral-layout{grid-template-columns:1fr}.referral-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.referral-page{padding:14px}.referral-hero{padding:22px;align-items:flex-start;flex-direction:column}.referral-grid{gap:8px}.referral-stat{padding:13px}.referral-stat strong{font-size:23px}}
</style>
<div class="referral-page"><div class="referral-shell">
  <section class="referral-hero"><div><div class="referral-kicker">Clinical Services / Continuity of Care</div><h1>Referral Management</h1><p>Track incoming and outgoing referrals from registration through receiving-provider response and closure.</p></div><div class="referral-pill">Lifecycle register · UAT required</div></section>
  <?php if($error!==''): ?><div class="referral-alert error"><?=referral_h($error)?></div><?php endif; ?>
  <?php if($message!==''): ?><div class="referral-alert success"><?=referral_h($message)?></div><?php endif; ?>
  <div class="referral-grid"><div class="referral-stat"><small>Awaiting response</small><strong><?=$stats['pending']?></strong></div><div class="referral-stat"><small>In progress</small><strong><?=$stats['active']?></strong></div><div class="referral-stat"><small>Report received</small><strong><?=$stats['reports']?></strong></div><div class="referral-stat"><small>Closed</small><strong><?=$stats['closed']?></strong></div></div>
  <div class="referral-layout">
    <section class="referral-panel"><h2>Register referral</h2><?php if($canCreate): ?>
      <form class="referral-form" method="post"><input type="hidden" name="csrf_token" value="<?=referral_h(csrf_token())?>"><input type="hidden" name="action" value="create">
        <label for="patient_id">Patient *</label><select id="patient_id" name="patient_id" required><option value="">Select patient</option><?php if($patients):while($p=$patients->fetch_assoc()):?><option value="<?=(int)$p['id']?>"><?=referral_h($p['patient_number'].' — '.$p['full_name'])?></option><?php endwhile;endif;?></select>
        <label for="visit_id">Linked visit (optional)</label><select id="visit_id" name="visit_id"><option value="">No visit linked</option><?php if($visits):while($v=$visits->fetch_assoc()):?><option value="<?=(int)$v['id']?>"><?=referral_h($v['visit_number'].' — '.$v['patient_number'].' '.$v['full_name'].' ('.$v['visit_date'].')')?></option><?php endwhile;endif;?></select>
        <label for="direction">Direction *</label><select id="direction" name="direction"><option>Outgoing</option><option>Incoming</option></select>
        <label for="urgency">Urgency *</label><select id="urgency" name="urgency"><option>Routine</option><option>Urgent</option><option>Emergency</option></select>
        <label for="destination_facility">Receiving / referring facility *</label><input id="destination_facility" name="destination_facility" maxlength="180" required>
        <label for="destination_provider">Provider / contact</label><input id="destination_provider" name="destination_provider" maxlength="180">
        <label for="requested_service">Requested specialty / service</label><input id="requested_service" name="requested_service" maxlength="180" placeholder="e.g. Orthopaedics">
        <label for="referring_department">Originating department</label><input id="referring_department" name="referring_department" maxlength="120">
        <label for="referring_provider">Referring clinician</label><input id="referring_provider" name="referring_provider" maxlength="180">
        <label for="referral_reason">Reason for referral *</label><textarea id="referral_reason" name="referral_reason" maxlength="3000" required></textarea>
        <label for="clinical_summary">Clinical summary / handover</label><textarea id="clinical_summary" name="clinical_summary" maxlength="6000"></textarea>
        <button class="referral-btn" type="submit">Register referral</button>
      </form><?php else:?><p class="referral-muted">You have view access only. Request Referral Create permission to register referrals.</p><?php endif;?>
    </section>
    <section class="referral-panel"><h2>Referral lifecycle queue</h2><div class="referral-table-wrap"><table class="referral-table"><thead><tr><th>Referral / Patient</th><th>Destination / Service</th><th>Urgency / Status</th><th>Lifecycle update</th></tr></thead><tbody>
      <?php if($cases&&$cases->num_rows):while($r=$cases->fetch_assoc()):?><tr><td><strong><?=referral_h($r['referral_number'])?></strong><div><?=referral_h($r['patient_number'].' — '.$r['full_name'])?></div><div class="referral-muted"><?=referral_h($r['direction'])?> · <?=referral_h($r['visit_number']?:'No visit linked')?></div><div class="referral-muted"><?=referral_h($r['referral_reason'])?></div></td>
      <td><?=referral_h($r['destination_facility'])?><div class="referral-muted"><?=referral_h($r['destination_provider']?:'Provider not recorded')?></div><div class="referral-muted"><?=referral_h($r['requested_service']?:'Service not specified')?></div></td>
      <td><span class="referral-pill <?=strtolower($r['urgency'])?>"><?=referral_h($r['urgency'])?></span><div style="margin-top:7px"><span class="referral-pill"><?=referral_h($r['status'])?></span></div><div class="referral-muted">Created <?=referral_h(date('d M Y',strtotime($r['created_at'])))?></div><?php if($r['receiving_appointment_at']):?><div class="referral-muted">Appointment <?=referral_h(date('d M Y H:i',strtotime($r['receiving_appointment_at'])))?></div><?php endif;?></td>
      <td class="referral-edit"><?php if($canEdit&&!in_array($r['status'],['Closed','Cancelled'],true)):?><details><summary>Update status / response</summary><form class="referral-form" method="post"><input type="hidden" name="csrf_token" value="<?=referral_h(csrf_token())?>"><input type="hidden" name="action" value="update"><input type="hidden" name="referral_id" value="<?=(int)$r['id']?>">
        <label>Status</label><select name="status"><?php $next=['Pending'=>['Pending','Accepted','Scheduled','Cancelled'],'Accepted'=>['Accepted','Scheduled','Seen','Report Received','Cancelled'],'Scheduled'=>['Scheduled','Seen','Report Received','Cancelled'],'Seen'=>['Seen','Report Received','Closed'],'Report Received'=>['Report Received','Closed']];foreach(($next[$r['status']]??[$r['status']]) as $st):?><option value="<?=referral_h($st)?>" <?=$st===$r['status']?'selected':''?>><?=referral_h($st)?></option><?php endforeach;?></select>
        <label>Receiving appointment</label><input type="datetime-local" name="receiving_appointment_at" value="<?=$r['receiving_appointment_at']?referral_h(date('Y-m-d\TH:i',strtotime($r['receiving_appointment_at']))):''?>">
        <label>External reference / letter number</label><input name="external_reference" maxlength="180" value="<?=referral_h($r['external_reference'])?>">
        <label>Follow-up / outcome notes (required to close)</label><textarea name="follow_up_notes" maxlength="5000"><?=referral_h($r['follow_up_notes'])?></textarea>
        <label>Cancellation reason (required to cancel)</label><textarea name="closure_reason" maxlength="1000"><?=referral_h($r['closure_reason'])?></textarea>
        <button class="referral-btn" type="submit">Save referral update</button></form></details><?php else:?><span class="referral-muted"><?=in_array($r['status'],['Closed','Cancelled'],true)?'Case closed':($canEdit?'':'Edit permission required')?></span><?php endif;?></td></tr>
      <?php endwhile;else:?><tr><td colspan="4" class="referral-empty">No referrals registered yet.</td></tr><?php endif;?>
    </tbody></table></div></section>
  </div>
  <p class="referral-muted" style="margin-top:16px">Clinical safety note: referrals should include an appropriate clinical handover, urgency, safe transfer arrangements and receiving-provider confirmation. This register does not replace emergency stabilization, ambulance dispatch, secure document exchange or a signed referral letter.</p>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
