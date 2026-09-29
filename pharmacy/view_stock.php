<style>
/* HMS visual refresh — styling only; page structure/workflow unchanged */
.main-content{background:#f5f7fb;min-height:calc(100vh - 72px)}
.main-content>.container-fluid{max-width:1500px}
.content-area{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 18px rgba(31,45,61,.05);padding:24px;margin:24px 0 40px}
.content-area h2{color:#25324a;font-size:24px;font-weight:800;margin:0 0 20px}
.content-area .alert{border:0;border-radius:10px}
.content-area .form-control{border-color:#d7dee8;border-radius:9px;min-height:40px}
.content-area .form-control:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}
.content-area .btn{border-radius:8px;font-weight:700}
.content-area .btn-success{background:#198754;border-color:#198754}
.content-area .btn-warning{color:#fff;background:#b7791f;border-color:#b7791f}
.content-area .btn-info{background:#075b9d;border-color:#075b9d}
.content-area .btn-dark{background:#25324a;border-color:#25324a}
.content-area .table{margin-top:14px;border:1px solid #e5eaf1}
.content-area .table thead th{background:#f8fafc;border-top:0;border-color:#e5eaf1;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.35px;white-space:nowrap}
.content-area .table td{border-color:#edf0f5;vertical-align:middle;font-size:13px}
.content-area .table tbody tr:hover{background:#f8fbff}
.content-area .table tbody tr.table-danger:hover{background:#fff1f0}
.content-area .table tfoot th{background:#f8fafc;border-color:#e5eaf1;color:#25324a}
.content-area .table td strong{color:#25324a}
.content-area .table .btn{margin:2px 0}
.content-area .text-danger{font-size:11px}
@media(max-width:900px){.content-area{margin:16px 10px 30px;padding:16px}.content-area .table{min-width:950px}}
</style>
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_module_access($conn, 'pharmacy', 'view');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$successMessage = null;
$errorMessage = null;

/* ==================================
    EXCEL EXPORT LOGIC
    (Must be before any HTML output)
================================== */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = 'Pharmacy_Stock_Take_' . date('Y-m-d') . '.xls';

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $exportQuery = "SELECT drug_name, supplier, unit, quantity, buying_price, selling_price, expiry_date FROM pharmacy_stock ORDER BY drug_name ASC";
    $exportRes = $conn->query($exportQuery);

    echo "Medicine Name\tSupplier\tUnit\tQuantity\tBuying Price\tSelling Price\tTotal Value\tExpiry Date\n";

    while ($row = $exportRes->fetch_assoc()) {
        $total = $row['quantity'] * $row['selling_price'];
        echo htmlspecialchars($row['drug_name']) . "\t";
        echo htmlspecialchars($row['supplier']) . "\t";
        echo htmlspecialchars($row['unit']) . "\t";
        echo $row['quantity'] . "\t";
        echo $row['buying_price'] . "\t";
        echo $row['selling_price'] . "\t";
        echo $total . "\t";
        echo $row['expiry_date'] . "\n";
    }
    exit;
}

/* Handle deletion on the same page */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    require_module_access($conn, 'pharmacy', 'delete');
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        $errorMessage = 'Security token mismatch. Please refresh and try again.';
    } else {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId > 0) {
            $stmt = $conn->prepare('DELETE FROM pharmacy_stock WHERE id = ?');
            $stmt->bind_param('i', $deleteId);
            if ($stmt->execute()) {
                $successMessage = 'Medicine deleted successfully.';
            } else {
                $errorMessage = 'Unable to delete medicine right now.';
            }
            $stmt->close();
        }
    }
}

$search = trim($_GET['search'] ?? '');
$query = 'SELECT id, drug_name AS name, quantity, selling_price, unit, supplier, expiry_date FROM pharmacy_stock';
if ($search !== '') {
    $query .= ' WHERE drug_name LIKE ?';
    $stmt = $conn->prepare($query . ' ORDER BY drug_name ASC');
    $like = "%$search%";
    $stmt->bind_param('s', $like);
} else {
    $stmt = $conn->prepare($query . ' ORDER BY drug_name ASC');
}
$stmt->execute();
$result = $stmt->get_result();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="content-area">
    <h2><i class="fa fa-pills"></i> Pharmacy Stock Management</h2>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <form method="GET" class="d-flex mb-2">
            <input type="text" id="searchInput" name="search" class="form-control me-2" placeholder="Search medicine..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-primary">Search</button>
            <a href="view_stock.php" class="btn btn-secondary ms-2">Reset</a>
        </form>

        <a href="add_stock.php" class="btn btn-success mb-2"><i class="fa fa-plus"></i> Add Stock</a>
        <a href="../procurement/purchase_orders.php" class="btn btn-warning mb-2 ms-2"><i class="fa fa-shopping-cart"></i> Procurement / PO</a>
        <a href="?export=excel" class="btn btn-info mb-2 ms-2"><i class="fa fa-file-excel"></i> Export Stock Sheet</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped" id="stockTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Medicine Name</th>
                    <th>Supplier</th>
                    <th>Unit</th>
                    <th>Quantity</th>
                    <th>Selling Price (KES)</th>
                    <th>Stock Value (KES)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; $totalValue = 0; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        $stockValue = $row['quantity'] * $row['selling_price'];
                        $totalValue += $stockValue;
                        $lowStockAlert = ((int)$row['quantity'] <= 10) ? 'table-danger' : '';
                    ?>
                    <tr class="<?= $lowStockAlert ?>">
                        <td><?= $count++; ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['name']); ?></strong>
                            <?php if((int)$row['quantity'] <= 10): ?>
                                <br><small class="text-danger font-weight-bold"><i class="fa fa-exclamation-triangle"></i> Reorder Soon</small>
                            <?php endif; ?>
                        </td>
                        <td><small><?= htmlspecialchars($row['supplier'] ?? 'N/A'); ?></small></td>
                        <td><?= htmlspecialchars($row['unit']); ?></td>
                        <td><?= (int)$row['quantity']; ?></td>
                        <td><?= number_format((float)$row['selling_price'], 2); ?></td>
                        <td><?= number_format((float)$stockValue, 2); ?></td>
                        <td>
                            <a href="../procurement/purchase_orders.php?item=<?= urlencode($row['name']); ?>" class="btn btn-dark btn-sm" title="Reorder Item">
                                <i class="fa fa-truck"></i> Reorder
                            </a>
                            <a href="adjust_price.php?id=<?= (int)$row['id']; ?>" class="btn btn-warning btn-sm"><i class="fa fa-edit"></i> Adjust Price</a>
                            <a href="stock_take.php?id=<?= (int)$row['id']; ?>" class="btn btn-info btn-sm"><i class="fa fa-boxes"></i> Stock Take</a>
                            <?php if (can_module_action($conn, 'pharmacy', 'delete')): ?><form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this medicine?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </form><?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-end">Total Stock Value:</th>
                    <th colspan="2"><?= number_format((float)$totalValue, 2); ?></th>
                </tr>
            </tfoot>
        </table>
    <?php else: ?>
        <p>No medications found.</p>
    <?php endif; ?>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById('searchInput');
    filter = input.value.toUpperCase();
    table = document.getElementById('stockTable');
    if (!table) return;
    tr = table.getElementsByTagName('tr');

    for (i = 1; i < tr.length; i++) {
        if (tr[i].getElementsByTagName('td').length > 0) {
            var nameCol = tr[i].getElementsByTagName('td')[1];
            var supplierCol = tr[i].getElementsByTagName('td')[2];

            if (nameCol || supplierCol) {
                txtValue = (nameCol.textContent || nameCol.innerText) + ' ' + (supplierCol.textContent || supplierCol.innerText);
                tr[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
            }
        }
    }
});
</script>

<?php
$stmt->close();
include __DIR__ . '/../includes/footer.php';
?>
