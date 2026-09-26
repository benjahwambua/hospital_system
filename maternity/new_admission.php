<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'maternity', 'create');
require_role(['admin','doctor','nurse']);

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token'];
$error='';
$selectedPatient=(int)($_POST['patient_id']??$_GET['patient_id']??0);
$selectedWard=trim((string)($_POST['ward']??$_GET['ward']??''));
$selectedBed=(int)($_POST['bed_number']??$_GET['bed']??0);
$selectedDoctor=trim((string)($_POST['attending_doctor']??''));
$selectedNote=trim((string)($_POST['note']??''));

$wards=['Maternity Ward A','Maternity Ward B','Labour Ward'];
$bedCount=6;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($csrfToken,(string)($_POST['csrf_token']??''))) {
        $error='Security token mismatch. Please refresh and try again.';
    } else {
        $pid=$selectedPatient; $ward=$selectedWard; $bed=$selectedBed; $doctor=$selectedDoctor; $note=$selectedNote;
        if ($pid<=0 || $ward==='' || $bed<1 || $bed>$bedCount) {
            $error='Patient, maternity ward and a valid bed are required.';
        } else {
            $patientCheck=$conn->prepare("SELECT id,full_name FROM patients WHERE id=? AND (gender='Female' OR clinic_category IN ('ANC','PNC','Maternity')) LIMIT 1");
            $patientCheck->bind_param('i',$pid); $patientCheck->execute(); $patient=$patientCheck->get_result()->fetch_assoc(); $patientCheck->close();
            if (!$patient) {
                $error='The selected patient is not eligible for maternity admission.';
            } else {
                $active=$conn->prepare("SELECT id FROM admissions WHERE patient_id=? AND status='Admitted' LIMIT 1");
                $active->bind_param('i',$pid); $active->execute(); $activeAdmission=$active->get_result()->fetch_assoc(); $active->close();
                if ($activeAdmission) {
                    $error='This patient already has an active inpatient admission.';
                } else {
                    $bedCheck=$conn->prepare("SELECT id FROM admissions WHERE ward_name='Maternity Ward' AND bed_number=? AND status='Admitted' LIMIT 1");
                    $bedCheck->bind_param('i',$bed); $bedCheck->execute(); $occupied=$bedCheck->get_result()->fetch_assoc(); $bedCheck->close();
                    if ($occupied) {
                        $error='That maternity bed is already occupied.';
                    } else {
                        $conn->begin_transaction();
                        try {
                            $visitId=get_or_create_current_visit($conn,$pid,'Inpatient','Maternity',0);
                            if ($visitId<=0) throw new Exception('Unable to create the inpatient patient visit.');

                            $status='Admitted';
                            $wardCanonical='Maternity Ward';
                            $admitDate=date('Y-m-d H:i:s');
                            $userId=(int)($_SESSION['user_id']??0);
                            $admissionStmt=$conn->prepare("INSERT INTO admissions (patient_id,visit_id,ward_name,bed_number,admit_date,reason,admitted_by,attending_doctor,created_by,status) VALUES (?,?,?,?,?,?,?,?,?,?)");
                            if (!$admissionStmt) throw new Exception('Unable to prepare clinical admission: '.$conn->error);
                            $admissionStmt->bind_param('iisisisiss',$pid,$visitId,$wardCanonical,$bed,$admitDate,$note,$userId,$doctor,$userId,$status);
                            if (!$admissionStmt->execute()) throw new Exception('Unable to create clinical admission: '.$admissionStmt->error);
                            $admissionId=(int)$admissionStmt->insert_id; $admissionStmt->close();

                            $matStmt=$conn->prepare("INSERT INTO maternity_admissions (patient_id,admission_id,visit_id,admission_date,ward,status,note) VALUES (?,?,?,NOW(),?,'Admitted',?)");
                            if (!$matStmt) throw new Exception('Maternity linkage fields are not available. Run the clinical IPD migration first.');
                            $matStmt->bind_param('iiiss',$pid,$admissionId,$visitId,$ward,$note);
                            if (!$matStmt->execute()) throw new Exception('Unable to create maternity admission: '.$matStmt->error);
                            $matStmt->close();

                            $visitStmt=$conn->prepare("UPDATE visits SET visit_type='Inpatient',clinic_category='Maternity',status='In Progress',updated_at=NOW() WHERE id=? AND patient_id=?");
                            if ($visitStmt) { $visitStmt->bind_param('ii',$visitId,$pid); $visitStmt->execute(); $visitStmt->close(); }

                            if (function_exists('audit')) audit('maternity_admission',"admission_id={$admissionId},visit_id={$visitId},patient_id={$pid},ward={$ward},bed={$bed}");
                            $conn->commit();
                            header('Location: admissions.php?success=1'); exit;
                        } catch (Throwable $e) {
                            $conn->rollback();
                            error_log('Maternity Admission Error: '.$e->getMessage());
                            $error=$e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

$patients=$conn->query("SELECT id,full_name,patient_number FROM patients WHERE gender='Female' OR clinic_category IN ('ANC','PNC','Maternity') ORDER BY full_name ASC");
$occupiedBeds=[];
$bedResult=$conn->query("SELECT bed_number FROM admissions WHERE ward_name='Maternity Ward' AND status='Admitted'");
if($bedResult) while($b=$bedResult->fetch_assoc()) $occupiedBeds[(int)$b['bed_number']]=true;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.maternity-admit-page{padding:28px 0 48px}.ma-hero{background:linear-gradient(135deg,#f7faff,#fff);border:1px solid #e5ebf3;border-radius:16px;padding:22px 24px;margin-bottom:20px}.ma-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.3px;font-weight:800;color:#2f6fed}.ma-hero h1{font-size:25px;font-weight:800;color:#293646;margin:4px 0}.ma-hero p{margin:0;color:#6c7886}.ma-card{background:#fff;border:1px solid #e7edf3;border-radius:16px;box-shadow:0 7px 22px rgba(31,45,61,.06);overflow:hidden}.ma-head{padding:17px 22px;border-bottom:1px solid #edf1f5;font-weight:800;color:#293646}.ma-body{padding:23px}.ma-label{font-size:12px;text-transform:uppercase;letter-spacing:.45px;font-weight:800;color:#566474;margin-bottom:7px}.ma-field{border:1px solid #dbe3ec;border-radius:9px;padding:11px 13px}.bed-choice{border:1px solid #dbe3ec;border-radius:10px;padding:11px;text-align:center;cursor:pointer;display:block;font-weight:800;color:#475569;background:#fff}.bed-choice input{display:none}.bed-choice.selected,.bed-choice:hover{border-color:#2f6fed;background:#eff6ff;color:#1d4ed8}.bed-choice.disabled{opacity:.45;cursor:not-allowed;background:#f1f5f9}.ma-actions{border-top:1px solid #edf1f5;margin-top:22px;padding-top:20px;display:flex;justify-content:space-between;gap:10px}
@media(max-width:767px){.ma-actions{flex-direction:column}.ma-actions .btn{width:100%}}
</style>
<div class="main-content"><div class="container-fluid maternity-admit-page">
 <div class="ma-hero"><div class="ma-kicker">Maternity · Inpatient Care</div><h1>New Maternity Admission</h1><p>This admission creates the shared inpatient encounter used by Clinical, Maternity and Ward Management.</p></div>
 <?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
 <div class="ma-card"><div class="ma-head"><i class="fas fa-procedures text-primary mr-2"></i>Admission Details</div><div class="ma-body">
  <form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
   <div class="form-row">
    <div class="form-group col-md-7"><label class="ma-label">Patient</label><select name="patient_id" class="form-control ma-field" required><option value="">Select patient</option><?php if($patients):while($p=$patients->fetch_assoc()):?><option value="<?=$p['id']?>" <?=$selectedPatient===(int)$p['id']?'selected':''?>><?=htmlspecialchars($p['full_name'])?> — <?=htmlspecialchars($p['patient_number'])?></option><?php endwhile;endif;?></select></div>
    <div class="form-group col-md-5"><label class="ma-label">Maternity Area</label><select name="ward" class="form-control ma-field" required><option value="">Select area</option><?php foreach($wards as $w):?><option value="<?=htmlspecialchars($w)?>" <?=$selectedWard===$w?'selected':''?>><?=htmlspecialchars($w)?></option><?php endforeach;?></select></div>
   </div>
   <div class="form-group"><label class="ma-label">Bed</label><div class="row"><?php for($i=1;$i<=$bedCount;$i++):$disabled=isset($occupiedBeds[$i]);?><div class="col-6 col-md-2 mb-2"><label class="bed-choice <?=$disabled?'disabled':''?>"><input type="radio" name="bed_number" value="<?=$i?>" <?=$selectedBed===$i?'checked':''?> <?=$disabled?'disabled':''?>>BED <?=$i?><?php if($disabled):?><small class="d-block text-danger mt-1">Occupied</small><?php else:?><small class="d-block text-success mt-1">Available</small><?php endif;?></label></div><?php endfor;?></div></div>
   <div class="form-row"><div class="form-group col-md-6"><label class="ma-label">Attending Clinician</label><input name="attending_doctor" class="form-control ma-field" value="<?=htmlspecialchars($selectedDoctor)?>" placeholder="Doctor / clinician name"></div><div class="form-group col-md-6"><label class="ma-label">Admission Notes</label><textarea name="note" class="form-control ma-field" rows="3"><?=htmlspecialchars($selectedNote)?></textarea></div></div>
   <div class="ma-actions"><a href="admissions.php" class="btn btn-light border"><i class="fas fa-arrow-left mr-1"></i>Back to Admissions</a><button class="btn btn-primary px-4"><i class="fas fa-bed mr-1"></i>Admit Patient</button></div>
  </form>
 </div></div>
</div></div>
<script>document.addEventListener('change',function(e){if(e.target.matches('.bed-choice input')){document.querySelectorAll('.bed-choice').forEach(function(x){x.classList.remove('selected')});e.target.closest('.bed-choice').classList.add('selected')}});document.querySelectorAll('.bed-choice input:checked').forEach(function(x){x.closest('.bed-choice').classList.add('selected')});</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>