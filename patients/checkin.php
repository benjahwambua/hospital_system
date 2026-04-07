<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $doctor_id = intval($_POST['doctor_id']);
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];
    $reason = $conn->real_escape_string($_POST['reason']);
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?,?,?,?,?,'waiting')");
    $stmt->bind_param("iisss",$patient_id,$doctor_id,$date,$time,$reason);
    $stmt->execute();
    $stmt->close();
    header("Location: /hospital_system/queue/index.php");
    exit;
}

$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name ASC");
$doctors = $conn->query("SELECT id, full_name FROM users WHERE role='doctor' ORDER BY full_name ASC");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main">
  <div class="page-title">Check-in / New Appointment</div>
  <div class="card" style="max-width:700px;">
    <form method="post">
      <label>Patient</label><select name="patient_id" class="form-control"><?php while($p=$patients->fetch_assoc()): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option><?php endwhile; ?></select>
      <label>Doctor</label><select name="doctor_id" class="form-control"><?php while($d=$doctors->fetch_assoc()): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option><?php endwhile; ?></select>
      <label>Date</label><input type="date" name="appointment_date" class="form-control" required>
      <label>Time</label><input type="time" name="appointment_time" class="form-control" required>
      <label>Reason</label><input name="reason" class="form-control">
      <div style="margin-top:8px;"><button class="btn" type="submit">Check In / Book</button></div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
