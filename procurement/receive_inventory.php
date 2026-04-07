<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if (!isset($_SESSION['is_super']) || (int)$_SESSION['is_super'] !== 1) {
    die('Access Denied: Superuser Privileges Required.');
}

// Schema safety
$conn->query("CREATE TABLE IF NOT EXISTS inventory_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    po_item_id INT NOT NULL,
    supplier_invoice_no VARCHAR(120) NOT NULL,
    qty_received INT NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(50) DEFAULT NULL,
    received_by INT DEFAULT NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)");
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

$poiCols = [];
$poiColsRes = $conn->query('SHOW COLUMNS FROM purchase_order_items');
if ($poiColsRes) {
    while ($col = $poiColsRes->fetch_assoc()) {
        $poiCols[] = $col['Field'] ?? '';
    }
}
if (!in_array('received_qty', $poiCols, true)) {
    $conn->query('ALTER TABLE purchase_order_items ADD COLUMN received_qty INT NOT NULL DEFAULT 0 AFTER quantity');
}

$expCols = [];
$expColsRes = $conn->query('SHOW COLUMNS FROM expenses');
if ($expColsRes) {
    while ($col = $expColsRes->fetch_assoc()) {
        $expCols[] = $col['Field'] ?? '';
    }
}
if (!in_array('source_type', $expCols, true)) {
    $conn->query('ALTER TABLE expenses ADD COLUMN source_type VARCHAR(80) DEFAULT NULL AFTER amount');
}
if (!in_array('source_id', $expCols, true)) {
    $conn->query('ALTER TABLE expenses ADD COLUMN source_id INT DEFAULT NULL AFTER source_type');
}
if (!in_array('status', $expCols, true)) {
    $conn->query("ALTER TABLE expenses ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Pending' AFTER source_id");
}
if (!in_array('payment_method', $expCols, true)) {
    $conn->query('ALTER TABLE expenses ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER expense_date');
}

