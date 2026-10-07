<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'finance_admin', 'view');
require_role(['admin','cashier','accountant']);
$canFinanceEdit = can_module_action($conn, 'finance_admin', 'edit');
$canFinanceDelete = can_module_action($conn, 'finance_admin', 'delete');
$canFinanceCreate = can_module_action($conn, 'finance', 'create');

// --- 1. ADMIN CORRECTION HANDLER (With Basic CSRF/Role Protection) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_invoice_id'])) {
    require_module_access($conn, 'finance_admin', 'delete');
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); exit('Invalid security token.'); }

    $del_id = (int)$_POST['delete_invoice_id'];
    
    $conn->begin_transaction();
    try {
        if ($del_id <= 0) throw new Exception('Invalid invoice.');

        $lock = $conn->prepare("SELECT id FROM invoices WHERE id = ? LIMIT 1 FOR UPDATE");
        if (!$lock) throw new Exception('Unable to validate invoice.');
        $lock->bind_param('i', $del_id);
        $lock->execute();
        $invoice = $lock->get_result()->fetch_assoc();
        $lock->close();
        if (!$invoice) throw new Exception('Invoice not found.');

        $checks = [
            'payments' => "SELECT COUNT(*) AS c FROM payments WHERE invoice_id = ?",
            'payment_refunds' => "SELECT COUNT(*) AS c FROM payment_refunds WHERE invoice_id = ?",
            'accounting_entries' => "SELECT COUNT(*) AS c FROM accounting_entries WHERE invoice_id = ?"
        ];
        foreach ($checks as $label => $sql) {
            $check = $conn->prepare($sql);
            if (!$check) throw new Exception('Unable to validate invoice financial history.');
            $check->bind_param('i', $del_id);
            $check->execute();
            $count = (int)($check->get_result()->fetch_assoc()['c'] ?? 0);
            $check->close();
            if ($count > 0) throw new Exception("Invoice #$del_id has financial activity and cannot be deleted.");
        }

        $stmt = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
        if (!$stmt) throw new Exception('Unable to remove invoice items.');
        $stmt->bind_param('i', $del_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
        if (!$stmt) throw new Exception('Unable to remove invoice.');
        $stmt->bind_param('i', $del_id);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) throw new Exception('Invoice not found.');
        $stmt->close();

        if (function_exists('audit')) {
            audit('invoice_deleted', 'invoice_id=' . $del_id);
        }

        $conn->commit();
        $_SESSION['success'] = "Invoice #$del_id deleted successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        error_log('HMS invoice deletion failed: ' . $e->getMessage());
        $_SESSION['error'] = 'Unable to delete the invoice. No changes were made.';
    }
    header("Location: view_bills.php");
    exit();
}

// --- 2. RANGE & FILTER LOGIC ---
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
$to_date   = $_GET['to_date'] ?? date('Y-m-d');
$status    = $_GET['status'] ?? 'All';

$allowedStatuses = ['All', 'Paid', 'Unpaid', 'Partial'];
if (!in_array($status, $allowedStatuses, true)) $status = 'All';
if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $from_date)) $from_date = date('Y-m-d', strtotime('-30 days'));
if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $to_date)) $to_date = date('Y-m-d');
if ($from_date > $to_date) { [$from_date, $to_date] = [$to_date, $from_date]; }

$where_clauses = ["DATE(i.created_at) BETWEEN ? AND ?"];
$where_sql = implode(' AND ', $where_clauses);

// --- 3. THE MASTER QUERY (Optimized with aggregated payments & walk-in exclusion) ---
$query = "
    SELECT i.*, 
           p.full_name as patient_name, 
           p.is_walkin,
           w.full_name as walkin_name,
           COALESCE(items.items_total, i.total, 0) as total,
           COALESCE(pay.total_paid, 0) as amount_paid
    FROM invoices i
    LEFT JOIN (
        SELECT invoice_id, SUM(total) AS items_total
        FROM invoice_items
        GROUP BY invoice_id
    ) items ON i.id = items.invoice_id
    LEFT JOIN patients p ON i.patient_id = p.id
    LEFT JOIN walkin_customers w ON i.walkin_id = w.id
    LEFT JOIN (
        -- Payments belong to invoices, not patients. Aggregating by patient
        -- caused one patient's old payments to reduce another invoice.
        SELECT p.invoice_id,
               SUM(p.amount) - COALESCE((
                   SELECT SUM(r.amount)
                   FROM payment_refunds r
                   WHERE r.invoice_id = p.invoice_id AND r.status='Approved'
               ),0) AS total_paid
        FROM payments p
        WHERE p.invoice_id IS NOT NULL
        GROUP BY p.invoice_id
    ) pay ON i.id = pay.invoice_id
    WHERE $where_sql
    ORDER BY i.created_at DESC";

$queryStmt = $conn->prepare($query);
if (!$queryStmt) {
    error_log('HMS billing report query failed: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load billing records.');
}
$queryStmt->bind_param('ss', $from_date, $to_date);
$queryStmt->execute();
$result = $queryStmt->get_result();
$invoices_data = [];
$total_invoices = 0;
$total_revenue = 0;
$total_outstanding = 0;
$paid_count = 0;
$unpaid_count = 0;
$walkin_excluded_count = 0;

