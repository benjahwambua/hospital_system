<?php
require_once __DIR__ . '/../config/config.php'; require_once __DIR__ . '/../includes/session.php'; require_login();
$id=intval($_GET['id']??0);
$invStmt = $conn->prepare("SELECT i.*, p.full_name FROM invoices i LEFT JOIN patients p ON p.id=i.patient_id WHERE i.id=? LIMIT 1");
$invStmt->bind_param('i', $id);
$invStmt->execute();
$inv = $invStmt->get_result()->fetch_assoc();
$invStmt->close();
include __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:800px;margin:20px auto">
  <h2>Invoice: <?=htmlspecialchars($inv['invoice_number'])?></h2>
  <div>Patient: <?=htmlspecialchars($inv['full_name'])?></div>
  <div>Total: KES <?=number_format($inv['total'],2)?></div>
  <div style="margin-top:12px"><button onclick="window.print()" class="btn">Print</button></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
