<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token']; $message='';

$vitalsHasStatus=false;
$vc=$conn->query("SHOW COLUMNS FROM vitals");
if($vc) while($col=$vc->fetch_assoc()) if(($col['Field']??'')==='status') {$vitalsHasStatus=true;break;}
$hasVisitColumn=($x=$conn->query("SHOW COLUMNS FROM vitals LIKE 'visit_id'")) && $x->num_rows>0;

if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrfToken,$_POST['csrf_token']??'')) $message="<div class='alert alert-danger'>Invalid security token. Please try again.</div>";
 else{
  $patientId=(int)($_POST['patient_id']??0);
  $bp=trim($_POST['bp']??''); $temp=trim($_POST['temp']??''); $weight=trim($_POST['weight']??''); $pulse=trim($_POST['pulse']??'');
  $complaints=trim($_POST['complaints']??''); $clinic=trim($_POST['clinic_category']??'General');
  if($patientId<=0) $message="<div class='alert alert-danger'>Select a valid patient.</div>";
  else{
   $p=$conn->prepare("SELECT id,full_name FROM patients WHERE id=? LIMIT 1"); $p->bind_param('i',$patientId); $p->execute(); $patient=$p->get_result()->fetch_assoc(); $p->close();
   if(!$patient) $message="<div class='alert alert-danger'>Patient not found.</div>";
   else{
    try{
     $visitId=get_or_create_current_visit($conn,$patientId,'Outpatient',$clinic);
     if($hasVisitColumn && $vitalsHasStatus){
      $status='pending'; $stmt=$conn->prepare("INSERT INTO vitals (patient_id,bp,temp,weight,pulse,complaints,recorded_by,visit_id,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
      $stmt->bind_param('isssssiis',$patientId,$bp,$temp,$weight,$pulse,$complaints,$_SESSION['user_id'],$visitId,$status);
     }elseif($hasVisitColumn){
      $stmt=$conn->prepare("INSERT INTO vitals (patient_id,bp,temp,weight,pulse,complaints,recorded_by,visit_id,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
      $stmt->bind_param('isssssii',$patientId,$bp,$temp,$weight,$pulse,$complaints,$_SESSION['user_id'],$visitId);
     }elseif($vitalsHasStatus){
      $status='pending'; $stmt=$conn->prepare("INSERT INTO vitals (patient_id,bp,temp,weight,pulse,complaints,recorded_by,status,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
      $stmt->bind_param('isssssis',$patientId,$bp,$temp,$weight,$pulse,$complaints,$_SESSION['user_id'],$status);
     }else{
      $stmt=$conn->prepare("INSERT INTO vitals (patient_id,bp,temp,weight,pulse,complaints,recorded_by,created_at) VALUES (?,?,?,?,?,?,?,NOW())");
      $stmt->bind_param('isssssi',$patientId,$bp,$temp,$weight,$pulse,$complaints,$_SESSION['user_id']);
     }
     if(!$stmt || !$stmt->execute()) throw new Exception($stmt?$stmt->error:$conn->error);
     if($stmt)$stmt->close();
     if($visitId>0){$v=$conn->prepare("UPDATE visits SET clinic_category=?,status='Open',updated_at=NOW() WHERE id=? AND patient_id=?");if($v){$v->bind_param('sii',$clinic,$visitId,$patientId);$v->execute();$v->close();}}
     header("Location: consultations.php?triage=success"); exit;
    }catch(Throwable $e){$message="<div class='alert alert-danger'>Unable to record triage: ".htmlspecialchars($e->getMessage())."</div>";}
   }
  }
 }
}
$patients=$conn->query("SELECT id,full_name,gender,age,patient_number FROM patients ORDER BY full_name ASC");
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4"><div class="row justify-content-center"><div class="col-xl-9">
<div class="card shadow-sm border-0"><div class="card-header bg-white py-3 d-flex justify-content-between"><h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-heartbeat mr-2"></i>Patient Triage & Vitals</h5><a href="consultations.php" class="btn btn-sm btn-outline-primary">Doctor Queue</a></div>
<div class="card-body p-4"><?=$message?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
<div class="row"><div class="col-md-8 mb-3"><label class="small font-weight-bold">PATIENT</label><select name="patient_id" class="form-control select2" required><option value="">-- Search patient --</option><?php if($patients)while($p=$patients->fetch_assoc()): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['full_name'])?> — <?=htmlspecialchars($p['patient_number']??'')?> · <?=htmlspecialchars($p['gender']??'')?> · <?=htmlspecialchars($p['age']??'')?> yrs</option><?php endwhile; ?></select></div>
<div class="col-md-4 mb-3"><label class="small font-weight-bold">CLINIC / DEPARTMENT</label><select name="clinic_category" class="form-control"><option>General</option><option>Outpatient</option><option>Dental</option><option>Maternal</option><option>Pediatric</option><option>Emergency</option><option>Specialist</option></select></div></div>
<div class="row">
<div class="col-md-3 mb-3"><label>BP (mmHg)</label><input name="bp" class="form-control" placeholder="120/80"></div>
<div class="col-md-3 mb-3"><label>Temperature (°C)</label><input name="temp" type="number" step="0.1" class="form-control" placeholder="36.5"></div>
<div class="col-md-3 mb-3"><label>Weight (kg)</label><input name="weight" type="number" step="0.1" class="form-control" placeholder="70"></div>
<div class="col-md-3 mb-3"><label>Pulse (bpm)</label><input name="pulse" type="number" class="form-control" placeholder="72"></div></div>
<div class="form-group"><label>Chief Complaints / Triage Notes</label><textarea name="complaints" class="form-control" rows="4" placeholder="Symptoms, duration, immediate observations..."></textarea></div>
<button class="btn btn-primary btn-block font-weight-bold py-2"><i class="fas fa-arrow-right mr-1"></i> Submit to Doctor's Queue</button>
</form></div></div></div></div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>