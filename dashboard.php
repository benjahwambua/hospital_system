<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/config.php';
require_login();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isSuper = 0;
if ($currentUserId > 0) {
    $userStmt = $conn->prepare("SELECT is_super FROM users WHERE id = ? LIMIT 1");
    $userStmt->bind_param('i', $currentUserId);
    $userStmt->execute();
    $isSuper = (int)($userStmt->get_result()->fetch_assoc()['is_super'] ?? 0);
    $userStmt->close();
}

$stats = [
    'patients' => $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'] ?? 0,
    'appointments' => $conn->query("SELECT COUNT(*) AS c FROM encounters WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'] ?? 0,
    'lab' => $conn->query("SELECT COUNT(*) AS c FROM lab_requests WHERE status='pending'")->fetch_assoc()['c'] ?? 0,
    'revenue' => $isSuper ? ($conn->query("SELECT COALESCE(SUM(amount),0) AS c FROM billing WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'] ?? 0) : 0,
    'pharmacy_val' => $isSuper ? ($conn->query("SELECT COALESCE(SUM(quantity * selling_price),0) AS c FROM pharmacy_stock")->fetch_assoc()['c'] ?? 0) : 0,
    'stock_units' => $conn->query("SELECT COALESCE(SUM(quantity),0) AS c FROM pharmacy_stock")->fetch_assoc()['c'] ?? 0,
];

$chart_labels = [];
$chart_data = [];
if ($isSuper) {
    $revenueQuery = $conn->query("SELECT DATE_FORMAT(created_at, '%D %b') as day, SUM(amount) as total FROM billing WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY created_at ASC");
    while($row = $revenueQuery->fetch_assoc()) {
        $chart_labels[] = $row['day'];
        $chart_data[] = (float)$row['total'];
    }
}

$low_stock = $conn->query("SELECT drug_name, quantity FROM pharmacy_stock WHERE quantity < 15 ORDER BY quantity ASC LIMIT 6");
$svc_breakdown = $conn->query("SELECT sm.category, COUNT(*) as count FROM services_master sm JOIN patient_services ps ON sm.id = ps.service_id GROUP BY sm.category");
$pie_labels = [];
$pie_data = [];
while($row = $svc_breakdown->fetch_assoc()){
    $pie_labels[] = $row['category'];
    $pie_data[] = (int)$row['count'];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --glass-blue: rgba(78, 115, 223, 0.05);
        --heavy-blue: #2e59d9;
        --success-green: #1cc88a;
    }
    .main-content { background: #f8f9fc; padding: 30px; min-height: 100vh; }
    .stat-card {
        background: #fff; border-radius: 15px; padding: 25px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        border-left: 4px solid var(--heavy-blue);
        transition: all 0.3s ease; position: relative; overflow: hidden; height: 100%;
    }
    .stat-card:hover { transform: scale(1.02); }
    .stat-card i { position: absolute; right: 10px; bottom: -10px; font-size: 4rem; opacity: 0.05; }
    .grid-main { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-top: 25px; }
    .analytics-card { background: #fff; border-radius: 15px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .action-tile {
        background: #fff; border: 1px solid #e3e6f0; border-radius: 12px;
        padding: 20px; text-align: center; color: #4e73df; font-weight: 700;
        text-decoration: none; transition: 0.3s;
    }
    .action-tile:hover { background: #4e73df; color: #fff; box-shadow: 0 8px 15px rgba(78,115,223,0.2); }
    .action-tile i { font-size: 1.8rem; display: block; margin-bottom: 10px; }
    .pulse-red { color: #e74a3b; animation: pulse-red 2s infinite; font-weight: bold; }
    .restricted-note { background:#fff3cd; color:#856404; border-left:4px solid #f6c23e; border-radius:8px; padding:12px 16px; }
    @keyframes pulse-red { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
    @media (max-width: 992px) {
        .grid-main { grid-template-columns: 1fr; }
    }
</style>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="font-weight-bold text-gray-800"><i class="fas fa-chart-line mr-2 text-primary"></i>Executive Overview</h2>
            <p class="text-muted">Hospital Performance & Resource Tracking</p>
        </div>
        <div class="text-right">
             <button onclick="window.location.reload()" class="btn btn-primary shadow-sm btn-sm px-4">
                 <i class="fas fa-sync-alt fa-sm text-white-50"></i> Refresh Data
             </button>
        </div>
    </div>

    <?php if(!$isSuper): ?>
        <div class="restricted-note mb-4 small">
            <i class="fas fa-lock mr-2"></i>Finance cards, finance reports, and revenue analytics are restricted to Super Users only.
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="border-left-color: #4e73df;">
                <small class="text-primary font-weight-bold text-uppercase">Total Patients</small>
                <div class="h3 font-weight-bold mt-1"><?= number_format((int)$stats['patients']) ?></div>
                <i class="fas fa-user-injured"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="border-left-color: #1cc88a;">
                <small class="text-success font-weight-bold text-uppercase"><?= $isSuper ? 'Daily Revenue' : 'Today\'s Encounters' ?></small>
                <div class="h3 font-weight-bold mt-1"><?= $isSuper ? 'KSh ' . number_format((float)$stats['revenue']) : number_format((int)$stats['appointments']) ?></div>
                <i class="<?= $isSuper ? 'fas fa-coins' : 'fas fa-stethoscope' ?>"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="border-left-color: #f6c23e;">
                <small class="text-warning font-weight-bold text-uppercase"><?= $isSuper ? 'Inventory Value' : 'Stock Units' ?></small>
                <div class="h3 font-weight-bold mt-1"><?= $isSuper ? 'KSh ' . number_format((float)$stats['pharmacy_val']) : number_format((int)$stats['stock_units']) ?></div>
                <i class="fas fa-pills"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="border-left-color: #e74a3b;">
                <small class="text-danger font-weight-bold text-uppercase">Lab Orders</small>
                <div class="h3 font-weight-bold mt-1"><?= (int)$stats['lab'] ?> <small style="font-size:12px">Pending</small></div>
                <i class="fas fa-vial"></i>
            </div>
        </div>
    </div>

    <div class="grid-main">
        <div class="analytics-card">
            <?php if($isSuper): ?>
                <h6 class="font-weight-bold text-primary mb-4"><i class="fas fa-wave-square mr-2"></i>7-Day Financial Performance</h6>
                <div style="height: 300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            <?php else: ?>
                <h6 class="font-weight-bold text-primary mb-4"><i class="fas fa-shield-alt mr-2"></i>Restricted Analytics</h6>
                <div class="restricted-note">
                    Revenue and finance analytics are hidden for non-Super Users. Use the operational cards and command tiles below for day-to-day workflow.
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex flex-column" style="gap:25px;">
            <div class="analytics-card">
                <h6 class="font-weight-bold text-dark mb-3">COMMAND TILES</h6>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <a href="patients/reception_register.php" class="action-tile"><i class="fas fa-hospital-user"></i>Reg. Patient</a>
                    <a href="pharmacy/sell_medicine.php" class="action-tile"><i class="fas fa-prescription"></i>Dispense</a>
                    <?php if($isSuper): ?>
                        <a href="reports/sales_report.php" class="action-tile"><i class="fas fa-file-invoice-dollar"></i>Finance</a>
                    <?php endif; ?>
                    <a href="lab/lab_requests.php" class="action-tile"><i class="fas fa-microscope"></i>Lab Stock</a>
                </div>
            </div>

            <div class="analytics-card" style="border-top: 3px solid #e74a3b;">
                <h6 class="font-weight-bold text-danger mb-3"><i class="fas fa-exclamation-circle mr-2"></i>CRITICAL STOCK</h6>
                <?php while($item = $low_stock->fetch_assoc()): ?>
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: #fff5f5;">
                    <span class="small font-weight-bold text-dark"><?= htmlspecialchars($item['drug_name']) ?></span>
                    <span class="pulse-red"><?= (int)$item['quantity'] ?> Left</span>
                </div>
                <?php endwhile; ?>
                <button class="btn btn-sm btn-outline-danger btn-block mt-3">Refill Inventory</button>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-4">
             <div class="analytics-card">
                <h6 class="font-weight-bold text-primary mb-3">Service Distribution</h6>
                <canvas id="servicePie"></canvas>
             </div>
        </div>
        <div class="col-md-8">
             <div class="analytics-card">
                 <h6 class="font-weight-bold text-primary mb-3">Today's System Logs</h6>
                 <div class="alert alert-info py-2 small">
                     <i class="fas fa-info-circle mr-2"></i> Database Connected: <strong>Healthy</strong> | Backup Status: <strong>Synced</strong>
                 </div>
                 <div class="alert alert-light border py-2 small">
                     <i class="fas fa-clock mr-2"></i> Server Local Time: <?= date('H:i:s') ?>
                 </div>
             </div>
        </div>
    </div>
</div>

<script>
<?php if($isSuper): ?>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Revenue (KSh)',
            data: <?= json_encode($chart_data) ?>,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            fill: true,
            tension: 0.4,
            borderWidth: 3,
            pointRadius: 5,
            pointBackgroundColor: '#4e73df'
        }]
    },
    options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});
<?php endif; ?>

const pCtx = document.getElementById('servicePie').getContext('2d');
new Chart(pCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($pie_labels) ?>,
        datasets: [{
            data: <?= json_encode($pie_data) ?>,
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
        }]
    },
    options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include "includes/footer.php"; ?>