while ($row = $result->fetch_assoc()) { 
    // Walk-ins are exempt from consultation, but their actual laboratory,
    // pharmacy and other service charges must remain visible in billing.
    $row['total'] = (float)$row['total'];
    $row['amount_paid'] = (float)$row['amount_paid'];
    $outstanding = max($row['total'] - $row['amount_paid'], 0);
    if ($outstanding <= 0.00001 && $row['total'] > 0) {
        $row['display_status'] = 'Paid';
    } elseif ($row['amount_paid'] > 0) {
        $row['display_status'] = 'Partial';
    } else {
        $row['display_status'] = 'Unpaid';
    }

    // Filter using the derived financial state, not the legacy invoices.status field.
    if ($status !== 'All' && strcasecmp($row['display_status'], $status) !== 0) {
        continue;
    }

    $row['balance'] = $outstanding;
    $invoices_data[] = $row;
    $total_invoices++;
    $total_revenue += $row['total'];
    $total_outstanding += $outstanding;

    if ($row['display_status'] === 'Paid') {
        $paid_count++;
    } else {
        $unpaid_count++;
    }
}

if ($queryStmt) $queryStmt->close();

// --- 4. CSV EXPORT LOGIC ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Billing_Report_'.$from_date.'_to_'.$to_date.'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Inv #', 'Date', 'Customer', 'Type', 'Total', 'Paid', 'Balance', 'Status']);
    
    foreach ($invoices_data as $row) {
        $name = $row['patient_name'] ?: ($row['walkin_name'] ?: 'Unknown');
        $balance = $row['total'] - $row['amount_paid'];
        fputcsv($output, [$row['id'], $row['created_at'], $name, ($row['patient_id'] ? 'In-Patient' : 'Walk-in'), $row['total'], $row['amount_paid'], $balance, $row['display_status']]);
    }
    fclose($output);
    exit();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<link rel="stylesheet" href="../assets/css/finance_modules.css">
