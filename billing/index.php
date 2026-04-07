<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$encounter_id = (int)$_GET['encounter_id'];
if (!$encounter_id) die("Missing encounter");

$inv = $conn->query("SELECT * FROM invoices WHERE encounter_id=$encounter_id")->fetch_assoc();

if (!$inv) {
    $no = 'INV'.time();
    $conn->query("
        INSERT INTO invoices (encounter_id, invoice_no, total)
        VALUES ($encounter_id,'$no',0)
    ");
    $inv = $conn->query("SELECT * FROM invoices WHERE encounter_id=$encounter_id")->fetch_assoc();
}

$items = $conn->query("SELECT * FROM billing_items WHERE invoice_id=".$inv['id']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main">
<h2>Invoice <?= $inv['invoice_no'] ?></h2>

<table class="table">
<tr><th>Item</th><th>Total</th></tr>
<?php while($i=$items->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($i['item_name']) ?></td>
<td><?= number_format($i['total'],2) ?></td>
</tr>
<?php endwhile; ?>
</table>

<a class="btn" href="/hospital_system/invoices/print.php?id=<?= $inv['id'] ?>">Print Invoice</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
