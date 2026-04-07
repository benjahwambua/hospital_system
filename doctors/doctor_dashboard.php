<?php
// doctors/doctor_dashboard.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// show today's appointments for logged-in doctor
$doc = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT a.*, p.full_name FROM appointments a LEFT JOIN patients p ON p.id=a.patient_id WHERE a.doctor_id=? AND a.appointment_date=CURDATE() ORDER BY a.appointment_time");
$stmt->bind_param("i",$doc); $stmt->execute(); $res = $stmt->get_result();
?>
<div class="card"><h3>Doctor Dashboard</h3>
<table class="table"><thead><tr><th>Time</th><th>Patient</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php while($r=$res->fetch_assoc()): ?>
<tr><td><?php echo $r['appointment_time'] ?></td><td><?php echo htmlspecialchars($r['full_name']) ?></td><td><?php echo $r['status'] ?></td>
<td><a class="btn" href="/hospital_system/doctors/doctor_view.php?patient_id=<?php echo $r['patient_id'] ?>">Open</a></td></tr>
<?php endwhile; ?>
</tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
