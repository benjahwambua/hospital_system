<?php
// maternity/stats.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// totals
$total_maternity = $conn->query("SELECT COUNT(*) AS c FROM maternity")->fetch_assoc()['c'];
$total_deliveries = $conn->query("SELECT COUNT(*) AS c FROM maternity_delivery")->fetch_assoc()['c'];
$total_babies = $conn->query("SELECT COUNT(*) AS c FROM maternity_baby")->fetch_assoc()['c'];

// monthly deliveries (last 6 months)
$monthly = [];
$res = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c FROM maternity_delivery GROUP BY ym ORDER BY ym DESC LIMIT 6");
while($r=$res->fetch_assoc()) $monthly[] = $r;

// recent deliveries
$recent = $conn->query("SELECT d.*, p.full_name FROM maternity_delivery d JOIN maternity m ON m.id=d.maternity_id JOIN patients p ON p.id=m.patient_id ORDER BY d.created_at DESC LIMIT 20");
?>

<div class="main">
  <div class="page-title">Maternity Statistics</div>

  <div class="card" style="display:flex;gap:12px;">
    <div style="flex:1">
      <div class="stat-card"><strong>Total Maternity Records</strong><div class="stat-number"><?= $total_maternity ?></div></div>
    </div>
    <div style="flex:1">
      <div class="stat-card"><strong>Total Deliveries</strong><div class="stat-number"><?= $total_deliveries ?></div></div>
    </div>
    <div style="flex:1">
      <div class="stat-card"><strong>Total Babies</strong><div class="stat-number"><?= $total_babies ?></div></div>
    </div>
  </div>

  <div class="card" style="margin-top:12px">
    <h4>Monthly Deliveries (last months)</h4>
    <table class="table">
      <thead><tr><th>Month</th><th>Count</th></tr></thead>
      <tbody>
        <?php foreach($monthly as $m): ?>
          <tr><td><?= htmlspecialchars($m['ym']) ?></td><td><?= htmlspecialchars($m['c']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-top:8px;">
      <a class="btn" href="stats_export_csv.php">Export CSV</a>
    </div>
  </div>

  <div class="card" style="margin-top:12px">
    <h4>Recent Deliveries</h4>
    <table class="table">
      <thead><tr><th>Date</th><th>Patient</th><th>Mode</th><th>Baby Weight</th></tr></thead>
      <tbody>
        <?php while($r=$recent->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['created_at']) ?></td>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><?= htmlspecialchars($r['delivery_mode'] ?? $r['type'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['baby_weight'] ?? '') ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
