<?php
require_once __DIR__ . '/../config/config.php'; require_once __DIR__ . '/../includes/session.php'; require_login();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
$today = date('Y-m-d');
$patientsStmt = $conn->prepare("SELECT COUNT(*) as c FROM appointments WHERE appointment_date=?");
$patientsStmt->bind_param('s', $today);
$patientsStmt->execute();
$patients_today = (int)($patientsStmt->get_result()->fetch_assoc()['c'] ?? 0);
$patientsStmt->close();

$salesStmt = $conn->prepare("SELECT COALESCE(SUM(total),0) as s FROM invoices WHERE DATE(created_at)=?");
$salesStmt->bind_param('s', $today);
$salesStmt->execute();
$sales_today = (float)($salesStmt->get_result()->fetch_assoc()['s'] ?? 0);
$salesStmt->close();
?>
<div class="card"><h3>Daily Report (<?php echo $today ?>)</h3>
<div>Appointments today: <?php echo $patients_today ?></div>
<div>Total sales today: KES <?php echo number_format($sales_today,2) ?></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
