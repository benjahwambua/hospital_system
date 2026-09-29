<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_once __DIR__ . '/../includes/permissions.php';
require_module_access($conn, 'front_desk', 'create');

// Ensure walk-in registrations work even if the migration has not yet been run.
ensure_walkin_column($conn);

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
    $clinicalType = trim((string)($_POST['clinic_category'] ?? ($_GET['clinic_category'] ?? 'General')));

    // Clinical observations are captured once, authoritatively in Clinical Care → Triage & Vitals.
    // Reception only handles demographics, registration and visit/queue creation.

    // Walk-in: Only clinical type required, name & phone optional
    if ($isWalkin) {
        if (!in_array($clinicalType, $allowedClinicalTypes, true)) {
            $errors[] = 'Invalid clinical department selected.';
        }
        // If no name provided, generate anonymous ID
        if ($fullName === '') {
            $fullName = 'Walk-in Patient ' . date('Hi');
        }
    } else {
        // Full registration: require all patient information
        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }
        if (!in_array($gender, $allowedGenders, true)) {
            $errors[] = 'Invalid gender selected.';
        }
        if (!in_array($clinicalType, $allowedClinicalTypes, true)) {
            $errors[] = 'Invalid clinical department selected.';
        }
        if ($nextOfKinName === '') {
            $errors[] = 'Next of kin name is required for full registration.';
        }
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
        if ($doctorCheck) {
            $doctorCheck->bind_param('i', $doctorId);
            $doctorCheck->execute();
            if ($doctorCheck->get_result()->num_rows === 0) {
                $errors[] = 'Selected doctor was not found.';
            }
            $doctorCheck->close();
        }
    }

    if (!$errors) {
        // Walk-in treatment is never a maternity registration. Maternity/ANC/PNC
        // patients must go through full registration so a proper maternity record exists.
        if ($isWalkin && in_array($clinicalType, $genderRestrictedDepartments, true)) {
            $errors[] = 'Walk-in treatment cannot be registered as Maternity, ANC or PNC. Please use Full Registration for maternity care.';
        }
    }

    if (!$errors) {
        $conn->begin_transaction();

        try {
            // Walk-in registrations create their own patient record. Walk-ins are exempt
            // from the KES 200 consultation charge, but they still need a patient record so
            // their visit, services, laboratory work and payments can be tracked separately.
            if ($isWalkin) {
                $patientNumber = 'TEMP-WLK-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $walkinName = $fullName !== '' ? $fullName : 'Walk-in Patient';
                $walkinGender = $gender !== '' ? $gender : '';
                $walkinFlag = 1;

                $stmt = $conn->prepare(
                    'INSERT INTO patients (patient_number, full_name, gender, phone, date_of_birth, address, age, next_of_kin_name, next_of_kin_phone, doctor_id, clinic_category, is_walkin, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                );
                if (!$stmt) {
                    throw new Exception("Walk-in patient prepare failed: " . $conn->error);
                }
                $stmt->bind_param(
                    'ssssssissisi',
                    $patientNumber, $walkinName, $walkinGender, $phone, $dob, $address, $age,
                    $nextOfKinName, $nextOfKinPhone, $doctorId, $clinicalType, $walkinFlag
                );
                if (!$stmt->execute()) {
                    throw new Exception("Walk-in patient creation failed: " . $stmt->error);
                }
                $patientId = $stmt->insert_id;
                $stmt->close();

                // Walk-in patient numbers use the short WLK format, e.g. WLK0011.
                $patientNumber = 'WLK' . str_pad((string)$patientId, 4, '0', STR_PAD_LEFT);
                $numberStmt = $conn->prepare('UPDATE patients SET patient_number = ? WHERE id = ?');
                if (!$numberStmt) throw new Exception("Walk-in patient number update failed: " . $conn->error);
                $numberStmt->bind_param('si', $patientNumber, $patientId);
                if (!$numberStmt->execute()) throw new Exception("Walk-in patient number update failed: " . $numberStmt->error);
                $numberStmt->close();
            } else {
                // Full registration creates a new patient record.
                $patientNumber = 'TEMP-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));

                $stmt = $conn->prepare(
                    'INSERT INTO patients (patient_number, full_name, gender, phone, date_of_birth, address, age, next_of_kin_name, next_of_kin_phone, doctor_id, clinic_category, is_walkin, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                );

                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $walkinFlag = 0;
                $stmt->bind_param(
                    'ssssssissisi',
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
                    $clinicalType,
                    $walkinFlag
                );

                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }

                $patientId = $stmt->insert_id;
                $stmt->close();

                // Registered patient numbers use the short EMC format, e.g. EMC0011.
                $patientNumber = 'EMC' . str_pad((string)$patientId, 4, '0', STR_PAD_LEFT);
                $numberStmt = $conn->prepare('UPDATE patients SET patient_number = ? WHERE id = ?');
                if (!$numberStmt) {
                    throw new Exception("Patient number update failed: " . $conn->error);
                }
                $numberStmt->bind_param('si', $patientNumber, $patientId);
                if (!$numberStmt->execute()) {
                    throw new Exception("Patient number update failed: " . $numberStmt->error);
                }
                $numberStmt->close();
            }

            // Create one visit/encounter container for this registration.
            // The migration adds the visits table and visit_id links to appointments,
            // vitals, patient_services and invoices. If the migration is not yet run,
            // registration continues using the existing workflow.
            $visitId = 0;
            $visitTableCheck = $conn->query("SHOW TABLES LIKE 'visits'");
            if ($visitTableCheck && $visitTableCheck->num_rows > 0) {
                $visitNumber = 'V-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $visitType = $isWalkin ? 'Walk-in' : 'Outpatient';
                $createdBy = (int)($_SESSION['user_id'] ?? 0);

                $visitStmt = $conn->prepare(
                    "INSERT INTO visits
                        (visit_number, patient_id, visit_date, visit_time, visit_type, clinic_category, doctor_id, status, created_by, created_at)
                     VALUES (?, ?, CURDATE(), CURTIME(), ?, ?, NULLIF(?, 0), 'Open', NULLIF(?, 0), NOW())"
                );
                if (!$visitStmt) {
                    throw new Exception("Visit prepare failed: " . $conn->error);
                }

                $visitStmt->bind_param(
                    'sissii',
                    $visitNumber,
                    $patientId,
                    $visitType,
                    $clinicalType,
                    $doctorId,
                    $createdBy
                );

                if (!$visitStmt->execute()) {
                    throw new Exception("Visit creation failed: " . $visitStmt->error);
                }

                $visitId = (int)$visitStmt->insert_id;
                $visitStmt->close();
            }

            // Reception creates exactly one Visit. Appointments are kept as a
            // separate scheduling workflow and are not duplicated into the clinical queue.
            // Vitals are recorded by clinical staff in the Triage & Vitals workflow.
            // This prevents reception and triage from creating competing clinical records.

            // Create maternity record if applicable
            if (in_array($clinicalType, ['Maternity', 'ANC', 'PNC'], true)) {
                $checkMaternity = $conn->prepare('SELECT id FROM maternity WHERE patient_id = ? LIMIT 1');
                
                if ($checkMaternity) {
                    $checkMaternity->bind_param('i', $patientId);
                    $checkMaternity->execute();
                    $existingMaternity = $checkMaternity->get_result()->fetch_assoc();
                    $checkMaternity->close();

                    if (!$existingMaternity) {
                        $ancNumber = 'ANC-' . date('Y') . '-' . str_pad($patientId, 4, '0', STR_PAD_LEFT);
                        $maternityStmt = $conn->prepare(
                            'INSERT INTO maternity (patient_id, anc_number, created_at)
                             VALUES (?, ?, NOW())'
                        );
                        
                        if ($maternityStmt) {
                            $maternityStmt->bind_param('is', $patientId, $ancNumber);
                            if (!$maternityStmt->execute()) {
                                throw new Exception("Maternity insert failed: " . $maternityStmt->error);
                            }
                            $maternityStmt->close();
                        }
                    }
                }

                $encounterType = 'maternity';
            } else {
                $encounterType = 'general';
            }

            // Create encounter record
            $encounterStmt = $conn->prepare("INSERT INTO encounters (patient_id, type, status, presenting_complaint, created_at) VALUES (?, ?, 'open', ?, NOW())");
            
            if (!$encounterStmt) {
                throw new Exception("Encounter prepare failed: " . $conn->error);
            }
            
            $complaint = $isWalkin ? 'Walk-in registration' : 'Registered via reception';
            $encounterStmt->bind_param('iss', $patientId, $encounterType, $complaint);
            
            if (!$encounterStmt->execute()) {
                throw new Exception("Encounter execute failed: " . $encounterStmt->error);
            }
            
            $encounterStmt->close();

            // Registered patients always receive the fixed KES 200 consultation
            // charge. Walk-in registrations are explicitly exempt.
            if (!$isWalkin) {
                ensure_registered_consultation_charge($conn, $patientId, 200.00);
            }

            $conn->commit();
            header('Location: /hospital_system/patients/patient_list.php?registered=1&patient_id=' . $patientId);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $errors[] = 'Unable to complete registration: ' . $e->getMessage();
        }
    }
}

