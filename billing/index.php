<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$encounter_id = (int)$_GET['encounter_id'];
if (!$encounter_id) die("Missing encounter");

$inv = $conn->query("SELECT * FROM invoices WHERE encounter_id=$encounter_id")->fetch_assoc();

if (!$inv) {
    die("No invoice found for this encounter");
}

$items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id=".$inv['id']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main">
<h2>Invoice <?= $inv['invoice_number'] ?></h2>

<table class="table">
<tr><th>Item</th><th>Total</th></tr>
<?php while($i=$items->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($i['description']) ?></td>
<td><?= number_format($i['total'],2) ?></td>
</tr>
<?php endwhile; ?>
</table>

<a class="btn" href="/hospital_system/invoices/print_invoice.php?id=<?= $inv['id'] ?>">Print Invoice</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
