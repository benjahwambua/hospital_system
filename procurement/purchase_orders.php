<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

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

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$viewId = max(0, (int)($_GET['view_id'] ?? 0));

if ($viewId > 0):
    $po_stmt = $conn->prepare("SELECT po.*, s.name AS s_name, s.phone, s.email, u.username
                               FROM purchase_orders po
                               JOIN suppliers s ON po.supplier_id = s.id
                               LEFT JOIN users u ON po.user_id = u.id
                               WHERE po.id = ? LIMIT 1");
    $po_stmt->bind_param('i', $viewId);
    $po_stmt->execute();
    $po = $po_stmt->get_result()->fetch_assoc();
    $po_stmt->close();

    $items = [];
    if ($po) {
        $items_stmt = $conn->prepare('SELECT item_name, quantity, unit_price, line_total FROM purchase_order_items WHERE purchase_order_id = ? ORDER BY id ASC');
        $items_stmt->bind_param('i', $viewId);
        $items_stmt->execute();
        $itemsRes = $items_stmt->get_result();
        while ($row = $itemsRes->fetch_assoc()) {
            $items[] = $row;
        }
        $items_stmt->close();
    }

    $expense = null;
    $expenseStmt = $conn->prepare("SELECT id, amount, status, expense_date, payment_method FROM expenses WHERE source_type = 'purchase_order' AND source_id = ? ORDER BY id DESC LIMIT 1");
    $expenseStmt->bind_param('i', $viewId);
    $expenseStmt->execute();
    $expense = $expenseStmt->get_result()->fetch_assoc();
    $expenseStmt->close();
?>
<style>
.po-container { max-width: 1000px; margin: 24px auto; padding: 0 12px; width: 100%; }
.content-wrapper .po-container, .main-content .po-container { margin-left:auto !important; margin-right:auto !important; }
.po-card { background:#fff; border-radius:14px; box-shadow:0 8px 28px rgba(0,0,0,.08); padding:40px; position:relative; overflow:hidden; }
.watermark { position:absolute; top:50%; left:50%; transform:translate(-50%, -50%) rotate(-30deg); width:60%; opacity:.05; z-index:0; pointer-events:none; }
.po-content { position:relative; z-index:2; }
.po-branding { display:flex; justify-content:space-between; align-items:center; gap:20px; margin-bottom:20px; }
.po-branding img { max-height:75px; }
.po-hospital { text-align:right; font-size:13px; color:#555; }
.po-hospital h2 { margin:0; font-size:22px; text-transform:uppercase; color:#1f2937; }
.po-top { display:flex; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:24px; }
.po-table { width:100%; border-collapse: collapse; margin-top:20px; }
.po-table th, .po-table td { padding:11px 12px; border-bottom:1px solid #e5e7eb; }
.po-table th { background:#f8fafc; text-transform:uppercase; font-size:.76rem; color:#6b7280; }
.po-total { margin-top:20px; text-align:right; font-size:1.35rem; font-weight:800; color:#1d4ed8; }
.status-pill { display:inline-flex; padding:4px 10px; border-radius:999px; font-size:.78rem; font-weight:700; background:#e0f2fe; color:#075985; }
.po-signatures { display:flex; justify-content:space-between; align-items:flex-end; gap:24px; margin-top:40px; }
.sig-box { width:260px; text-align:center; }
.sig-line { border-top:1px solid #333; margin-bottom:6px; }
.stamp-space { width:140px; height:140px; border:2px dashed #93c5fd; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#60a5fa; font-size:11px; text-align:center; }
@media print {
    @page { size: A4 portrait; margin: 10mm; }
    html, body, .content-wrapper, .main-content, .container-fluid { width:100% !important; margin:0 !important; padding:0 !important; background:#fff !important; }
    header, footer, nav, aside, .sidebar, .navbar, .no-print { display:none !important; }
    .po-container { max-width:180mm !important; margin:0 auto !important; padding:0 !important; }
    .po-card { box-shadow:none; border:none; padding:6mm !important; }
    .po-table th, .po-table td { padding:7px 8px; font-size:12px; }
    .po-hospital { font-size:11px; }
    .po-total { font-size:18px; }
    .po-signatures { margin-top:18mm; gap:20mm; }
    .stamp-space { width:120px; height:120px; }
}
</style>

<div class="po-container">
    <div class="no-print d-flex justify-content-between align-items-center mb-3">
        <a href="purchase_orders.php" class="btn btn-secondary btn-sm">← Back to History</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">Print Order</button>
    </div>

    <?php if ($po): ?>
        <div class="po-card">
            <img src="../assets/img/logo.png" class="watermark" alt="Watermark" onerror="this.style.display='none'">
            <div class="po-content">
                <div class="po-branding">
                    <div><img src="/hospital_system/assets/img/logo.png" alt="Hospital Logo" onerror="this.style.display='none'"></div>
                    <div class="po-hospital">
                        <h2>Emaqure Medical Centre</h2>
                        <div>Biashara Street, Opposite Old Naiwe School, Mlolongo</div>
                        <div>Contact: +254793069565</div>
                        <div>emaquremedicalcentre@gmail.com</div>
                    </div>
                </div>
                <div class="po-top">
                    <div>
                        <h3 style="margin:0;color:#1d4ed8;">Official Purchase Order</h3>
                        <div style="color:#6b7280;">PO-<?= str_pad((string)$po['id'], 5, '0', STR_PAD_LEFT) ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div><strong>Date:</strong> <?= !empty($po['order_date']) ? date('d M Y', strtotime($po['order_date'])) : 'N/A' ?></div>
                        <div><strong>Status:</strong> <span class="status-pill"><?= htmlspecialchars((string)($po['status'] ?? 'Pending')) ?></span></div>
                        <div><strong>Issued By:</strong> <?= htmlspecialchars((string)($po['username'] ?? 'System')) ?></div>
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <small style="text-transform:uppercase;color:#6b7280;">Supplier</small>
                    <div><strong><?= htmlspecialchars((string)$po['s_name']) ?></strong></div>
                    <div><?= htmlspecialchars((string)($po['phone'] ?? '')) ?></div>
                    <div><?= htmlspecialchars((string)($po['email'] ?? '')) ?></div>
                </div>

                <table class="po-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items): foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$item['item_name']) ?></td>
                                <td class="text-right"><?= (int)$item['quantity'] ?></td>
                                <td class="text-right">KES <?= number_format((float)$item['unit_price'], 2) ?></td>
                                <td class="text-right">KES <?= number_format((float)$item['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center">No line items found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="po-total">Grand Total: KES <?= number_format((float)$po['total_amount'], 2) ?></div>
                <div style="margin-top:8px; text-align:right;">
                    <?php if ($expense): ?>
                        <small>Linked Expense #<?= (int)$expense['id'] ?> • <?= htmlspecialchars((string)$expense['status']) ?> • <?= htmlspecialchars((string)($expense['payment_method'] ?? 'N/A')) ?> • KES <?= number_format((float)$expense['amount'], 2) ?></small><br>
                        <a class="btn btn-sm btn-light no-print mt-2" href="/hospital_system/accounting/ledger.php?view_mode=ledger&account=<?= urlencode('Procurement Expense') ?>">Open in Ledger</a>
                    <?php else: ?>
                        <small>No linked expense found for this PO.</small>
                    <?php endif; ?>
                </div>

                <div class="po-signatures">
                    <div class="stamp-space">Hospital Stamp</div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div><strong>Hospital Authorized Signature</strong></div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div><strong>Supplier Signature</strong></div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">Purchase Order not found.</div>
    <?php endif; ?>
</div>

<?php else:
$statusFilter = trim((string)($_GET['status'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));
$generateReport = isset($_GET['generate']) && $_GET['generate'] === '1';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = $generateReport ? 10000 : 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
$types = '';
if ($statusFilter !== '') {
    $where[] = 'po.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $where[] = '(s.name LIKE ? OR po.id LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) AS total FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id {$whereSql}";
$countStmt = $conn->prepare($countSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$listSql = "SELECT po.*, s.name AS s_name, e.id AS expense_id, e.status AS expense_status, e.amount AS expense_amount, e.payment_method AS expense_payment_method
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN expenses e ON e.source_type = 'purchase_order' AND e.source_id = po.id
            {$whereSql}
            ORDER BY po.id DESC
            LIMIT ? OFFSET ?";
$listTypes = $types . 'ii';
$listParams = $params;
$listParams[] = $perPage;
$listParams[] = $offset;
$listStmt = $conn->prepare($listSql);
$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();
$listRes = $listStmt->get_result();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 text-gray-800">Purchase Order History</h2>
        <a href="create_po.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> New Order</a>
    </div>

    <div class="card shadow mb-3">
        <div class="card-body">
            <form class="form-row">
                <div class="col-md-4 mb-2"><input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search supplier or PO number"></div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All statuses</option>
                        <?php foreach (['Pending', 'Approved', 'Received', 'Cancelled'] as $status): ?>
                            <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <button type="submit" name="generate" value="1" class="btn btn-dark">Generate Report</button>
                    <a href="purchase_orders.php" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body table-responsive">
            <div class="mb-2 d-flex justify-content-end">
                <button onclick="exportTableToCSV('po_report_<?= date('Y-m-d') ?>.csv')" class="btn btn-sm btn-outline-dark mr-2">Download CSV</button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary">Print Report</button>
            </div>
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>PO #</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Expense Link</th>
                        <th>Payment Method</th>
                        <th>Total Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($listRes && $listRes->num_rows > 0): while ($row = $listRes->fetch_assoc()): ?>
                        <tr>
                            <td><strong>PO-<?= str_pad((string)$row['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                            <td><?= !empty($row['order_date']) ? date('d M Y', strtotime($row['order_date'])) : 'N/A' ?></td>
                            <td><?= htmlspecialchars((string)$row['s_name']) ?></td>
                            <td><?= htmlspecialchars((string)($row['status'] ?? 'Pending')) ?></td>
                            <td>
                                <?php if (!empty($row['expense_id'])): ?>
                                    <span class="badge badge-info">#<?= (int)$row['expense_id'] ?> <?= htmlspecialchars((string)($row['expense_status'] ?? 'Pending')) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not linked</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($row['expense_payment_method'] ?? 'N/A')) ?></td>
                            <td>KES <?= number_format((float)$row['total_amount'], 2) ?></td>
                            <td>
                                <a href="purchase_orders.php?view_id=<?= (int)$row['id'] ?>" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> View</a>
                                <a href="receive_inventory.php?po_id=<?= (int)$row['id'] ?>" class="btn btn-secondary btn-sm mt-1">Receive</a>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="d-flex justify-content-between align-items-center">
                <small><?= $generateReport ? 'Report mode: showing up to 10,000 records' : ('Page ' . $page . ' of ' . $totalPages) ?></small>
                <div>
                    <?php $base = ['search' => $search, 'status' => $statusFilter]; ?>
                    <?php if (!$generateReport): ?>
                        <?php if ($page > 1): ?><a class="btn btn-sm btn-light" href="?<?= htmlspecialchars(http_build_query(array_merge($base, ['page' => $page - 1]))) ?>">Previous</a><?php endif; ?>
                        <?php if ($page < $totalPages): ?><a class="btn btn-sm btn-light" href="?<?= htmlspecialchars(http_build_query(array_merge($base, ['page' => $page + 1]))) ?>">Next</a><?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function exportTableToCSV(filename) {
    const rows = document.querySelectorAll('table tr');
    const csv = [];
    rows.forEach((row) => {
        const cols = row.querySelectorAll('th, td');
        csv.push(Array.from(cols).map((c) => '"' + c.innerText.replace(/"/g, '""') + '"').join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
}
</script>
<?php
$listStmt->close();
endif;

include __DIR__ . '/../includes/footer.php';
?>
