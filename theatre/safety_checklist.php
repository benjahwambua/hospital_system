<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'theatre', 'view');

function theatre_safety_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$canEdit = can_module_action($conn, 'theatre', 'edit');
$error = '';
$message = '';
$caseId = filter_input(INPUT_GET, 'case_id', FILTER_VALIDATE_INT) ?: 0;
$phases = [
 'Sign In' => [
  'Confirm patient identity using approved identifiers',
  'Confirm procedure and operative site/side',
  'Consent form verified and procedure explained',
  'Site marked where applicable',
  'Anaesthesia machine and medication safety check completed',
  'Pulse oximeter attached and functioning',
  'Known allergies reviewed and communicated',
  'Airway/aspiration risk assessed and equipment ready where indicated',
  'Risk of significant blood loss assessed and plan ready where indicated'
 ],
 'Time Out' => [
  'All team members introduce themselves by name and role',
  'Team verbally confirms patient identity',
  'Team verbally confirms procedure, site and side',
  'Anticipated critical events discussed by surgeon',
  'Anaesthesia concerns and patient-specific risks discussed',
  'Nursing concerns and sterility indicators confirmed',
  'Antibiotic prophylaxis confirmed where indicated',
  'Essential imaging and equipment available where required'
 ],
 'Sign Out' => [
  'Procedure performed recorded and verbally confirmed',
  'Instrument, sponge and needle counts confirmed or discrepancy escalated',
  'Specimen labels read aloud and patient identifiers checked where applicable',
  'Equipment problems identified and escalated',
  'Post-operative recovery and management plan confirmed',
  'Handover destination and responsible clinician confirmed'
 ]
];
$case = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $caseId = filter_input(INPUT_POST, 'case_id', FILTER_VALIDATE_INT) ?: 0;
 if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $error = 'Your session token expired. Refresh and try again.';
 elseif (!$canEdit) { http_response_code(403); exit('Forbidden: theatre edit permission required.'); }
 else {
  $phase = (string)($_POST['phase'] ?? '');
  if (!isset($phases[$phase])) $error = 'Select a valid surgical safety phase.';
  $stmt = $conn->prepare('SELECT id,status FROM theatre_cases WHERE id=? LIMIT 1');
  if (!$stmt) $error = 'Theatre case lookup unavailable. Confirm the theatre migration is applied.';
  else {
   $stmt->bind_param('i', $caseId);
   if (!$stmt->execute()) $error = 'Unable to load the selected case.';
   else $caseRow = $stmt->get_result()->fetch_assoc();
   $stmt->close();
   if ($error === '' && !$caseRow) $error = 'Select an existing theatre case.';
   if ($error === '' && in_array($caseRow['status'], ['Completed','Cancelled'], true)) $error = 'Closed cases are read-only.';
   if ($error === '' && $phase === 'Sign In' && !in_array($caseRow['status'], ['Planned','Pre-op'], true)) $error = 'Sign In must be recorded before theatre starts.';
   if ($error === '' && $phase === 'Time Out' && !in_array($caseRow['status'], ['Pre-op','In Theatre'], true)) $error = 'Time Out is recorded during the pre-operative pause, before incision.';
   if ($error === '' && $phase === 'Sign Out' && !in_array($caseRow['status'], ['In Theatre','Recovery'], true)) $error = 'Sign Out is recorded at the end of the operation before recovery handover.';
   if ($error === '') {
    $items = [];
    foreach ($phases[$phase] as $i => $label) {
     $value = (string)($_POST['item_'.$i] ?? '');
     if (!in_array($value, ['confirmed','not_applicable'], true)) { $error = 'Every checklist item must be marked Confirmed or Not applicable before saving the phase.'; break; }
     $items[(string)$i] = ['label'=>$label, 'status'=>$value];
    }
    $notes = trim((string)($_POST['notes'] ?? ''));
    if (strlen($notes) > 2000) $error = 'Checklist notes must not exceed 2,000 characters.';
    if ($error === '') {
     $json = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
     $uid = (int)($_SESSION['user_id'] ?? 0);
     $check = $conn->prepare('SELECT id,completed_at FROM theatre_safety_checklist_phases WHERE case_id=? AND phase=? LIMIT 1');
     if (!$check) $error = 'Phased checklist migration missing. Apply database/theatre_safety_checklist_migration.sql.';
     else {
      $check->bind_param('is', $caseId, $phase);
      if (!$check->execute()) $error = 'Unable to check existing checklist phase.';
      else $existing = $check->get_result()->fetch_assoc();
      $check->close();
      if ($error === '' && $existing && $existing['completed_at'] !== null) $error = 'This phase is already completed and locked. Do not overwrite the historical checklist; document changes in the clinical record.';
      elseif ($error === '') {
       if ($existing) $save = $conn->prepare('UPDATE theatre_safety_checklist_phases SET items_json=?,notes=?,recorded_by=?,completed_at=NOW() WHERE id=? AND completed_at IS NULL');
       else $save = $conn->prepare('INSERT INTO theatre_safety_checklist_phases (case_id,phase,items_json,notes,recorded_by,completed_at) VALUES (?,?,?,?,?,NOW())');
       if (!$save) $error = 'Unable to prepare checklist save.';
       else {
        if ($existing) $save->bind_param('ssii', $json, $notes, $uid, $existing['id']);
        else $save->bind_param('isssi', $caseId, $phase, $json, $notes, $uid);
        if ($save->execute()) {
         $message = $phase.' checklist saved and locked.';
         if (function_exists('audit')) audit('theatre_safety_phase_completed', 'case_id='.$caseId.',phase='.$phase);
        } else { error_log('Theatre phased checklist save failed: '.$save->error); $error='Could not save the checklist. Verify the migration and retry.'; }
        $save->close();
       }
      }
     }
    }
   }
  }
 }
}
$caseStmt = $conn->prepare('SELECT tc.id,tc.case_number,tc.procedure_name,tc.status,tc.patient_id,p.patient_number,p.full_name FROM theatre_cases tc JOIN patients p ON p.id=tc.patient_id WHERE tc.id=? LIMIT 1');
if ($caseStmt) { $caseStmt->bind_param('i',$caseId); if($caseStmt->execute()) $case=$caseStmt->get_result()->fetch_assoc(); $caseStmt->close(); }
$records = [];
if ($case) {
 $stmt=$conn->prepare('SELECT phase,items_json,notes,recorded_by,created_at,completed_at FROM theatre_safety_checklist_phases WHERE case_id=?');
 if($stmt) { $stmt->bind_param('i',$caseId); if($stmt->execute()) { $rs=$stmt->get_result(); while($r=$rs->fetch_assoc()) $records[$r['phase']]=$r; } $stmt->close(); }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Phased Surgical Safety Checklist | HMS</title>
<style>
body{margin:0;background:#f3f6fa;color:#172b45;font:15px/1.5 Arial,sans-serif}.wrap{max-width:1100px;margin:24px auto;padding:0 16px}.hero,.panel{background:#fff;border:1px solid #dbe4ef;border-radius:12px;padding:20px;margin-bottom:16px}.hero{background:linear-gradient(120deg,#102b49,#075b9d);color:#fff}.hero h1{margin:0 0 6px}.muted{color:#65758b}.hero .muted{color:#dceafa}.notice{padding:12px 14px;border-radius:8px;margin:12px 0}.error{background:#fff0ef;color:#9c2923}.success{background:#eaf7ed;color:#216537}.item{display:grid;grid-template-columns:1fr minmax(160px,220px);gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid #edf1f6}.item select,textarea{width:100%;padding:9px;border:1px solid #c7d3e1;border-radius:7px;box-sizing:border-box}.btn{display:inline-block;background:#075b9d;color:#fff;border:0;border-radius:7px;padding:10px 14px;text-decoration:none;font-weight:700;cursor:pointer}.locked{color:#216537;font-weight:700}.links{display:flex;gap:12px;flex-wrap:wrap;margin:12px 0}@media(max-width:650px){.item{grid-template-columns:1fr}.wrap{margin:12px auto}}
</style></head><body><main class="wrap">
<section class="hero"><h1>Phased Surgical Safety Checklist</h1><div class="muted">Sign In · Time Out · Sign Out — recorded separately and locked once completed.</div></section>
<?php if($error!==''): ?><div class="notice error"><?= theatre_safety_h($error) ?></div><?php endif; ?>
<?php if($message!==''): ?><div class="notice success"><?= theatre_safety_h($message) ?></div><?php endif; ?>
<?php if(!$case): ?><section class="panel">Theatre case not found. <a href="index.php">Return to Theatre &amp; Surgery</a></section><?php else: ?>
<section class="panel"><h2><?= theatre_safety_h($case['case_number']) ?> — <?= theatre_safety_h($case['procedure_name']) ?></h2><p><?= theatre_safety_h($case['patient_number']) ?> · <?= theatre_safety_h($case['full_name']) ?> · Status: <strong><?= theatre_safety_h($case['status']) ?></strong></p>
<div class="links"><a class="btn" href="index.php">Back to Theatre</a><a class="btn" href="clinical_records.php?case_id=<?= (int)$caseId ?>">PACU &amp; Implant Records</a></div>
<p class="muted">For each item choose Confirmed only after the team has verified it. Choose Not applicable only when the item genuinely does not apply; this choice is recorded. This electronic checklist supports, but does not replace, the hospital-approved surgical safety protocol or clinician judgement.</p></section>
<?php foreach($phases as $phase=>$items): $record=$records[$phase]??null; $allowed=$canEdit && !$record && !in_array($case['status'],['Completed','Cancelled'],true) && (($phase==='Sign In' && in_array($case['status'],['Planned','Pre-op'],true)) || ($phase==='Time Out' && in_array($case['status'],['Pre-op','In Theatre'],true)) || ($phase==='Sign Out' && in_array($case['status'],['In Theatre','Recovery'],true))); ?>
<section class="panel"><h2><?= theatre_safety_h($phase) ?> <?php if($record && $record['completed_at']): ?><span class="locked">✓ Completed &amp; locked</span><?php endif; ?></h2>
<?php if($record): ?><p class="muted">Recorded <?= theatre_safety_h($record['completed_at']??$record['created_at']) ?> by user ID <?= (int)$record['recorded_by'] ?></p><?php $saved=json_decode($record['items_json'],true)?:[]; foreach($items as $i=>$label): $status=$saved[(string)$i]['status']??'not recorded'; ?><div class="item"><span><?= theatre_safety_h($label) ?></span><strong><?= theatre_safety_h($status==='confirmed'?'Confirmed':($status==='not_applicable'?'Not applicable':'Not recorded')) ?></strong></div><?php endforeach; if(!empty($record['notes'])): ?><p><strong>Notes:</strong> <?= nl2br(theatre_safety_h($record['notes'])) ?></p><?php endif; ?>
<?php elseif($allowed): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= theatre_safety_h(csrf_token()) ?>"><input type="hidden" name="case_id" value="<?= (int)$caseId ?>"><input type="hidden" name="phase" value="<?= theatre_safety_h($phase) ?>">
<?php foreach($items as $i=>$label): ?><label class="item"><span><?= theatre_safety_h($label) ?></span><select name="item_<?= (int)$i ?>" required><option value="">Select status</option><option value="confirmed">Confirmed</option><option value="not_applicable">Not applicable</option></select></label><?php endforeach; ?>
<label for="notes_<?= md5($phase) ?>">Notes / exceptions</label><textarea id="notes_<?= md5($phase) ?>" name="notes" rows="3" maxlength="2000"></textarea><p><button class="btn" type="submit">Complete &amp; lock <?= theatre_safety_h($phase) ?></button></p></form>
<?php else: ?><p class="muted"><?= $record?'Completed checklist is read-only.':(!$canEdit?'Edit permission is required to record this phase.':'This phase is available only at the appropriate point in the theatre workflow.') ?></p><?php endif; ?></section>
<?php endforeach; ?>
<?php endif; ?>
</main></body></html>
