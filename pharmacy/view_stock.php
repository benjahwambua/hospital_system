<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_module_access($conn, 'pharmacy', 'view');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$successMessage = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
$errorMessage = null;

/* Excel export must happen before page output. */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = 'Pharmacy_Stock_Take_' . date('Y-m-d') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $exportQuery = "SELECT drug_name, supplier, unit, quantity, buying_price, selling_price, expiry_date
                    FROM pharmacy_stock ORDER BY drug_name ASC";
    $exportRes = $conn->query($exportQuery);

    echo "Medicine Name\tSupplier\tUnit\tQuantity\tBuying Price\tSelling Price\tTotal Value\tExpiry Date\n";
    if ($exportRes) {
        while ($row = $exportRes->fetch_assoc()) {
            $total = (float)$row['quantity'] * (float)$row['selling_price'];
            echo htmlspecialchars($row['drug_name']) . "\t";
            echo htmlspecialchars($row['supplier'] ?? '') . "\t";
            echo htmlspecialchars($row['unit'] ?? '') . "\t";
            echo (int)$row['quantity'] . "\t";
            echo number_format((float)$row['buying_price'], 2, '.', '') . "\t";
            echo number_format((float)$row['selling_price'], 2, '.', '') . "\t";
            echo number_format($total, 2, '.', '') . "\t";
            echo htmlspecialchars($row['expiry_date'] ?? '') . "\n";
        }
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    require_module_access($conn, 'pharmacy', 'delete');

    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errorMessage = 'Security token mismatch. Please refresh and try again.';
    } else {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId > 0) {
            $stmt = $conn->prepare('DELETE FROM pharmacy_stock WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $deleteId);
                if ($stmt->execute()) {
                    $successMessage = 'Stock item deleted successfully.';
                } else {
                    $errorMessage = 'Unable to delete the stock item right now.';
                }
                $stmt->close();
            } else {
                $errorMessage = 'Unable to prepare the stock deletion.';
            }
        }
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$stockFilter = trim((string)($_GET['stock'] ?? 'all'));
$expiryFilter = trim((string)($_GET['expiry'] ?? 'all'));

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(drug_name LIKE ? OR supplier LIKE ? OR batch_no LIKE ? OR invoice_no LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}

if ($stockFilter === 'low') {
    $where[] = 'quantity <= 10';
} elseif ($stockFilter === 'out') {
    $where[] = 'quantity <= 0';
} elseif ($stockFilter === 'available') {
    $where[] = 'quantity > 0';
}

if ($expiryFilter === 'expired') {
    $where[] = 'expiry_date IS NOT NULL AND expiry_date < CURDATE()';
} elseif ($expiryFilter === '30') {
    $where[] = 'expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
} elseif ($expiryFilter === '90') {
    $where[] = 'expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
}

$sql = 'SELECT id, drug_name AS name, quantity, buying_price, selling_price, unit, supplier, batch_no, invoice_no, expiry_date
        FROM pharmacy_stock';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY
            CASE WHEN quantity <= 0 THEN 0 WHEN quantity <= 10 THEN 1 ELSE 2 END,
            CASE WHEN expiry_date IS NOT NULL AND expiry_date < CURDATE() THEN 0 ELSE 1 END,
            drug_name ASC';

$stmt = $conn->prepare($sql);
if ($stmt && $types !== '') {
    $bind = [$types];
    foreach ($params as $key => $value) {
        $bind[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = false;
}

$totalItems = 0;
$lowStock = 0;
$outOfStock = 0;
$stockValue = 0.0;
$rows = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $qty = (int)$row['quantity'];
        $value = $qty * (float)$row['buying_price'];
        $row['_value'] = $value;
        $rows[] = $row;
        $totalItems++;
        $stockValue += $value;
        if ($qty <= 0) {
            $outOfStock++;
        } elseif ($qty <= 10) {
            $lowStock++;
        }
    }
}

