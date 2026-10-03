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

<link rel="stylesheet" href="../assets/css/finance_modules.css">
<style>
.cashier-page{padding:28px 24px 50px;background:#f4f7fb;min-height:calc(100vh - 72px)}
.cashier-shell{max-width:1500px;margin:0 auto}
.cashier-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#0b3d91 0%,#1261c9 55%,#13a8b8 100%);color:#fff;border-radius:22px;padding:30px 32px;margin-bottom:22px;box-shadow:0 16px 38px rgba(16,77,153,.22);display:flex;justify-content:space-between;align-items:center;gap:24px}
.cashier-hero:after{content:"";position:absolute;width:300px;height:300px;border:1px solid rgba(255,255,255,.13);border-radius:50%;right:-100px;top:-145px;box-shadow:0 0 0 35px rgba(255,255,255,.025),0 0 0 70px rgba(255,255,255,.015);pointer-events:none}
.cashier-hero>*{position:relative;z-index:1}
.cashier-eyebrow{font-size:11px;text-transform:uppercase;letter-spacing:2px;font-weight:800;opacity:.72}
.cashier-hero h1{color:#fff!important;font-size:30px;font-weight:800;margin:5px 0 7px}
.cashier-hero p{color:rgba(255,255,255,.82);margin:0;font-size:14px}
.cashier-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.cashier-actions .btn{border-radius:10px;font-weight:800}
.cashier-actions .btn-light{color:#0b3d91}
.cashier-alert{border-radius:12px;padding:13px 16px;margin-bottom:20px;border:1px solid}
.cashier-alert.warning{background:#fffaeb;border-color:#fedf89;color:#8a5b00}
.cashier-alert.success{background:#ecfdf3;border-color:#a7f3d0;color:#087443}
.cashier-alert a{margin-left:10px}
.cashier-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.cashier-metric{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px 20px;box-shadow:0 8px 25px rgba(20,40,70,.065)}
.cashier-metric .label{display:flex;justify-content:space-between;align-items:center;color:#697586;font-size:10px;text-transform:uppercase;letter-spacing:.07em;font-weight:800}
.cashier-metric .icon{width:34px;height:34px;border-radius:9px;background:#edf6ff;color:#1261c9;display:flex;align-items:center;justify-content:center}
.cashier-metric .value{display:block;color:#182334;font-size:22px;font-weight:800;margin-top:12px}
.cashier-metric .hint{display:block;color:#98a2b3;font-size:11px;margin-top:3px}
.cashier-card{background:#fff;border:1px solid #e2e8f0;border-radius:17px;box-shadow:0 8px 25px rgba(20,40,70,.065);overflow:hidden;margin-bottom:20px}
.cashier-card-head{padding:17px 21px;border-bottom:1px solid #e8edf3;background:linear-gradient(180deg,#fff,#f8fafc);display:flex;justify-content:space-between;align-items:center;gap:14px}
.cashier-card-head h2{margin:0;color:#182334;font-size:16px;font-weight:800}
.cashier-card-head p{margin:3px 0 0;color:#98a2b3;font-size:11px}
.cashier-card-body{padding:21px}
.cashier-filter{display:grid;grid-template-columns:minmax(240px,1fr) auto;gap:10px;margin-bottom:18px}
.cashier-input{width:100%;min-height:43px;padding:10px 12px;border:1px solid #d8e0ea;border-radius:9px;background:#fff;color:#344054}
.cashier-input:focus{border-color:#2f78c8;box-shadow:0 0 0 3px rgba(47,120,200,.10);outline:0}
.cashier-btn{border-radius:9px;font-weight:800;min-height:43px;padding:10px 15px}
.cashier-table-wrap{overflow-x:auto}
.cashier-table{width:100%;border-collapse:separate;border-spacing:0;min-width:980px}
.cashier-table th{background:#f1f5f9;color:#64748b;text-transform:uppercase;letter-spacing:.06em;font-size:10px;font-weight:800;padding:12px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.cashier-table td{padding:14px 12px;border-bottom:1px solid #edf1f5;color:#344054;font-size:12px;vertical-align:middle}
.cashier-table tbody tr:hover{background:#f7fbff}
.cashier-table tbody tr:last-child td{border-bottom:0}
.cashier-person strong{display:block;color:#182334;font-size:13px}.cashier-person small{color:#98a2b3}.cashier-amount{font-weight:800;white-space:nowrap;color:#182334}.cashier-paid{color:#087443;font-weight:800}.cashier-balance{color:#b42318;font-weight:800}
.cashier-badge{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eaf4ff;color:#1261c9;font-size:9px;text-transform:uppercase;font-weight:800;letter-spacing:.04em}
.cashier-pay{min-width:310px}.cashier-pay .rowline{display:grid;grid-template-columns:1fr 105px auto;gap:5px;margin-bottom:7px}.cashier-pay select,.cashier-pay input{min-height:36px;font-size:11px}.cashier-pay .mpesa-fields{display:none;margin-bottom:7px}.cashier-pay .mpesa-fields .rowline{grid-template-columns:1fr 1fr}.cashier-pay .btn{border-radius:8px;font-weight:800;white-space:nowrap}
.cashier-invoice{display:block;width:100%;border-radius:8px!important;font-weight:800!important}
.cashier-empty{text-align:center;padding:60px 20px;color:#98a2b3}.cashier-empty i{font-size:38px;margin-bottom:12px;color:#c5ccd6}.cashier-empty h3{color:#475467;font-size:17px;margin:0 0 6px}.cashier-empty p{margin:0;font-size:12px}
@media(max-width:1000px){.cashier-metrics{grid-template-columns:1fr 1fr}.cashier-hero{align-items:flex-start;flex-direction:column}.cashier-actions{justify-content:flex-start}}
@media(max-width:700px){.cashier-page{padding:18px 12px 35px}.cashier-hero{padding:23px;border-radius:17px}.cashier-hero h1{font-size:24px}.cashier-metrics{grid-template-columns:1fr}.cashier-filter{grid-template-columns:1fr}.cashier-card-body{padding:16px}}
</style>

<div class="cashier-page">
  <div class="cashier-shell">
    <div class="cashier-hero">
      <div>
        <div class="cashier-eyebrow">Finance &amp; Controls / Collections</div>
        <h1>Central Cashier</h1>
        <p>Receive patient payments, monitor today's queue and keep collections reconciled.</p>
      </div>
      <div class="cashier-actions">
        <a href="/hospital_system/cashier/shifts.php" class="btn btn-light"><i class="fas fa-clock"></i>&nbsp; Cashier Shifts</a>
        <a href="/hospital_system/cashier/payment_history.php" class="btn btn-light"><i class="fas fa-receipt"></i>&nbsp; Payment History</a>
        <a href="/hospital_system/cashier/aged_receivables.php" class="btn btn-light"><i class="fas fa-user-clock"></i>&nbsp; Receivables</a>
      </div>
    </div>

    <?php if (!$openShift): ?>
      <div class="cashier-alert warning"><i class="fas fa-lock"></i> <strong>No open cashier shift.</strong> Payments are disabled until a shift is opened.<a href="/hospital_system/cashier/shifts.php" class="btn btn-sm btn-warning">Open Cashier Shift</a></div>
    <?php else: ?>
      <div class="cashier-alert success"><i class="fas fa-unlock"></i> <strong>Shift #<?= (int)$openShift['id'] ?> is open.</strong> Cash: <strong>KSH <?= number_format($shiftTotals['cash'],2) ?></strong> &nbsp;·&nbsp; M-Pesa: <strong>KSH <?= number_format($shiftTotals['mpesa'],2) ?></strong><a href="/hospital_system/cashier/shifts.php" class="btn btn-sm btn-outline-success">Manage Shift</a></div>
    <?php endif; ?>

    <div class="cashier-metrics">
      <div class="cashier-metric"><div class="label"><span>Today's Outstanding</span><span class="icon"><i class="fas fa-hourglass-half"></i></span></div><span class="value">KSH <?= number_format($pendingTotal,2) ?></span><span class="hint">Open balances in today's queue</span></div>
      <div class="cashier-metric"><div class="label"><span>Today's Queue</span><span class="icon"><i class="fas fa-list"></i></span></div><span class="value"><?= count($pending) ?></span><span class="hint">Invoices awaiting collection</span></div>
      <div class="cashier-metric"><div class="label"><span>Collected Today</span><span class="icon"><i class="fas fa-money-bill-wave"></i></span></div><span class="value">KSH <?= number_format($collectedToday,2) ?></span><span class="hint">Net payments received today</span></div>
      <div class="cashier-metric"><div class="label"><span>Current Shift</span><span class="icon"><i class="fas fa-cash-register"></i></span></div><span class="value"><?= $openShift ? 'OPEN' : 'CLOSED' ?></span><span class="hint"><?= $openShift ? 'Ready to receive payments' : 'Open a shift to collect' ?></span></div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="cashier-alert success"><i class="fas fa-check-circle"></i> <strong>Payment received successfully.</strong> Patient balance updated.
        <?php if (!empty($_GET['paid_invoice'])): ?>
          <?php if (!empty($_GET['payment_id'])): ?><a class="btn btn-sm btn-success" target="_blank" href="/hospital_system/billing/print_receipt.php?id=<?= (int)$_GET['payment_id'] ?>"><i class="fas fa-receipt"></i> Receipt</a><?php endif; ?>
          <a class="btn btn-sm btn-outline-primary" target="_blank" href="/hospital_system/billing/view_invoice.php?id=<?= (int)$_GET['paid_invoice'] ?>"><i class="fas fa-file-invoice"></i> Invoice</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="cashier-card">
      <div class="cashier-card-head">
        <div><h2><i class="fas fa-hand-holding-usd"></i>&nbsp; Today's Payment Queue</h2><p>Outstanding invoices raised today · <?= htmlspecialchars($today) ?></p></div>
        <span class="cashier-badge"><?= count($pending) ?> Pending</span>
      </div>
      <div class="cashier-card-body">
        <form method="get" class="cashier-filter">
          <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" class="cashier-input" placeholder="Search invoice, patient name, patient number or phone">
          <div><button class="btn btn-primary cashier-btn" type="submit"><i class="fas fa-search"></i>&nbsp; Search Queue</button><a href="/hospital_system/cashier/index.php" class="btn btn-outline-secondary cashier-btn ml-1">Clear</a></div>
        </form>

        <?php if (!$pending): ?>
          <div class="cashier-empty"><i class="fas fa-check-circle"></i><h3>No outstanding payments</h3><p>Today's collection queue is clear. Older unpaid balances are tracked under Aged Receivables.</p></div>
        <?php else: ?>
          <div class="cashier-table-wrap">
            <table class="cashier-table">
              <thead><tr><th>Patient</th><th>Bill</th><th>Paid</th><th>Balance</th><th>Receive Payment</th></tr></thead>
              <tbody>
              <?php foreach ($pending as $row): ?>
                <tr>
                  <td class="cashier-person"><strong><?= htmlspecialchars($row['patient_name'] ?: 'Unknown') ?></strong><small><?= htmlspecialchars($row['patient_number'] ?: 'N/A') ?> <?php if (!empty($row['is_walkin'])): ?><span class="cashier-badge">Walk-in</span><?php endif; ?></small></td>
                  <td class="cashier-amount">KSH <?= number_format($row['bill_total'],2) ?></td>
                  <td class="cashier-paid">KSH <?= number_format($row['paid_total'],2) ?></td>
                  <td class="cashier-balance">KSH <?= number_format($row['balance'],2) ?></td>
                  <td>
                    <div class="cashier-pay">
                      <form method="post" action="/hospital_system/billing/pay_invoice.php" class="cashier-payment-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="invoice_id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="return_to" value="cashier">
                        <div class="rowline">
                          <input type="number" name="amount" class="form-control" min="0.01" max="<?= number_format($row['balance'],2,'.','') ?>" step="0.01" value="<?= number_format($row['balance'],2,'.','') ?>" required>
                          <select name="payment_mode" class="form-control"><option value="Cash">Cash</option><option value="Mpesa">M-Pesa</option></select>
                          <button class="btn btn-success" type="submit" <?= !$openShift ? 'disabled title="Open a cashier shift first"' : '' ?>><i class="fas fa-check"></i> Receive</button>
                        </div>
                        <div class="mpesa-fields">
                          <div class="rowline"><input type="text" name="phone" class="form-control" placeholder="Phone (optional)"><input type="text" name="reference" class="form-control" placeholder="M-Pesa receipt / transaction"></div>
                        </div>
                      </form>
                      <a class="btn btn-sm btn-outline-primary cashier-invoice" target="_blank" href="/hospital_system/billing/view_invoice.php?id=<?= (int)$row['id'] ?>"><i class="fas fa-file-invoice"></i>&nbsp; View Invoice</a>
                    </div>
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
  const mode=form.querySelector('select[name="payment_mode"]'), mpesa=form.querySelector('.mpesa-fields');
  if(mode&&mpesa) mode.addEventListener('change',function(){mpesa.style.display=this.value.toLowerCase()==='mpesa'?'block':'none';});
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>