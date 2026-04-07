<?php
// add_patient.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $conn->real_escape_string(trim($_POST['full_name'] ?? ''));
    $gender = $conn->real_escape_string(trim($_POST['gender'] ?? ''));
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $dob = $_POST['dob'] ?: null;
    $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));
    $next_of_kin_name = $conn->real_escape_string(trim($_POST['next_of_kin_name'] ?? ''));
    $next_of_kin_phone = $conn->real_escape_string(trim($_POST['next_of_kin_phone'] ?? ''));
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $appointment_date = $_POST['appointment_date'] ?: null;

    if ($full_name === '') {
        $err = "Full name is required.";
    } elseif ($next_of_kin_name === '') {
        $err = "Next of kin name is required.";
    } else {
        // generate patient number
        $patient_number = 'EMC-' . date('Ymd') . '-' . substr((string)time(), -4) . rand(10,99);

        // Calculate age from DOB
        $age = 0;
        if ($dob) {
            $dob_obj = new DateTime($dob);
            $now = new DateTime();
            $age = $now->diff($dob_obj)->y;
        }

        $stmt = $conn->prepare("INSERT INTO patients (patient_number, full_name, gender, phone, dob, address, age, next_of_kin_name, next_of_kin_phone, doctor_id, appointment_date, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
        $stmt->bind_param("ssssssiissi", $patient_number, $full_name, $gender, $phone, $dob, $address, $age, $next_of_kin_name, $next_of_kin_phone, $doctor_id, $appointment_date);
        
        if ($stmt->execute()) {
            $pid = $stmt->insert_id;
            $stmt->close();
            audit('patient_register', "patient_id={$pid}");
            $success = "Patient registered successfully.";
            header("Location: /hospital_system/patients/patient_dashboard.php?id={$pid}");
            exit;
        } else {
            $err = "Error: " . $conn->error;
        }
    }
}

// Fetch all doctors
$doctors = $conn->query("SELECT id, full_name FROM users WHERE role='doctor' ORDER BY full_name");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
.card {
    max-width: 700px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}
.form-group {
    margin-bottom: 15px;
}
label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}
.label-required::after {
    content: " *";
    color: #f44336;
    font-weight: bold;
}
.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}
.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0,123,255,0.3);
}
.btn {
    padding: 10px 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}
.btn:hover {
    background: #0056b3;
}
.btn-cancel {
    background: #6c757d;
    text-decoration: none;
    display: inline-block;
    margin-left: 10px;
}
.btn-cancel:hover {
    background: #5a6268;
}
.alert {
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 4px;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
.form-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}
.form-section:last-child {
    border-bottom: none;
}
.section-title {
    font-size: 14px;
    font-weight: 700;
    color: #007bff;
    text-transform: uppercase;
    margin-bottom: 15px;
}
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .card {
        margin: 10px;
    }
    .btn-cancel {
        margin-left: 0;
        margin-top: 10px;
        width: 100%;
        box-sizing: border-box;
    }
}
</style>

<div class="card">
    <h3>Register New Patient</h3>

    <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success); ?></div><?php endif; ?>

    <form method="post">
        
        <!-- Patient Information Section -->
        <div class="form-section">
            <div class="section-title">Patient Information</div>
            
            <div class="form-group">
                <label class="label-required">Full Name</label>
                <input name="full_name" class="form-control" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="">--select--</option>
                        <option>Male</option>
                        <option>Female</option>
                        <option>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Phone</label>
                    <input name="phone" class="form-control" type="tel">
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input name="address" class="form-control">
                </div>
            </div>
        </div>

        <!-- Next of Kin Section -->
        <div class="form-section">
            <div class="section-title">Next of Kin</div>
            
            <div class="form-group">
                <label class="label-required">Next of Kin Name</label>
                <input name="next_of_kin_name" class="form-control" placeholder="Full name of next of kin" required>
            </div>

            <div class="form-group">
                <label>Next of Kin Phone</label>
                <input name="next_of_kin_phone" class="form-control" type="tel" placeholder="Contact number">
            </div>
        </div>

        <!-- Appointment Section -->
        <div class="form-section">
            <div class="section-title">Appointment</div>
            
            <div class="form-group">
                <label>Assign Doctor</label>
                <select name="doctor_id" class="form-control">
                    <option value="0">--select doctor--</option>
                    <?php while($d = $doctors->fetch_assoc()): ?>
                    <option value="<?= $d['id']; ?>"><?= htmlspecialchars($d['full_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Appointment Date & Time</label>
                <input type="datetime-local" name="appointment_date" class="form-control">
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button class="btn" type="submit">Register Patient</button>
            <a href="/hospital_system/patients/patient_list.php" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>