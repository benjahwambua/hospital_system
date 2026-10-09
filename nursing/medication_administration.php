<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_module_access($conn, 'nursing', 'view');
$canCreate = can_module_action($conn, 'nursing', 'create');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];
$admissionId = max(0, (int)($_GET['admission_id'] ?? $_POST['admission_id'] ?? 0));
$message = '';
$admission = null;
if ($admissionId > 0) {
  $s = $conn->prepare("SELECT a.id,a.patient_id,a.ward_name,a.bed_number,p.full_name,p.patient_number FROM admissions a JOIN patients p ON p.id=a.patient_id WHERE a.id=? AND a.status='Admitted' LIMIT 1");
  if ($s) { $s->bind_param('i',$admissionId); $s->execute(); $admission=$s->get_result()->fetch_assoc(); $s->close(); }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_mar'])) {
  if (!$canCreate) $message='<div class="alert alert-danger">You do not have permission to record medication administration.</div>';
  elseif (!hash_equals($csrf,(string)($_POST['csrf_token']??''))) $message='<div class="alert alert-danger">Invalid security token.</div>';
  elseif (!$admission) $message='<div class="alert alert-danger">Active inpatient admission not found.</div>';
  else {
    $queueId=(int)($_POST['pharmacy_queue_id']??0);
    $status=(string)($_POST['status']??'');
    $dose=trim((string)($_POST['dose_given']??''));
    $route=trim((string)($_POST['route']??''));
    $notes=trim((string)($_POST['administration_notes']??''));
    $allowed=['Given','Omitted','Refused','Held','Not Available'];
    if (!in_array($status,$allowed,true) || ($status==='Given' && ($dose==='' || $route===''))) $message='<div class="alert alert-danger">Choose a valid outcome. Dose and route are required when recording a dose as given.</div>';
    else {
      $s=$conn->prepare("SELECT q.id,q.patient_id,q.prescription_id,q.medicine_id,q.quantity,s.drug_name,pr.frequency FROM pharmacy_queue q JOIN pharmacy_stock s ON s.id=q.medicine_id JOIN prescriptions pr ON pr.id=q.prescription_id WHERE q.id=? AND q.patient_id=? AND q.status='completed' LIMIT 1");
      if($s){$s->bind_param('ii',$queueId,$admission['patient_id']);$s->execute();$med=$s->get_result()->fetch_assoc();$s->close();} else $med=null;
      if(!$med) $message='<div class="alert alert-danger">Medication must match this patient’s existing prescription and a completed pharmacy dispense.</div>';
      else {
        $uid=(int)($_SESSION['user_id']??0);
        $when=$status==='Given'?date('Y-m-d H:i:s'):null;
        $s=$conn->prepare("INSERT INTO nursing_medication_administrations(admission_id,patient_id,prescription_id,pharmacy_queue_id,medicine_id,administered_at,status,dose_given,route,administration_notes,recorded_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
        if($s){$s->bind_param('iiiiisssssi',$admissionId,$admission['patient_id'],$med['prescription_id'],$med['id'],$med['medicine_id'],$when,$status,$dose,$route,$notes,$uid);
          if($s->execute()){if(function_exists('audit'))audit('nursing_medication_administration_recorded',"admission_id={$admissionId},prescription_id=".(int)$med['prescription_id'].",queue_id=".(int)$med['id'].",status={$status}");$message='<div class="alert alert-success">Medication administration outcome recorded.</div>';}
          else $message='<div class="alert alert-danger">Unable to save the medication outcome. Please review the record list before retrying.</div>';
          $s->close();
        }
      }
    }
  }
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="container-fluid py-4">
 <div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Medication Administration Record</h1><p class="text-muted mb-0">Record administration outcomes against existing prescriptions and completed pharmacy dispenses.</p></div><a class="btn btn-outline-secondary" href="index.php<?= $admissionId ? '?admission_id='.(int)$admissionId : '' ?>">Back to Nursing</a></div>
 <?= $message ?>
 <?php if(!$admission): ?>
 <div class="alert alert-warning">Open this page from an active inpatient nursing record. An active admission is required.</div>
 <?php else: ?>
 <div class="card mb-4"><div class="card-body"><strong><?=htmlspecialchars($admission['full_name'])?></strong> · <?=htmlspecialchars($admission['patient_number'])?><div class="text-muted"><?=htmlspecialchars($admission['ward_name'])?> · Bed <?= (int)$admission['bed_number'] ?> · Admission #<?= (int)$admission['id'] ?></div></div></div>
 <?php if($canCreate): ?><div class="card mb-4"><div class="card-header"><strong>Record medication outcome</strong></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="admission_id" value="<?=(int)$admissionId?>">
 <div class="form-group"><label>Completed pharmacy dispense</label><select class="form-control" name="pharmacy_queue_id" required><option value="">Select dispensed medicine</option>
 <?php $s=$conn->prepare("SELECT q.id,q.prescription_id,q.quantity,q.completed_at,s.drug_name,pr.frequency FROM pharmacy_queue q JOIN pharmacy_stock s ON s.id=q.medicine_id JOIN prescriptions pr ON pr.id=q.prescription_id WHERE q.patient_id=? AND q.status='completed' ORDER BY q.completed_at DESC"); if($s){$s->bind_param('i',$admission['patient_id']);$s->execute();$rs=$s->get_result();while($row=$rs->fetch_assoc()): ?><option value="<?=(int)$row['id']?>"><?=htmlspecialchars($row['drug_name'])?> · Rx #<?=(int)$row['prescription_id']?> · Qty <?=(int)$row['quantity']?> · <?=htmlspecialchars($row['completed_at']??'Dispensed')?></option><?php endwhile;$s->close();} ?>
 </select><small class="text-muted">Only completed dispenses linked to this patient’s existing prescription are selectable.</small></div>
 <div class="form-row"><div class="form-group col-md-4"><label>Outcome</label><select class="form-control" name="status" required><option value="Given">Given</option><option value="Omitted">Omitted</option><option value="Refused">Refused</option><option value="Held">Held</option><option value="Not Available">Not Available</option></select></div><div class="form-group col-md-4"><label>Dose given</label><input class="form-control" name="dose_given" maxlength="120" placeholder="Dose actually administered"></div><div class="form-group col-md-4"><label>Route</label><input class="form-control" name="route" maxlength="80" placeholder="e.g. oral, IV"></div></div>
 <div class="form-group"><label>Notes / reason if not given</label><textarea class="form-control" name="administration_notes" rows="2"></textarea></div><button class="btn btn-primary" name="save_mar" value="1">Save administration record</button></form></div></div><?php endif; ?>
 <div class="card"><div class="card-header"><strong>Recorded medication outcomes</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Medicine / Rx</th><th>Outcome</th><th>Dose / Route</th><th>Recorded</th><th>Notes</th></tr></thead><tbody>
 <?php
 $s=$conn->prepare("SELECT m.*,s.drug_name FROM nursing_medication_administrations m JOIN pharmacy_stock s ON s.id=m.medicine_id WHERE m.admission_id=? ORDER BY m.created_at DESC");
 if($s){
   $s->bind_param('i',$admissionId);
   $s->execute();
   $rs=$s->get_result();
   if($rs->num_rows>0){
     while($row=$rs->fetch_assoc()){
       echo '<tr><td>'.htmlspecialchars($row['drug_name']).'<br><small>Prescription #'.(int)$row['prescription_id'].'</small></td><td>'.htmlspecialchars($row['status']).'</td><td>'.htmlspecialchars($row['dose_given']??'—').' / '.htmlspecialchars($row['route']??'—').'</td><td>'.htmlspecialchars($row['administered_at']??$row['created_at']).'</td><td>'.nl2br(htmlspecialchars($row['administration_notes']??'')).'</td></tr>';
     }
   } else {
     echo '<tr><td colspan="5" class="text-center text-muted py-3">No medication administration outcomes recorded.</td></tr>';
   }
   $s->close();
 } ?>
 </tbody></table></div></div>
 <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