$message = '';
$messageType = 'success';
$printReceiptId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        $message = 'Invalid CSRF token.';
        $messageType = 'danger';
    } elseif (isset($_POST['receive_stock'])) {
        $poItemId = (int)($_POST['po_item_id'] ?? 0);
        $poId = (int)($_POST['po_id'] ?? 0);
        $incomingQty = (int)($_POST['actual_qty'] ?? 0);
        $drugName = trim((string)($_POST['drug_name'] ?? ''));
        $unitCost = (float)($_POST['unit_cost'] ?? 0);
        $supplierInvoiceNo = trim((string)($_POST['supplier_invoice_no'] ?? ''));
        $paymentMethod = trim((string)($_POST['payment_method'] ?? 'Cash'));

        if ($incomingQty <= 0 || $poItemId <= 0 || $poId <= 0 || $supplierInvoiceNo === '' || $drugName === '') {
            $message = 'PO item, quantity, drug and supplier invoice are required.';
            $messageType = 'danger';
        } else {
            $conn->begin_transaction();
            try {
                $stockStmt = $conn->prepare('UPDATE pharmacy_stock SET quantity = quantity + ? WHERE drug_name = ?');
                $stockStmt->bind_param('is', $incomingQty, $drugName);
                $stockStmt->execute();
                $stockStmt->close();

                $itemStmt = $conn->prepare('UPDATE purchase_order_items SET received_qty = received_qty + ? WHERE id = ?');
                $itemStmt->bind_param('ii', $incomingQty, $poItemId);
                $itemStmt->execute();
                $itemStmt->close();

                $checkStmt = $conn->prepare('SELECT id FROM purchase_order_items WHERE purchase_order_id = ? AND (quantity - received_qty) > 0 LIMIT 1');
                $checkStmt->bind_param('i', $poId);
                $checkStmt->execute();
                $hasBalance = $checkStmt->get_result()->num_rows > 0;
                $checkStmt->close();

                $newStatus = $hasBalance ? 'Partial' : 'Received';
                $poStmt = $conn->prepare('UPDATE purchase_orders SET status = ? WHERE id = ?');
                $poStmt->bind_param('si', $newStatus, $poId);
                $poStmt->execute();
                $poStmt->close();

                $totalVal = $incomingQty * $unitCost;
                $receiptStmt = $conn->prepare('INSERT INTO inventory_receipts (po_id, po_item_id, supplier_invoice_no, qty_received, unit_cost, total_cost, payment_method, received_by, received_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                $userId = (int)($_SESSION['user_id'] ?? 0);
                $receiptStmt->bind_param('iisiddsi', $poId, $poItemId, $supplierInvoiceNo, $incomingQty, $unitCost, $totalVal, $paymentMethod, $userId);
                $receiptStmt->execute();
                $printReceiptId = (int)$receiptStmt->insert_id;
                $receiptStmt->close();

                $accStmt = $conn->prepare("INSERT INTO accounting_entries (account, debit, credit, note, created_at) VALUES ('Procurement Expense', ?, 0, ?, NOW())");
                $note = 'Stock In: ' . $incomingQty . ' x ' . $drugName . ' (PO #' . $poId . ', Supplier Invoice ' . $supplierInvoiceNo . ')';
                $accStmt->bind_param('ds', $totalVal, $note);
                $accStmt->execute();
                $accStmt->close();

                $expStmt = $conn->prepare("UPDATE expenses SET status = CASE WHEN ? = 'Paid' THEN 'Paid' ELSE status END, payment_method = ? WHERE source_type = 'purchase_order' AND source_id = ?");
                $paidMarker = isset($_POST['mark_paid']) ? 'Paid' : 'Pending';
                $expStmt->bind_param('ssi', $paidMarker, $paymentMethod, $poId);
                $expStmt->execute();
                $expStmt->close();

                $conn->commit();
                $message = 'Confirmed: ' . $incomingQty . ' units received successfully.';
                $messageType = 'success';
            } catch (Throwable $e) {
                $conn->rollback();
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

$pendingItemsStmt = $conn->prepare("SELECT po.id AS po_id, s.name AS supplier_name, poi.id AS po_item_id, poi.item_name AS drug_name,
       poi.quantity AS total_ordered, COALESCE(poi.received_qty, 0) AS total_received,
       (poi.quantity - COALESCE(poi.received_qty, 0)) AS balance_remaining, poi.unit_price AS unit_cost,
       e.status AS expense_status, e.payment_method AS expense_payment_method
    FROM purchase_order_items poi
    JOIN purchase_orders po ON poi.purchase_order_id = po.id
    JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN expenses e ON e.source_type = 'purchase_order' AND e.source_id = po.id
    WHERE (poi.quantity - COALESCE(poi.received_qty, 0)) > 0 AND po.status <> 'Cancelled'
    ORDER BY po.id DESC");
$pendingItemsStmt->execute();
$pendingItems = $pendingItemsStmt->get_result();
$pendingItemsStmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h3 text-gray-800">Inventory Receiving & PO Payment</h2>
        <a href="purchase_orders.php" class="btn btn-secondary btn-sm">Back to PO List</a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
            <?php if ($printReceiptId > 0): ?>
                <a class="btn btn-sm btn-light ml-2" target="_blank" href="receive_inventory.php?print_receipt=<?= $printReceiptId ?>">Print Receiving Note</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow border-left-info mb-4">
        <div class="card-header py-3 bg-info text-white"><strong>Awaiting Fulfillment</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="bg-light">
                    <tr>
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>Drug</th>
                        <th>Ordered</th>
                        <th>Received</th>
                        <th>Balance</th>
                        <th>Supplier Invoice #</th>
                        <th>Pay Method</th>
                        <th>Mark Paid</th>
                        <th>Receive Qty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pendingItems && $pendingItems->num_rows > 0): ?>
                        <?php while ($row = $pendingItems->fetch_assoc()): ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="po_id" value="<?= (int)$row['po_id'] ?>">
                                    <input type="hidden" name="po_item_id" value="<?= (int)$row['po_item_id'] ?>">
                                    <input type="hidden" name="drug_name" value="<?= htmlspecialchars($row['drug_name']) ?>">
                                    <input type="hidden" name="unit_cost" value="<?= (float)$row['unit_cost'] ?>">
                                    <td><strong>#<?= (int)$row['po_id'] ?></strong></td>
                                    <td><?= htmlspecialchars((string)$row['supplier_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$row['drug_name']) ?></td>
                                    <td class="text-center"><?= (int)$row['total_ordered'] ?></td>
                                    <td class="text-center"><?= (int)$row['total_received'] ?></td>
                                    <td class="text-center text-danger font-weight-bold"><?= (int)$row['balance_remaining'] ?></td>
                                    <td><input type="text" name="supplier_invoice_no" class="form-control form-control-sm" placeholder="INV-..." required></td>
                                    <td>
                                        <select name="payment_method" class="form-control form-control-sm" required>
                                            <option value="Cash">Cash</option>
                                            <option value="Mpesa">Mpesa</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                        </select>
                                    </td>
                                    <td class="text-center"><input type="checkbox" name="mark_paid" value="1"></td>
                                    <td><input type="number" name="actual_qty" class="form-control form-control-sm" value="<?= (int)$row['balance_remaining'] ?>" min="1" max="<?= (int)$row['balance_remaining'] ?>" required></td>
                                    <td>
                                        <button type="submit" name="receive_stock" class="btn btn-info btn-sm btn-block">Receive</button>
                                        <a href="purchase_orders.php?view_id=<?= (int)$row['po_id'] ?>" target="_blank" class="btn btn-light btn-sm btn-block mt-1">Print PO</a>
                                    </td>
                                </form>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="11" class="text-center">All pending items received.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (isset($_GET['print_receipt']) && (int)$_GET['print_receipt'] > 0):
    $receiptId = (int)$_GET['print_receipt'];
    $printStmt = $conn->prepare("SELECT ir.*, poi.item_name, s.name AS supplier_name
                                FROM inventory_receipts ir
                                JOIN purchase_order_items poi ON ir.po_item_id = poi.id
                                JOIN purchase_orders po ON ir.po_id = po.id
                                JOIN suppliers s ON po.supplier_id = s.id
                                WHERE ir.id = ? LIMIT 1");
    $printStmt->bind_param('i', $receiptId);
    $printStmt->execute();
    $receipt = $printStmt->get_result()->fetch_assoc();
    $printStmt->close();
    if ($receipt): ?>
    <script>
        window.open('', '_blank');
    </script>
    <div class="container-fluid">
        <div class="card shadow mt-3">
            <div class="card-body">
                <h4>Receiving Note #<?= (int)$receipt['id'] ?></h4>
                <p><strong>PO:</strong> #<?= (int)$receipt['po_id'] ?> | <strong>Supplier:</strong> <?= htmlspecialchars((string)$receipt['supplier_name']) ?></p>
                <p><strong>Supplier Invoice:</strong> <?= htmlspecialchars((string)$receipt['supplier_invoice_no']) ?></p>
                <p><strong>Item:</strong> <?= htmlspecialchars((string)$receipt['item_name']) ?> | <strong>Qty:</strong> <?= (int)$receipt['qty_received'] ?></p>
                <p><strong>Unit Cost:</strong> KES <?= number_format((float)$receipt['unit_cost'], 2) ?> | <strong>Total:</strong> KES <?= number_format((float)$receipt['total_cost'], 2) ?></p>
                <p><strong>Payment Method:</strong> <?= htmlspecialchars((string)$receipt['payment_method']) ?> | <strong>Received At:</strong> <?= htmlspecialchars((string)$receipt['received_at']) ?></p>
                <button onclick="window.print()" class="btn btn-primary no-print">Print Receiving Note</button>
            </div>
        </div>
    </div>
    <?php endif; endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
