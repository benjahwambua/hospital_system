<?php
/* ==================================================
   1. LOGIC SECTION
================================================== */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// Handle Form Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock_submit'])) {
    $stock_id     = intval($_POST['stock_id']);
    $new_quantity = intval($_POST['new_quantity']);
    $new_price    = floatval($_POST['new_price']);
    $note         = trim($_POST['note'] ?? '');

    $current = $conn->query("SELECT quantity FROM pharmacy_stock WHERE id = $stock_id")->fetch_assoc();
    
    if ($current) {
        $quantity_change = $new_quantity - $current['quantity'];

        $stmt = $conn->prepare("UPDATE pharmacy_stock SET quantity = ?, selling_price = ? WHERE id = ?");
        $stmt->bind_param("ddi", $new_quantity, $new_price, $stock_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO stock_movements (stock_id, quantity_change, balance_after, user_id, note, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $user_id = $_SESSION['user_id'] ?? 0;
        $stmt->bind_param("iiiis", $stock_id, $quantity_change, $new_quantity, $user_id, $note);
        $stmt->execute();
        $stmt->close();
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    }
}

// Handle Search and Filtering
$search_term = $_GET['search'] ?? '';
$start_date  = $_GET['start_date'] ?? date('Y-m-d');
$end_date    = $_GET['end_date'] ?? date('Y-m-d');
$show_report = isset($_GET['filter_report']);

// Export Excel Functionality
if (isset($_GET['export'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Report_$start_date.xls");
    $q = "SELECT sm.*, ps.drug_name, u.username FROM stock_movements sm JOIN pharmacy_stock ps ON sm.stock_id = ps.id LEFT JOIN users u ON sm.user_id = u.id WHERE DATE(sm.created_at) BETWEEN ? AND ? ORDER BY sm.created_at DESC";
    $stmt = $conn->prepare($q);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $res = $stmt->get_result();
    echo "Date\tMedicine\tChange\tBalance\tUser\tNote\n";
    while ($h = $res->fetch_assoc()) echo "{$h['created_at']}\t{$h['drug_name']}\t{$h['quantity_change']}\t{$h['balance_after']}\t{$h['username']}\t{$h['note']}\n";
    exit;
}

// Fetch Inventory
$stmt = $conn->prepare("SELECT * FROM pharmacy_stock WHERE drug_name LIKE ? ORDER BY drug_name ASC");
$like = "%$search_term%";
$stmt->bind_param("s", $like);
$stmt->execute();
$res = $stmt->get_result();

// Stats for dynamic summary
$total_items = $conn->query("SELECT COUNT(*) as count FROM pharmacy_stock")->fetch_assoc()['count'];

// Fetch Movement Report
$history_res = null;
if ($show_report) {
    $stmt = $conn->prepare("SELECT sm.*, ps.drug_name, u.username FROM stock_movements sm JOIN pharmacy_stock ps ON sm.stock_id = ps.id LEFT JOIN users u ON sm.user_id = u.id WHERE DATE(sm.created_at) BETWEEN ? AND ? ORDER BY sm.created_at DESC");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $history_res = $stmt->get_result();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex align-items-center justify-content-between p-2 mb-4 bg-light border rounded">
        <span class="text-muted"><strong>Total Items:</strong> <?= $total_items ?></span>
        <span class="text-muted"><strong>Report Period:</strong> <?= $start_date ?> to <?= $end_date ?></span>
        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Stock updated successfully!</div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white"><h3>Stock Take / Adjustments</h3></div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search_term) ?>" placeholder="Search medicine name...">
                    <button type="submit" class="btn btn-secondary">Search</button>
                </div>
            </form>
            <table class="table table-bordered table-striped">
                <thead><tr><th>Medicine</th><th>Current Qty</th><th>New Qty</th><th>Price</th><th>Note</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while ($row = $res->fetch_assoc()): ?>
                        <tr>
                            <form method="POST">
                                <td class="medicine-name"><strong><?= htmlspecialchars($row['drug_name']) ?></strong></td>
                                <td><?= $row['quantity'] ?></td>
                                <td><input type="number" name="new_quantity" value="<?= $row['quantity'] ?>" class="form-control" required></td>
                                <td><input type="number" name="new_price" step="0.01" value="<?= $row['selling_price'] ?>" class="form-control" required></td>
                                <td><input type="text" name="note" class="form-control"></td>
                                <td>
                                    <input type="hidden" name="stock_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="update_stock_submit" class="btn btn-primary btn-sm">Update</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h3>Usage & Movement Report</h3>
            <?php if ($show_report): ?>
                <a href="?export=1&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search=<?= urlencode($search_term) ?>" class="btn btn-success btn-sm">Download Excel</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-4">
                <div class="col-md-3"><input type="date" name="start_date" class="form-control" value="<?= $start_date ?>"></div>
                <div class="col-md-3"><input type="date" name="end_date" class="form-control" value="<?= $end_date ?>"></div>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_term) ?>">
                <div class="col-md-2"><button type="submit" name="filter_report" class="btn btn-info w-100">Filter Report</button></div>
            </form>
            
            <?php if ($show_report && $history_res): ?>
                <table class="table table-hover">
                    <thead><tr><th>Date</th><th>Medicine</th><th>Change</th><th>Balance</th><th>User</th></tr></thead>
                    <tbody>
                        <?php while ($h = $history_res->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($h['created_at'])) ?></td>
                                <td><?= htmlspecialchars($h['drug_name']) ?></td>
                                <td><span class="badge bg-<?= $h['quantity_change'] >= 0 ? 'success' : 'danger' ?>"><?= $h['quantity_change'] ?></span></td>
                                <td><?= $h['balance_after'] ?></td>
                                <td><?= htmlspecialchars($h['username'] ?? 'System') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">Select dates and click "Filter Report" to view movements.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.querySelector('input[name="search"]').addEventListener('input', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.table-bordered tbody tr');
        rows.forEach(row => {
            let name = row.querySelector('.medicine-name').textContent.toLowerCase();
            row.style.display = name.includes(filter) ? '' : 'none';
        });
    });
    </script>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>