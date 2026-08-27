<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$patients = $conn->query("SELECT id, full_name FROM patients");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $total = floatval($_POST['total']);
    $inv_no = generate_invoice_number($conn);

    $invoice_id = create_invoice($conn, $patient_id, null, null, 'unpaid', null, 0.0, $inv_no);

    // Add a single item for the total
    add_invoice_item($conn, $invoice_id, 'Custom Bill', 1, $total, 'billing');

    update_invoice_total($conn, $invoice_id, $total);

    // Post journal entries for invoice creation
    post_invoice_journal($conn, $invoice_id, $patient_id, $total, 'Invoice ' . $inv_no);

    header("Location: /hospital_system/billing/view_bills.php");
    exit;
}
?>

<div class="card">
    <h3>New Bill</h3>
    <form method="post">
        <label>Patient</label>
        <select name="patient_id" class="form-control">
            <?php while ($p = $patients->fetch_assoc()): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
            <?php endwhile; ?>
        </select>
        <label>Total</label>
        <input name="total" type="number" step="0.01" class="form-control">
        <div style="margin-top:8px">
            <button class="btn" type="submit">Create Invoice</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