$today = new DateTimeImmutable('today');
$expirySoon = 0;
$expired = 0;
foreach ($rows as $row) {
    if (!empty($row['expiry_date'])) {
        try {
            $expiry = new DateTimeImmutable($row['expiry_date']);
            if ($expiry < $today) {
                $expired++;
            } elseif ($expiry <= $today->modify('+30 days')) {
                $expirySoon++;
            }
        } catch (Exception $e) {
            // Ignore invalid legacy dates.
        }
    }
}
?>
<style>
/* HMS unified operational workspace */
.main-content{background:#f5f7fb;min-height:calc(100vh - 72px)}
.main-content>.container-fluid{max-width:1500px}
.main-content h1,.main-content h2,.main-content h3{color:#25324a}
.main-content .card{border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 18px rgba(31,45,61,.05);overflow:hidden}
.main-content .card-header{background:#fff;border-bottom:1px solid #edf0f5;color:#25324a}
.main-content .table thead th{background:#f8fafc;border-top:0;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.35px}
.main-content .table td{border-color:#edf0f5;vertical-align:middle;font-size:13px}
.main-content .table tbody tr:hover{background:#f8fbff}
.main-content .form-control{border-color:#d7dee8;border-radius:9px}
.main-content .form-control:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}
.main-content .btn{border-radius:8px;font-weight:700}
.main-content .btn-primary{background:#075b9d;border-color:#075b9d}
.main-content .page-header,.main-content .d-flex.justify-content-between.align-items-center{margin-bottom:20px!important}

.stock-workspace{padding:26px 24px 42px}
.stock-hero{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:20px}
.stock-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;font-weight:800;color:#667085;margin-bottom:5px}
.stock-hero h1{font-size:26px;font-weight:800;margin:0 0 5px}
.stock-hero p{margin:0;color:#667085;font-size:14px}
.stock-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.stock-stat{height:100%;padding:18px 20px}
.stock-stat .label{font-size:10px;text-transform:uppercase;letter-spacing:.8px;font-weight:800;color:#667085}
.stock-stat .value{font-size:22px;font-weight:800;color:#25324a;margin-top:4px}
.stock-stat .hint{font-size:12px;color:#98a2b3;margin-top:2px}
.stock-icon{width:38px;height:38px;border-radius:10px;background:#eef5fb;color:#075b9d;display:flex;align-items:center;justify-content:center;font-size:16px}
.stock-filter{padding:18px 20px}
.stock-filter label{font-size:10px;text-transform:uppercase;letter-spacing:.7px;font-weight:800;color:#667085}
.stock-item-name{font-weight:800;color:#25324a}
.stock-item-meta{font-size:11px;color:#98a2b3;margin-top:2px}
.stock-number{font-weight:800;color:#25324a;white-space:nowrap}
.stock-value{font-weight:800;color:#075b9d;white-space:nowrap}
.stock-status{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;white-space:nowrap}
.stock-status.ok{background:#ecfdf3;color:#067647}.stock-status.low{background:#fffaeb;color:#b54708}.stock-status.out{background:#fef3f2;color:#b42318}
.expiry{font-size:12px}.expiry-danger{color:#b42318;font-weight:800}.expiry-warning{color:#b54708;font-weight:700}.expiry-ok{color:#667085}
.stock-table .actions{min-width:260px}
.stock-table .btn{margin:2px 0}
.stock-total{font-size:13px;font-weight:800;color:#25324a}
.stock-empty{padding:60px 20px;text-align:center;color:#98a2b3}
.stock-empty i{font-size:38px;margin-bottom:12px;color:#c5ccd6}
@media(max-width:900px){.stock-hero{flex-direction:column}.stock-actions{justify-content:flex-start}.stock-workspace{padding:20px 12px 35px}}
</style>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content">
<div class="container-fluid stock-workspace">
    <div class="stock-hero">
        <div>
            <div class="stock-kicker">Pharmacy · Inventory</div>
            <h1><i class="fas fa-boxes-stacked mr-2"></i> Stock Management</h1>
            <p>Monitor quantities, expiry dates, stock value and replenishment from one workspace.</p>
        </div>
        <div class="stock-actions">
            <?php if (can_module_action($conn, 'pharmacy', 'create')): ?>
                <a href="add_stock.php" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Add Stock</a>
            <?php endif; ?>
            <a href="../procurement/purchase_orders.php" class="btn btn-outline-secondary"><i class="fas fa-cart-shopping mr-1"></i> Procurement</a>
            <a href="?export=excel" class="btn btn-outline-success"><i class="fas fa-file-excel mr-1"></i> Export</a>
        </div>
    </div>

    <?php if ($successMessage): ?><div class="alert alert-success"><i class="fas fa-circle-check mr-1"></i><?= htmlspecialchars($successMessage) ?></div><?php endif; ?>
    <?php if ($errorMessage): ?><div class="alert alert-danger"><i class="fas fa-triangle-exclamation mr-1"></i><?= htmlspecialchars($errorMessage) ?></div><?php endif; ?>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3"><div class="card h-100"><div class="stock-stat d-flex justify-content-between"><div><div class="label">Stock Items</div><div class="value"><?= number_format($totalItems) ?></div><div class="hint">Items in inventory</div></div><div class="stock-icon"><i class="fas fa-boxes-stacked"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="card h-100"><div class="stock-stat d-flex justify-content-between"><div><div class="label">Low Stock</div><div class="value"><?= number_format($lowStock) ?></div><div class="hint">10 units or less</div></div><div class="stock-icon"><i class="fas fa-arrow-trend-down"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="card h-100"><div class="stock-stat d-flex justify-content-between"><div><div class="label">Expiry Attention</div><div class="value"><?= number_format($expired + $expirySoon) ?></div><div class="hint"><?= $expired ?> expired · <?= $expirySoon ?> due ≤30 days</div></div><div class="stock-icon"><i class="fas fa-calendar-xmark"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="card h-100"><div class="stock-stat d-flex justify-content-between"><div><div class="label">Inventory Cost Value</div><div class="value">KES <?= number_format($stockValue, 2) ?></div><div class="hint">Based on buying price</div></div><div class="stock-icon"><i class="fas fa-coins"></i></div></div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div><strong>Inventory Filters</strong><div class="small text-muted">Search by item, supplier, batch or invoice.</div></div>
            <a href="view_stock.php" class="btn btn-sm btn-light">Reset</a>
        </div>
        <div class="stock-filter">
            <form method="get" class="row align-items-end">
                <div class="col-lg-5 mb-2">
                    <label>Search</label>
                    <input type="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Medicine, supplier, batch or invoice">
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <label>Stock Status</label>
                    <select name="stock" class="form-control">
                        <option value="all" <?= $stockFilter === 'all' ? 'selected' : '' ?>>All stock</option>
                        <option value="available" <?= $stockFilter === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low stock</option>
                        <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Out of stock</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 mb-2">
                    <label>Expiry</label>
                    <select name="expiry" class="form-control">
                        <option value="all" <?= $expiryFilter === 'all' ? 'selected' : '' ?>>All dates</option>
                        <option value="30" <?= $expiryFilter === '30' ? 'selected' : '' ?>>Due ≤30 days</option>
                        <option value="90" <?= $expiryFilter === '90' ? 'selected' : '' ?>>Due ≤90 days</option>
                        <option value="expired" <?= $expiryFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>
                <div class="col-lg-2 mb-2"><button class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> Apply Filters</button></div>
            </form>
        </div>
    </div>

    <div class="card stock-table">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div><strong>Current Pharmacy Stock</strong><div class="small text-muted"><?= number_format(count($rows)) ?> item(s) shown</div></div>
            <span class="stock-total">Cost value: KES <?= number_format($stockValue, 2) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (!$rows): ?>
                <div class="stock-empty"><i class="fas fa-box-open d-block"></i><h5 class="mb-1">No stock items found</h5><div>Try changing your filters or add a new stock entry.</div></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr>
                            <th class="pl-4">Item</th><th>Supplier / Batch</th><th>Qty</th><th>Pricing</th><th>Expiry</th><th>Stock Value</th><th class="actions">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($rows as $row):
                            $qty=(int)$row['quantity'];
                            $expiryClass='expiry-ok'; $expiryText=$row['expiry_date'] ? date('d M Y', strtotime($row['expiry_date'])) : 'Not set';
                            if (!empty($row['expiry_date'])) {
                                try {
                                    $expiry=new DateTimeImmutable($row['expiry_date']);
                                    if ($expiry < $today) {$expiryClass='expiry-danger'; $expiryText='Expired · '.$expiry->format('d M Y');}
                                    elseif ($expiry <= $today->modify('+30 days')) {$expiryClass='expiry-warning';}
                                } catch(Exception $e) {}
                            }
                        ?>
                            <tr>
                                <td class="pl-4"><div class="stock-item-name"><?= htmlspecialchars($row['name']) ?></div><div class="stock-item-meta"><?= htmlspecialchars($row['unit'] ?: 'Unit not specified') ?></div></td>
                                <td><div><?= htmlspecialchars($row['supplier'] ?: 'Generic Supplier') ?></div><div class="stock-item-meta"><?= $row['batch_no'] ? 'Batch: '.htmlspecialchars($row['batch_no']) : 'No batch recorded' ?></div></td>
                                <td><span class="stock-status <?= $qty<=0?'out':($qty<=10?'low':'ok') ?>"><?= $qty<=0?'Out':($qty<=10?'Low':'Available') ?> · <?= number_format($qty) ?></span></td>
                                <td><div class="stock-number">Sell KES <?= number_format((float)$row['selling_price'],2) ?></div><div class="stock-item-meta">Cost KES <?= number_format((float)$row['buying_price'],2) ?></div></td>
                                <td><span class="expiry <?= $expiryClass ?>"><?= htmlspecialchars($expiryText) ?></span></td>
                                <td class="stock-value">KES <?= number_format((float)$row['_value'],2) ?></td>
                                <td class="actions">
                                    <?php if (can_module_action($conn, 'pharmacy', 'create')): ?><a href="../procurement/purchase_orders.php?item=<?= urlencode($row['name']) ?>" class="btn btn-dark btn-sm"><i class="fas fa-truck mr-1"></i> Reorder</a><?php endif; ?>
                                    <?php if (can_module_action($conn, 'pharmacy', 'edit')): ?>
                                        <a href="adjust_price.php?id=<?= (int)$row['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-tag mr-1"></i> Price</a>
                                        <a href="stock_take.php?id=<?= (int)$row['id'] ?>" class="btn btn-outline-info btn-sm"><i class="fas fa-clipboard-check mr-1"></i> Stock Take</a>
                                    <?php endif; ?>
                                    <?php if (can_module_action($conn, 'pharmacy', 'delete')): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this stock item? This action cannot be undone.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                                            <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash mr-1"></i> Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr><th colspan="5" class="text-right">Inventory Cost Value</th><th>KES <?= number_format($stockValue,2) ?></th><th></th></tr></tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php
if ($stmt) $stmt->close();
include __DIR__ . '/../includes/footer.php';
?>