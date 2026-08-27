<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// Revenue: sum of total from paid invoices
$revenue = $conn->query("SELECT SUM(total) as rev FROM invoices WHERE status = 'paid'")->fetch_assoc()['rev'] ?? 0;

// Expenses: prefer the dedicated expenses table, fallback to expense ledger entries if needed
$expenses = $conn->query("SELECT COALESCE(SUM(amount), 0) as exp FROM expenses")->fetch_assoc()['exp'] ?? 0;
if ($expenses <= 0) {
    $expenses = $conn->query("SELECT COALESCE(SUM(debit - credit), 0) as exp FROM accounting_entries WHERE LOWER(account) LIKE '%expense%'")->fetch_assoc()['exp'] ?? 0;
}

$profit = $revenue - $expenses;

// Pending payments: sum of total from unpaid/partial invoices
$pending = $conn->query("SELECT SUM(total) as pend FROM invoices WHERE status IN ('unpaid', 'partial')")->fetch_assoc()['pend'] ?? 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.finance-dashboard .metric-card {
    border-radius: 18px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: #ffffff;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
}
.finance-dashboard .metric-card .metric-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #ffffff;
}
.finance-dashboard .metric-card .metric-title {
    font-size: 0.85rem;
    letter-spacing: 0.08em;
    color: #6b7280;
    text-transform: uppercase;
    margin-bottom: 0.6rem;
}
.finance-dashboard .metric-card .metric-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #111827;
}
.finance-dashboard .chart-card {
    border-radius: 20px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
}
</style>

<div class="main finance-dashboard">
    <div class="page-title mb-4">Finance Dashboard</div>
    <div class="row gx-4 gy-4">
        <div class="col-xl-3 col-md-6">
            <div class="card metric-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="metric-title">Revenue</div>
                        <div class="metric-value">KSH <?= number_format($revenue, 2) ?></div>
                    </div>
                    <div class="metric-icon" style="background:#16a34a;"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card metric-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="metric-title">Expenses</div>
                        <div class="metric-value">KSH <?= number_format($expenses, 2) ?></div>
                    </div>
                    <div class="metric-icon" style="background:#dc2626;"><i class="fas fa-file-invoice-dollar"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card metric-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="metric-title">Profit</div>
                        <div class="metric-value">KSH <?= number_format($profit, 2) ?></div>
                    </div>
                    <div class="metric-icon" style="background:#2563eb;"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card metric-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="metric-title">Pending Payments</div>
                        <div class="metric-value">KSH <?= number_format($pending, 2) ?></div>
                    </div>
                    <div class="metric-icon" style="background:#f59e0b;"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card chart-card mt-4 p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Revenue vs Expenses</h5>
                <p class="text-muted mb-0">A quick comparison of income and outgoing cash for the current book.</p>
            </div>
        </div>
        <div style="min-height: 320px;">
            <canvas id="financeChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('financeChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Revenue', 'Expenses', 'Profit'],
        datasets: [{
            label: 'Amount (KSH)',
            data: [<?= round($revenue, 2) ?>, <?= round($expenses, 2) ?>, <?= round($profit, 2) ?>],
            backgroundColor: ['#16a34a', '#dc2626', '#2563eb'],
            borderRadius: 12,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) { return 'KSH ' + value.toLocaleString(); }
                }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: function(context) { return 'KSH ' + context.parsed.y.toLocaleString(); } } }
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>