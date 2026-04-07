<?php
// add_patient.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape Patient Data
    $full_name = $conn->real_escape_string(trim($_POST['full_name'] ?? ''));
    $gender = $conn->real_escape_string(trim($_POST['gender'] ?? ''));
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $dob = $_POST['dob'] ?: null;
    $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));
    $next_of_kin_name = $conn->real_escape_string(trim($_POST['next_of_kin_name'] ?? ''));
    $next_of_kin_phone = $conn->real_escape_string(trim($_POST['next_of_kin_phone'] ?? ''));
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $appointment_date = $_POST['appointment_date'] ?: null;

    // Vitals Data
    $temp = $_POST['temperature'] ?? '';
    $bp = $_POST['blood_pressure'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $height = $_POST['height'] ?? '';

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

        // Start Transaction to ensure both patient and vitals are saved
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("INSERT INTO patients (patient_number, full_name, gender, phone, date_of_birth, address, age, next_of_kin_name, next_of_kin_phone, doctor_id, appointment_date, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->bind_param("ssssssiissi", $patient_number, $full_name, $gender, $phone, $dob, $address, $age, $next_of_kin_name, $next_of_kin_phone, $doctor_id, $appointment_date);
            
            if ($stmt->execute()) {
                $pid = $stmt->insert_id;
                
                // If any vital info is provided, save it to the vitals table
                if ($temp != '' || $bp != '' || $weight != '' || $height != '') {
                    $v_stmt = $conn->prepare("INSERT INTO vitals (patient_id, temperature, blood_pressure, weight, height, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $v_stmt->bind_param("issss", $pid, $temp, $bp, $weight, $height);
                    $v_stmt->execute();
                    $v_stmt->close();
                }

                $conn->commit(); // Save changes
                audit('patient_register', "patient_id={$pid}");
                header("Location: /hospital_system/patients/patient_dashboard.php?id={$pid}");
                exit;
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback(); // Undo changes if something fails
            $err = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all doctors
$doctors = $conn->query("SELECT id, full_name FROM users WHERE role='doctor' ORDER BY full_name");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
/* ... Rest of your existing CSS remains exactly the same ... */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
.main-container { padding: 30px 20px; min-height: calc(100vh - 60px); }
.page-wrapper { max-width: 900px; margin: 0 auto; }
.page-header { margin-bottom: 30px; }
.page-header h1 { font-size: 32px; color: #fff; margin-bottom: 10px; }
.page-header p { color: rgba(255, 255, 255, 0.8); font-size: 14px; }
.form-card { background: #fff; border-radius: 10px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); overflow: hidden; }
.form-header { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); padding: 25px; color: white; }
.form-header h2 { font-size: 24px; margin: 0; }
.form-body { padding: 30px; }
.alert { padding: 15px 20px; margin-bottom: 20px; border-radius: 6px; border-left: 4px solid; font-size: 14px; }
.alert-danger { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
.alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
.form-section { margin-bottom: 35px; padding-bottom: 25px; border-bottom: 2px solid #f0f0f0; }
.form-section:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.section-title { font-size: 16px; font-weight: 700; color: #007bff; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; display: flex; align-items: center; }
.section-title::before { content: ''; display: inline-block; width: 4px; height: 20px; background: #007bff; border-radius: 2px; margin-right: 12px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
.label-required::after { content: " *"; color: #f44336; font-weight: bold; }
.form-control { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; transition: all 0.3s ease; font-family: inherit; }
.form-control:focus { outline: none; border-color: #007bff; background-color: #f8f9ff; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.btn { padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; display: inline-block; text-decoration: none; text-align: center; min-width: 150px; }
.btn-primary { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3); }
.btn-cancel { background: #6c757d; color: white; }
.helper-text { font-size: 12px; color: #999; margin-top: 5px; }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>

<div class="main-container">
    <div class="page-wrapper">
        <div class="page-header">
            <h1>Patient Registration</h1>
            <p>Add a new patient and record initial vitals</p>
        </div>

        <div class="form-card">
            <div class="form-header">
                <h2>Register New Patient</h2>
            </div>

            <div class="form-body">
                <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err); ?></div><?php endif; ?>

                <form method="post">
                    
                    <div class="form-section">
                        <div class="section-title">Patient Information</div>
                        <div class="form-group">
                            <label class="label-required">Full Name</label>
                            <input name="full_name" class="form-control" placeholder="Enter patient's full name" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
                                    <option value="">Select gender</option>
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
                                <label>Phone Number</label>
                                <input name="phone" class="form-control" type="tel" placeholder="+254712345678">
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <input name="address" class="form-control" placeholder="Street address">
                            </div>
                        </div>
                    </div>

                    <div class="form-section" style="background-color: #f9f9f9; padding: 20px; border-radius: 8px;">
                        <div class="section-title">Patient Vitals</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Temperature (°C)</label>
                                <input name="temperature" type="number" step="0.1" class="form-control" placeholder="e.g. 36.5">
                            </div>
                            <div class="form-group">
                                <label>Blood Pressure</label>
                                <input name="blood_pressure" class="form-control" placeholder="e.g. 120/80">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Weight (kg)</label>
                                <input name="weight" type="number" step="0.1" class="form-control" placeholder="e.g. 70">
                            </div>
                            <div class="form-group">
                                <label>Height (cm)</label>
                                <input name="height" type="number" step="0.1" class="form-control" placeholder="e.g. 175">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Next of Kin Information</div>
                        <div class="form-group">
                            <label class="label-required">Next of Kin Name</label>
                            <input name="next_of_kin_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Next of Kin Phone</label>
                            <input name="next_of_kin_phone" class="form-control" type="tel">
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Appointment Details</div>
                        <div class="form-group">
                            <label>Assign Doctor</label>
                            <select name="doctor_id" class="form-control">
                                <option value="0">Select a doctor (optional)</option>
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

                    <div class="button-group" style="display: flex; gap: 15px; margin-top: 30px;">
                        <button class="btn btn-primary" type="submit">Register Patient</button>
                        <a href="/hospital_system/patients/patient_list.php" class="btn btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>