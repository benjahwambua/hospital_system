<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function add_patient_old(string $key, string $default = ''): string
{
    return htmlspecialchars((string)($_POST[$key] ?? $default));
}

function add_patient_normalize_date(?string $value): ?string
{
    if (!$value) {
        return null;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $value);
    return ($dt && $dt->format('Y-m-d') === $value) ? $value : null;
}

function add_patient_generate_number(mysqli $conn, bool $isWalkin): string
{
    $prefix = $isWalkin ? 'WLK' : 'EMC';

    do {
        $candidate = sprintf('%s-%s-%04d', $prefix, date('Ymd'), random_int(1000, 9999));
        $stmt = $conn->prepare('SELECT id FROM patients WHERE patient_number = ? LIMIT 1');
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    } while ($exists);

    return $candidate;
}

$errors = [];
$success = '';
$allowedClinicalTypes = [
    'General', 'Emergency', 'OPD', 'ANC', 'PNC', 'Maternity', 'Immunization',
    'Family Planning', 'SGBV', 'CCC', 'Nutrition', 'Dental', 'Physiotherapy'
];
$allowedGenders = ['Male', 'Female', 'Other', ''];
$genderRestrictedDepartments = ['ANC', 'PNC', 'Maternity'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        $errors[] = 'Security token mismatch. Please refresh the page and try again.';
    }

    $registrationMode = ($_POST['registration_mode'] ?? 'full') === 'walkin' ? 'walkin' : 'full';
    $isWalkin = $registrationMode === 'walkin';

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $gender = trim((string)($_POST['gender'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $dob = add_patient_normalize_date($_POST['dob'] ?? null);
    $address = trim((string)($_POST['address'] ?? ''));
    $nextOfKinName = trim((string)($_POST['next_of_kin_name'] ?? ''));
    $nextOfKinPhone = trim((string)($_POST['next_of_kin_phone'] ?? ''));
    $doctorId = max(0, (int)($_POST['doctor_id'] ?? 0));
    $clinicalType = trim((string)($_POST['clinical_type'] ?? 'General'));

    $temperature = trim((string)($_POST['temperature'] ?? ''));
    $bp = trim((string)($_POST['bp'] ?? ''));
    $weight = trim((string)($_POST['weight'] ?? ''));
    $pulse = trim((string)($_POST['pulse'] ?? ''));
    $respiration = trim((string)($_POST['respiration'] ?? ''));

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }
    if (!in_array($gender, $allowedGenders, true)) {
        $errors[] = 'Invalid gender selected.';
    }
    if (!in_array($clinicalType, $allowedClinicalTypes, true)) {
        $errors[] = 'Invalid clinical department selected.';
    }
    if (!$isWalkin && $nextOfKinName === '') {
        $errors[] = 'Next of kin name is required for full registration.';
    }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $errors[] = 'Phone number format is invalid.';
    }
    if ($nextOfKinPhone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $nextOfKinPhone)) {
        $errors[] = 'Next of kin phone number format is invalid.';
    }
    if (($dobRaw = ($_POST['dob'] ?? '')) && $dob === null) {
        $errors[] = 'Date of birth must be a valid date.';
    }

    $age = 0;
    if ($dob !== null) {
        $dobObj = new DateTime($dob);
        $todayObj = new DateTime('today');
        if ($dobObj > $todayObj) {
            $errors[] = 'Date of birth cannot be in the future.';
        } else {
            $age = $todayObj->diff($dobObj)->y;
        }
    }

    if (in_array($clinicalType, $genderRestrictedDepartments, true) && $gender !== '' && $gender !== 'Female') {
        $errors[] = $clinicalType . ' registrations should be recorded as Female patients.';
    }

    if ($doctorId > 0) {
        $doctorCheck = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'doctor' LIMIT 1");
        $doctorCheck->bind_param('i', $doctorId);
        $doctorCheck->execute();
        if ($doctorCheck->get_result()->num_rows === 0) {
            $errors[] = 'Selected doctor was not found.';
        }
        $doctorCheck->close();
    }

    if ($temperature !== '' && !is_numeric($temperature)) {
        $errors[] = 'Temperature must be numeric.';
    }
    if ($weight !== '' && !is_numeric($weight)) {
        $errors[] = 'Weight must be numeric.';
    }
    if ($pulse !== '' && !ctype_digit($pulse)) {
        $errors[] = 'Pulse must be a whole number.';
    }
    if ($respiration !== '' && !ctype_digit($respiration)) {
        $errors[] = 'Respiration must be a whole number.';
    }

    if (!$errors) {
        $patientNumber = add_patient_generate_number($conn, $isWalkin);
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare(
                'INSERT INTO patients (patient_number, full_name, gender, phone, date_of_birth, address, age, next_of_kin_name, next_of_kin_phone, doctor_id, clinic_category, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->bind_param(
                'ssssssissis',
                $patientNumber,
                $fullName,
                $gender,
                $phone,
                $dob,
                $address,
                $age,
                $nextOfKinName,
                $nextOfKinPhone,
                $doctorId,
                $clinicalType
            );
            if (!$stmt->execute()) {
                throw new Exception($stmt->error ?: $conn->error);
            }
            $patientId = $stmt->insert_id;
            $stmt->close();

            $appointmentDate = date('Y-m-d');
            $appointmentTime = date('H:i:s');
            $reason = 'Clinical Service: ' . $clinicalType;
            $apptStmt = $conn->prepare(
                "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, created_at)
                 VALUES (?, ?, ?, ?, ?, 'Pending', NOW())"
            );
            $apptStmt->bind_param('iisss', $patientId, $doctorId, $appointmentDate, $appointmentTime, $reason);
            if (!$apptStmt->execute()) {
                throw new Exception($apptStmt->error ?: $conn->error);
            }
            $apptStmt->close();

            if ($temperature !== '' || $bp !== '' || $weight !== '' || $pulse !== '' || $respiration !== '') {
                $vitalsStmt = $conn->prepare(
                    'INSERT INTO vitals (patient_id, temperature, bp, weight, pulse, respiration, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())'
                );
                $vitalsStmt->bind_param('isssss', $patientId, $temperature, $bp, $weight, $pulse, $respiration);
                if (!$vitalsStmt->execute()) {
                    throw new Exception($vitalsStmt->error ?: $conn->error);
                }
                $vitalsStmt->close();
            }

            $conn->commit();
            header('Location: /hospital_system/patients/appointments.php?success=1&patient_id=' . $patientId);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $errors[] = 'Unable to complete registration right now. ' . $e->getMessage();
        }
    }
}

