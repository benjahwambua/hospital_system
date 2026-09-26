<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_module_access($conn, 'clinical', 'approve');
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin','doctor','nurse']);

if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token'];$message='';
$admissionId=(int)($_GET['id']??$_POST['id']??0);
if($admissionId<=0){header('Location: ward_management.php');exit;}

$stmt=$conn->prepare("SELECT a.*,p.full_name,p.patient_number FROM admissions a JOIN patients p ON p.id=a.patient_id WHERE a.id=? LIMIT 1");
$stmt->bind_param('i',$admissionId);$stmt->execute();$admission=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$admission){$_SESSION['msg_error']='Admission record not found.';header('Location: ward_management.php');exit;}

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['confirm_discharge'])){
 if(!hash_equals($csrfToken,(string)($_POST['csrf_token']??'')))$message="<div class='alert alert-danger'>Invalid security token. Please refresh and try again.</div>";
 elseif(($admission['status']??'')!=='Admitted')$message="<div class='alert alert-warning'>This admission is already closed and cannot be discharged again.</div>";
 else{
  $diagnosis=trim((string)($_POST['discharge_diagnosis']??''));$notes=trim((string)($_POST['discharge_notes']??''));$followUp=trim((string)($_POST['follow_up']??''));$userId=(int)($_SESSION['user_id']??0);
  if($diagnosis===''||$notes==='')$message="<div class='alert alert-danger'>Discharge diagnosis and condition/notes are required.</div>";
  else{
   $conn->begin_transaction();
   try{
    $cols=[];$has=$conn->query("SHOW COLUMNS FROM admissions");if($has)while($c=$has->fetch_assoc())$cols[$c['Field']]=true;
    if(isset($cols['discharge_diagnosis'],$cols['discharge_notes'],$cols['follow_up'])){
     $u=$conn->prepare("UPDATE admissions SET status='Discharged',discharge_date=NOW(),discharged_by=?,discharge_diagnosis=?,discharge_notes=?,follow_up=? WHERE id=? AND status='Admitted'");
     $u->bind_param('isssi',$userId,$diagnosis,$notes,$followUp,$admissionId);
    }else{
     $u=$conn->prepare("UPDATE admissions SET status='Discharged',discharge_date=NOW(),discharged_by=? WHERE id=? AND status='Admitted'");
     $u->bind_param('ii',$userId,$admissionId);
    }
    if(!$u||!$u->execute()||$u->affected_rows!==1)throw new Exception('Admission could not be discharged.');if($u)$u->close();
    if(!empty($admission['visit_id'])){$v=$conn->prepare("UPDATE visits SET status='Completed',updated_at=NOW() WHERE id=? AND patient_id=? AND status<>'Cancelled'");if($v){$v->bind_param('ii',$admission['visit_id'],$admission['patient_id']);$v->execute();$v->close();}}
    $a=$conn->prepare("UPDATE appointments SET status='Closed' WHERE patient_id=? AND COALESCE(visit_id,0)=? AND status NOT IN ('Closed','Cancelled','Completed')");if($a&&isset($admission['visit_id'])){$a->bind_param('ii',$admission['patient_id'],$admission['visit_id']);$a->execute();$a->close();}
    if(function_exists('audit'))audit('patient_discharge',"admission_id={$admissionId},patient_id={$admission['patient_id']}");
    $conn->commit();$_SESSION['msg_success']='Patient discharged successfully.';header('Location: ward_management.php?status=discharged');exit;
   }catch(Throwable $e){$conn->rollback();error_log('Discharge Error: '.$e->getMessage());$message="<div class='alert alert-danger'>Unable to complete discharge. No changes were saved.</div>";}
  }
 }
}

