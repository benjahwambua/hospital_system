<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'emergency', 'view');
$canRecord = can_module_action($conn, 'emergency', 'create');
$csrf = csrf_token();
$error = $message = '';
$caseId = filter_input(INPUT_GET, 'case_id', FILTER_VALIDATE_INT);
if (!$caseId && $_SERVER['REQUEST_METHOD'] === 'POST') $caseId = filter_input(INPUT_POST, 'case_id', FILTER_VALIDATE_INT);
$e = static fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$valid = static fn($v,$min,$max): bool => $v === '' || (is_numeric($v) && (float)$v >= $min && (float)$v <= $max);
if (!$caseId || $caseId < 1) { http_response_code(400); exit('A valid emergency case is required.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!$canRecord) { http_response_code(403); exit('Forbidden: emergency create permission required.'); }
 if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $error = 'Your security token expired. Refresh the page and try again.';
 else {
  $temp=trim((string)($_POST['temperature_c']??'')); $sys=trim((string)($_POST['systolic_bp']??'')); $dia=trim((string)($_POST['diastolic_bp']??''));
  $pulse=trim((string)($_POST['pulse_bpm']??'')); $resp=trim((string)($_POST['respiratory_rate']??'')); $spo2=trim((string)($_POST['oxygen_saturation']??''));
  $pain=trim((string)($_POST['pain_score']??'')); $notes=trim((string)($_POST['notes']??''));
  if (strlen($notes)>1500) $error='Observation notes must be 1,500 characters or fewer.';
  elseif (!$valid($temp,25,45)||!$valid($sys,40,300)||!$valid($dia,20,200)||!$valid($pulse,1,300)||!$valid($resp,1,100)||!$valid($spo2,0,100)||!$valid($pain,0,10)) $error='One or more observations are outside the permitted input range.';
  elseif (($sys==='')!==($dia==='')) $error='Enter both systolic and diastolic blood pressure, or leave both blank.';
  elseif ($sys!==''&&$dia!==''&&(int)$sys<=(int)$dia) $error='Systolic blood pressure must be greater than diastolic blood pressure.';
  else {
   try {
    $conn->begin_transaction();
    $lock=$conn->prepare('SELECT status FROM emergency_cases WHERE id=? FOR UPDATE');
    if(!$lock) throw new RuntimeException('Could not prepare case lock.');
    $lock->bind_param('i',$caseId); $lock->execute(); $state=$lock->get_result()->fetch_assoc(); $lock->close();
    if(!$state) throw new InvalidArgumentException('Emergency case not found.');
    if(in_array($state['status'],['Admitted','Discharged','Transferred','Left Before Assessment','Cancelled'],true)) throw new InvalidArgumentException('Repeat observations cannot be added to a closed case.');
    $tv=$temp!==''?(float)$temp:null; $sv=$sys!==''?(int)$sys:null; $dv=$dia!==''?(int)$dia:null;
    $pv=$pulse!==''?(int)$pulse:null; $rv=$resp!==''?(int)$resp:null; $ov=$spo2!==''?(float)$spo2:null; $painv=$pain!==''?(int)$pain:null; $nv=$notes!==''?$notes:null; $actor=(int)($_SESSION['user_id']??0);
    $ins=$conn->prepare('INSERT INTO emergency_observations (case_id,temperature_c,systolic_bp,diastolic_bp,pulse_bpm,respiratory_rate,oxygen_saturation,pain_score,notes,recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?)');
    if(!$ins) throw new RuntimeException('Could not prepare observation insert.');
    $ins->bind_param('idiiiidisi',$caseId,$tv,$sv,$dv,$pv,$rv,$ov,$painv,$nv,$actor);
    $ins->execute(); $ins->close();
    if(function_exists('audit')) audit('emergency_observation_recorded','case_id='.$caseId);
    $conn->commit(); $message='Repeat observations recorded.';
   } catch(Throwable $ex) {
    $conn->rollback();
    if($ex instanceof InvalidArgumentException) $error=$ex->getMessage();
    else { error_log('Emergency observation save failed: '.$ex->getMessage()); $error='Observations could not be saved. Confirm database/emergency_observations_migration.sql has been applied.'; }
   }
  }
 }
}
$cs=$conn->prepare('SELECT e.id,e.status,e.triage_level,e.arrived_at,e.chief_complaint,p.patient_number,p.full_name,v.visit_number FROM emergency_cases e JOIN patients p ON p.id=e.patient_id JOIN visits v ON v.id=e.visit_id WHERE e.id=? LIMIT 1');
if(!$cs){http_response_code(500);exit('Unable to load emergency case.');}
$cs->bind_param('i',$caseId);$cs->execute();$case=$cs->get_result()->fetch_assoc();$cs->close();
if(!$case){http_response_code(404);exit('Emergency case not found.');}
$hs=$conn->prepare('SELECT o.*,u.full_name recorder_name FROM emergency_observations o LEFT JOIN users u ON u.id=o.recorded_by WHERE o.case_id=? ORDER BY o.recorded_at DESC,o.id DESC');
$hs->bind_param('i',$caseId);$hs->execute();$history=$hs->get_result();
include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php';
?>
<div class="container-fluid py-4">
 <div class="card mb-3"><div class="card-body">
  <h2>Emergency Repeat Observations</h2>
  <p><strong><?= $e($case['patient_number']) ?> · <?= $e($case['full_name']) ?></strong> — Visit <?= $e($case['visit_number']) ?></p>
  <p>Status: <?= $e($case['status']) ?> · Triage: <?= $e($case['triage_level']) ?> · Arrival: <?= $e($case['arrived_at']) ?></p>
  <p>Presenting problem: <?= $e($case['chief_complaint']) ?></p>
  <?php if($error!==''): ?><div class="alert alert-danger"><?= $e($error) ?></div><?php endif; ?>
  <?php if($message!==''): ?><div class="alert alert-success"><?= $e($message) ?></div><?php endif; ?>
 </div></div>
 <?php if($canRecord&&!in_array($case['status'],['Discharged','Transferred','Left Before Assessment','Cancelled'],true)): ?>
 <div class="card mb-3"><div class="card-header font-weight-bold">Record follow-up observations</div><div class="card-body">
  <form method="post"><input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>"><input type="hidden" name="case_id" value="<?= (int)$caseId ?>">
   <div class="form-row">
    <div class="form-group col-md-3"><label>Temperature °C</label><input class="form-control" type="number" step=".1" min="25" max="45" name="temperature_c"></div>
    <div class="form-group col-md-3"><label>Systolic BP</label><input class="form-control" type="number" min="40" max="300" name="systolic_bp"></div>
    <div class="form-group col-md-3"><label>Diastolic BP</label><input class="form-control" type="number" min="20" max="200" name="diastolic_bp"></div>
    <div class="form-group col-md-3"><label>Pulse / min</label><input class="form-control" type="number" min="1" max="300" name="pulse_bpm"></div>
    <div class="form-group col-md-3"><label>Respiratory rate</label><input class="form-control" type="number" min="1" max="100" name="respiratory_rate"></div>
    <div class="form-group col-md-3"><label>Oxygen saturation %</label><input class="form-control" type="number" step=".1" min="0" max="100" name="oxygen_saturation"></div>
    <div class="form-group col-md-3"><label>Pain score (0–10)</label><input class="form-control" type="number" min="0" max="10" name="pain_score"></div>
    <div class="form-group col-md-12"><label>Notes / clinical context</label><textarea class="form-control" name="notes" maxlength="1500" rows="2"></textarea></div>
   </div><button class="btn btn-primary" type="submit">Save observations</button>
  </form>
 </div></div>
 <?php endif; ?>
 <div class="card mb-3"><div class="card-header font-weight-bold">Observation history · newest first</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Recorded at</th><th>Temp °C</th><th>BP</th><th>Pulse</th><th>Respiratory rate</th><th>SpO₂</th><th>Pain</th><th>Notes / recorded by</th></tr></thead><tbody>
 <?php if($history->num_rows):while($o=$history->fetch_assoc()): ?><tr><td><?= $e($o['recorded_at']) ?></td><td><?= $e($o['temperature_c']??'—') ?></td><td><?= $e($o['systolic_bp']!==null?$o['systolic_bp'].'/'.$o['diastolic_bp']:'—') ?></td><td><?= $e($o['pulse_bpm']??'—') ?></td><td><?= $e($o['respiratory_rate']??'—') ?></td><td><?= $e($o['oxygen_saturation']??'—') ?></td><td><?= $e($o['pain_score']??'—') ?></td><td><?= $e($o['notes']?:'—') ?><small class="d-block text-muted"><?= $e($o['recorder_name']?:'Unknown user') ?></small></td></tr><?php endwhile;else: ?><tr><td colspan="8" class="text-center p-4">No repeat observations recorded yet.</td></tr><?php endif; ?>
 </tbody></table></div></div>
 <a class="btn btn-outline-secondary" href="/hospital_system/emergency/index.php">Back to Emergency Queue</a>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
