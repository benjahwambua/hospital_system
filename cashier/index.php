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
.hms-workspace{padding:26px 24px 42px}
.hms-hero{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:20px}
.hms-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;font-weight:800;color:#667085;margin-bottom:5px}
.hms-hero h1{font-size:26px;font-weight:800;margin:0 0 5px}
.hms-hero p{margin:0;color:#667085;font-size:14px}
.hms-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.hms-stat{height:100%;padding:18px 20px}
.hms-stat .label{font-size:10px;text-transform:uppercase;letter-spacing:.8px;font-weight:800;color:#667085}
.hms-stat .value{font-size:22px;font-weight:800;color:#25324a;margin-top:4px}
.hms-stat .hint{font-size:12px;color:#98a2b3;margin-top:2px}
.hms-icon{width:38px;height:38px;border-radius:10px;background:#eef5fb;color:#075b9d;display:flex;align-items:center;justify-content:center}
.hms-filter{padding:18px 20px}
.hms-filter label{font-size:10px;text-transform:uppercase;letter-spacing:.7px;font-weight:800;color:#667085}
.hms-person{font-weight:800;color:#25324a}.hms-meta{font-size:11px;color:#98a2b3}
.hms-amount{font-weight:800;color:#25324a;white-space:nowrap}
.hms-danger{color:#b42318!important;font-weight:800}.hms-success{color:#067647!important;font-weight:800}
.hms-empty{padding:60px 20px;text-align:center;color:#98a2b3}.hms-empty i{font-size:38px;color:#c5ccd6;margin-bottom:12px}
@media(max-width:900px){.hms-hero{flex-direction:column}.hms-actions{justify-content:flex-start}.hms-workspace{padding:20px 12px 35px}}
</style>
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_once __DIR__ . '/../helpers/cashier.php';
require_login();
require_module_access($conn, 'finance', 'view');

if (!can_module_action($conn, 'finance', 'view')) {
    http_response_code(403);
    die('Access denied. Finance View permission is required.');
}

$today = date('Y-m-d');
$search = trim((string)($_GET['q'] ?? ''));
$searchEsc = $conn->real_escape_string($search);

$whereSearch = '';
if ($search !== '') {
    $whereSearch = " AND (i.invoice_number LIKE '%{$searchEsc}%' OR p.full_name LIKE '%{$searchEsc}%' OR p.patient_number LIKE '%{$searchEsc}%' OR wc.full_name LIKE '%{$searchEsc}%' OR wc.phone LIKE '%{$searchEsc}%')";
}

/*
 * Active cashier queue:
 * Only charges created/raised for today's operational date are shown here.
 * Historical unpaid invoices are deliberately kept out of this queue and
 * appear in Aged Receivables instead.
 */
$sql = "
    SELECT i.id, i.invoice_number, i.patient_id, i.visit_id, i.created_at,
           p.patient_number, COALESCE(p.full_name, wc.full_name) AS patient_name,
           COALESCE(p.is_walkin, 1*(i.walkin_id IS NOT NULL)) AS is_walkin,
           wc.phone AS walkin_phone,
           v.visit_number, v.visit_type, v.clinic_category, v.status AS visit_status,
           COALESCE(items.total, i.total, 0) AS bill_total,
           COALESCE(pay.paid, 0) AS paid_total
    FROM invoices i
    LEFT JOIN patients p ON p.id = i.patient_id
    LEFT JOIN walkin_customers wc ON wc.id = i.walkin_id
    LEFT JOIN visits v ON v.id = i.visit_id
    LEFT JOIN (
        SELECT invoice_id, SUM(total) AS total
        FROM invoice_items GROUP BY invoice_id
    ) items ON items.invoice_id = i.id
    LEFT JOIN (
        SELECT p.invoice_id,
               SUM(p.amount) - COALESCE((
                   SELECT SUM(r.amount)
                   FROM payment_refunds r
                   WHERE r.invoice_id = p.invoice_id AND r.status = 'Approved'
               ),0) AS paid
        FROM payments p
        WHERE p.invoice_id IS NOT NULL
        GROUP BY p.invoice_id
    ) pay ON pay.invoice_id = i.id
    WHERE DATE(i.created_at) = '{$today}'
      AND (i.visit_id IS NOT NULL OR i.walkin_id IS NOT NULL OR COALESCE(p.is_walkin,0)=1)
      AND COALESCE(items.total, i.total, 0) > COALESCE(pay.paid, 0)
      AND LOWER(COALESCE(i.status,'')) NOT IN ('cancelled','canceled','void')
      {$whereSearch}
    ORDER BY i.created_at ASC, i.id ASC
";

$pending = [];
$pendingTotal = 0.0;
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['bill_total'] = (float)$row['bill_total'];
        $row['paid_total'] = (float)$row['paid_total'];
        $row['balance'] = max($row['bill_total'] - $row['paid_total'], 0);
        if ($row['balance'] > 0.00001) {
            $pending[] = $row;
            $pendingTotal += $row['balance'];
        }
    }
}
$todayPayments = $conn->query("SELECT COALESCE(SUM(p.amount - COALESCE((
        SELECT SUM(r.amount) FROM payment_refunds r
        WHERE r.payment_id=p.id AND r.status='Approved'
    ),0)),0) AS total
    FROM payments p
    WHERE DATE(p.created_at)=CURDATE()");
$collectedToday = $todayPayments ? (float)($todayPayments->fetch_assoc()['total'] ?? 0) : 0.0;

$cashierId = (int)($_SESSION['user_id'] ?? 0);
$openShift = get_open_cashier_shift($conn, $cashierId);
$shiftTotals = $openShift ? cashier_shift_totals($conn, (int)$openShift['id']) : ['cash'=>0,'mpesa'=>0,'other'=>0,'total'=>0];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content"><div class="container-fluid hms-workspace">
        <?php if (!$openShift): ?>
            <div class="alert alert-warning mb-4"><i class="fas fa-lock"></i> <strong>No open cashier shift.</strong> <a href="/hospital_system/cashier/shifts.php" class="btn btn-sm btn-warning ml-2">Open Cashier Shift</a></div>
        <?php else: ?>
            <div class="alert alert-success mb-4"><i class="fas fa-unlock"></i> Shift #<?= (int)$openShift['id'] ?> is open. Cash collected: <strong>KSH <?= number_format($shiftTotals['cash'],2) ?></strong> · M-Pesa: <strong>KSH <?= number_format($shiftTotals['mpesa'],2) ?></strong> <a href="/hospital_system/cashier/shifts.php" class="btn btn-sm btn-outline-success ml-2">Manage Shift</a></div>
        <?php endif; ?><div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-1 text-gray-800"><i class="fas fa-cash-register"></i> Central Cashier</h2>
                
            </div>
            <div>
                <a href="/hospital_system/cashier/payment_history.php" class="btn btn-outline-primary mr-2">
                    <i class="fas fa-receipt"></i> Payment History
                </a>
                <a href="/hospital_system/cashier/aged_receivables.php" class="btn btn-outline-warning mr-2">
                    <i class="fas fa-user-clock"></i> Aged Receivables
                </a>
                <a href="/hospital_system/billing/view_bills.php" class="btn btn-outline-secondary">
                    <i class="fas fa-history"></i> Billing History
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Payment received successfully. Patient balance updated. <?php if (!empty($_GET['paid_invoice'])): ?><?php if (!empty($_GET['payment_id'])): ?><a class="btn btn-sm btn-success ml-2" target="_blank" href="/hospital_system/billing/print_receipt.php?id=<?= (int)$_GET['payment_id'] ?>"><i class="fas fa-receipt"></i> View & Print Receipt</a><?php endif; ?><a class="btn btn-sm btn-outline-primary ml-2" target="_blank" href="/hospital_system/billing/view_invoice.php?id=<?= (int)$_GET['paid_invoice'] ?>"><i class="fas fa-file-invoice"></i> View Invoice</a><?php endif; ?></div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-left-warning shadow h-100 py-2"><div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Today's Outstanding</div>
                    <div class="h4 mb-0 font-weight-bold">KSH <?= number_format($pendingTotal, 2) ?></div>
                </div></div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-left-primary shadow h-100 py-2"><div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Today's Queue</div>
                    <div class="h4 mb-0 font-weight-bold"><?= count($pending) ?></div>
                </div></div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-left-success shadow h-100 py-2"><div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Collected Today</div>
                    <div class="h4 mb-0 font-weight-bold">KSH <?= number_format($collectedToday, 2) ?></div>
                </div></div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Today's Payment Queue <span class="text-muted font-weight-normal">(<?= htmlspecialchars($today) ?>)</span></h6></div>
            <div class="card-body">
                <form method="get" class="form-row mb-3">
                    <div class="col-md-8 mb-2"><input type="search" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search invoice, patient, patient number or phone"></div>
                    <div class="col-md-4 mb-2"><button class="btn btn-outline-primary mr-2"><i class="fas fa-search"></i> Search Today's Queue</button><a href="/hospital_system/cashier/index.php" class="btn btn-outline-secondary">Clear</a></div>
                </form>
                <?php if (!$pending): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h5>No outstanding payments in today's queue</h5>
                        <p class="mb-0">Older unpaid balances are tracked separately under Aged Receivables.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light"><tr>
                                <th>Patient Number</th><th>Patient</th><th>Bill</th><th>Paid</th><th>Balance</th><th>Receive / Invoice</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($pending as $row): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['patient_number'] ?: 'N/A') ?></strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['patient_name'] ?: 'Unknown') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($row['patient_number'] ?: 'N/A') ?></small>
                                        <?php if (!empty($row['is_walkin'])): ?><span class="badge badge-info">Walk-in</span><?php endif; ?>
                                    </td>
                                    <td>KSH <?= number_format($row['bill_total'], 2) ?></td>
                                    <td class="text-success">KSH <?= number_format($row['paid_total'], 2) ?></td>
                                    <td class="text-danger font-weight-bold">KSH <?= number_format($row['balance'], 2) ?></td>
                                    <td style="min-width:320px">
                                        <form method="post" action="/hospital_system/billing/pay_invoice.php" class="cashier-payment-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="invoice_id" value="<?= (int)$row['id'] ?>">
                                            <input type="hidden" name="return_to" value="cashier">
                                            <div class="input-group input-group-sm mb-2">
                                                <input type="number" name="amount" class="form-control" min="0.01"
                                                       max="<?= number_format($row['balance'],2,'.','') ?>"
                                                       step="0.01" value="<?= number_format($row['balance'],2,'.','') ?>" required>
                                                <select name="payment_mode" class="form-control" style="max-width:105px">
                                                    <option value="Cash">Cash</option>
                                                    <option value="Mpesa">M-Pesa</option>
                                                </select>
                                                <button class="btn btn-success" type="submit" <?= !$openShift ? 'disabled title="Open a cashier shift first"' : '' ?>><i class="fas fa-check"></i> Receive</button>
                                            </div>
                                            <div class="mpesa-fields" style="display:none">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="phone" class="form-control" placeholder="Phone (optional)">
                                                    <input type="text" name="reference" class="form-control" placeholder="M-Pesa receipt / transaction">
                                                </div>
                                            </div>
                                        </form>
                                        <a class="btn btn-sm btn-outline-primary btn-block" target="_blank" href="/hospital_system/billing/view_invoice.php?id=<?= (int)$row['id'] ?>">
                                            <i class="fas fa-file-invoice"></i> View Invoice
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

<script>
document.querySelectorAll('.cashier-payment-form').forEach(function(form) {
    const mode = form.querySelector('select[name="payment_mode"]');
    const mpesa = form.querySelector('.mpesa-fields');
    mode.addEventListener('change', function() {
        mpesa.style.display = this.value.toLowerCase() === 'mpesa' ? 'block' : 'none';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>