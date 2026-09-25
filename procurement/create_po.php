<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_role(['admin']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_po'])) {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        $error = 'Invalid CSRF token. Please refresh and try again.';
    } else {
        $supplier_id = max(0, (int)($_POST['supplier_id'] ?? 0));
        $order_date = trim((string)($_POST['order_date'] ?? ''));
        $total_amount = (float)($_POST['grand_total'] ?? 0);
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $paymentMethod = trim((string)($_POST['payment_method'] ?? 'Cash'));
        $paymentStatus = trim((string)($_POST['payment_status'] ?? 'Pending'));

        $itemIds = $_POST['item_id'] ?? [];
        $itemNames = $_POST['item_name'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $prices = $_POST['price'] ?? [];
        $inventoryTypes = $_POST['inventory_type'] ?? [];
        $inventoryItemIds = $_POST['inventory_item_id'] ?? [];

        if ($supplier_id <= 0 || $order_date === '' || $user_id <= 0) {
            $error = 'Supplier, order date and user session are required.';
        } elseif (!$itemIds || count($itemIds) !== count($qtys) || count($itemIds) !== count($prices) || count($itemIds) !== count($inventoryTypes) || count($itemIds) !== count($inventoryItemIds)) {
            $error = 'Please add at least one valid item.';
        } else {
            $conn->begin_transaction();
            try {
                $conn->query("CREATE TABLE IF NOT EXISTS expenses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    expense_date DATE NOT NULL,
                    category VARCHAR(120) NOT NULL,
                    description VARCHAR(255) NOT NULL,
                    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                    source_type VARCHAR(80) DEFAULT NULL,
                    source_id INT DEFAULT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
                    created_by INT DEFAULT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )");

                $expenseColumns = [];
                $expenseColsRes = $conn->query("SHOW COLUMNS FROM expenses");
                if ($expenseColsRes) {
                    while ($col = $expenseColsRes->fetch_assoc()) {
                        $expenseColumns[] = $col['Field'] ?? '';
                    }
                }
                if (!in_array('source_type', $expenseColumns, true)) {
                    $conn->query("ALTER TABLE expenses ADD COLUMN source_type VARCHAR(80) DEFAULT NULL AFTER amount");
                }
                if (!in_array('source_id', $expenseColumns, true)) {
                    $conn->query("ALTER TABLE expenses ADD COLUMN source_id INT DEFAULT NULL AFTER source_type");
                }
                if (!in_array('status', $expenseColumns, true)) {
                    $conn->query("ALTER TABLE expenses ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Pending' AFTER source_id");
                }
                if (!in_array('created_by', $expenseColumns, true)) {
                    $conn->query("ALTER TABLE expenses ADD COLUMN created_by INT DEFAULT NULL AFTER status");
                }
                if (!in_array('created_at', $expenseColumns, true)) {
                    $conn->query("ALTER TABLE expenses ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_by");
                }
                if (!in_array('payment_method', $expenseColumns, true)) {
                    $conn->query("ALTER TABLE expenses ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER expense_date");
                }

                $stmt = $conn->prepare("INSERT INTO purchase_orders (supplier_id, order_date, total_amount, user_id, status) VALUES (?, ?, ?, ?, 'Pending')");
                $stmt->bind_param('isdi', $supplier_id, $order_date, $total_amount, $user_id);
                $stmt->execute();
                $po_id = (int)$conn->insert_id;
                $stmt->close();

                $item_stmt = $conn->prepare('INSERT INTO purchase_order_items (purchase_order_id, item_name, inventory_type, inventory_item_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?, ?)');

                $serverGrandTotal = 0.0;
                $insertedItems = 0;

                foreach ($itemIds as $i => $stockId) {
                    $stockId = (int)$stockId;
                    $inventoryType = strtolower(trim((string)($inventoryTypes[$i] ?? 'pharmacy')));
                    if (!in_array($inventoryType, ['pharmacy','lab'], true)) $inventoryType = 'pharmacy';
                    $inventoryItemId = (int)($inventoryItemIds[$i] ?? $stockId);
                    $name = trim((string)($itemNames[$i] ?? ''));
                    $qty = max(1, (int)($qtys[$i] ?? 0));
                    $u_price = max(0, (float)($prices[$i] ?? 0));
                    $l_total = round($qty * $u_price, 2);

                    if ($stockId <= 0 || $name === '' || $inventoryItemId <= 0 || $qty <= 0 || $u_price < 0) {
                        continue;
                    }

                    $item_stmt->bind_param('issiidd', $po_id, $name, $inventoryType, $inventoryItemId, $qty, $u_price, $l_total);
                    if (!$item_stmt->execute()) {
                        throw new Exception('Unable to save PO item: ' . $item_stmt->error);
                    }
                    $serverGrandTotal += $l_total;
                    $insertedItems++;
                }
                $item_stmt->close();

                if ($insertedItems < 1) {
                    throw new Exception('At least one valid PO item is required.');
                }
                $total_amount = round($serverGrandTotal, 2);
                $updateTotal = $conn->prepare("UPDATE purchase_orders SET total_amount=? WHERE id=?");
                $updateTotal->bind_param('di', $total_amount, $po_id);
                $updateTotal->execute();
                $updateTotal->close();

                // A PO is a commitment, not an accounting expense.
                // Inventory/AP are recognized only when goods are actually received through GRN.
                $conn->commit();
                header('Location: purchase_orders.php?view_id=' . $po_id);
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $error = 'Error creating PO: ' . $e->getMessage();
            }
        }
    }
}

