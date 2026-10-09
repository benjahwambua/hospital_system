<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_module_access($conn, 'nursing', 'view');

$canCreate = can_module_action($conn, 'nursing', 'create');
$canApprove = can_module_action($conn, 'nursing', 'approve');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canCreate) {
        $message = '<div class="alert alert-danger">You do not have permission to record nursing care.</div>';
    } elseif (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        $message = '<div class="alert alert-danger">Invalid security token. Please refresh and try again.</div>';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $admissionId = (int)($_POST['admission_id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $check = $conn->prepare("SELECT a.id,a.patient_id,a.ward_name,a.bed_number,p.full_name,p.patient_number
                                 FROM admissions a JOIN patients p ON p.id=a.patient_id
                                 WHERE a.id=? AND a.status='Admitted' LIMIT 1");
        if ($check) {
            $check->bind_param('i',$admissionId);
            $check->execute();
            $admission = $check->get_result()->fetch_assoc();
            $check->close();
        } else $admission = null;

        if (!$admission) {
            $message = '<div class="alert alert-danger">Active inpatient admission not found.</div>';
        } elseif ($action === 'note') {
            $noteType = trim((string)($_POST['note_type'] ?? 'Progress Note'));
            $shift = trim((string)($_POST['shift'] ?? ''));
            $noteText = trim((string)($_POST['note_text'] ?? ''));
            $allowedTypes = ['Assessment','Progress Note','Shift Handover','Care Plan','Other'];
            $allowedShifts = ['Day','Evening','Night',''];
            if (!in_array($noteType,$allowedTypes,true) || !in_array($shift,$allowedShifts,true) || $noteText==='') {
                $message = '<div class="alert alert-danger">Select a valid note type and enter the nursing note.</div>';
            } elseif ($noteType === 'Shift Handover' && !$canApprove) {
                $message = '<div class="alert alert-danger">Shift handover requires nursing approval permission.</div>';
            } else {
                $s=$conn->prepare("INSERT INTO nursing_notes(admission_id,patient_id,note_type,shift,note_text,recorded_by) VALUES(?,?,?,?,?,?)");
                if($s){
                    $s->bind_param('iisssi',$admissionId,$admission['patient_id'],$noteType,$shift,$noteText,$userId);
                    if($s->execute()){
                        if(function_exists('audit')) audit('nursing_note_recorded',"admission_id={$admissionId},note_id={$s->insert_id},type={$noteType}");
                        $message='<div class="alert alert-success">Nursing note recorded.</div>';
                    } else $message='<div class="alert alert-danger">Unable to save the nursing note.</div>';
                    $s->close();
                }
            }
        } elseif ($action === 'observation') {
            $temperature = ($_POST['temperature'] ?? '') === '' ? null : (float)$_POST['temperature'];
            $systolic = ($_POST['systolic_bp'] ?? '') === '' ? null : (int)$_POST['systolic_bp'];
            $diastolic = ($_POST['diastolic_bp'] ?? '') === '' ? null : (int)$_POST['diastolic_bp'];
            $pulse = ($_POST['pulse'] ?? '') === '' ? null : (int)$_POST['pulse'];
            $resp = ($_POST['respiration'] ?? '') === '' ? null : (int)$_POST['respiration'];
            $spo2 = ($_POST['spo2'] ?? '') === '' ? null : (float)$_POST['spo2'];
            $pain = ($_POST['pain_score'] ?? '') === '' ? null : (int)$_POST['pain_score'];
            $consciousness = trim((string)($_POST['consciousness'] ?? ''));
            $intake = ($_POST['intake_ml'] ?? '') === '' ? null : (int)$_POST['intake_ml'];
            $output = ($_POST['output_ml'] ?? '') === '' ? null : (int)$_POST['output_ml'];
            $obsNotes = trim((string)($_POST['observation_notes'] ?? ''));
            $obsTime = trim((string)($_POST['observation_time'] ?? ''));
            $obsTime = $obsTime !== '' ? date('Y-m-d H:i:s', strtotime($obsTime)) : date('Y-m-d H:i:s');

            if (($pain !== null && ($pain < 0 || $pain > 10)) || ($spo2 !== null && ($spo2 < 0 || $spo2 > 100))) {
                $message='<div class="alert alert-danger">Pain score must be 0–10 and SpO₂ must be 0–100.</div>';
            } else {
                $s=$conn->prepare("INSERT INTO nursing_observations(admission_id,patient_id,observation_time,temperature,systolic_bp,diastolic_bp,pulse,respiration,spo2,pain_score,consciousness,intake_ml,output_ml,observation_notes,recorded_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                if($s){
                    $s->bind_param('iisiiiidisiisi',$admissionId,$admission['patient_id'],$obsTime,$temperature,$systolic,$diastolic,$pulse,$resp,$spo2,$pain,$consciousness,$intake,$output,$obsNotes,$userId);
                    if($s->execute()){
                        if(function_exists('audit')) audit('nursing_observation_recorded',"admission_id={$admissionId},observation_id={$s->insert_id}");
                        $message='<div class="alert alert-success">Nursing observation recorded.</div>';
                    } else $message='<div class="alert alert-danger">Unable to save the nursing observation.</div>';
                    $s->close();
                }
            }
        } elseif ($action === 'task_create') {
            $title = trim((string)($_POST['task_title'] ?? ''));
            $details = trim((string)($_POST['task_details'] ?? ''));
            $priority = trim((string)($_POST['priority'] ?? 'Routine'));
            $dueRaw = trim((string)($_POST['due_at'] ?? ''));
            $assignedTo = (int)($_POST['assigned_to'] ?? 0);
            $allowedPriorities = ['Routine','High','Urgent'];
            $dueAt = null;
            if ($dueRaw !== '') {
                $parsedDue = DateTime::createFromFormat('Y-m-d\\TH:i', $dueRaw);
                if ($parsedDue) $dueAt = $parsedDue->format('Y-m-d H:i:s');
            }
            if ($title === '' || !in_array($priority, $allowedPriorities, true) || ($dueRaw !== '' && $dueAt === null)) {
                $message = '<div class="alert alert-danger">Enter a task title, valid priority and valid due time.</div>';
            } else {
                $s = $conn->prepare("INSERT INTO nursing_tasks(admission_id,patient_id,task_title,task_details,priority,due_at,assigned_to,created_by) VALUES(?,?,?,?,?,?,NULLIF(?,0),?)");
                if ($s) {
                    $s->bind_param('iissssii', $admissionId, $admission['patient_id'], $title, $details, $priority, $dueAt, $assignedTo, $userId);
                    if ($s->execute()) {
                        if (function_exists('audit')) audit('nursing_task_created', "admission_id={$admissionId},task_id={$s->insert_id},priority={$priority}");
                        $message = '<div class="alert alert-success">Nursing task assigned to the inpatient record.</div>';
                    } else $message = '<div class="alert alert-danger">Unable to create nursing task.</div>';
                    $s->close();
                }
            }
        } elseif ($action === 'task_complete') {
            $taskId = (int)($_POST['task_id'] ?? 0);
            $completionNotes = trim((string)($_POST['completion_notes'] ?? ''));
            $s = $conn->prepare("UPDATE nursing_tasks SET status='Completed',completed_by=?,completed_at=NOW(),completion_notes=? WHERE id=? AND admission_id=? AND status IN ('Open','In Progress')");
            if ($s) {
                $s->bind_param('isii', $userId, $completionNotes, $taskId, $admissionId);
                if ($s->execute() && $s->affected_rows === 1) {
                    if (function_exists('audit')) audit('nursing_task_completed', "admission_id={$admissionId},task_id={$taskId}");
                    $message = '<div class="alert alert-success">Nursing task marked complete.</div>';
                } else $message = '<div class="alert alert-warning">Task was not updated; it may already be completed or no longer active.</div>';
                $s->close();
            }
        } elseif ($action === 'care_plan_create') {
            $problem = trim((string)($_POST['problem_or_need'] ?? ''));
            $goal = trim((string)($_POST['goal'] ?? ''));
            $interventions = trim((string)($_POST['interventions'] ?? ''));
            $reviewRaw = trim((string)($_POST['review_due_at'] ?? ''));
            $reviewDue = null;
            if ($reviewRaw !== '') {
                $parsedReview = DateTime::createFromFormat('Y-m-d\\TH:i', $reviewRaw);
                if ($parsedReview) $reviewDue = $parsedReview->format('Y-m-d H:i:s');
            }
            if ($problem === '' || $goal === '' || $interventions === '' || ($reviewRaw !== '' && $reviewDue === null)) {
                $message = '<div class="alert alert-danger">Care plan problem/need, goal and interventions are required.</div>';
            } else {
                $s = $conn->prepare("INSERT INTO nursing_care_plans(admission_id,patient_id,problem_or_need,goal,interventions,review_due_at,created_by) VALUES(?,?,?,?,?,?,?)");
                if ($s) {
                    $s->bind_param('iissssi', $admissionId, $admission['patient_id'], $problem, $goal, $interventions, $reviewDue, $userId);
                    if ($s->execute()) {
                        if (function_exists('audit')) audit('nursing_care_plan_created', "admission_id={$admissionId},plan_id={$s->insert_id}");
                        $message = '<div class="alert alert-success">Nursing care plan saved.</div>';
                    } else $message = '<div class="alert alert-danger">Unable to save nursing care plan.</div>';
                    $s->close();
                }
            }
        }
    }
}

$admissions = $conn->query("SELECT a.id,a.patient_id,a.ward_name,a.bed_number,a.admit_date,a.attending_doctor,
                                   p.full_name,p.patient_number
                            FROM admissions a JOIN patients p ON p.id=a.patient_id
                            WHERE a.status='Admitted' ORDER BY a.ward_name,a.bed_number,p.full_name");
$recent = $conn->query("SELECT n.id,n.note_type,n.shift,n.note_text,n.created_at,p.full_name,p.patient_number,
                               a.ward_name,a.bed_number
                        FROM nursing_notes n JOIN patients p ON p.id=n.patient_id
                        JOIN admissions a ON a.id=n.admission_id
                        ORDER BY n.created_at DESC LIMIT 25");
$observations = $conn->query("SELECT o.*,p.full_name,p.patient_number,a.ward_name,a.bed_number
                              FROM nursing_observations o JOIN patients p ON p.id=o.patient_id
                              JOIN admissions a ON a.id=o.admission_id
                              ORDER BY o.observation_time DESC LIMIT 25");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.nursing-page{background:#f5f7fb;min-height:calc(100vh - 72px);padding:28px}.nursing-shell{max-width:1500px;margin:auto}
.nursing-hero{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:18px;padding:25px 28px;display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:20px;box-shadow:0 12px 30px rgba(6,59,115,.16)}
.nursing-hero h1{font-size:27px;margin:4px 0}.nursing-hero p{margin:0;color:#dbeafe}.nursing-kicker{text-transform:uppercase;letter-spacing:1.5px;font-size:10px;font-weight:800;color:#9edcff}
.nursing-grid{display:grid;grid-template-columns:1.05fr 1.4fr;gap:18px}.n-card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden;margin-bottom:18px}.n-head{padding:15px 18px;border-bottom:1px solid #edf0f5;display:flex;justify-content:space-between;align-items:center}.n-head strong{color:#25324a}.n-body{padding:18px}.patient-list{display:grid;gap:8px;max-height:620px;overflow:auto}.patient-item{display:block;padding:12px 14px;border:1px solid #e5eaf1;border-radius:10px;text-decoration:none;color:#344054}.patient-item:hover{background:#f5faff;border-color:#b9d5ea}.patient-item strong{display:block}.patient-item small{color:#667085}.n-form label{font-size:11px;text-transform:uppercase;letter-spacing:.4px;font-weight:800;color:#667085}.n-form .form-control{border-radius:8px}.n-table{width:100%;border-collapse:collapse}.n-table th,.n-table td{padding:10px;border-bottom:1px solid #edf0f5;font-size:12px;vertical-align:top}.n-table th{font-size:10px;text-transform:uppercase;color:#667085;background:#fafbfc}.badge-soft{background:#eef6ff;color:#075b9d;border-radius:20px;padding:5px 8px;font-size:10px;font-weight:800}.record-tabs{display:grid;grid-template-columns:1fr 1fr;gap:10px}.record-tabs button{border:1px solid #dbe4ee;background:#fff;border-radius:9px;padding:10px;font-weight:800;color:#344054}.record-tabs button.active{background:#075b9d;color:#fff;border-color:#075b9d}@media(max-width:1000px){.nursing-grid{grid-template-columns:1fr}.nursing-page{padding:18px 12px}}
</style>
<div class="nursing-page"><div class="nursing-shell">
  <div class="nursing-hero"><div><div class="nursing-kicker">Inpatient Care · Nursing</div><h1>Nursing Station</h1><p>Record bedside observations, nursing notes, care plans and shift handovers against the active inpatient admission.</p></div><div><span class="badge badge-light p-2"><?= $admissions ? $admissions->num_rows : 0 ?> active inpatients</span></div></div>
  <?=$message?>
  <div class="nursing-grid">
    <div>
      <div class="n-card"><div class="n-head"><strong>Active Inpatients</strong><span class="badge-soft">Select patient</span></div><div class="n-body"><div class="patient-list">
      <?php if($admissions && $admissions->num_rows): while($a=$admissions->fetch_assoc()): ?>
        <a class="patient-item" href="index.php?admission_id=<?= (int)$a['id'] ?>"><strong><?=htmlspecialchars($a['full_name'])?></strong><small><?=htmlspecialchars($a['patient_number'])?> · <?=htmlspecialchars($a['ward_name'])?> · Bed <?= (int)$a['bed_number'] ?></small></a>
      <?php endwhile; else: ?><div class="text-muted text-center py-4">No active inpatients.</div><?php endif; ?>
      </div></div></div>
    </div>
    <div>
      <?php
      $selectedId=(int)($_GET['admission_id']??0); $selected=null;
      if($selectedId>0){$s=$conn->prepare("SELECT a.id,a.patient_id,a.ward_name,a.bed_number,a.admit_date,a.attending_doctor,p.full_name,p.patient_number FROM admissions a JOIN patients p ON p.id=a.patient_id WHERE a.id=? AND a.status='Admitted' LIMIT 1");if($s){$s->bind_param('i',$selectedId);$s->execute();$selected=$s->get_result()->fetch_assoc();$s->close();}}
      ?>
      <div class="n-card"><div class="n-head"><strong><?= $selected ? htmlspecialchars($selected['full_name']) : 'Record Nursing Care' ?></strong><?php if($selected): ?><span class="badge-soft"><?=htmlspecialchars($selected['ward_name'])?> · Bed <?= (int)$selected['bed_number'] ?></span><?php endif; ?></div>
      <div class="n-body">
      <?php if(!$selected): ?><div class="text-muted">Select an active inpatient to begin recording nursing care.</div>
      <?php elseif(!$canCreate): ?><div class="alert alert-danger mb-0">You have view-only nursing access.</div>
      <?php else: ?>
        <div class="record-tabs mb-3"><button type="button" class="tab-btn active" data-target="obs">Observations</button><button type="button" class="tab-btn" data-target="note">Notes / Handover</button></div>
        <div id="obs" class="record-pane">
        <form method="post" class="n-form"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="observation"><input type="hidden" name="admission_id" value="<?= (int)$selected['id'] ?>">
          <div class="form-row"><div class="form-group col-md-4"><label>Observation Time</label><input type="datetime-local" name="observation_time" class="form-control" value="<?=date('Y-m-d\TH:i')?>"></div><div class="form-group col-md-4"><label>Temperature °C</label><input type="number" step="0.1" name="temperature" class="form-control"></div><div class="form-group col-md-4"><label>SpO₂ %</label><input type="number" step="0.1" min="0" max="100" name="spo2" class="form-control"></div></div>
          <div class="form-row"><div class="form-group col-md-3"><label>Systolic BP</label><input type="number" name="systolic_bp" class="form-control"></div><div class="form-group col-md-3"><label>Diastolic BP</label><input type="number" name="diastolic_bp" class="form-control"></div><div class="form-group col-md-3"><label>Pulse</label><input type="number" name="pulse" class="form-control"></div><div class="form-group col-md-3"><label>Respiration</label><input type="number" name="respiration" class="form-control"></div></div>
          <div class="form-row"><div class="form-group col-md-4"><label>Pain Score (0–10)</label><input type="number" min="0" max="10" name="pain_score" class="form-control"></div><div class="form-group col-md-4"><label>Consciousness</label><select name="consciousness" class="form-control"><option value="">Not recorded</option><option>Alert</option><option>Confused</option><option>Drowsy</option><option>Unresponsive</option></select></div><div class="form-group col-md-2"><label>Intake ml</label><input type="number" min="0" name="intake_ml" class="form-control"></div><div class="form-group col-md-2"><label>Output ml</label><input type="number" min="0" name="output_ml" class="form-control"></div></div>
          <div class="form-group"><label>Observation Notes</label><textarea name="observation_notes" class="form-control" rows="3"></textarea></div>
          <button class="btn btn-primary px-4"><i class="fas fa-save mr-1"></i>Record Observation</button>
        </form></div>
        <div id="note" class="record-pane" style="display:none">
        <form method="post" class="n-form"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="note"><input type="hidden" name="admission_id" value="<?= (int)$selected['id'] ?>">
          <div class="form-row"><div class="form-group col-md-6"><label>Note Type</label><select name="note_type" class="form-control"><option>Assessment</option><option selected>Progress Note</option><option>Shift Handover</option><option>Care Plan</option><option>Other</option></select></div><div class="form-group col-md-6"><label>Shift</label><select name="shift" class="form-control"><option value="">Not specified</option><option>Day</option><option>Evening</option><option>Night</option></select></div></div>
          <div class="form-group"><label>Nursing Note</label><textarea name="note_text" class="form-control" rows="7" required></textarea></div>
          <button class="btn btn-primary px-4"><i class="fas fa-notes-medical mr-1"></i>Save Nursing Note</button>
        </form></div>
      <?php endif; ?></div></div>
      <?php if($selected): ?>
      <div class="n-card"><div class="n-head"><strong>Recent Care Record</strong></div><div class="n-body">
        <h6 class="font-weight-bold">Latest Observations</h6><div class="table-responsive"><table class="n-table"><thead><tr><th>Time</th><th>Vitals</th><th>Pain</th><th>Intake/Output</th><th>Notes</th></tr></thead><tbody>
        <?php $os=$conn->prepare("SELECT * FROM nursing_observations WHERE admission_id=? ORDER BY observation_time DESC LIMIT 10"); if($os){$os->bind_param('i',$selected['id']);$os->execute();$orr=$os->get_result();while($o=$orr->fetch_assoc()): ?><tr><td><?=htmlspecialchars($o['observation_time'])?></td><td>BP <?=htmlspecialchars(($o['systolic_bp']??'—').'/'.($o['diastolic_bp']??'—'))?><br>T <?=htmlspecialchars($o['temperature']??'—')?> · P <?=htmlspecialchars($o['pulse']??'—')?> · RR <?=htmlspecialchars($o['respiration']??'—')?> · SpO₂ <?=htmlspecialchars($o['spo2']??'—')?></td><td><?=htmlspecialchars($o['pain_score']??'—')?></td><td><?=htmlspecialchars($o['intake_ml']??'—')?> / <?=htmlspecialchars($o['output_ml']??'—')?> ml</td><td><?=htmlspecialchars($o['observation_notes']??'')?></td></tr><?php endwhile; $os->close(); } ?></tbody></table></div>
        <h6 class="font-weight-bold mt-4">Latest Nursing Notes</h6><div class="table-responsive"><table class="n-table"><thead><tr><th>Date</th><th>Type</th><th>Shift</th><th>Note</th></tr></thead><tbody>
        <?php $ns=$conn->prepare("SELECT * FROM nursing_notes WHERE admission_id=? ORDER BY created_at DESC LIMIT 10"); if($ns){$ns->bind_param('i',$selected['id']);$ns->execute();$nrr=$ns->get_result();while($n=$nrr->fetch_assoc()): ?><tr><td><?=htmlspecialchars($n['created_at'])?></td><td><span class="badge-soft"><?=htmlspecialchars($n['note_type'])?></span></td><td><?=htmlspecialchars($n['shift']??'—')?></td><td><?=nl2br(htmlspecialchars($n['note_text']))?></td></tr><?php endwhile; $ns->close(); } ?></tbody></table></div>
      </div></div>
      <?php endif; ?>
      <?php if($selected): ?>
      <div class="n-card">
        <div class="n-head"><strong>Nursing Tasks</strong><span class="badge-soft">Admission #<?= (int)$selected['id'] ?></span></div>
        <div class="n-body">
          <?php if($canCreate): ?>
          <form method="post" class="n-form mb-4">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="task_create"><input type="hidden" name="admission_id" value="<?= (int)$selected['id'] ?>">
            <div class="form-row">
              <div class="form-group col-md-6"><label>Task</label><input name="task_title" class="form-control" maxlength="180" required placeholder="e.g. Recheck observations"></div>
              <div class="form-group col-md-3"><label>Priority</label><select name="priority" class="form-control"><option>Routine</option><option>High</option><option>Urgent</option></select></div>
              <div class="form-group col-md-3"><label>Due</label><input type="datetime-local" name="due_at" class="form-control"></div>
            </div>
            <div class="form-group"><label>Instructions</label><textarea name="task_details" class="form-control" rows="2"></textarea></div>
            <button class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Assign Task</button>
          </form>
          <?php endif; ?>
          <div class="table-responsive"><table class="n-table"><thead><tr><th>Task</th><th>Priority / Due</th><th>Status</th><th>Action</th></tr></thead><tbody>
          <?php $ts=$conn->prepare("SELECT * FROM nursing_tasks WHERE admission_id=? ORDER BY FIELD(priority,'Urgent','High','Routine'),due_at IS NULL,due_at,created_at DESC"); if($ts){$ts->bind_param('i',$selected['id']);$ts->execute();$tr=$ts->get_result();while($t=$tr->fetch_assoc()): ?>
            <tr><td><strong><?=htmlspecialchars($t['task_title'])?></strong><div><?=nl2br(htmlspecialchars($t['task_details']??''))?></div><?php if(!empty($t['completion_notes'])): ?><small class="text-muted">Completion: <?=htmlspecialchars($t['completion_notes'])?></small><?php endif; ?></td><td><?=htmlspecialchars($t['priority'])?><br><small><?=htmlspecialchars($t['due_at']??'No due time')?></small></td><td><span class="badge-soft"><?=htmlspecialchars($t['status'])?></span></td><td><?php if($canCreate && in_array($t['status'],['Open','In Progress'],true)): ?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="task_complete"><input type="hidden" name="admission_id" value="<?= (int)$selected['id'] ?>"><input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>"><input name="completion_notes" class="form-control mb-1" placeholder="Outcome (optional)"><button class="btn btn-sm btn-outline-success">Complete</button></form><?php else: ?>—<?php endif; ?></td></tr>
          <?php endwhile; $ts->close(); } ?></tbody></table></div>
        </div>
      </div>
      <div class="n-card">
        <div class="n-head"><strong>Structured Care Plan</strong><span class="badge-soft">Admission #<?= (int)$selected['id'] ?></span></div>
        <div class="n-body">
          <?php if($canCreate): ?><form method="post" class="n-form mb-4">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="care_plan_create"><input type="hidden" name="admission_id" value="<?= (int)$selected['id'] ?>">
            <div class="form-group"><label>Problem / Care Need</label><input name="problem_or_need" class="form-control" maxlength="180" required></div>
            <div class="form-group"><label>Expected Goal</label><input name="goal" class="form-control" maxlength="500" required></div>
            <div class="form-group"><label>Nursing Interventions</label><textarea name="interventions" class="form-control" rows="3" required></textarea></div>
            <div class="form-group"><label>Review Due</label><input type="datetime-local" name="review_due_at" class="form-control"></div>
            <button class="btn btn-primary"><i class="fas fa-clipboard-check mr-1"></i>Save Care Plan</button>
          </form><?php endif; ?>
          <div class="table-responsive"><table class="n-table"><thead><tr><th>Need / Goal</th><th>Interventions</th><th>Review</th><th>Status</th></tr></thead><tbody>
          <?php $ps=$conn->prepare("SELECT * FROM nursing_care_plans WHERE admission_id=? ORDER BY FIELD(status,'Active','Achieved','Discontinued'),review_due_at IS NULL,review_due_at,created_at DESC"); if($ps){$ps->bind_param('i',$selected['id']);$ps->execute();$pr=$ps->get_result();while($plan=$pr->fetch_assoc()): ?><tr><td><strong><?=htmlspecialchars($plan['problem_or_need'])?></strong><div><?=htmlspecialchars($plan['goal'])?></div></td><td><?=nl2br(htmlspecialchars($plan['interventions']))?></td><td><?=htmlspecialchars($plan['review_due_at']??'Not scheduled')?></td><td><span class="badge-soft"><?=htmlspecialchars($plan['status'])?></span></td></tr><?php endwhile; $ps->close(); } ?></tbody></table></div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="n-card"><div class="n-head"><strong>Recent Nursing Activity</strong><span class="text-muted small">Latest 25 notes and observations</span></div><div class="n-body"><div class="table-responsive"><table class="n-table"><thead><tr><th>Patient</th><th>Location</th><th>Type</th><th>Time</th><th>Summary</th></tr></thead><tbody>
  <?php if($recent && $recent->num_rows): while($r=$recent->fetch_assoc()): ?><tr><td><strong><?=htmlspecialchars($r['full_name'])?></strong><br><small><?=htmlspecialchars($r['patient_number'])?></small></td><td><?=htmlspecialchars($r['ward_name'])?> · Bed <?= (int)$r['bed_number'] ?></td><td><?=htmlspecialchars($r['note_type'])?></td><td><?=htmlspecialchars($r['created_at'])?></td><td><?=htmlspecialchars(mb_strimwidth($r['note_text'],0,120,'…'))?></td></tr><?php endwhile; else: ?><tr><td colspan="5" class="text-center text-muted py-4">No nursing notes recorded yet.</td></tr><?php endif; ?></tbody></table></div></div></div>
</div></div>
<script>
document.querySelectorAll('.tab-btn').forEach(btn=>btn.addEventListener('click',function(){
 document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));this.classList.add('active');
 document.querySelectorAll('.record-pane').forEach(p=>p.style.display='none');
 document.getElementById(this.dataset.target).style.display='block';
}));
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>