include __DIR__ . '/../includes/header.php';include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.discharge-page{padding:28px 0 45px}.discharge-hero{background:linear-gradient(135deg,#fff9f9,#fff);border:1px solid #f0dddd;border-radius:16px;padding:23px 25px;margin-bottom:20px}.discharge-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.4px;font-weight:800;color:#dc3545}.discharge-hero h1{font-size:25px;font-weight:800;color:#293646;margin:4px 0}.discharge-hero p{color:#6c7886;margin:0}.patient-summary{background:#f8fafc;border:1px solid #e7edf3;border-radius:12px;padding:18px;margin-bottom:22px}.summary-label{font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:#8491a0;font-weight:800}.summary-value{font-weight:700;color:#2f3d4d;margin-top:3px}.discharge-card{background:#fff;border:1px solid #e8edf3;border-radius:16px;box-shadow:0 7px 22px rgba(31,45,61,.06)}.discharge-card-head{padding:18px 22px;border-bottom:1px solid #edf1f5;font-weight:800;color:#293646}.discharge-card-body{padding:23px}.discharge-label{font-size:12px;text-transform:uppercase;letter-spacing:.45px;font-weight:800;color:#566474;margin-bottom:7px}.discharge-field{border:1px solid #dbe3ec;border-radius:9px;padding:11px 13px}.discharge-field:focus{border-color:#dc3545;box-shadow:0 0 0 3px rgba(220,53,69,.08)}.discharge-actions{border-top:1px solid #edf1f5;margin-top:22px;padding-top:20px;display:flex;justify-content:space-between;gap:10px}@media(max-width:767px){.discharge-actions{flex-direction:column}.discharge-actions .btn{width:100%}}
</style>
<div class="main-content"><div class="container-fluid discharge-page">
 <div class="discharge-hero"><div class="discharge-kicker">Clinical · Inpatient Care</div><h1>Discharge Patient</h1><p>Complete the clinical discharge documentation and close the inpatient encounter.</p></div>
 <?=$message?>
 <div class="discharge-card"><div class="discharge-card-head"><i class="fas fa-file-medical text-danger mr-2"></i>Admission Summary</div><div class="discharge-card-body">
  <div class="patient-summary"><div class="row">
   <div class="col-md-4 mb-3 mb-md-0"><div class="summary-label">Patient</div><div class="summary-value"><?=htmlspecialchars($admission['full_name'])?></div><small class="text-muted"><?=htmlspecialchars($admission['patient_number']??'')?></small></div>
   <div class="col-md-4 mb-3 mb-md-0"><div class="summary-label">Ward / Bed</div><div class="summary-value"><?=htmlspecialchars($admission['ward_name'])?> · Bed <?=htmlspecialchars($admission['bed_number'])?></div></div>
   <div class="col-md-4"><div class="summary-label">Admitted</div><div class="summary-value"><?=htmlspecialchars($admission['admit_date'])?></div></div>
  </div></div>
  <form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>"><input type="hidden" name="id" value="<?=$admissionId?>">
   <div class="form-group"><label class="discharge-label">Discharge Diagnosis</label><textarea name="discharge_diagnosis" class="form-control discharge-field" rows="3" required><?=htmlspecialchars($_POST['discharge_diagnosis']??'')?></textarea></div>
   <div class="form-group"><label class="discharge-label">Condition at Discharge / Clinical Notes</label><textarea name="discharge_notes" class="form-control discharge-field" rows="4" required><?=htmlspecialchars($_POST['discharge_notes']??'')?></textarea></div>
   <div class="form-group mb-0"><label class="discharge-label">Follow-up & Patient Instructions</label><textarea name="follow_up" class="form-control discharge-field" rows="4"><?=htmlspecialchars($_POST['follow_up']??'')?></textarea></div>
   <div class="discharge-actions"><a href="ward_management.php" class="btn btn-light border"><i class="fas fa-arrow-left mr-1"></i> Back to Wards</a><button name="confirm_discharge" class="btn btn-danger px-4" onclick="return confirm('Confirm discharge of this patient?');"><i class="fas fa-sign-out-alt mr-1"></i> Confirm Discharge</button></div>
  </form>
 </div></div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>