<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_module_access($conn, 'clinical', 'approve');
require_role(['admin','doctor','nurse']);

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token'];
$admissionId=(int)($_GET['id']??$_POST['id']??0);
if($admissionId<=0){ header('Location: ward_management.php'); exit; }

$stmt=$conn->prepare("SELECT a.*,p.full_name,p.patient_number FROM admissions a JOIN patients p ON p.id=a.patient_id WHERE a.id=? LIMIT 1");
$stmt->bind_param('i',$admissionId); $stmt->execute(); $admission=$stmt->get_result()->fetch_assoc(); $stmt->close();
if(!$admission){ $_SESSION['msg_error']='Admission record not found.'; header('Location: ward_management.php'); exit; }

$message='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['confirm_discharge'])){
 if(!hash_equals($csrfToken,$_POST['csrf_token']??'')) $message="<div class='alert alert-danger'>Invalid security token.</div>";
 elseif(($admission['status']??'')!=='Admitted') $message="<div class='alert alert-warning'>This admission is no longer active.</div>";
 else{
  $diagnosis=trim($_POST['discharge_diagnosis']??'');
  $notes=trim($_POST['discharge_notes']??'');
  $followUp=trim($_POST['follow_up']??'');
  $userId=(int)($_SESSION['user_id']??0);
  $conn->begin_transaction();
  try{
   $hasCols=$conn->query("SHOW COLUMNS FROM admissions");
   $cols=[]; if($hasCols) while($col=$hasCols->fetch_assoc()) $cols[$col['Field']]=true;
   if(isset($cols['discharge_diagnosis']) && isset($cols['discharge_notes']) && isset($cols['follow_up'])){
    $u=$conn->prepare("UPDATE admissions SET status='Discharged',discharge_date=NOW(),discharged_by=?,discharge_diagnosis=?,discharge_notes=?,follow_up=? WHERE id=? AND status='Admitted'");
    $u->bind_param('isssi',$userId,$diagnosis,$notes,$followUp,$admissionId);
   }else{
    $u=$conn->prepare("UPDATE admissions SET status='Discharged',discharge_date=NOW(),discharged_by=? WHERE id=? AND status='Admitted'");
    $u->bind_param('ii',$userId,$admissionId);
   }
   if(!$u || !$u->execute() || $u->affected_rows!==1) throw new Exception('Admission could not be discharged.');
   if($u) $u->close();

   if(isset($cols['visit_id']) && !empty($admission['visit_id'])){
    $v=$conn->prepare("UPDATE visits SET status='Completed',updated_at=NOW() WHERE id=? AND patient_id=? AND status<>'Cancelled'");
    if($v){$v->bind_param('ii',$admission['visit_id'],$admission['patient_id']);$v->execute();$v->close();}
   }
   $conn->commit();
   $_SESSION['msg_success']='Patient discharged successfully.';
   header('Location: ward_management.php?status=discharged'); exit;
  }catch(Throwable $e){$conn->rollback(); error_log('Discharge Error: '.$e->getMessage()); $message="<div class='alert alert-danger'>Unable to complete discharge. Please try again.</div>";}
 }
}

include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4"><div class="row justify-content-center"><div class="col-lg-8">
<div class="card shadow-sm"><div class="card-header bg-white"><h4 class="mb-0 font-weight-bold">Discharge Patient</h4></div><div class="card-body">
<?=$message?>
<div class="row mb-4">
<div class="col-md-6"><strong>Patient</strong><div><?=htmlspecialchars($admission['full_name'])?></div><small><?=htmlspecialchars($admission['patient_number']??'')?></small></div>
<div class="col-md-6"><strong>Admission</strong><div><?=htmlspecialchars($admission['ward_name'])?> · Bed <?=htmlspecialchars($admission['bed_number'])?></div><small>Admitted <?=htmlspecialchars($admission['admit_date'])?></small></div>
</div>
<form method="post">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
<input type="hidden" name="id" value="<?=$admissionId?>">
<div class="form-group"><label>Discharge Diagnosis</label><textarea name="discharge_diagnosis" class="form-control" rows="3" required></textarea></div>
<div class="form-group"><label>Discharge Notes / Condition</label><textarea name="discharge_notes" class="form-control" rows="4" required></textarea></div>
<div class="form-group"><label>Follow-up / Instructions</label><textarea name="follow_up" class="form-control" rows="3"></textarea></div>
<div class="d-flex justify-content-between"><a href="ward_management.php" class="btn btn-outline-secondary">Cancel</a><button name="confirm_discharge" class="btn btn-danger" onclick="return confirm('Confirm patient discharge?');"><i class="fas fa-sign-out-alt mr-1"></i> Confirm Discharge</button></div>
</form>
</div></div></div></div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>