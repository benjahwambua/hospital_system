<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_once __DIR__ . '/../helpers/cashier.php';
require_login();

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
                <p class="text-muted mb-0">Invoice-linked payments recorded through the Central Cashier.</p>
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
<!-- Refund links are provided by cashier/refund.php from the payment action column. -->
