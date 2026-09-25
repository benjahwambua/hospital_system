<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$encounter_id = (int)($_GET['encounter_id'] ?? 0);
if (!$encounter_id) die("Missing encounter");

$invStmt = $conn->prepare("SELECT * FROM invoices WHERE encounter_id=? ORDER BY id DESC LIMIT 1");
$invStmt->bind_param('i', $encounter_id);
$invStmt->execute();
$inv = $invStmt->get_result()->fetch_assoc();
$invStmt->close();

if (!$inv) {
    die("No invoice found for this encounter");
}

$itemsStmt = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY id ASC");
$itemsStmt->bind_param('i', $inv['id']);
$itemsStmt->execute();
$items = $itemsStmt->get_result();

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