<style>
.billing-page{padding:26px 24px 42px;background:#f5f7fb;min-height:calc(100vh - 72px)}
.billing-shell{width:100%;max-width:none;margin:0}
.billing-hero{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:16px;padding:24px 26px;margin-bottom:20px;box-shadow:0 10px 26px rgba(6,59,115,.14)}
.billing-hero h1{font-size:25px;margin:0 0 5px;font-weight:800}.billing-hero p{margin:0;color:#d9edff;font-size:13px}
.billing-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}.billing-actions .btn{border-radius:8px;font-weight:700}
.billing-actions .btn-light{color:#063b73}.billing-card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden}
.stat-card{height:100%;padding:18px 20px;border-left:4px solid #075b9d}.stat-label{font-size:10px;text-transform:uppercase;letter-spacing:.7px;font-weight:800;color:#667085}.stat-value{font-size:21px;font-weight:800;color:#25324a;margin-top:4px}
.filter-card{padding:18px}.filter-card label{font-size:11px;text-transform:uppercase;letter-spacing:.4px;font-weight:800;color:#667085}.filter-card .form-control{border-color:#d7dee8;border-radius:8px}
.billing-table{margin:0}.billing-table thead th{background:#f8fafc;color:#667085;border-top:0;font-size:10px;text-transform:uppercase;letter-spacing:.45px;white-space:nowrap}.billing-table td{border-color:#edf0f5;vertical-align:middle;font-size:13px}.billing-table tbody tr:hover{background:#f8fbff}
.invoice-number{font-weight:800;color:#075b9d}.customer-name{font-weight:700;color:#25324a}.amount{font-weight:700;white-space:nowrap}.balance-due{color:#c0392b;font-weight:800}.balance-clear{color:#667085}
.status-pill{display:inline-block;padding:5px 10px;border-radius:999px;font-size:10px;font-weight:800;text-transform:uppercase}.status-paid{background:#e8f5e9;color:#207438}.status-partial{background:#fff4d6;color:#8a6200}.status-unpaid{background:#fdeaea;color:#b42318}
@media(max-width:768px){.billing-page{padding:18px 12px}.billing-table{min-width:1050px}}
</style>

<div class="billing-page"><div class="billing-shell">
        <div class="billing-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div><div class="small text-uppercase font-weight-bold" style="letter-spacing:1.4px;color:#9edcff">Finance & Billing</div><h1>Invoices & Bills</h1><p>Monitor invoices, collections, balances and payment status from one workspace.</p></div>
                <i class="fas fa-file-invoice-dollar fa-2x" style="color:#9edcff"></i>
            </div>
            <div class="billing-actions">
                <a href="/hospital_system/reports/sales_report.php" class="btn btn-light btn-sm"><i class="fas fa-chart-line mr-1"></i> Sales Report</a>
                <a href="/hospital_system/accounting/dashboard.php" class="btn btn-outline-light btn-sm"><i class="fas fa-tachometer-alt mr-1"></i> Finance Dashboard</a>
                <button class="btn btn-outline-light btn-sm" onclick="exportCSV()"><i class="fas fa-file-csv mr-1"></i> Export CSV</button>
                <a href="/hospital_system/pharmacy/sell_medicine.php" class="btn btn-light btn-sm"><i class="fas fa-plus mr-1"></i> New Sale</a>
            </div>
        </div>

        <!-- Quick Stats Summary -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="billing-card stat-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label">Total Invoices</div>
                                <div class="stat-value"><?= $total_invoices ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="billing-card stat-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label">Total Billed</div>
                                <div class="stat-value">KSH <?= number_format($total_revenue, 2) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="billing-card stat-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label">Outstanding</div>
                                <div class="stat-value">KSH <?= number_format($total_outstanding, 2) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="billing-card stat-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label">Paid / Unpaid</div>
                                <div class="stat-value"><?= $paid_count ?> / <?= $unpaid_count ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="billing-card mb-4">
            <div class="filter-card">
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
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <div class="billing-card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center"><div><strong>Invoice Register</strong><div class="small text-muted">Showing <?= count($invoices_data) ?> invoice(s) for the selected period.</div></div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table billing-table table-hover" id="billingTable" width="100%">
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
                                $outstanding = $row['balance'] ?? max($row['total'] - $row['amount_paid'], 0);
                            ?>
                            <tr>
                                <td><span class="invoice-number">#<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                                <td class="small"><?= date("d-M-y H:i", strtotime($row['created_at'])) ?></td>
                                <td><div class="customer-name"><?= $name ?> <span class="badge badge-light border"><?= $is_patient ? 'Patient' : 'Walk-in' ?></span></div></td>
                                <td>KSH <?= number_format($row['total'], 2) ?></td>
                                <td class="text-success">KSH <?= number_format($row['amount_paid'], 2) ?></td>
                                <td class="<?= $outstanding > 0 ? 'balance-due' : 'balance-clear' ?>">
                                    KSH <?= number_format($outstanding, 2) ?>
                                </td>
                                <td>
                                    <span class="status-pill status-<?= strtolower($row['display_status']) ?>"><?= htmlspecialchars($row['display_status']) ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/hospital_system/billing/view_invoice.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary" title="View Invoice">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($is_patient): ?>
                                            <a href="/hospital_system/patients/patient_dashboard.php?id=<?= $row['patient_id'] ?>" class="btn btn-outline-success" title="Patient Dashboard">
                                                <i class="fas fa-user"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($outstanding > 0): ?>
                                            <a href="/hospital_system/billing/pay_invoice.php?id=<?= $row['id'] ?>" class="btn btn-outline-warning" title="Record Payment">
                                                <i class="fas fa-credit-card"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="/hospital_system/accounting/ledger.php?invoice_id=<?= $row['id'] ?>" class="btn btn-outline-info" title="View in Ledger">
                                            <i class="fas fa-book"></i>
                                        </a>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="/hospital_system/billing/print_invoice.php?id=<?= $row['id'] ?>" target="_blank">
                                                    <i class="fas fa-print"></i> Print Invoice
                                                </a>
                                                <?php if ($canFinanceEdit): ?><a class="dropdown-item" href="/hospital_system/billing/view_invoice.php?id=<?= $row['id'] ?>&edit=1">
                                                    <i class="fas fa-edit"></i> Edit Invoice
                                                </a><?php endif; ?>
                                                <?php if ($canFinanceCreate && $row['display_status'] !== 'Paid'): ?>
                                                    <a class="dropdown-item" href="#" onclick="markAsPaid(<?= $row['id'] ?>)">
                                                        <i class="fas fa-check"></i> Mark as Paid
                                                    </a>
                                                <?php endif; ?>
                                                <div class="dropdown-divider"></div>
                                                <?php if ($canFinanceDelete): ?><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?= $row['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Delete Invoice
                                                </a><?php endif; ?>
                                            </div>
                                        </div>
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
</div></div>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="delete_invoice_id" id="delete_id_input">
</form>

<script>
function confirmDelete(id) {
    if (confirm("CRITICAL: Delete Invoice #" + id + "? This will erase the bill and all itemized history. This cannot be undone.")) {
        document.getElementById('delete_id_input').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function markAsPaid(id) {
    if (confirm("Mark Invoice #" + id + " as fully paid? This will update the invoice status.")) {
        // Create a form to submit the mark as paid request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/hospital_system/billing/pay_invoice.php';
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = <?= json_encode(csrf_token()) ?>;
        form.appendChild(csrfInput);

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'invoice_id';
        idInput.value = id;
        
        const amountInput = document.createElement('input');
        amountInput.type = 'hidden';
        amountInput.name = 'amount';
        amountInput.value = '0'; // Will be calculated as remaining balance
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = 'payment_mode';
        methodInput.value = 'Cash';
        
        const markPaidInput = document.createElement('input');
        markPaidInput.type = 'hidden';
        markPaidInput.name = 'mark_paid';
        markPaidInput.value = '1';
        
        form.appendChild(idInput);
        form.appendChild(amountInput);
        form.appendChild(methodInput);
        form.appendChild(markPaidInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function exportCSV() {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('export', 'csv');
    window.location.href = "?" + urlParams.toString();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
