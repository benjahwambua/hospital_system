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
    die('Access denied. Only the cashier or administrator can access the Cashier module.');
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
$todayPayments = $conn->query("SELECT COALESCE(SUM(p.amount),0)-COALESCE((SELECT SUM(r.amount) FROM payment_refunds r WHERE DATE(r.created_at)=CURDATE() AND r.status='Approved'),0) AS total FROM payments p WHERE DATE(p.created_at)=CURDATE()");
$collectedToday = $todayPayments ? (float)($todayPayments->fetch_assoc()['total'] ?? 0) : 0.0;

$cashierId = (int)($_SESSION['user_id'] ?? 0);
$openShift = get_open_cashier_shift($conn, $cashierId);
$shiftTotals = $openShift ? cashier_shift_totals($conn, (int)$openShift['id']) : ['cash'=>0,'mpesa'=>0,'other'=>0,'total'=>0];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <?php if (!$openShift): ?>
            <div class="alert alert-warning mb-4"><i class="fas fa-lock"></i> <strong>No cashier shift is open.</strong> Open your shift before receiving payments. <a href="/hospital_system/cashier/shifts.php" class="btn btn-sm btn-warning ml-2">Open Cashier Shift</a></div>
        <?php else: ?>
            <div class="alert alert-success mb-4"><i class="fas fa-unlock"></i> Shift #<?= (int)$openShift['id'] ?> is open. Cash collected: <strong>KSH <?= number_format($shiftTotals['cash'],2) ?></strong> · M-Pesa: <strong>KSH <?= number_format($shiftTotals['mpesa'],2) ?></strong> <a href="/hospital_system/cashier/shifts.php" class="btn btn-sm btn-outline-success ml-2">Manage Shift</a></div>
        <?php endif; ?>
        <div class="alert alert-info mb-4"><i class="fas fa-info-circle"></i> Cashier is the single collection point. Clinical, laboratory, pharmacy and reception staff raise charges; only the Cashier records patient payments. Legacy invoices without a Visit are kept in billing history but are not placed in the active cashier queue.</div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-1 text-gray-800"><i class="fas fa-cash-register"></i> Central Cashier</h2>
                <p class="text-muted mb-0">Today's collection queue for the current operational day.</p>
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
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Payment received successfully. Patient balance updated. <?php if (!empty($_GET['paid_invoice'])): ?><a class="btn btn-sm btn-success ml-2" target="_blank" href="/hospital_system/billing/view_invoice.php?id=<?= (int)$_GET['paid_invoice'] ?>&print=1"><i class="fas fa-print"></i> View & Print Invoice</a><?php endif; ?></div>
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
                                                    <input type="text" name="mpesa_receipt" class="form-control" placeholder="Receipt (optional)">
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