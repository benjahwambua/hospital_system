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

$from = trim((string)($_GET['from'] ?? date('Y-m-d')));
$to = trim((string)($_GET['to'] ?? date('Y-m-d')));
$method = trim((string)($_GET['method'] ?? 'All'));
$search = trim((string)($_GET['search'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
if ($from > $to) { $tmp=$from; $from=$to; $to=$tmp; }

$where = ["DATE(pay.created_at) BETWEEN ? AND ?"];
$types = 'ss';
$params = [$from, $to];

if ($method !== '' && $method !== 'All') {
    $where[] = "LOWER(pay.method) = LOWER(?)";
    $types .= 's';
    $params[] = $method;
}
if ($search !== '') {
    $where[] = "(p.full_name LIKE ? OR p.patient_number LIKE ? OR w.full_name LIKE ? OR CAST(pay.invoice_id AS CHAR) LIKE ? OR pay.reference LIKE ?)";
    $types .= 'sssss';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

$sql = "SELECT pay.id, pay.invoice_id, pay.amount, pay.method, pay.reference, pay.created_at,
               p.full_name AS patient_name, p.patient_number,
               w.full_name AS walkin_name, v.visit_number,
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
    foreach ($params as $key => $value) $bind[] = &$params[$key];
    call_user_func_array([$stmt, 'bind_param'], $bind);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $payments[] = $row;
    $stmt->close();
}

$totalCollected=0.0; $cashTotal=0.0; $mpesaTotal=0.0; $otherTotal=0.0;
foreach ($payments as $payment) {
    $amount=(float)$payment['amount']; $totalCollected += $amount;
    $m=strtolower((string)$payment['method']);
    if (strpos($m,'mpesa')!==false || strpos($m,'m-pesa')!==false) $mpesaTotal += $amount;
    elseif (strpos($m,'cash')!==false) $cashTotal += $amount;
    else $otherTotal += $amount;
}
$paymentCount=count($payments);
$averagePayment=$paymentCount ? $totalCollected/$paymentCount : 0;
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

.payment-workspace{padding:26px 24px 42px}
.payment-hero{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:20px}
.payment-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;font-weight:800;color:#667085;margin-bottom:5px}
.payment-hero h1{font-size:26px;font-weight:800;margin:0 0 5px}.payment-hero p{margin:0;color:#667085;font-size:14px}
.payment-actions{display:flex;gap:8px;flex-wrap:wrap}
.payment-stat{padding:18px 20px;height:100%}
.payment-stat .label{font-size:10px;text-transform:uppercase;letter-spacing:.8px;font-weight:800;color:#667085}
.payment-stat .value{font-size:21px;font-weight:800;color:#25324a;margin-top:4px;white-space:nowrap}
.payment-stat .hint{font-size:12px;color:#98a2b3;margin-top:2px}
.payment-icon{width:38px;height:38px;border-radius:10px;background:#eef5fb;color:#075b9d;display:flex;align-items:center;justify-content:center}
.payment-filter{padding:18px 20px}
.payment-filter label{font-size:10px;text-transform:uppercase;letter-spacing:.7px;font-weight:800;color:#667085}
.payment-filter .btn{height:38px}
.payment-person{font-weight:800;color:#25324a}.payment-meta{font-size:11px;color:#98a2b3}
.payment-amount{font-weight:800;color:#25324a;white-space:nowrap}.payment-ref{font-family:monospace;font-size:12px;color:#475467}
.payment-method{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800}
.payment-method.cash{background:#ecfdf3;color:#067647}.payment-method.mpesa{background:#ecfdf3;color:#087443}.payment-method.other{background:#f2f4f7;color:#475467}
.payment-actions-cell{min-width:220px}.payment-actions-cell .btn{margin:2px 0}
.payment-empty{padding:65px 20px;text-align:center;color:#98a2b3}.payment-empty i{font-size:38px;color:#c5ccd6;margin-bottom:12px}
.payment-period{font-size:12px;color:#667085;background:#f8fafc;border:1px solid #edf0f5;padding:7px 10px;border-radius:8px}
@media(max-width:900px){.payment-hero{flex-direction:column}.payment-workspace{padding:20px 12px 35px}}
</style>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content">
<div class="container-fluid payment-workspace">
    <div class="payment-hero">
        <div>
            <div class="payment-kicker">Finance · Collections</div>
            <h1><i class="fas fa-receipt mr-2"></i> Payment History</h1>
            <p>Review received payments, payment methods, invoices and cashier activity for the selected period.</p>
        </div>
        <div class="payment-actions">
            <span class="payment-period"><i class="far fa-calendar mr-1"></i><?= date('d M Y', strtotime($from)) ?> — <?= date('d M Y', strtotime($to)) ?></span>
            <a href="/hospital_system/cashier/index.php" class="btn btn-primary"><i class="fas fa-cash-register mr-1"></i> Central Cashier</a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?><div class="alert alert-success"><i class="fas fa-circle-check mr-1"></i> Payment received successfully.</div><?php endif; ?>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3"><div class="card h-100"><div class="payment-stat d-flex justify-content-between"><div><div class="label">Total Collected</div><div class="value">KES <?= number_format($totalCollected,2) ?></div><div class="hint"><?= number_format($paymentCount) ?> payment(s)</div></div><div class="payment-icon"><i class="fas fa-coins"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="card h-100"><div class="payment-stat d-flex justify-content-between"><div><div class="label">Cash</div><div class="value">KES <?= number_format($cashTotal,2) ?></div><div class="hint">Cash collections</div></div><div class="payment-icon"><i class="fas fa-money-bill-wave"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="card h-100"><div class="payment-stat d-flex justify-content-between"><div><div class="label">M-Pesa</div><div class="value">KES <?= number_format($mpesaTotal,2) ?></div><div class="hint">Mobile collections</div></div><div class="payment-icon"><i class="fas fa-mobile-screen-button"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="card h-100"><div class="payment-stat d-flex justify-content-between"><div><div class="label">Average Payment</div><div class="value">KES <?= number_format($averagePayment,2) ?></div><div class="hint"><?= $otherTotal > 0 ? 'Other methods: KES '.number_format($otherTotal,2) : 'No other methods' ?></div></div><div class="payment-icon"><i class="fas fa-chart-line"></i></div></div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div><strong>Payment Filters</strong><div class="small text-muted">Narrow the collection history by date, method or patient/invoice.</div></div>
            <a href="payment_history.php" class="btn btn-sm btn-light">Reset</a>
        </div>
        <div class="payment-filter">
            <form method="get" class="row align-items-end">
                <div class="col-lg-2 col-md-6 mb-2"><label>From</label><input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>"></div>
                <div class="col-lg-2 col-md-6 mb-2"><label>To</label><input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>"></div>
                <div class="col-lg-2 col-md-6 mb-2"><label>Method</label><select name="method" class="form-control"><option value="All">All methods</option><option value="Cash" <?= strcasecmp($method,'Cash')===0?'selected':'' ?>>Cash</option><option value="Mpesa" <?= strcasecmp($method,'Mpesa')===0?'selected':'' ?>>M-Pesa</option></select></div>
                <div class="col-lg-4 col-md-6 mb-2"><label>Search</label><input type="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Patient, number, invoice or reference"></div>
                <div class="col-lg-2 mb-2"><button class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> Apply Filters</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div><strong>Recorded Payments</strong><div class="small text-muted"><?= number_format($paymentCount) ?> transaction(s) in this view</div></div>
            <span class="font-weight-bold text-primary">KES <?= number_format($totalCollected,2) ?></span>
        </div>
        <div class="card-body p-0">
        <?php if (!$payments): ?>
            <div class="payment-empty"><i class="fas fa-receipt d-block"></i><h5 class="mb-1">No payments found</h5><div>Try another date range, method or search term.</div></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th class="pl-4">Date / Time</th><th>Receipt / Reference</th><th>Invoice</th><th>Patient</th><th>Visit</th><th>Method</th><th>Amount</th><th>Cashier</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach($payments as $payment):
                        $m=strtolower((string)$payment['method']);
                        $methodClass=(strpos($m,'mpesa')!==false || strpos($m,'m-pesa')!==false)?'mpesa':(strpos($m,'cash')!==false?'cash':'other');
                    ?>
                        <tr>
                            <td class="pl-4"><div class="payment-person"><?= date('d M Y',strtotime($payment['created_at'])) ?></div><div class="payment-meta"><?= date('H:i',strtotime($payment['created_at'])) ?></div></td>
                            <td><span class="payment-ref"><?= htmlspecialchars($payment['reference'] ?: '—') ?></span></td>
                            <td><a href="/hospital_system/billing/view_invoice.php?id=<?= (int)$payment['invoice_id'] ?>">#<?= str_pad((string)$payment['invoice_id'],5,'0',STR_PAD_LEFT) ?></a></td>
                            <td><div class="payment-person"><?= htmlspecialchars($payment['patient_name'] ?: ($payment['walkin_name'] ?: 'Walk-in Customer')) ?></div><div class="payment-meta"><?= htmlspecialchars($payment['patient_number'] ?: 'Walk-in / No patient number') ?></div></td>
                            <td><?= htmlspecialchars($payment['visit_number'] ?: 'Unassigned') ?></td>
                            <td><span class="payment-method <?= $methodClass ?>"><?= htmlspecialchars($payment['method'] ?: 'Unknown') ?></span></td>
                            <td class="payment-amount">KES <?= number_format((float)$payment['amount'],2) ?></td>
                            <td><?= htmlspecialchars($payment['cashier_name'] ?: 'Legacy / Unassigned') ?></td>
                            <td class="payment-actions-cell">
                                <a class="btn btn-sm btn-outline-primary" target="_blank" href="/hospital_system/billing/view_invoice.php?id=<?= (int)$payment['invoice_id'] ?>&print=1"><i class="fas fa-print mr-1"></i> View / Print</a>
                                <?php if(can_approve($conn,'finance')): ?><a class="btn btn-sm btn-outline-danger" href="/hospital_system/cashier/refund.php?id=<?= (int)$payment['id'] ?>"><i class="fas fa-rotate-left mr-1"></i> Refund</a><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><th colspan="6" class="text-right">Total Collected</th><th>KES <?= number_format($totalCollected,2) ?></th><th colspan="2"></th></tr></tfoot>
                </table>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>