$doctors = $conn->query("SELECT id, full_name FROM users WHERE role='doctor' ORDER BY full_name ASC");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
* { box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
.main-container { padding: 30px 20px; min-height: calc(100vh - 60px); width: 100%; }
.page-wrapper { max-width: 1200px; margin: 0 auto; }
.form-card { background: #fff; border-radius: 14px; box-shadow: 0 18px 45px rgba(0, 0, 0, 0.18); overflow: hidden; }
.form-header { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); padding: 30px; color: white; }
.form-header h2 { margin-bottom: 8px; }
.form-body { padding: 36px; }
.alert { padding: 15px 18px; margin-bottom: 18px; border-radius: 8px; border-left: 4px solid; font-size: 14px; }
.alert-danger { background: #fef2f2; color: #991b1b; border-color: #ef4444; }
.alert-info { background: #eff6ff; color: #1d4ed8; border-color: #3b82f6; }
.form-section { margin-bottom: 35px; padding-bottom: 25px; border-bottom: 2px solid #f0f0f0; }
.section-title { font-size: 18px; font-weight: 700; color: #007bff; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; }
.section-title::before { content: ''; display: inline-block; width: 4px; height: 20px; background: #007bff; border-radius: 2px; margin-right: 12px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 15px; }
.label-required::after { content: ' *'; color: #dc2626; }
.form-control { width: 100%; padding: 14px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; transition: all 0.2s ease; }
.form-control:focus { outline: none; border-color: #2563eb; background-color: #f8fbff; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.button-group { display: flex; gap: 12px; flex-wrap: wrap; }
.btn { padding: 14px 28px; border: none; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 600; display: inline-block; text-decoration: none; text-align: center; }
.btn-primary { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; }
.btn-cancel { background: #6b7280; color: white; }
.mode-selector { background: #f0f4f8; padding: 20px; border-radius: 10px; margin-bottom: 30px; display: flex; gap: 40px; border: 1px solid #d1d9e6; flex-wrap: wrap; }
.mode-option { display: flex; align-items: center; cursor: pointer; font-weight: 700; font-size: 16px; color: #0056b3; }
.mode-option input { width: 18px; height: 18px; margin-right: 12px; }
.fee-badge { font-size: 12px; padding: 4px 10px; border-radius: 12px; margin-left: 10px; display: inline-block; }
.badge-waived { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.badge-standard { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
.maternity-alert { background: #fce7f3; color: #be185d; border: 1px solid #f9a8d4; font-size: 12px; padding: 7px 15px; border-radius: 20px; display: none; margin-top: 10px; font-weight: bold; }
.helper-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px; }
.helper-card { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
.helper-card small { display: block; text-transform: uppercase; color: #6b7280; font-weight: 700; margin-bottom: 6px; }
.helper-card strong { color: #111827; }
@media (max-width: 992px) { .form-row { grid-template-columns: 1fr; } .form-body { padding: 20px; } }
</style>

<div class="main-container">
    <div class="page-wrapper">
        <div class="form-card">
            <div class="form-header">
                <h2>Reception Desk</h2>
                <p>Register a new patient, create the queue/appointment record, and capture optional triage vitals in one controlled workflow.</p>
            </div>

            <div class="form-body">
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <strong>Please correct the following:</strong>
                        <ul style="margin: 10px 0 0 18px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="helper-grid">
                    <div class="helper-card">
                        <small>Registration modes</small>
                        <strong>Full registration</strong> captures demographics and next-of-kin, while <strong>walk-in</strong> supports fast treatment entry.
                    </div>
                    <div class="helper-card">
                        <small>What happens on submit</small>
                        Patient, appointment/queue record, and optional vitals are saved in a single database transaction.
                    </div>
                    <div class="helper-card">
                        <small>Patient number</small>
                        A unique patient number is auto-generated at save time based on registration mode and date.
                    </div>
                </div>

                <form method="post" id="registrationForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="mode-selector">
                        <label class="mode-option">
                            <input type="radio" name="registration_mode" value="full" <?= (($_POST['registration_mode'] ?? 'full') !== 'walkin') ? 'checked' : '' ?> onclick="toggleMode('full')">
                            Full Registration
                            <span id="fullFeeBadge" class="fee-badge badge-standard">Fee Required</span>
                        </label>
                        <label class="mode-option">
                            <input type="radio" name="registration_mode" value="walkin" <?= (($_POST['registration_mode'] ?? '') === 'walkin') ? 'checked' : '' ?> onclick="toggleMode('walkin')">
                            Walk-in Treatment
                            <span id="walkinFeeBadge" class="fee-badge badge-waived">Fee Waived</span>
                        </label>
                    </div>

                    <div class="alert alert-info">
                        <strong>Operational note:</strong> leave doctor assignment as <em>Unassigned</em> if the patient should go to the next available clinician.
                    </div>

                    <div class="form-section">
                        <div class="section-title">Patient Identification & Service Type</div>
                        <div class="form-row">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="label-required" for="full_name">Full Name</label>
                                <input id="full_name" name="full_name" class="form-control" placeholder="Patient Full Name" value="<?= add_patient_old('full_name') ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="label-required" for="clinicalTypeSelect">Clinical Department / Service</label>
                                <select name="clinical_type" id="clinicalTypeSelect" class="form-control" required onchange="checkMaternity(this.value)">
                                    <?php foreach (['Primary Services' => ['General' => 'General Consultation', 'Emergency' => 'Emergency / Trauma', 'OPD' => 'OPD (Outpatient Department)'], 'Maternal & Child Health' => ['ANC' => 'ANC (Antenatal Care)', 'PNC' => 'PNC (Postnatal Care)', 'Maternity' => 'Labor & Delivery', 'Immunization' => 'Immunization (KEPI)', 'Family Planning' => 'Family Planning'], 'Specialized Care' => ['SGBV' => 'SGBV Case Management', 'CCC' => 'CCC (Comprehensive Care Centre)', 'Nutrition' => 'Nutrition Program', 'Dental' => 'Dental Clinic', 'Physiotherapy' => 'Physiotherapy']] as $groupLabel => $options): ?>
                                        <optgroup label="<?= htmlspecialchars($groupLabel) ?>">
                                            <?php foreach ($options as $value => $label): ?>
                                                <option value="<?= htmlspecialchars($value) ?>" <?= (($_POST['clinical_type'] ?? 'General') === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <div id="maternityIndicator" class="maternity-alert">✨ Patient will appear in Maternity Module</div>
                            </div>
                            <div class="form-group" id="genderField">
                                <label for="genderSelect">Gender</label>
                                <select name="gender" class="form-control" id="genderSelect">
                                    <option value="" <?= add_patient_old('gender') === '' ? 'selected' : '' ?>>Select</option>
                                    <?php foreach (['Male', 'Female', 'Other'] as $genderOption): ?>
                                        <option value="<?= $genderOption ?>" <?= add_patient_old('gender') === $genderOption ? 'selected' : '' ?>><?= $genderOption ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row" id="idExtraFields">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input id="phone" name="phone" class="form-control" placeholder="07..." value="<?= add_patient_old('phone') ?>">
                            </div>
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob" class="form-control" value="<?= add_patient_old('dob') ?>" max="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>

                    <div id="vitalsSection" class="form-section" style="background-color: #fcfcfc; padding: 20px; border: 1px solid #eee; border-radius: 8px;">
                        <div class="section-title">Initial Vitals (Triage)</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="temperature">Temperature (°C)</label>
                                <input id="temperature" name="temperature" type="number" step="0.1" class="form-control" placeholder="36.5" value="<?= add_patient_old('temperature') ?>">
                            </div>
                            <div class="form-group">
                                <label for="bp">Blood Pressure (BP)</label>
                                <input id="bp" name="bp" class="form-control" placeholder="120/80" value="<?= add_patient_old('bp') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="weight">Weight (kg)</label>
                                <input id="weight" name="weight" type="number" step="0.1" class="form-control" placeholder="70" value="<?= add_patient_old('weight') ?>">
                            </div>
                            <div class="form-group">
                                <label for="pulse">Pulse (bpm)</label>
                                <input id="pulse" name="pulse" type="number" class="form-control" placeholder="72" value="<?= add_patient_old('pulse') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="respiration">Respiration (breaths/min)</label>
                                <input id="respiration" name="respiration" type="number" class="form-control" placeholder="16" value="<?= add_patient_old('respiration') ?>">
                            </div>
                        </div>
                    </div>

                    <div id="fullRegistrationFields">
                        <div class="form-section">
                            <div class="section-title">Next of Kin & Demographics</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label id="nokLabel" class="label-required" for="nokInput">NOK Name</label>
                                    <input name="next_of_kin_name" id="nokInput" class="form-control" value="<?= add_patient_old('next_of_kin_name') ?>" required placeholder="Full Name">
                                </div>
                                <div class="form-group">
                                    <label for="next_of_kin_phone">NOK Phone</label>
                                    <input id="next_of_kin_phone" name="next_of_kin_phone" class="form-control" placeholder="Contact Number" value="<?= add_patient_old('next_of_kin_phone') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="address">Residential Address</label>
                                <textarea name="address" id="address" class="form-control" rows="2" placeholder="Block/House/Street..."><?= add_patient_old('address') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section" id="appointmentSection">
                        <div class="section-title">Queue Assignment</div>
                        <div class="form-row">
                            <div class="form-group" style="grid-column: span 2;">
                                <label for="doctor_id">Assigning Doctor/Consultant</label>
                                <select name="doctor_id" id="doctor_id" class="form-control">
                                    <option value="0">Unassigned (First Available)</option>
                                    <?php if ($doctors): ?>
                                        <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                            <option value="<?= (int)$doctor['id'] ?>" <?= ((int)($_POST['doctor_id'] ?? 0) === (int)$doctor['id']) ? 'selected' : '' ?>><?= htmlspecialchars($doctor['full_name']) ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button class="btn btn-primary" type="submit">Complete Registration</button>
                        <a href="/hospital_system/patients/patient_list.php" class="btn btn-cancel">View Patient List</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function checkMaternity(val) {
    const matIndicator = document.getElementById('maternityIndicator');
    const genderSelect = document.getElementById('genderSelect');
    const maternityTypes = ['Maternity', 'ANC', 'PNC'];

    if (maternityTypes.includes(val)) {
        matIndicator.style.display = 'inline-block';
        if (genderSelect.value !== 'Female') {
            genderSelect.value = 'Female';
        }
    } else {
        matIndicator.style.display = 'none';
    }
}

function toggleMode(mode) {
    const nokSection = document.getElementById('fullRegistrationFields');
    const idExtraFields = document.getElementById('idExtraFields');
    const nokInput = document.getElementById('nokInput');
    const nokLabel = document.getElementById('nokLabel');
    const fullBadge = document.getElementById('fullFeeBadge');
    const walkinBadge = document.getElementById('walkinFeeBadge');

    if (mode === 'walkin') {
        nokSection.style.display = 'none';
        idExtraFields.style.display = 'none';
        nokInput.required = false;
        nokLabel.classList.remove('label-required');
        fullBadge.style.display = 'none';
        walkinBadge.style.display = 'inline-block';
    } else {
        nokSection.style.display = 'block';
        idExtraFields.style.display = 'grid';
        nokInput.required = true;
        nokLabel.classList.add('label-required');
        fullBadge.style.display = 'inline-block';
        walkinBadge.style.display = 'none';
    }
}

const selectedMode = document.querySelector('input[name="registration_mode"]:checked');
toggleMode(selectedMode ? selectedMode.value : 'full');
checkMaternity(document.getElementById('clinicalTypeSelect').value);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
