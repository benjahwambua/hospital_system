
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
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_once __DIR__ . '/../helpers/cashier.php';
require_login();
require_module_access($conn, 'finance', 'view');

$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isSuper = !empty($_SESSION['is_super']) && (int)$_SESSION['is_super'] === 1;
if (!$isSuper && !in_array($role, ['admin', 'cashier'], true)) {
    http_response_code(403);
    die('Access denied. Only the cashier or administrator can access payment history.');
}

$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');
$method = trim((string)($_GET['method'] ?? 'All'));
$search = trim((string)($_GET['search'] ?? ''));

$where = ["DATE(pay.created_at) BETWEEN ? AND ?"];
$types = 'ss';
$params = [$from, $to];

if ($method !== '' && $method !== 'All') {
    $where[] = "LOWER(pay.method) = LOWER(?)";
    $types .= 's';
    $params[] = $method;
}
if ($search !== '') {
    $where[] = "(p.full_name LIKE ? OR p.patient_number LIKE ? OR CAST(pay.invoice_id AS CHAR) LIKE ? OR pay.reference LIKE ?)";
    $types .= 'ssss';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}

$sql = "SELECT pay.id, pay.invoice_id, pay.amount, pay.method, pay.reference, pay.created_at,
               p.full_name AS patient_name, p.patient_number,
               v.visit_number,
               cs.id AS shift_id, cs.opened_at AS shift_opened_at,
               u.full_name AS cashier_name
        FROM payments pay
        LEFT JOIN patients p ON p.id = pay.patient_id
        LEFT JOIN invoices i ON i.id = pay.invoice_id
        LEFT JOIN visits v ON v.id = i.visit_id
        LEFT JOIN cashier_shifts cs ON cs.id = pay.cashier_shift_id
        LEFT JOIN users u ON u.id = cs.cashier_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY pay.created_at DESC, pay.id DESC";

$payments = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    $bind = [$types];
    foreach ($params as $key => $value) {
        $bind[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();
}

$totalCollected = 0.0;
$cashTotal = 0.0;
$mpesaTotal = 0.0;
foreach ($payments as $payment) {
    $amount = (float)$payment['amount'];
    $totalCollected += $amount;
    $m = strtolower((string)$payment['method']);
    if (strpos($m, 'mpesa') !== false || strpos($m, 'm-pesa') !== false) {
        $mpesaTotal += $amount;
    } elseif (strpos($m, 'cash') !== false) {
        $cashTotal += $amount;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-1 text-gray-800"><i class="fas fa-receipt"></i> Payment History</h2>
                
            </div>
            <a href="/hospital_system/cashier/index.php" class="btn btn-primary"><i class="fas fa-cash-register"></i> Central Cashier</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Payment received successfully.</div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-4 mb-3"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Collected</div>
                <div class="h4 mb-0 font-weight-bold">KSH <?= number_format($totalCollected, 2) ?></div>
            </div></div></div>
            <div class="col-md-4 mb-3"><div class="card border-left-success shadow h-100 py-2"><div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cash</div>
                <div class="h4 mb-0 font-weight-bold">KSH <?= number_format($cashTotal, 2) ?></div>
            </div></div></div>
            <div class="col-md-4 mb-3"><div class="card border-left-info shadow h-100 py-2"><div class="card-body">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">M-Pesa</div>
                <div class="h4 mb-0 font-weight-bold">KSH <?= number_format($mpesaTotal, 2) ?></div>
            </div></div></div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="get" class="row align-items-end">
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">From</label>
                        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">To</label>
                        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">Method</label>
                        <select name="method" class="form-control">
                            <option value="All">All</option>
                            <option value="Cash" <?= strcasecmp($method, 'Cash') === 0 ? 'selected' : '' ?>>Cash</option>
                            <option value="Mpesa" <?= strcasecmp($method, 'Mpesa') === 0 ? 'selected' : '' ?>>M-Pesa</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="small font-weight-bold">Search</label>
                        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Patient, patient number or invoice #">
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-info btn-block" type="submit"><i class="fas fa-filter"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Recorded Payments</h6></div>
            <div class="card-body">
                <?php if (!$payments): ?>
                    <div class="text-center text-muted py-5"><i class="fas fa-receipt fa-3x mb-3"></i><h5>No payments found for the selected period.</h5></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th><th>Receipt / Ref</th><th>Invoice</th><th>Patient</th><th>Visit</th><th>Method</th><th>Amount</th><th>Cashier</th><th>Invoice / Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= date('d-M-y H:i', strtotime($payment['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($payment['reference'] ?: '—') ?></td>
                                    <td><a href="/hospital_system/billing/view_invoice.php?id=<?= (int)$payment['invoice_id'] ?>">#<?= str_pad((string)$payment['invoice_id'], 5, '0', STR_PAD_LEFT) ?></a></td>
                                    <td>
                                        <strong><?= htmlspecialchars($payment['patient_name'] ?: 'Unknown') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($payment['patient_number'] ?: 'N/A') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($payment['visit_number'] ?: 'Legacy / Unassigned') ?></td>
                                    <td><span class="badge badge-<?= stripos((string)$payment['method'], 'mpesa') !== false ? 'info' : 'success' ?>"><?= htmlspecialchars($payment['method']) ?></span></td>
                                    <td class="font-weight-bold">KSH <?= number_format((float)$payment['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($payment['cashier_name'] ?: 'Legacy / Unassigned') ?></td>
                                    <td>
    <a class="btn btn-sm btn-outline-primary" target="_blank"
       href="/hospital_system/billing/view_invoice.php?id=<?= (int)$payment['invoice_id'] ?>&print=1">
        <i class="fas fa-print"></i> View & Print Invoice
    </a>
    <?php if(can_approve($conn, 'finance')): ?><a class="btn btn-sm btn-outline-danger" href="/hospital_system/cashier/refund.php?id=<?= (int)$payment['id'] ?>">
        <i class="fas fa-undo"></i> Refund
    </a><?php endif; ?>
</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
