<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$paid_invoices = $conn->query("SELECT i.*, p.full_name FROM invoices i JOIN encounters e ON e.id=i.encounter_id JOIN patients p ON p.id=e.patient_id WHERE i.status='paid' ORDER BY i.created_at DESC");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
<h3>Collections Report</h3>
<table class="table">
<thead><tr><th>Invoice No</th><th>Patient</th><th>Payment Mode</th><th>Amount</th><th>Date</th></tr></thead>
<tbody>
<?php while($p=$paid_invoices->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($p['invoice_no'])?></td>
<td><?=htmlspecialchars($p['full_name'])?></td>
<td><?=htmlspecialchars($p['payment_mode'])?></td>
<td><?=number_format($p['paid_amount'],2)?></td>
<td><?=$p['created_at']?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
