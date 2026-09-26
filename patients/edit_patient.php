<?php
// patients/edit_patient.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'front_desk', 'edit');
require_role(['admin','receptionist']);

$id = max(0, (int)($_GET['id'] ?? $_POST['id'] ?? 0));
if ($id <= 0) { header('Location: /hospital_system/patients/patient_list.php'); exit; }

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$errors = [];

$patientStmt = $conn->prepare("SELECT p.* FROM patients p WHERE p.id=? LIMIT 1");
$patientStmt->bind_param('i', $id);
$patientStmt->execute();
$patient = $patientStmt->get_result()->fetch_assoc();
$patientStmt->close();

if (!$patient) { http_response_code(404); exit('Patient not found.'); }

$allowedClinicalTypes = ['General','Emergency','OPD','ANC','PNC','Maternity','Immunization','Family Planning','SGBV','CCC','Nutrition','Dental','Physiotherapy'];
$allowedGenders = ['Male','Female','Other',''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) $errors[] = 'Security token mismatch. Please refresh and try again.';

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $gender = trim((string)($_POST['gender'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $dob = trim((string)($_POST['dob'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $nextOfKinName = trim((string)($_POST['next_of_kin_name'] ?? ''));
    $nextOfKinPhone = trim((string)($_POST['next_of_kin_phone'] ?? ''));
    $doctorId = max(0, (int)($_POST['doctor_id'] ?? 0));
    $clinicCategory = trim((string)($_POST['clinic_category'] ?? 'General'));

    if ($fullName === '') $errors[] = 'Full name is required.';
    if (!in_array($gender, $allowedGenders, true)) $errors[] = 'Invalid gender selected.';
    if (!in_array($clinicCategory, $allowedClinicalTypes, true)) $errors[] = 'Invalid clinic/service type selected.';
    if ($nextOfKinName === '' && empty($patient['is_walkin'])) $errors[] = 'Next of kin name is required for registered patients.';
    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors[] = 'Phone number format is invalid.';
    if ($nextOfKinPhone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $nextOfKinPhone)) $errors[] = 'Next of kin phone number format is invalid.';

    $validDob = null;
    if ($dob !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$dt || $dt->format('Y-m-d') !== $dob) $errors[] = 'Date of birth must be a valid date.';
        elseif ($dt > new DateTime('today')) $errors[] = 'Date of birth cannot be in the future.';
        else $validDob = $dob;
    }

    $age = 0;
    if ($validDob !== null) $age = (new DateTime('today'))->diff(new DateTime($validDob))->y;
    if (in_array($clinicCategory, ['ANC','PNC','Maternity'], true) && $gender !== '' && $gender !== 'Female') $errors[] = $clinicCategory . ' patients should be recorded as Female.';

    if ($doctorId > 0) {
        $doctorCheck = $conn->prepare("SELECT id FROM users WHERE id=? AND role='doctor' LIMIT 1");
        $doctorCheck->bind_param('i', $doctorId);
        $doctorCheck->execute();
        if ($doctorCheck->get_result()->num_rows === 0) $errors[] = 'Selected doctor was not found.';
        $doctorCheck->close();
    }

    if (!$errors) {
        $stmt = $conn->prepare("UPDATE patients SET full_name=?, gender=?, phone=?, date_of_birth=?, address=?, age=?, next_of_kin_name=?, next_of_kin_phone=?, doctor_id=NULLIF(?,0), clinic_category=? WHERE id=?");
        if (!$stmt) {
            $errors[] = 'Unable to prepare patient update: ' . $conn->error;
        } else {
            $stmt->bind_param('sssssissisi', $fullName, $gender, $phone, $validDob, $address, $age, $nextOfKinName, $nextOfKinPhone, $doctorId, $clinicCategory, $id);
            if (!$stmt->execute()) $errors[] = 'Unable to update patient: ' . $stmt->error;
            else {
                if (function_exists('audit')) audit('patient_update', 'patient_id=' . $id);
                header('Location: /hospital_system/patients/patient_dashboard.php?id=' . $id . '&updated=1');
                exit;
            }
            $stmt->close();
        }
    }

    $patient = array_merge($patient, [
        'full_name'=>$fullName,'gender'=>$gender,'phone'=>$phone,'date_of_birth'=>$validDob ?? $dob,
        'address'=>$address,'age'=>$age,'next_of_kin_name'=>$nextOfKinName,'next_of_kin_phone'=>$nextOfKinPhone,
        'doctor_id'=>$doctorId,'clinic_category'=>$clinicCategory
    ]);
}

$doctors = $conn->query("SELECT id, full_name FROM users WHERE role='doctor' ORDER BY full_name ASC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="h4 mb-1 text-gray-800"><i class="fas fa-user-edit text-primary mr-2"></i>Edit Patient</h2><p class="text-muted mb-0">Update demographic and registration details without changing the patient number or clinical history.</p></div>
    <div><a href="/hospital_system/patients/patient_dashboard.php?id=<?= $id ?>" class="btn btn-outline-primary mr-2"><i class="fas fa-notes-medical mr-1"></i>Patient Dashboard</a><a href="/hospital_system/patients/patient_list.php" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i>Patient List</a></div>
  </div>
  <?php if ($errors): ?><div class="alert alert-danger"><strong>Please correct the following:</strong><ul class="mb-0 mt-2"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center"><div><strong><?= htmlspecialchars($patient['full_name']) ?></strong><div class="small text-muted"><?= htmlspecialchars($patient['patient_number'] ?? '') ?></div></div><span class="badge badge-<?= !empty($patient['is_walkin']) ? 'warning' : 'success' ?>"><?= !empty($patient['is_walkin']) ? 'Walk-in' : 'Registered Patient' ?></span></div>
    <div class="card-body"><form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" value="<?= $id ?>">
      <h6 class="text-primary font-weight-bold text-uppercase mb-3"><i class="fas fa-id-card mr-2"></i>Patient Identification</h6>
      <div class="row">
        <div class="col-md-8 form-group"><label>Full Name *</label><input name="full_name" class="form-control" required value="<?= htmlspecialchars($patient['full_name'] ?? '') ?>"></div>
        <div class="col-md-4 form-group"><label>Patient Number</label><input class="form-control bg-light" readonly value="<?= htmlspecialchars($patient['patient_number'] ?? '') ?>"></div>
        <div class="col-md-4 form-group"><label>Gender</label><select name="gender" class="form-control"><?php foreach($allowedGenders as $g): ?><option value="<?= htmlspecialchars($g) ?>" <?= (($patient['gender'] ?? '')===$g?'selected':'') ?>><?= $g===''?'Not specified':htmlspecialchars($g) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4 form-group"><label>Date of Birth</label><input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($patient['date_of_birth'] ?? '') ?>"></div>
        <div class="col-md-4 form-group"><label>Age</label><input class="form-control bg-light" readonly value="<?= (int)($patient['age'] ?? 0) ?>"><small class="text-muted">Calculated from date of birth when supplied.</small></div>
        <div class="col-md-6 form-group"><label>Phone</label><input name="phone" class="form-control" value="<?= htmlspecialchars($patient['phone'] ?? '') ?>"></div>
        <div class="col-md-6 form-group"><label>Address</label><input name="address" class="form-control" value="<?= htmlspecialchars($patient['address'] ?? '') ?>"></div>
      </div>
      <hr><h6 class="text-primary font-weight-bold text-uppercase mb-3"><i class="fas fa-user-friends mr-2"></i>Next of Kin</h6>
      <div class="row"><div class="col-md-6 form-group"><label>Next of Kin Name <?= empty($patient['is_walkin']) ? '*' : '' ?></label><input name="next_of_kin_name" class="form-control" value="<?= htmlspecialchars($patient['next_of_kin_name'] ?? '') ?>"></div><div class="col-md-6 form-group"><label>Next of Kin Phone</label><input name="next_of_kin_phone" class="form-control" value="<?= htmlspecialchars($patient['next_of_kin_phone'] ?? '') ?>"></div></div>
      <hr><h6 class="text-primary font-weight-bold text-uppercase mb-3"><i class="fas fa-route mr-2"></i>Care Routing</h6>
      <div class="row"><div class="col-md-6 form-group"><label>Clinic / Service Type</label><select name="clinic_category" class="form-control"><?php foreach($allowedClinicalTypes as $type): ?><option value="<?= htmlspecialchars($type) ?>" <?= (($patient['clinic_category'] ?? 'General')===$type?'selected':'') ?>><?= htmlspecialchars($type) ?></option><?php endforeach; ?></select></div><div class="col-md-6 form-group"><label>Assigned Doctor</label><select name="doctor_id" class="form-control"><option value="0">— Not assigned —</option><?php if($doctors): while($d=$doctors->fetch_assoc()): ?><option value="<?= (int)$d['id'] ?>" <?= ((int)($patient['doctor_id'] ?? 0)===(int)$d['id']?'selected':'') ?>><?= htmlspecialchars($d['full_name']) ?></option><?php endwhile; endif; ?></select></div></div>
      <div class="d-flex justify-content-end mt-3"><a href="/hospital_system/patients/patient_dashboard.php?id=<?= $id ?>" class="btn btn-light mr-2">Cancel</a><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save Patient Changes</button></div>
    </form></div>
  </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>