<?php
require_once __DIR__ . '/../config/config.php'; require_once __DIR__ . '/../includes/session.php'; require_login();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
$today = date('Y-m-d');
$patients_today = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE appointment_date='$today'")->fetch_assoc()['c'];
$sales_today = $conn->query("SELECT SUM(total) as s FROM invoices WHERE DATE(created_at)='$today'")->fetch_assoc()['s'];
?>
<div class="card"><h3>Daily Report (<?php echo $today ?>)</h3>
<div>Appointments today: <?php echo $patients_today ?></div>
<div>Total sales today: KES <?php echo number_format($sales_today,2) ?></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
