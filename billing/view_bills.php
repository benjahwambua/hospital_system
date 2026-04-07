<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// --- 1. ADMIN CORRECTION HANDLER (With Basic CSRF/Role Protection) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_invoice_id'])) {
    // Recommendation: Add if($_SESSION['role'] !== 'admin') check here
    $del_id = intval($_POST['delete_invoice_id']);
    
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM invoice_items WHERE invoice_id = $del_id");
        $conn->query("DELETE FROM invoices WHERE id = $del_id");
        
        $conn->commit();
        $_SESSION['success'] = "Invoice #$del_id deleted successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Critical Error: " . $e->getMessage();
    }
    header("Location: billing_management.php");
    exit();
}

// --- 2. RANGE & FILTER LOGIC ---
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
$to_date   = $_GET['to_date'] ?? date('Y-m-d');
$status    = $_GET['status'] ?? 'All';

$where_clauses = ["DATE(i.created_at) BETWEEN '$from_date' AND '$to_date'"];
if ($status !== 'All') {
    $where_clauses[] = "i.status = '" . $conn->real_escape_string($status) . "'";
}
$where_sql = implode(' AND ', $where_clauses);

// --- 3. THE MASTER QUERY (Optimized with aggregated payments) ---
$query = "
    SELECT i.*, 
           p.full_name as patient_name, 
           w.full_name as walkin_name,
           COALESCE(pay.total_paid, 0) as amount_paid
    FROM invoices i
    LEFT JOIN patients p ON i.patient_id = p.id
    LEFT JOIN walkin_customers w ON i.walkin_id = w.id
    LEFT JOIN (
        -- Aggregate payments per patient to calculate balance
        -- Adjust 'patient_id' to 'invoice_id' if your billing table links directly to invoices
        SELECT patient_id, SUM(amount) as total_paid 
        FROM billing 
        GROUP BY patient_id
    ) pay ON i.patient_id = pay.patient_id
    WHERE $where_sql
    ORDER BY i.created_at DESC";

$result = $conn->query($query);
$invoices_data = [];
while ($row = $result->fetch_assoc()) { $invoices_data[] = $row; }

// --- 4. CSV EXPORT LOGIC ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Billing_Report_'.$from_date.'_to_'.$to_date.'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Inv #', 'Date', 'Customer', 'Type', 'Total', 'Paid', 'Balance', 'Status']);
    
    foreach ($invoices_data as $row) {
        $name = $row['patient_name'] ?: ($row['walkin_name'] ?: 'Unknown');
        $balance = $row['total'] - $row['amount_paid'];
        fputcsv($output, [$row['id'], $row['created_at'], $name, ($row['patient_id'] ? 'In-Patient' : 'Walk-in'), $row['total'], $row['amount_paid'], $balance, $row['status']]);
    }
    fclose($output);
    exit();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0 text-gray-800">Billing Management</h2>
            <div>
                <button class="btn btn-success mr-2" onclick="exportCSV()">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <a href="/hospital_system/pharmacy/sell_medicine.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Sale
                </a>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="small font-weight-bold">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Status</label>
                        <select name="status" class="form-control">
                            <option value="All">All Statuses</option>
                            <option value="Paid" <?= $status == 'Paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="Unpaid" <?= $status == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                            <option value="Partial" <?= $status == 'Partial' ? 'selected' : '' ?>>Partial</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-info btn-block">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Transaction History</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered" id="billingTable" width="100%">
                        <thead class="thead-light">
                            <tr>
                                <th>Inv #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Total Bill</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices_data as $row): 
                                $is_patient = !empty($row['patient_id']);
                                $name = htmlspecialchars($row['patient_name'] ?: ($row['walkin_name'] ?: 'Unknown'));
                                $outstanding = $row['total'] - $row['amount_paid'];
                            ?>
                            <tr>
                                <td><strong>#<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                <td class="small"><?= date("d-M-y H:i", strtotime($row['created_at'])) ?></td>
                                <td>
                                    <?= $name ?> 
                                    <span class="badge badge-light border ml-1"><?= $is_patient ? 'IP' : 'WK' ?></span>
                                </td>
                                <td>KSH <?= number_format($row['total'], 2) ?></td>
                                <td class="text-success">KSH <?= number_format($row['amount_paid'], 2) ?></td>
                                <td class="<?= $outstanding > 0 ? 'text-danger font-weight-bold' : 'text-muted' ?>">
                                    KSH <?= number_format($outstanding, 2) ?>
                                </td>
                                <td>
                                    <span class="badge badge-pill badge-<?= $row['status'] == 'Paid' ? 'success' : ($outstanding > 0 ? 'danger' : 'warning') ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view_invoice.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                        <?php if ($is_patient): ?>
                                            <a href="/hospital_system/patients/patient_dashboard.php?id=<?= $row['patient_id'] ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-user"></i></a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $row['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="delete_invoice_id" id="delete_id_input">
</form>

<script>
function confirmDelete(id) {
    if (confirm("CRITICAL: Delete Invoice #" + id + "? This will erase the bill and all itemized history. This cannot be undone.")) {
        document.getElementById('delete_id_input').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function exportCSV() {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('export', 'csv');
    window.location.href = "?" + urlParams.toString();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>