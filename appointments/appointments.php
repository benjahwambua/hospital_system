<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$error = '';
$success = false;

// 1. Fetch patients currently in Maternity for the dropdown
$maternity_patients = $conn->query("
    SELECT m.id as maternity_id, p.id as patient_id, p.full_name, m.anc_number 
    FROM maternity m 
    JOIN patients p ON m.patient_id = p.id 
    ORDER BY p.full_name ASC
");

// 2. Fetch Doctors (Assuming you have a 'users' or 'doctors' table)
$doctors = $conn->query("SELECT id, full_name FROM users WHERE role = 'Doctor' OR role = 'Admin' ORDER BY full_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_id    = intval($_POST['patient_id']);
    $doc_id  = intval($_POST['doctor_id']);
    $date    = $conn->real_escape_string($_POST['appointment_date']);
    $time    = $conn->real_escape_string($_POST['appointment_time']);
    $reason  = $conn->real_escape_string($_POST['reason']);
    $status  = 'Scheduled'; // Default status

    // Insert into your existing appointments table
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iissss", $p_id, $doc_id, $date, $time, $reason, $status);

    if ($stmt->execute()) {
        header("Location: dashboard.php?appt_success=1");
        exit;
    } else {
        $error = "Database Error: " . $conn->error;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="content-wrapper" style="padding: 30px; background: #f4f7f6; min-height: 100vh;">
    <div style="max-width: 900px; margin: auto;">
        
        <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
            <div style="border-bottom: 2px solid #f0f2f5; margin-bottom: 25px; padding-bottom: 10px;">
                <h2 style="color: #2c3e50; margin: 0;">📅 Schedule Maternity Follow-up</h2>
                <p style="color: #7f8c8d; font-size: 14px;">Link a patient to a doctor for ANC or Postnatal visits.</p>
            </div>

            <?php if($error): ?>
                <div style="background: #fff5f5; color: #c53030; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #c53030;">
                    <strong>Error:</strong> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    
                    <div class="form-group">
                        <label style="display: block; font-weight: 700; font-size: 12px; color: #5a67d8; text-transform: uppercase; margin-bottom: 8px;">Maternity Patient</label>
                        <select name="patient_id" style="width: 100%; padding: 12px; border: 1px solid #d1d9e0; border-radius: 8px;" required>
                            <option value="">-- Select Patient --</option>
                            <?php while($p = $maternity_patients->fetch_assoc()): ?>
                                <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['full_name']) ?> (ANC: <?= $p['anc_number'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-weight: 700; font-size: 12px; color: #5a67d8; text-transform: uppercase; margin-bottom: 8px;">Assign Doctor</label>
                        <select name="doctor_id" style="width: 100%; padding: 12px; border: 1px solid #d1d9e0; border-radius: 8px;" required>
                            <option value="">-- Select Doctor --</option>
                            <?php while($d = $doctors->fetch_assoc()): ?>
                                <option value="<?= $d['id'] ?>">Dr. <?= htmlspecialchars($d['full_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-weight: 700; font-size: 12px; color: #5a67d8; text-transform: uppercase; margin-bottom: 8px;">Date</label>
                        <input type="date" name="appointment_date" style="width: 100%; padding: 12px; border: 1px solid #d1d9e0; border-radius: 8px;" required min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-weight: 700; font-size: 12px; color: #5a67d8; text-transform: uppercase; margin-bottom: 8px;">Time</label>
                        <input type="time" name="appointment_time" style="width: 100%; padding: 12px; border: 1px solid #d1d9e0; border-radius: 8px;" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label style="display: block; font-weight: 700; font-size: 12px; color: #5a67d8; text-transform: uppercase; margin-bottom: 8px;">Reason for Appointment</label>
                    <textarea name="reason" rows="3" style="width: 100%; padding: 12px; border: 1px solid #d1d9e0; border-radius: 8px;" placeholder="e.g., Routine ANC checkup, Ultrasound, Postnatal Review..."></textarea>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 10px;">
                    <button type="submit" style="flex: 2; background: #27ae60; color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                        CREATE APPOINTMENT
                    </button>
                    <a href="dashboard.php" style="flex: 1; text-align: center; background: #95a5a6; color: white; padding: 15px; border-radius: 8px; text-decoration: none; font-weight: 700;">CANCEL</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>