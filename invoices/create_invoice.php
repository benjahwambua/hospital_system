<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name ASC");
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $inv_no = generate_invoice_number($conn);

    $conn->begin_transaction();
    try {
        $invoice_id = create_invoice($conn, $patient_id, null, null, 'unpaid', null, 0.0, $inv_no);
        $total = 0;

        // Items posted as arrays: desc[], qty[], unit_price[]
        $descs = $_POST['desc'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $units = $_POST['unit_price'] ?? [];
        for ($i = 0; $i < count($descs); $i++) {
            $d = trim($descs[$i]);
            if ($d === '') continue;
            $q = intval($qtys[$i]);
            $u = floatval($units[$i]);
            add_invoice_item($conn, $invoice_id, $d, $q, $u, 'billing');
            $total += $q * $u;
        }

        update_invoice_total($conn, $invoice_id, $total);

        // Post journal entries for invoice creation
        post_invoice_journal($conn, $invoice_id, $patient_id, $total, 'Invoice ' . $inv_no);

        $conn->commit();
        $success = "Invoice created.";
        header("Location: /hospital_system/invoices/print_invoice.php?id={$invoice_id}");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $success = "Error: " . $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main">
    <div class="page-title">Create Invoice</div>
    <div class="card" style="max-width:900px">
        <?php if ($success): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="post">
            <label>Patient</label>
            <select name="patient_id" class="form-control" required>
                <?php while ($p = $patients->fetch_assoc()): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
                <?php endwhile; ?>
            </select>

            <div id="items">
                <div class="item-row" style="display:flex;gap:8px;margin-top:8px;">
                    <input name="desc[]" class="form-control" placeholder="Description">
                    <input name="qty[]" class="form-control" placeholder="Qty" type="number">
                    <input name="unit_price[]" class="form-control" placeholder="Unit price" type="number" step="0.01">
                </div>
            </div>

            <div style="margin-top:8px;">
                <button type="button" class="btn btn-secondary" onclick="addItem()">Add another item</button>
            </div>

            <div style="margin-top:8px;">
                <button class="btn" type="submit">Create Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
function addItem() {
    const container = document.getElementById('items');
    const row = document.createElement('div');
    row.className = 'item-row';
    row.style = 'display:flex;gap:8px;margin-top:8px;';
    row.innerHTML = '<input name="desc[]" class="form-control" placeholder="Description">\
                     <input name="qty[]" class="form-control" placeholder="Qty" type="number">\
                     <input name="unit_price[]" class="form-control" placeholder="Unit price" type="number" step="0.01">\
                     <button onclick="this.parentNode.remove()" type="button" class="btn btn-secondary">Remove</button>';
    container.appendChild(row);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
