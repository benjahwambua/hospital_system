<?php
require_once __DIR__ . '/../config/config.php'; 
require_once __DIR__ . '/../includes/session.php'; 
require_login(); 
require_once __DIR__ . '/../includes/auth.php'; 
require_role(['admin','accountant']);

include __DIR__ . '/../includes/header.php'; 
include __DIR__ . '/../includes/sidebar.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-t');

// Logic for CSV Export
if (isset($_GET['export'])) {
    $filename = "Expenses_Report_" . $start_date . "_to_" . $end_date . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Category', 'Reference', 'Method', 'Description', 'Amount']);
    
    $query = "SELECT * FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ? ORDER BY expense_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        fputcsv($output, [$row['expense_date'], $row['category_id'], $row['reference_no'], $row['payment_method'], $row['description'], $row['amount']]);
    }
    fclose($output);
    exit();
}

$sql = "SELECT e.*, u.full_name as recorder 
        FROM expenses e 
        LEFT JOIN users u ON e.recorded_by = u.id 
        WHERE DATE(e.expense_date) BETWEEN ? AND ?
        ORDER BY e.expense_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result();

$sum_sql = "SELECT SUM(amount) as total FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ?";
$sum_stmt = $conn->prepare($sum_sql);
$sum_stmt->bind_param("ss", $start_date, $end_date);
$sum_stmt->execute();
$total_val = $sum_stmt->get_result()->fetch_assoc()['total'] ?? 0;
?>

<div class="main-content">
    <div class="bg-white shadow-sm border-bottom mb-4 p-3">
        <form method="GET" class="row align-items-center">
            <div class="col-md-3"><h4 class="font-weight-bold m-0 text-primary">Expense History</h4></div>
            <div class="col-md-3">
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-filter"></i> Filter</button>
                <a href="?export=1&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-success flex-fill"><i class="fas fa-file-csv"></i> CSV</a>
            </div>
        </form>
    </div>

    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-left-danger py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Expenditure</div>
                        <div class="h4 font-weight-bold text-gray-800">KSH <?= number_format($total_val, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($res->num_rows > 0): while($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= date("d M Y", strtotime($r['expense_date'])) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($r['category_id']) ?></span></td>
                            <td><?= htmlspecialchars($r['reference_no']) ?> <small class="text-muted d-block"><?= htmlspecialchars($r['payment_method']) ?></small></td>
                            <td class="text-muted"><?= htmlspecialchars($r['description']) ?></td>
                            <td class="text-right font-weight-bold text-danger">- <?= number_format($r['amount'], 2) ?></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-5">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>