$doctors = $conn->query("SELECT id, full_name FROM users WHERE role='doctor' ORDER BY full_name ASC");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.reception-page{padding:28px 24px 44px;background:#f5f7fb;min-height:calc(100vh - 72px)}
.reception-shell{width:100%;margin:0}.reception-hero{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:18px;padding:26px 28px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 10px 28px rgba(6,59,115,.16)}.reception-hero h1{font-size:27px;margin:4px 0}.reception-hero p{margin:0;color:#d9edff;font-size:13px}.reception-eyebrow{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:800;color:#9edcff}.reception-hero-icon{width:54px;height:54px;border-radius:15px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;font-size:22px}
.registration-card{background:#fff;border:1px solid #e4eaf1;border-radius:16px;box-shadow:0 5px 20px rgba(31,45,61,.06);overflow:hidden}.registration-body{padding:30px 34px}.mode-selector{background:#f7f9fc;padding:7px;border:1px solid #e3e8ef;border-radius:12px;margin-bottom:26px;display:flex;gap:8px;flex-wrap:wrap}.mode-option{flex:1;min-width:230px;display:flex;align-items:center;cursor:pointer;font-weight:700;font-size:14px;color:#344054;padding:13px 15px;border-radius:9px}.mode-option:hover{background:#fff}.mode-option input{width:18px;height:18px;margin-right:10px;accent-color:#075b9d}.fee-badge{font-size:10px;padding:4px 9px;border-radius:20px;margin-left:auto;text-transform:uppercase;font-weight:800}.badge-waived{background:#e7f8ef;color:#087443;border:1px solid #c9efd9}.badge-standard{background:#fff4d6;color:#8a6200;border:1px solid #f3df9b}
.form-section{margin-bottom:26px;padding-bottom:24px;border-bottom:1px solid #edf0f5}.section-title{font-size:13px;font-weight:800;color:#344054;text-transform:uppercase;letter-spacing:.7px;margin-bottom:18px;display:flex;align-items:center}.section-title::before{content:'';display:inline-block;width:4px;height:18px;background:#075b9d;border-radius:4px;margin-right:10px}.form-group{margin-bottom:16px}.form-group label{display:block;margin-bottom:7px;font-weight:700;color:#475467;font-size:12px}.label-required::after{content:' *';color:#d92d20}.label-optional::after{content:' (optional)';color:#98a2b3;font-weight:400}.form-control{width:100%;padding:11px 13px;border:1px solid #d7dee8;border-radius:9px;font-size:14px;color:#344054;background:#fff;transition:.2s}.form-control:focus{outline:none;border-color:#2782c4;background:#fbfdff;box-shadow:0 0 0 3px rgba(7,91,157,.08)}textarea.form-control{resize:vertical}.button-group{display:flex;gap:10px;flex-wrap:wrap;padding-top:2px}.btn{padding:11px 18px;border:0;border-radius:9px;cursor:pointer;font-size:13px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}.btn-primary{background:#075b9d;color:#fff}.btn-primary:hover{background:#064b82;color:#fff}.btn-cancel{background:#eef2f6;color:#344054}.btn-cancel:hover{background:#e2e8f0;color:#1d2939}.alert{padding:13px 16px;margin-bottom:18px;border-radius:10px;font-size:13px}.alert-danger{background:#fff1f0;color:#b42318;border:1px solid #fecdca}.walkin-info-alert{background:#eef8ff;color:#155e8a;border:1px solid #cce8f7;border-left:4px solid #2782c4;padding:11px 14px;border-radius:9px;margin-bottom:20px;font-size:12px}.maternity-alert{background:#fff0f6;color:#b4236a;border:1px solid #f7c6dc;font-size:11px;padding:6px 11px;border-radius:20px;display:none;margin-top:8px;font-weight:700}
@media(max-width:900px){.reception-page{padding:18px 12px 32px}.registration-body{padding:22px 20px}.form-row{grid-template-columns:1fr!important}.reception-hero{padding:22px;align-items:flex-start}.reception-hero-icon{display:none}}
</style>

<div class="reception-page"><div class="reception-shell">
        <div class="reception-hero"><div><div class="reception-eyebrow">Front Desk</div><h1>Patient Registration</h1><p>Register a patient and assign the appropriate service.</p></div><div class="reception-hero-icon"><i class="fas fa-user-plus"></i></div></div>
        <div class="registration-card"><div class="registration-body">
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

                    <div id="walkinInfoBox" class="walkin-info-alert" style="display: none;">
                        <strong>⚡ Fast Track Walk-in:</strong> Only service type required. Name & phone optional. No consultation fee. No vitals needed.
                    </div>

                    <div class="form-section">
                        <div class="section-title">Patient Identification & Service Type</div>
                        
                        <div class="form-row">
                            <div class="form-group" style="grid-column: span 2;">
                                <label id="nameLabel" class="label-required" for="full_name">Full Name</label>
                                <input id="full_name" name="full_name" class="form-control" placeholder="Patient Full Name" value="<?= add_patient_old('full_name') ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="label-required" for="clinicalTypeSelect">Clinical Department / Service</label>
                                <select name="clinic_category" id="clinicalTypeSelect" class="form-control" required onchange="checkMaternity(this.value)">
                                    <?php foreach (['Primary Services' => ['General' => 'General Consultation', 'Emergency' => 'Emergency / Trauma', 'OPD' => 'OPD (Outpatient Department)'], 'Maternity Services' => ['Maternity' => 'Maternity Unit', 'ANC' => 'Antenatal Care (ANC)', 'PNC' => 'Postnatal Care (PNC)'], 'Specialty Services' => ['Immunization' => 'Immunization', 'Family Planning' => 'Family Planning', 'SGBV' => 'SGBV (Gender-Based Violence)', 'CCC' => 'Comprehensive Care Clinic (CCC)', 'Nutrition' => 'Nutrition Services', 'Dental' => 'Dental Services', 'Physiotherapy' => 'Physiotherapy / Rehabilitation']] as $groupLabel => $options): ?>
                                        <optgroup label="<?= htmlspecialchars($groupLabel) ?>">
                                            <?php foreach ($options as $value => $label): ?>
                                                <option value="<?= htmlspecialchars($value) ?>" data-maternity="<?= in_array($value, ['Maternity','ANC','PNC'], true) ? '1' : '0' ?>" <?= (($_POST['clinic_category'] ?? 'General') === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <div id="maternityIndicator" class="maternity-alert">✨ Patient will appear in Maternity Module</div>
                            </div>
                            <div class="form-group" id="genderField" style="display: grid;">
                                <label for="genderSelect">Gender</label>
                                <select name="gender" class="form-control" id="genderSelect">
                                    <option value="" <?= add_patient_old('gender') === '' ? 'selected' : '' ?>>Select</option>
                                    <?php foreach (['Male', 'Female', 'Other'] as $genderOption): ?>
                                        <option value="<?= $genderOption ?>" <?= add_patient_old('gender') === $genderOption ? 'selected' : '' ?>><?= $genderOption ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row" id="idExtraFields" style="display: grid;">
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



                    <div id="fullRegistrationFields">
                        <div class="form-section">
                            <div class="section-title">Next of Kin & Demographics</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label id="nokLabel" class="label-required" for="nokInput">NOK Name</label>
                                    <input name="next_of_kin_name" id="nokInput" class="form-control" value="<?= add_patient_old('next_of_kin_name') ?>" placeholder="Full Name">
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
    </div></div>

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
    const clinicalTypeSelect = document.getElementById('clinicalTypeSelect');
    if (clinicalTypeSelect) {
        Array.from(clinicalTypeSelect.options).forEach(function(option) {
            const isMaternity = option.getAttribute('data-maternity') === '1';
            option.disabled = mode === 'walkin' && isMaternity;
        });
        if (mode === 'walkin' && ['Maternity','ANC','PNC'].includes(clinicalTypeSelect.value)) {
            clinicalTypeSelect.value = 'General';
        }
        checkMaternity(clinicalTypeSelect.value);
    }

    const nokSection = document.getElementById('fullRegistrationFields');
    const idExtraFields = document.getElementById('idExtraFields');
    const genderField = document.getElementById('genderField');
    const nokInput = document.getElementById('nokInput');
    const nokLabel = document.getElementById('nokLabel');
    const nameLabel = document.getElementById('nameLabel');
    const nameField = document.getElementById('full_name');
    const fullBadge = document.getElementById('fullFeeBadge');
    const walkinBadge = document.getElementById('walkinFeeBadge');
    const walkinInfoBox = document.getElementById('walkinInfoBox');

    if (mode === 'walkin') {
        // Walk-in: Hide all extra fields, make name optional, hide vitals
        nokSection.style.display = 'none';
        idExtraFields.style.display = 'none';
        genderField.style.display = 'none';
        nokInput.required = false;
        nokLabel.classList.remove('label-required');
        
        // Make name optional for walk-in
        nameField.required = false;
        nameLabel.classList.remove('label-required');
        nameLabel.classList.add('label-optional');
        
        fullBadge.style.display = 'none';
        walkinBadge.style.display = 'inline-block';
        walkinInfoBox.style.display = 'block';
    } else {
        // Full registration: Show the complete demographic and next-of-kin fields
        nokSection.style.display = 'block';
        idExtraFields.style.display = 'grid';
        genderField.style.display = 'grid';
        nokInput.required = true;
        nokLabel.classList.add('label-required');
        
        // Make name required for full registration
        nameField.required = true;
        nameLabel.classList.add('label-required');
        nameLabel.classList.remove('label-optional');
        
        fullBadge.style.display = 'inline-block';
        walkinBadge.style.display = 'none';
        walkinInfoBox.style.display = 'none';
    }
}

const selectedMode = document.querySelector('input[name="registration_mode"]:checked');
toggleMode(selectedMode ? selectedMode.value : 'full');
checkMaternity(document.getElementById('clinicalTypeSelect').value);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
