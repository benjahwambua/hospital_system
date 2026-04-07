<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_login();


include "../includes/header.php";
include "../includes/sidebar.php";

$patients = $conn->query("SELECT id, fullname FROM patients ORDER BY fullname");
$doctors  = $conn->query("SELECT id, fullname FROM doctors ORDER BY fullname");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient = $_POST['patient_id'];
    $doctor  = $_POST['doctor_id'];
    $date    = $_POST['appointment_date'];
    $reason  = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason) VALUES (?,?,?,?)");
    $stmt->bind_param("iiss", $patient, $doctor, $date, $reason);
    $stmt->execute();

    audit("New appointment created");
    header("Location: view_appointments.php");
    exit;
}
?>
<div class="content-area">
<h2><i class="fa fa-calendar-plus"></i> Add Appointment</h2>

<form method="POST" class="form-card">
    <label>Patient</label>
    <select name="patient_id" required>
        <?php while($p = $patients->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>"><?= $p['fullname'] ?></option>
        <?php endwhile; ?>
    </select>

    <label>Doctor</label>
    <select name="doctor_id" required>
        <?php while($d = $doctors->fetch_assoc()): ?>
            <option value="<?= $d['id'] ?>"><?= $d['fullname'] ?></option>
        <?php endwhile; ?>
    </select>

    <label>Date & Time</label>
    <input type="datetime-local" name="appointment_date" required>

    <label>Reason</label>
    <input type="text" name="reason">

    <button class="btn">Save Appointment</button>
</form>

</div>
<?php include "../includes/footer.php"; ?>
