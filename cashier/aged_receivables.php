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
    die('Access denied. Only the cashier or administrator can access Aged Receivables.');
}

$search = trim((string)($_GET['q'] ?? ''));
$bucket = trim((string)($_GET['bucket'] ?? 'all'));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;
$searchEsc = $conn->real_escape_string($search);

$bucketSql = '';
switch ($bucket) {
    case '0-30':  $bucketSql = ' AND DATEDIFF(CURDATE(), DATE(i.created_at)) BETWEEN 1 AND 30'; break;
    case '31-60': $bucketSql = ' AND DATEDIFF(CURDATE(), DATE(i.created_at)) BETWEEN 31 AND 60'; break;
    case '61-90': $bucketSql = ' AND DATEDIFF(CURDATE(), DATE(i.created_at)) BETWEEN 61 AND 90'; break;
    case '90+':   $bucketSql = ' AND DATEDIFF(CURDATE(), DATE(i.created_at)) > 90'; break;
}

$whereSearch = '';
if ($search !== '') {
    $whereSearch = " AND (i.invoice_number LIKE '%{$searchEsc}%' OR p.full_name LIKE '%{$searchEsc}%' OR p.patient_number LIKE '%{$searchEsc}%' OR wc.full_name LIKE '%{$searchEsc}%' OR wc.phone LIKE '%{$searchEsc}%' OR v.visit_number LIKE '%{$searchEsc}%')";
}

$baseFrom = "
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
    WHERE DATE(COALESCE(v.visit_date, DATE(i.created_at))) < CURDATE()
      AND (i.visit_id IS NOT NULL OR i.walkin_id IS NOT NULL OR COALESCE(p.is_walkin,0)=1)
      AND COALESCE(items.total, i.total, 0) > COALESCE(pay.paid, 0)
      AND LOWER(COALESCE(i.status,'')) NOT IN ('cancelled','canceled','void')
      {$whereSearch}
      {$bucketSql}
";

$countResult = $conn->query("SELECT COUNT(*) AS total {$baseFrom}");
$totalRows = $countResult ? (int)($countResult->fetch_assoc()['total'] ?? 0) : 0;
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$sql = "
    SELECT i.id, i.invoice_number, i.patient_id, i.visit_id, i.created_at,
           p.patient_number, COALESCE(p.full_name, wc.full_name) AS patient_name,
           COALESCE(p.is_walkin, 1*(i.walkin_id IS NOT NULL)) AS is_walkin,
           wc.phone AS walkin_phone,
           v.visit_number, v.visit_type, v.clinic_category,
           COALESCE(items.total, i.total, 0) AS bill_total,
           COALESCE(pay.paid, 0) AS paid_total,
           DATEDIFF(CURDATE(), DATE(i.created_at)) AS age_days
    {$baseFrom}
    ORDER BY age_days DESC, i.created_at ASC, i.id ASC
    LIMIT {$perPage} OFFSET {$offset}
";

$rows = [];
$agedTotal = 0.0;
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['bill_total'] = (float)$row['bill_total'];
        $row['paid_total'] = (float)$row['paid_total'];
        $row['balance'] = max($row['bill_total'] - $row['paid_total'], 0);
        $row['age_days'] = (int)$row['age_days'];
        if ($row['balance'] > 0.00001) {
            $rows[] = $row;
            $agedTotal += $row['balance'];
        }
    }
}

function age_label(int $days): string {
    if ($days <= 30) return '0–30 days';
    if ($days <= 60) return '31–60 days';
    if ($days <= 90) return '61–90 days';
    return '90+ days';
}

