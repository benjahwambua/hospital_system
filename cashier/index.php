<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isSuper = !empty($_SESSION['is_super']) && (int)$_SESSION['is_super'] === 1;
if (!$isSuper && !in_array($role, ['admin', 'cashier'], true)) {
    http_response_code(403);
    die('Access denied. Only the cashier or administrator can access the Cashier module.');
}

$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');
$fromEsc = $conn->real_escape_string($from);
$toEsc = $conn->real_escape_string($to);

$sql = "
    SELECT i.id, i.patient_id, i.visit_id, i.created_at,
           p.patient_number, p.full_name AS patient_name, p.is_walkin,
           v.visit_number, v.visit_type, v.clinic_category, v.status AS visit_status,
           COALESCE(items.total, i.total, 0) AS bill_total,
           COALESCE(pay.paid, 0) AS paid_total
    FROM invoices i
    LEFT JOIN patients p ON p.id = i.patient_id
    LEFT JOIN visits v ON v.id = i.visit_id
    LEFT JOIN (
        SELECT invoice_id, SUM(total) AS total
        FROM invoice_items GROUP BY invoice_id
    ) items ON items.invoice_id = i.id
    LEFT JOIN (
        SELECT invoice_id, SUM(amount) AS paid
        FROM payments WHERE invoice_id IS NOT NULL
        GROUP BY invoice_id
    ) pay ON pay.invoice_id = i.id
    WHERE COALESCE(items.total, i.total, 0) > COALESCE(pay.paid, 0)
    ORDER BY COALESCE(v.visit_date, DATE(i.created_at)) DESC, i.id DESC
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

$todayPayments = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE DATE(created_at)=CURDATE()");
$collectedToday = $todayPayments ? (float)($todayPayments->fetch_assoc()['total'] ?? 0) : 0.0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="alert alert-info mb-4"><i class="fas fa-info-circle"></i> Cashier is the single collection point. Clinical, laboratory, pharmacy and reception staff raise charges; only the Cashier records patient payments.</div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-1 text-gray-800"><i class="fas fa-cash-register"></i> Central Cashier</h2>
                <p class="text-muted mb-0">All patient payments are collected here.</p>
            </div>
            <a href="/hospital_system/billing/view_bills.php" class="btn btn-outline-secondary">
                <i class="fas fa-history"></i> Billing History
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Payment received successfully. Patient balance updated.</div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-left-warning shadow h-100 py-2"><div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Outstanding</div>
                    <div class="h4 mb-0 font-weight-bold">KSH <?= number_format($pendingTotal, 2) ?></div>
                </div></div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-left-primary shadow h-100 py-2"><div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Patients Waiting</div>
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
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Pending Patient Payments</h6></div>
            <div class="card-body">
                <?php if (!$pending): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h5>No outstanding patient payments</h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light"><tr>
                                <th>Visit</th><th>Patient</th><th>Bill</th><th>Paid</th><th>Balance</th><th>Receive</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($pending as $row): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['visit_number'] ?: 'Legacy / Unassigned') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($row['visit_type'] ?: 'Legacy') ?> · <?= htmlspecialchars($row['clinic_category'] ?: 'General') ?></small>
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
                                                <button class="btn btn-success" type="submit"><i class="fas fa-check"></i> Receive</button>
                                            </div>
                                            <div class="mpesa-fields" style="display:none">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="phone" class="form-control" placeholder="Phone (optional)">
                                                    <input type="text" name="mpesa_receipt" class="form-control" placeholder="Receipt (optional)">
                                                </div>
                                            </div>
                                        </form>
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