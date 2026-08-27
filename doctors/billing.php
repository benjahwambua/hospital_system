<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../helpers/billing.php';
require_login();
require_role('doctor');

$encounter_id = intval($_GET['encounter_id'] ?? 0);
if (!$encounter_id) die("Invalid encounter");

// Get or create invoice for encounter
$invoice_result = $conn->query("SELECT id FROM invoices WHERE encounter_id = $encounter_id");
if ($invoice_result->num_rows == 0) {
    $inv_no = generate_invoice_number($conn);
    $invoice_id = create_invoice($conn, null, $encounter_id, null, 'unpaid', null, 0.0, $inv_no);
} else {
    $invoice_id = $invoice_result->fetch_assoc()['id'];
}

// Add billing item
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $desc = $_POST['description'];
    $amount = floatval($_POST['amount']);

    add_invoice_item($conn, $invoice_id, $desc, 1, $amount, 'procedure');
    update_invoice_total($conn, $invoice_id);
}

$items = $conn->query("
SELECT description, qty, unit_price, total FROM invoice_items WHERE invoice_id = $invoice_id
");
?>

<h3>Billing</h3>
<form method="post">
<input name="description" placeholder="Procedure">
<input name="amount" type="number" step="0.01">
<button>Add</button>
</form>

<table>
<?php while($i=$items->fetch_assoc()): ?>
<tr><td><?= $i['description'] ?></td><td><?= $i['total'] ?></td></tr>
<?php endwhile; ?>
</table>
