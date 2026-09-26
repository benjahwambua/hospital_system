<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_module_access($conn, 'clinical', 'create');
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin','doctor','nurse','receptionist']);

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token']; $message='';
$preWard=trim((string)($_GET['ward']??''));
$preBed=(int)($_GET['bed']??0);

$wards=['General Ward (Male)','General Ward (Female)','Maternity Ward','Pediatric Ward','ICU'];

if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrfToken,(string)($_POST['csrf_token']??''))){$message="<div class='alert alert-danger'>Invalid security token. Please refresh and try again.</div>";}
 else{
  $patientId=(int)($_POST['patient_id']??0); $ward=trim((string)($_POST['ward_name']??'')); $bed=(int)($_POST['bed_number']??0);
  $admitDate=trim((string)($_POST['admit_date']??'')); $reason=trim((string)($_POST['reason']??'')); $doctor=trim((string)($_POST['attending_doctor']??'')); $userId=(int)($_SESSION['user_id']??0);
  if($patientId<=0||!in_array($ward,$wards,true)||$bed<1||$reason===''||$doctor===''){$message="<div class='alert alert-danger'>Complete all required admission details.</div>";}
  else{
   $s=$conn->prepare("SELECT id FROM admissions WHERE ward_name=? AND bed_number=? AND status='Admitted' LIMIT 1");$s->bind_param('si',$ward,$bed);$s->execute();$occupied=$s->get_result()->fetch_assoc();$s->close();
   $s=$conn->prepare("SELECT id FROM admissions WHERE patient_id=? AND status='Admitted' LIMIT 1");$s->bind_param('i',$patientId);$s->execute();$already=$s->get_result()->fetch_assoc();$s->close();
   if($occupied)$message="<div class='alert alert-danger'><strong>Bed unavailable.</strong> Bed {$bed} in ".htmlspecialchars($ward)." is occupied.</div>";
   elseif($already)$message="<div class='alert alert-warning'>This patient already has an active admission.</div>";
   else{
    $visitId=get_or_create_current_visit($conn,$patientId,'Inpatient',$ward);
    $hasVisitColumn=false;$vc=$conn->query("SHOW COLUMNS FROM admissions LIKE 'visit_id'");if($vc&&$vc->num_rows)$hasVisitColumn=true;
    $status='Admitted';
    if($hasVisitColumn&&$visitId>0){$s=$conn->prepare("INSERT INTO admissions (patient_id,visit_id,ward_name,bed_number,admit_date,reason,admitted_by,attending_doctor,created_by,status) VALUES (?,?,?,?,?,?,?,?,?,?)");$s->bind_param('iisisisiss',$patientId,$visitId,$ward,$bed,$admitDate,$reason,$userId,$doctor,$userId,$status);}
    else{$s=$conn->prepare("INSERT INTO admissions (patient_id,ward_name,bed_number,admit_date,reason,admitted_by,attending_doctor,created_by,status) VALUES (?,?,?,?,?,?,?,?,?)");$s->bind_param('isisisiss',$patientId,$ward,$bed,$admitDate,$reason,$userId,$doctor,$userId,$status);}
    if($s&&$s->execute()){
      if($visitId>0){$v=$conn->prepare("UPDATE visits SET visit_type='Inpatient',clinic_category=?,status='In Progress',updated_at=NOW() WHERE id=? AND patient_id=?");if($v){$v->bind_param('sii',$ward,$visitId,$patientId);$v->execute();$v->close();}}
      if(function_exists('audit'))audit('patient_admission',"patient_id={$patientId},ward={$ward},bed={$bed}");
      $message="<div class='alert alert-success'><strong>Patient admitted successfully.</strong> Bed {$bed} in ".htmlspecialchars($ward)." is now occupied. <a href='ward_management.php' class='alert-link ml-2'>View Ward</a></div>";
    }else $message="<div class='alert alert-danger'>Unable to complete admission: ".htmlspecialchars($s?$s->error:$conn->error)."</div>";
    if($s)$s->close();
   }
  }
 }
}
$patients=$conn->query("SELECT id,full_name,patient_number,phone FROM patients ORDER BY full_name ASC");
include __DIR__ . '/../includes/header.php';include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.admit-page{padding:28px 0 45px}.admit-hero{background:linear-gradient(135deg,#f7fbff,#fff);border:1px solid #e6edf5;border-radius:16px;padding:24px 26px;display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px}.admit-kicker{font-size:11px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:#4e73df}.admit-hero h1{font-size:26px;font-weight:800;color:#26364a;margin:4px 0}.admit-hero p{color:#6b7785;margin:0}.admit-card{background:#fff;border:1px solid #e8edf3;border-radius:16px;box-shadow:0 7px 22px rgba(31,45,61,.06)}.admit-section{padding:22px 25px;border-bottom:1px solid #edf1f5}.admit-section:last-child{border-bottom:0}.admit-section h3{font-size:13px;text-transform:uppercase;letter-spacing:.8px;color:#4e73df;font-weight:800;margin:0 0 18px}.admit-section h3 i{margin-right:7px}.admit-label{font-size:12px;font-weight:800;color:#566474;text-transform:uppercase;letter-spacing:.35px;margin-bottom:7px}.admit-field{border:1px solid #dbe3ec;border-radius:9px;padding:11px 13px;height:auto}.admit-field:focus{border-color:#4e73df;box-shadow:0 0 0 3px rgba(78,115,223,.1)}.admit-footer{padding:20px 25px;background:#fafbfd;border-radius:0 0 16px 16px;display:flex;justify-content:space-between;align-items:center;gap:15px}@media(max-width:767px){.admit-hero,.admit-footer{align-items:flex-start;flex-direction:column}.admit-footer .btn{width:100%}}
</style>
<div class="main-content"><div class="container-fluid admit-page">
 <div class="admit-hero"><div><div class="admit-kicker">Clinical · Inpatient Care</div><h1>New Patient Admission</h1><p>Register the admission, allocate a bed and start the inpatient encounter.</p></div><a href="ward_management.php" class="btn btn-light border"><i class="fas fa-bed text-primary mr-1"></i> Ward Management</a></div>
 <?=$message?>
 <div class="admit-card">
  <form method="post" autocomplete="off">
   <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
   <div class="admit-section"><h3><i class="fas fa-user-injured"></i>Patient & Clinician</h3><div class="row">
    <div class="col-lg-7 mb-3"><label class="admit-label">Patient</label><select name="patient_id" class="form-control admit-field select2" required><option value="">Search patient</option><?php if($patients):while($p=$patients->fetch_assoc()):?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['full_name'])?> — <?=htmlspecialchars($p['patient_number'])?><?=!empty($p['phone'])?' · '.htmlspecialchars($p['phone']):''?></option><?php endwhile;endif;?></select></div>
    <div class="col-lg-5 mb-3"><label class="admit-label">Attending Clinician</label><input name="attending_doctor" class="form-control admit-field" value="<?=htmlspecialchars($_POST['attending_doctor']??'')?>" placeholder="Physician / clinician name" required></div>
   </div></div>
   <div class="admit-section"><h3><i class="fas fa-bed"></i>Ward & Bed Allocation</h3><div class="row">
    <div class="col-md-6 mb-3"><label class="admit-label">Ward</label><select name="ward_name" class="form-control admit-field" required><?php foreach($wards as $w):?><option value="<?=htmlspecialchars($w)?>" <?=($preWard===$w?'selected':'')?>><?=htmlspecialchars($w)?><?=($w==='ICU'?' — Intensive Care Unit':'')?></option><?php endforeach;?></select></div>
    <div class="col-md-3 mb-3"><label class="admit-label">Bed Number</label><input type="number" min="1" max="6" name="bed_number" class="form-control admit-field" value="<?=htmlspecialchars((string)$preBed)?>" required></div>
    <div class="col-md-3 mb-3"><label class="admit-label">Admission Date & Time</label><input type="datetime-local" name="admit_date" class="form-control admit-field" value="<?=htmlspecialchars($_POST['admit_date']??date('Y-m-d\TH:i'))?>" required></div>
   </div></div>
   <div class="admit-section"><h3><i class="fas fa-notes-medical"></i>Clinical Information</h3><label class="admit-label">Reason for Admission / Initial Diagnosis</label><textarea name="reason" class="form-control admit-field" rows="5" placeholder="Document the clinical reason for admission, presenting diagnosis or indication..." required><?=htmlspecialchars($_POST['reason']??'')?></textarea></div>
   <div class="admit-footer"><div class="small text-muted"><i class="fas fa-user-circle mr-1"></i> Recorded by <strong><?=htmlspecialchars($_SESSION['user_name']??'System User')?></strong></div><div><a href="ward_management.php" class="btn btn-light border mr-2">Cancel</a><button class="btn btn-primary px-4"><i class="fas fa-check-circle mr-1"></i> Admit Patient</button></div></div>
  </form>
 </div>
</div></div>
<script>$(function(){if($.fn.select2){$('.select2').select2({theme:'bootstrap4',width:'100%',placeholder:'Search patient'});}});</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>