$bucketTotals = ['0-30'=>0.0, '31-60'=>0.0, '61-90'=>0.0, '90+'=>0.0];
$bucketCounts = ['0-30'=>0, '31-60'=>0, '61-90'=>0, '90+'=>0];
$summaryResult = $conn->query("
    SELECT
        COALESCE(SUM(CASE WHEN age_days BETWEEN 1 AND 30 THEN balance ELSE 0 END),0) AS b0,
        COALESCE(SUM(CASE WHEN age_days BETWEEN 31 AND 60 THEN balance ELSE 0 END),0) AS b1,
        COALESCE(SUM(CASE WHEN age_days BETWEEN 61 AND 90 THEN balance ELSE 0 END),0) AS b2,
        COALESCE(SUM(CASE WHEN age_days > 90 THEN balance ELSE 0 END),0) AS b3,
        COALESCE(SUM(CASE WHEN age_days BETWEEN 1 AND 30 THEN 1 ELSE 0 END),0) AS c0,
        COALESCE(SUM(CASE WHEN age_days BETWEEN 31 AND 60 THEN 1 ELSE 0 END),0) AS c1,
        COALESCE(SUM(CASE WHEN age_days BETWEEN 61 AND 90 THEN 1 ELSE 0 END),0) AS c2,
        COALESCE(SUM(CASE WHEN age_days > 90 THEN 1 ELSE 0 END),0) AS c3
    FROM (
        SELECT DATEDIFF(CURDATE(), DATE(i.created_at)) AS age_days,
               GREATEST(COALESCE(items.total, i.total, 0) - COALESCE(pay.paid,0), 0) AS balance
        {$baseFrom}
    ) x
");
if ($summaryResult) {
    $s = $summaryResult->fetch_assoc();
    $bucketTotals = ['0-30'=>(float)$s['b0'], '31-60'=>(float)$s['b1'], '61-90'=>(float)$s['b2'], '90+'=> (float)$s['b3']];
    $bucketCounts = ['0-30'=>(int)$s['c0'], '31-60'=>(int)$s['c1'], '61-90'=>(int)$s['c2'], '90+'=> (int)$s['c3']];
}
$allAgedTotal = array_sum($bucketTotals);
$cashierId = (int)($_SESSION['user_id'] ?? 0);
$openShift = get_open_cashier_shift($conn, $cashierId);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-1 text-gray-800"><i class="fas fa-user-clock"></i> Aged Receivables</h2>
                <p class="text-muted mb-0">Historical patient balances that are no longer part of today's collection queue.</p>
            </div>
            <a href="/hospital_system/cashier/index.php" class="btn btn-outline-primary"><i class="fas fa-cash-register"></i> Today's Cashier Queue</a>
        </div>

        <?php if (!$openShift): ?>
            <div class="alert alert-warning"><i class="fas fa-lock"></i> No cashier shift is open. Open a shift before receiving any debtor payment.</div>
        <?php endif; ?>

        <div class="row mb-4">
            <?php foreach (['0-30'=>'0–30 Days','31-60'=>'31–60 Days','61-90'=>'61–90 Days','90+'=>'90+ Days'] as $key=>$label): ?>
            <div class="col-md-3 mb-3">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-uppercase text-muted mb-1"><?= $label ?></div>
                        <div class="h5 font-weight-bold mb-1">KSH <?= number_format($bucketTotals[$key],2) ?></div>
                        <small class="text-muted"><?= $bucketCounts[$key] ?> invoice(s)</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Debtors · KSH <?= number_format($allAgedTotal,2) ?></h6>
                <span class="badge badge-warning"><?= $totalRows ?> outstanding invoice(s)</span>
            </div>
            <div class="card-body">
                <form method="get" class="form-row mb-3">
                    <div class="col-md-6 mb-2"><input type="search" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search invoice, patient, number, phone or visit"></div>
                    <div class="col-md-3 mb-2">
                        <select name="bucket" class="form-control">
                            <option value="all" <?= $bucket==='all'?'selected':'' ?>>All ageing buckets</option>
                            <option value="0-30" <?= $bucket==='0-30'?'selected':'' ?>>0–30 days</option>
                            <option value="31-60" <?= $bucket==='31-60'?'selected':'' ?>>31–60 days</option>
                            <option value="61-90" <?= $bucket==='61-90'?'selected':'' ?>>61–90 days</option>
                            <option value="90+" <?= $bucket==='90+'?'selected':'' ?>>90+ days</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-outline-primary mr-2"><i class="fas fa-filter"></i> Filter</button>
                        <a href="/hospital_system/cashier/aged_receivables.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </form>

                <?php if (!$rows): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h5>No aged receivables found</h5>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr><th>Age</th><th>Patient</th><th>Invoice / Visit</th><th>Bill</th><th>Paid</th><th>Outstanding</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><strong><?= $row['age_days'] ?> days</strong><br><span class="badge badge-secondary"><?= htmlspecialchars(age_label($row['age_days'])) ?></span></td>
                                <td><strong><?= htmlspecialchars($row['patient_name'] ?: 'Unknown') ?></strong><br><small class="text-muted"><?= htmlspecialchars($row['patient_number'] ?: ($row['walkin_phone'] ?: 'N/A')) ?></small></td>
                                <td><strong><?= htmlspecialchars($row['invoice_number'] ?: ('INV-'.$row['id'])) ?></strong><br><small class="text-muted"><?= htmlspecialchars($row['visit_number'] ?: 'Legacy / Unassigned') ?></small></td>
                                <td>KSH <?= number_format($row['bill_total'],2) ?></td>
                                <td class="text-success">KSH <?= number_format($row['paid_total'],2) ?></td>
                                <td class="text-danger font-weight-bold">KSH <?= number_format($row['balance'],2) ?></td>
                                <td style="min-width:250px">
                                    <form method="post" action="/hospital_system/billing/pay_invoice.php" class="cashier-payment-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="invoice_id" value="<?= (int)$row['id'] ?>">
                                        <input type="hidden" name="return_to" value="aged_receivables">
                                        <input type="hidden" name="return_page" value="<?= $page ?>">
                                        <input type="hidden" name="return_q" value="<?= htmlspecialchars($search) ?>">
                                        <input type="hidden" name="return_bucket" value="<?= htmlspecialchars($bucket) ?>">
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="amount" class="form-control" min="0.01" max="<?= number_format($row['balance'],2,'.','') ?>" step="0.01" value="<?= number_format($row['balance'],2,'.','') ?>" required>
                                            <select name="payment_mode" class="form-control" style="max-width:95px"><option value="Cash">Cash</option><option value="Mpesa">M-Pesa</option></select>
                                            <button class="btn btn-success" type="submit" <?= !$openShift ? 'disabled title="Open a cashier shift first"' : '' ?>><i class="fas fa-check"></i> Receive</button>
                                        </div>
                                    </form>
                                    <a class="btn btn-sm btn-outline-primary btn-block mt-1" target="_blank" href="/hospital_system/billing/view_invoice.php?id=<?= (int)$row['id'] ?>"><i class="fas fa-file-invoice"></i> View Invoice</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center">
                            <?php for ($p=1; $p<=$totalPages; $p++): ?>
                                <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="?q=<?= urlencode($search) ?>&bucket=<?= urlencode($bucket) ?>&page=<?= $p ?>"><?= $p ?></a></li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
