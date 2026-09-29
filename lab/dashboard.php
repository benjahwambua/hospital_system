<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'laboratory', 'view');

$canCreate = can_module_action($conn, 'laboratory', 'create');
$canApprove = can_module_action($conn, 'laboratory', 'approve');

function lab_count(mysqli $conn, string $sql): int {
    $q = $conn->query($sql);
    return $q ? (int)($q->fetch_assoc()['total'] ?? 0) : 0;
}
$pending = lab_count($conn, "SELECT COUNT(*) total FROM patient_services WHERE category='lab' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')");
$completedToday = lab_count($conn, "SELECT COUNT(*) total FROM patient_services WHERE category='lab' AND status='Completed' AND DATE(created_at)=CURDATE()");
$cancelledToday = lab_count($conn, "SELECT COUNT(*) total FROM patient_services WHERE category='lab' AND status='Cancelled' AND DATE(created_at)=CURDATE()");
$testsToday = lab_count($conn, "SELECT COUNT(*) total FROM patient_services WHERE category='lab' AND DATE(created_at)=CURDATE()");
$lowStock = lab_count($conn, "SELECT COUNT(*) total FROM lab_inventory WHERE quantity <= COALESCE(reorder_level,5)");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.lab-dash{padding:28px 24px 42px;background:#f5f7fb;min-height:calc(100vh - 60px)}.lab-shell{max-width:1500px;margin:auto}
.lab-hero{background:linear-gradient(135deg,#0d6efd,#174ea6);color:#fff;border-radius:18px;padding:28px 30px;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 12px 30px rgba(13,110,253,.2);margin-bottom:20px}.lab-hero h1{font-size:28px;margin:5px 0}.lab-hero p{margin:0;color:rgba(255,255,255,.8);font-size:14px}.lab-kicker{text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:800;color:#bcd6ff}.lab-actions{display:flex;gap:9px;flex-wrap:wrap}.lab-actions .btn-light{color:#1451a0}
.lab-metrics{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px}.lab-stat{background:#fff;border:1px solid #e7ebf2;border-radius:14px;padding:18px;box-shadow:0 4px 14px rgba(31,45,61,.05)}.lab-stat small{display:block;color:#7b8798;text-transform:uppercase;font-size:10px;font-weight:800;letter-spacing:.5px}.lab-stat strong{display:block;font-size:28px;color:#25324a;margin-top:7px}
.lab-grid{display:grid;grid-template-columns:2fr 1fr;gap:18px}.lab-panel{background:#fff;border:1px solid #e7ebf2;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden}.lab-head{padding:16px 20px;border-bottom:1px solid #edf0f5;display:flex;justify-content:space-between;align-items:center}.lab-head strong{color:#25324a}.lab-body{padding:20px}.lab-links{display:grid;grid-template-columns:1fr 1fr;gap:12px}.lab-link{padding:17px;border:1px solid #e7ebf2;border-radius:12px;text-decoration:none;color:#344054;background:#fbfcfe;display:flex;align-items:center;gap:13px}.lab-link:hover{background:#f2f7ff;text-decoration:none}.lab-link i{width:34px;height:34px;border-radius:9px;background:#edf4ff;color:#0d6efd;display:flex;align-items:center;justify-content:center}.lab-status{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #edf0f5}.lab-status:last-child{border-bottom:0}.lab-status span{color:#667085;font-size:13px}.lab-status strong{color:#25324a}
@media(max-width:1050px){.lab-metrics{grid-template-columns:repeat(3,1fr)}.lab-grid{grid-template-columns:1fr}}@media(max-width:600px){.lab-dash{padding:18px 12px}.lab-hero{flex-direction:column;align-items:flex-start}.lab-metrics{grid-template-columns:1fr 1fr}.lab-links{grid-template-columns:1fr}}
</style>
<div class="lab-dash"><div class="lab-shell">
  <div class="lab-hero"><div><div class="lab-kicker">Laboratory</div><h1>Laboratory Dashboard</h1><p>Monitor requests, results and laboratory inventory from one workspace.</p></div><div class="lab-actions"><?php if($canCreate): ?><a href="lab_requests.php" class="btn btn-light"><i class="fas fa-plus mr-1"></i>New Request</a><?php endif; ?><a href="lab_results.php" class="btn btn-outline-light"><i class="fas fa-poll-h mr-1"></i>Results</a></div></div>
  <div class="lab-metrics">
    <a href="lab_results.php" class="lab-stat text-decoration-none"><small>Pending Results</small><strong><?= $pending ?></strong></a>
    <a href="lab_results.php" class="lab-stat text-decoration-none"><small>Completed Today</small><strong><?= $completedToday ?></strong></a>
    <div class="lab-stat"><small>Requests Today</small><strong><?= $testsToday ?></strong></div>
    <div class="lab-stat"><small>Cancelled Today</small><strong><?= $cancelledToday ?></strong></div>
    <a href="inventory/index.php" class="lab-stat text-decoration-none"><small>Low Stock Items</small><strong><?= $lowStock ?></strong></a>
  </div>
  <div class="lab-grid">
    <div class="lab-panel"><div class="lab-head"><strong>Laboratory Operations</strong><small class="text-muted">Quick access</small></div><div class="lab-body"><div class="lab-links">
      <?php if($canCreate): ?><a class="lab-link" href="lab_requests.php"><i class="fas fa-vial"></i><span><strong>Lab Requests</strong><small class="d-block text-muted">Create laboratory requests</small></span></a><?php endif; ?>
      <a class="lab-link" href="lab_results.php"><i class="fas fa-poll-h"></i><span><strong>Lab Results</strong><small class="d-block text-muted">Process and complete results</small></span></a>
      <a class="lab-link" href="inventory/index.php"><i class="fas fa-boxes"></i><span><strong>Lab Inventory</strong><small class="d-block text-muted">Reagents and consumables</small></span></a>
    </div></div></div>
    <div class="lab-panel"><div class="lab-head"><strong>Work Status</strong></div><div class="lab-body">
      <div class="lab-status"><span>Pending work</span><strong><?= $pending ?></strong></div>
      <div class="lab-status"><span>Completed today</span><strong><?= $completedToday ?></strong></div>
      <div class="lab-status"><span>Low stock</span><strong><?= $lowStock ?></strong></div>
      <?php if($canApprove): ?><a href="lab_results.php" class="btn btn-primary btn-block mt-3"><i class="fas fa-check-circle mr-1"></i>Open Results Queue</a><?php endif; ?>
    </div></div>
  </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>