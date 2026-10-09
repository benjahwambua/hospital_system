<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_module_access($conn, 'finance', 'view');

$revenue = $conn->query("SELECT COALESCE(SUM(total), 0) AS rev FROM invoices WHERE status = 'paid'")->fetch_assoc()['rev'] ?? 0;

$expenses = $conn->query("SELECT COALESCE(SUM(amount), 0) AS exp FROM expenses")->fetch_assoc()['exp'] ?? 0;
if ($expenses <= 0) {
    $expenses = $conn->query("SELECT COALESCE(SUM(debit - credit), 0) AS exp FROM accounting_entries WHERE LOWER(account) LIKE '%expense%'")->fetch_assoc()['exp'] ?? 0;
}

$profit = $revenue - $expenses;
$pending = $conn->query("SELECT COALESCE(SUM(total), 0) AS pend FROM invoices WHERE status IN ('unpaid', 'partial')")->fetch_assoc()['pend'] ?? 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<link rel="stylesheet" href="../assets/css/finance_modules.css">
<style>
.finance-dashboard{padding-bottom:32px}.finance-dashboard .finance-hero{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:20px;padding:30px 32px;border-radius:22px;color:#fff;background:linear-gradient(135deg,#0b3d91 0%,#1261c9 55%,#13a8b8 100%);box-shadow:0 16px 38px rgba(16,77,153,.18)}.finance-dashboard .finance-hero:after{content:"";position:absolute;width:210px;height:210px;right:-65px;top:-95px;border-radius:50%;background:rgba(255,255,255,.08);pointer-events:none}.finance-dashboard .finance-hero>*{position:relative;z-index:1}.finance-dashboard .finance-hero h1{font-size:clamp(24px,3vw,32px);font-weight:800;margin:0 0 6px;color:#fff}.finance-dashboard .finance-hero p{margin:0;color:rgba(255,255,255,.82);font-size:14px}.finance-dashboard .finance-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.8px;font-weight:800;color:#bfe8ff;margin-bottom:5px}.finance-dashboard .metric-card{height:100%;border-radius:18px;border:1px solid rgba(15,23,42,.08);background:#fff;box-shadow:0 16px 40px rgba(15,23,42,.06)}
.finance-dashboard .metric-card .metric-icon{width:52px;height:52px;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff}
.finance-dashboard .metric-card .metric-title{font-size:.85rem;letter-spacing:.08em;color:#6b7280;text-transform:uppercase;margin-bottom:.6rem}
.finance-dashboard .metric-card .metric-value{font-size:1.8rem;font-weight:800;color:#111827}
.finance-dashboard .chart-card{border-radius:20px;border:1px solid rgba(15,23,42,.08);box-shadow:0 16px 40px rgba(15,23,42,.06)}
@media(max-width:600px){.finance-dashboard .finance-hero{padding:24px 20px;border-radius:17px}.finance-dashboard .metric-card .metric-value{font-size:1.35rem}}
</style>
<div class="main finance-dashboard">
    <div class="finance-hero mb-4"><div><div class="finance-kicker">Finance &amp; Accounting</div><h1>Finance Dashboard</h1><p>Monitor collections, expenses, profitability and outstanding invoices.</p></div></div>
    <div class="row gx-4 gy-4">
        <div class="col-xl-3 col-md-6"><div class="card metric-card p-4"><div class="d-flex align-items-center justify-content-between"><div><div class="metric-title">Revenue</div><div class="metric-value">KSH <?= number_format($revenue, 2) ?></div></div><div class="metric-icon" style="background:#16a34a;"><i class="fas fa-money-bill-wave"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card metric-card p-4"><div class="d-flex align-items-center justify-content-between"><div><div class="metric-title">Expenses</div><div class="metric-value">KSH <?= number_format($expenses, 2) ?></div></div><div class="metric-icon" style="background:#dc2626;"><i class="fas fa-file-invoice-dollar"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card metric-card p-4"><div class="d-flex align-items-center justify-content-between"><div><div class="metric-title">Profit</div><div class="metric-value">KSH <?= number_format($profit, 2) ?></div></div><div class="metric-icon" style="background:#2563eb;"><i class="fas fa-chart-line"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card metric-card p-4"><div class="d-flex align-items-center justify-content-between"><div><div class="metric-title">Pending Payments</div><div class="metric-value">KSH <?= number_format($pending, 2) ?></div></div><div class="metric-icon" style="background:#f59e0b;"><i class="fas fa-clock"></i></div></div></div></div>
    </div>
    <div class="card chart-card mt-4 p-4"><div class="d-flex align-items-center justify-content-between mb-3"><div><h5 class="mb-1">Revenue vs Expenses</h5><p class="text-muted mb-0">A quick comparison of income and outgoing cash for the current book.</p></div></div><div style="min-height:320px"><canvas id="financeChart" role="img" aria-label="Bar chart comparing revenue, expenses and profit in Kenyan shillings"></canvas></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx=document.getElementById('financeChart').getContext('2d');
new Chart(ctx,{type:'bar',data:{labels:['Revenue','Expenses','Profit'],datasets:[{label:'Amount (KSH)',data:[<?= round($revenue,2) ?>,<?= round($expenses,2) ?>,<?= round($profit,2) ?>],backgroundColor:['#16a34a','#dc2626','#2563eb'],borderRadius:12,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{callback:function(value){return 'KSH '+value.toLocaleString();}}}},plugins:{legend:{display:false},tooltip:{callbacks:{label:function(context){return 'KSH '+context.parsed.y.toLocaleString();}}}}}});
</script>
<?php include __DIR__ . '/../includes/footer.php';