$stockItems = [];
$stockRes = $conn->query('SELECT id, drug_name AS item_name, buying_price, quantity FROM pharmacy_stock ORDER BY drug_name ASC');
if ($stockRes) while ($row = $stockRes->fetch_assoc()) { $row['inventory_type']='pharmacy'; $stockItems[]=$row; }
$labRes = $conn->query("SELECT id, item_name, buying_price, quantity FROM lab_inventory WHERE status='active' ORDER BY item_name ASC");
if ($labRes) while ($row = $labRes->fetch_assoc()) { $row['inventory_type']='lab'; $stockItems[]=$row; }
$stockRes = null;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 text-gray-800">Create New Purchase Order</h2>
        <a href="purchase_orders.php" class="btn btn-secondary btn-sm">Cancel</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="po-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Select Supplier</label>
                        <select name="supplier_id" class="form-control" required>
                            <option value="">-- Search Supplier --</option>
                            <?php
                            $suppliers = $conn->query('SELECT id, name FROM suppliers ORDER BY name ASC');
                            if ($suppliers) {
                                while ($s = $suppliers->fetch_assoc()) {
                                    echo '<option value="' . (int)$s['id'] . '">' . htmlspecialchars($s['name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Order Date</label>
                        <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Payment Terms</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="Credit">Credit / Supplier Payable</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Payment Status</label>
                        <input type="text" class="form-control" value="Pending until supplier payment" readonly>
                        <small class="text-muted">Payment is recorded after GRN in Supplier Payables.</small>
                    </div>
                </div>

                <hr>

                <table class="table table-bordered" id="items-table">
                    <thead class="bg-light">
                        <tr>
                            <th>Medicine/Item Name</th>
                            <th width="120">Quantity</th>
                            <th width="150">Unit Price (KES)</th>
                            <th width="150">Line Total</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody id="item-rows"></tbody>
                </table>

                <button type="button" class="btn btn-info btn-sm mb-3" id="add-row">
                    <i class="fa fa-plus"></i> Add Another Item
                </button>

                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <table class="table table-bordered">
                            <tr>
                                <th class="bg-light">Grand Total (KES)</th>
                                <td>
                                    <input type="text" name="grand_total" id="grand-total" class="form-control font-weight-bold text-primary" readonly value="0.00">
                                </td>
                            </tr>
                        </table>
                        <button type="submit" name="save_po" class="btn btn-success btn-block btn-lg">
                            <i class="fa fa-save"></i> Save & Generate PO
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="po-row-template">
    <tr>
        <td>
            <input type="hidden" name="item_id[]" class="item-id" value="">
            <input type="hidden" name="item_name[]" class="item-name" value="">
            <input type="hidden" name="inventory_item_id[]" class="inventory-item-id" value="">
            <select name="inventory_type[]" class="form-control form-control-sm inventory-type mb-1"><option value="pharmacy">Pharmacy</option><option value="lab">Laboratory</option></select>
            <input type="text" class="form-control item-search" list="stock-item-options" placeholder="Search medicine/item..." required>
        </td>
        <td><input type="number" name="qty[]" class="form-control qty" min="1" value="1" required></td>
        <td><input type="number" name="price[]" class="form-control price" step="0.01" min="0" value="0.00" required></td>
        <td><input type="text" class="form-control row-total" value="0.00" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-times"></i></button></td>
    </tr>
</template>
<datalist id="stock-item-options">
    <?php foreach ($stockItems as $stock): ?>
        <option
            value="<?= htmlspecialchars($stock['item_name']) ?>"
            data-id="<?= (int)$stock['id'] ?>"
            data-type="<?= htmlspecialchars($stock['inventory_type']) ?>"
            data-price="<?= number_format((float)($stock['buying_price'] ?? 0), 2, '.', '') ?>"
        ><?= htmlspecialchars($stock['item_name']) ?> [<?= strtoupper($stock['inventory_type']) ?>] (Stock: <?= (int)$stock['quantity'] ?>)</option>
    <?php endforeach; ?>
</datalist>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('item-rows');
    const addBtn = document.getElementById('add-row');
    const rowTemplate = document.getElementById('po-row-template');

    function addRow() {
        tableBody.appendChild(rowTemplate.content.firstElementChild.cloneNode(true));
    }

    function recalc() {
        let grandTotal = 0;
        tableBody.querySelectorAll('tr').forEach((row) => {
            const qty = parseFloat(row.querySelector('.qty')?.value || 0);
            const price = parseFloat(row.querySelector('.price')?.value || 0);
            const total = qty * price;
            row.querySelector('.row-total').value = total.toFixed(2);
            grandTotal += total;
        });
        document.getElementById('grand-total').value = grandTotal.toFixed(2);
    }

    addBtn.addEventListener('click', addRow);

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-search')) {
            const row = e.target.closest('tr');
            const chosenName = (e.target.value || '').trim().toLowerCase();
            const option = Array.from(document.querySelectorAll('#stock-item-options option'))
                .find((opt) => (opt.value || '').trim().toLowerCase() === chosenName);
            const id = option?.dataset?.id || '';
            const name = option?.value || '';
            const price = option?.dataset?.price || '0.00';
            const type = option?.dataset?.type || 'pharmacy';

            row.querySelector('.item-id').value = id;
            row.querySelector('.inventory-item-id').value = id;
            row.querySelector('.inventory-type').value = type;
            row.querySelector('.item-name').value = name;
            row.querySelector('.price').value = parseFloat(price || 0).toFixed(2);
            recalc();
        }
    });

    tableBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('qty') || e.target.classList.contains('price')) {
            recalc();
        }
    });

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            e.target.closest('tr').remove();
            recalc();
        }
    });

    addRow();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
