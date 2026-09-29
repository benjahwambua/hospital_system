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
               ),0) AS paid,
               MAX(p.created_at) AS last_payment_date
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
           pay.last_payment_date,
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

<div class="main-content"><div class="container-fluid hms-workspace">
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
                            <tr><th>Age</th><th>Client Name</th><th>Invoice / Visit</th><th>Invoice Date</th><th>Bill</th><th>Paid</th><th>Outstanding</th><th>Last Payment</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><strong><?= $row['age_days'] ?> days</strong><br><span class="badge badge-secondary"><?= htmlspecialchars(age_label($row['age_days'])) ?></span></td>
                                <td><strong><?= htmlspecialchars($row['patient_name'] ?: 'Unknown Client') ?></strong><br><small class="text-muted"><?= htmlspecialchars($row['patient_number'] ?: ($row['walkin_phone'] ?: 'N/A')) ?></small><?php if (!empty($row['is_walkin'])): ?><span class="badge badge-info ml-1">Walk-in</span><?php endif; ?></td>
                                <td><strong><?= htmlspecialchars($row['invoice_number'] ?: ('INV-'.$row['id'])) ?></strong><br><small class="text-muted"><?= htmlspecialchars($row['visit_number'] ?: 'Legacy / Unassigned') ?></small></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($row['created_at']))) ?></td>
                                <td>KSH <?= number_format($row['bill_total'],2) ?></td>
                                <td class="text-success">KSH <?= number_format($row['paid_total'],2) ?></td>
                                <td class="text-danger font-weight-bold">KSH <?= number_format($row['balance'],2) ?></td>
                                <td><?= !empty($row['last_payment_date']) ? htmlspecialchars(date('d M Y', strtotime($row['last_payment_date']))) : '<span class="text-muted">None</span>' ?></td>
                                <td style="min-width:290px">
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
                                    <?php if (!empty($row['patient_id'])): ?><a class="btn btn-sm btn-outline-secondary btn-block mt-1" target="_blank" href="/hospital_system/patients/patient_dashboard.php?id=<?= (int)$row['patient_id'] ?>"><i class="fas fa-user"></i> View Client / Patient</a><?php endif; ?>
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
