<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_module_access($conn, 'clinical', 'create');

$error = '';
$success = '';
$patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $date = trim((string)($_POST['appointment_date'] ?? ''));
        $time = trim((string)($_POST['appointment_time'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));

        if ($patientId <= 0 || $doctorId <= 0 || $date === '' || $time === '') {
            $error = 'Patient, clinician, date and time are required.';
        } elseif ($date < date('Y-m-d')) {
            $error = 'Appointment date cannot be in the past.';
        } else {
            $patientCheck = $conn->prepare("SELECT id FROM patients WHERE id=? LIMIT 1");
            $patientCheck->bind_param('i', $patientId);
            $patientCheck->execute();
            $patientExists = $patientCheck->get_result()->fetch_assoc();
            $patientCheck->close();

            $doctorCheck = $conn->prepare("SELECT id FROM users WHERE id=? AND LOWER(role) IN ('doctor','admin') LIMIT 1");
            $doctorCheck->bind_param('i', $doctorId);
            $doctorCheck->execute();
            $doctorExists = $doctorCheck->get_result()->fetch_assoc();
            $doctorCheck->close();

            if (!$patientExists) {
                $error = 'Selected patient was not found.';
            } elseif (!$doctorExists) {
                $error = 'Selected clinician is not available for appointments.';
            } else {
                $conflict = $conn->prepare("SELECT id FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND COALESCE(status,'') NOT IN ('Cancelled','Closed','Completed') LIMIT 1");
                $conflict->bind_param('iss', $doctorId, $date, $time);
                $conflict->execute();
                $hasConflict = $conflict->get_result()->fetch_assoc();
                $conflict->close();

                if ($hasConflict) {
                    $error = 'The selected clinician already has an active appointment at that time.';
                } else {
                    $status = 'Scheduled';
                    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, created_at) VALUES (?,?,?,?,?,?,NOW())");
                    $stmt->bind_param('iissss', $patientId, $doctorId, $date, $time, $reason, $status);
                    if ($stmt->execute()) {
                        $stmt->close();
                        header('Location: ../patients/appointments.php?scheduled=1');
                        exit;
                    }
                    $error = 'Unable to create appointment: ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

$patients = $conn->query("SELECT id, full_name, patient_number, phone FROM patients ORDER BY full_name ASC");
$doctors = $conn->query("SELECT id, full_name, role FROM users WHERE LOWER(role) IN ('doctor','admin') ORDER BY full_name ASC");

$selectedPatient = null;
if ($patientId > 0) {
    $s = $conn->prepare("SELECT id, full_name, patient_number FROM patients WHERE id=? LIMIT 1");
    $s->bind_param('i', $patientId);
    $s->execute();
    $selectedPatient = $s->get_result()->fetch_assoc();
    $s->close();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.appt-page{padding:28px 0 45px}.appt-hero{background:linear-gradient(135deg,#f7fbff,#fff);border:1px solid #e7edf5;border-radius:16px;padding:24px 26px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:20px}.appt-kicker{text-transform:uppercase;font-size:11px;font-weight:800;letter-spacing:1.5px;color:#4e73df;margin-bottom:5px}.appt-hero h1{font-size:25px;font-weight:800;margin:0;color:#26364a}.appt-hero p{margin:6px 0 0;color:#6b7785}.appt-card{background:#fff;border:1px solid #e8edf3;border-radius:16px;box-shadow:0 6px 20px rgba(31,45,61,.06)}.appt-card-head{padding:18px 22px;border-bottom:1px solid #edf1f5;font-weight:800;color:#26364a}.appt-card-body{padding:24px}.appt-label{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#596779;margin-bottom:7px}.appt-field{border:1px solid #dbe3ec;border-radius:9px;padding:11px 13px;height:auto}.appt-field:focus{border-color:#4e73df;box-shadow:0 0 0 3px rgba(78,115,223,.10)}.appt-actions{border-top:1px solid #edf1f5;margin-top:25px;padding-top:20px;display:flex;justify-content:flex-end;gap:10px}@media(max-width:767px){.appt-hero{align-items:flex-start;flex-direction:column}.appt-actions{flex-direction:column}.appt-actions .btn{width:100%}}
</style>
<div class="main-content">
 <div class="container-fluid appt-page">
  <div class="appt-hero">
   <div><div class="appt-kicker">Clinical Scheduling</div><h1>Schedule Appointment</h1><p>Create a patient appointment and assign the responsible clinician.</p></div>
   <a href="../patients/appointments.php" class="btn btn-light border"><i class="fas fa-calendar-alt mr-1 text-primary"></i> Appointment Register</a>
  </div>
  <?php if($error): ?><div class="alert alert-danger border-0 shadow-sm"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <div class="appt-card">
   <div class="appt-card-head"><i class="fas fa-calendar-plus text-primary mr-2"></i>Appointment Details</div>
   <div class="appt-card-body">
    <form method="post" autocomplete="off">
     <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
     <div class="row">
      <div class="col-lg-6 mb-4"><label class="appt-label">Patient</label>
       <select name="patient_id" class="form-control appt-field select2" required>
        <option value="">Select patient</option>
        <?php if($patients): while($p=$patients->fetch_assoc()): ?>
         <option value="<?=$p['id']?>" <?=((int)$p['id']===$patientId?'selected':'')?>><?=htmlspecialchars($p['full_name'])?> — <?=htmlspecialchars($p['patient_number'])?><?=!empty($p['phone'])?' · '.htmlspecialchars($p['phone']):''?></option>
        <?php endwhile; endif; ?>
       </select>
      </div>
      <div class="col-lg-6 mb-4"><label class="appt-label">Clinician</label>
       <select name="doctor_id" class="form-control appt-field" required><option value="">Select clinician</option>
        <?php if($doctors): while($d=$doctors->fetch_assoc()): ?><option value="<?=$d['id']?>"><?=htmlspecialchars($d['full_name'])?><?=strtolower((string)$d['role'])==='admin'?' · Admin':''?></option><?php endwhile; endif; ?>
       </select>
      </div>
      <div class="col-md-6 mb-4"><label class="appt-label">Appointment Date</label><input type="date" name="appointment_date" class="form-control appt-field" min="<?=date('Y-m-d')?>" value="<?=htmlspecialchars($_POST['appointment_date']??date('Y-m-d'))?>" required></div>
      <div class="col-md-6 mb-4"><label class="appt-label">Appointment Time</label><input type="time" name="appointment_time" class="form-control appt-field" value="<?=htmlspecialchars($_POST['appointment_time']??'')?>" required></div>
      <div class="col-12"><label class="appt-label">Reason / Service</label><textarea name="reason" class="form-control appt-field" rows="4" placeholder="e.g. Consultation, review, ANC follow-up, postnatal review..."><?=htmlspecialchars($_POST['reason']??'')?></textarea></div>
     </div>
     <div class="appt-actions"><a href="../patients/appointments.php" class="btn btn-light border">Cancel</a><button type="submit" class="btn btn-primary px-4"><i class="fas fa-calendar-check mr-1"></i> Create Appointment</button></div>
    </form>
   </div>
  </div>
 </div>
</div>
<script>$(function(){if($.fn.select2){$('.select2').select2({theme:'bootstrap4',width:'100%',placeholder:'Select patient'});}});</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>