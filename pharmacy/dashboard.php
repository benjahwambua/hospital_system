<?php
require_once __DIR__.'/../config/config.php'; require_once __DIR__.'/../includes/session.php'; require_once __DIR__.'/../includes/auth.php';
require_login(); require_module_access($conn,'pharmacy','view');
function ph_count(mysqli $c,string $s):int{$q=$c->query($s);return $q?(int)($q->fetch_assoc()['total']??0):0;}
$queue=ph_count($conn,"SELECT COUNT(*) total FROM pharmacy_queue WHERE status='pending'");
$stock=ph_count($conn,"SELECT COUNT(*) total FROM pharmacy_stock");
$low=ph_count($conn,"SELECT COUNT(*) total FROM pharmacy_stock WHERE quantity<15");
$canCreate=can_module_action($conn,'pharmacy','create'); $canEdit=can_module_action($conn,'pharmacy','edit'); $canApprove=can_module_action($conn,'pharmacy','approve');
include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php'; ?>
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
<div class="main-content"><div class="container-fluid hms-workspace"><div class="hms-hero"><div><div class="hms-kicker">Pharmacy · Operations</div><h1><i class="fas fa-prescription-bottle-medical mr-2"></i> Pharmacy</h1><p>Manage dispensing, stock and medicine sales from one operational workspace.</p></div><div class="hms-actions"><a href="dispensing_queue.php" class="btn btn-primary"><i class="fas fa-clipboard-check mr-1"></i> Dispensing Queue</a></div></div><div class="row mb-4"><div class="col-lg-4 col-md-6 mb-3"><div class="card"><div class="hms-stat d-flex justify-content-between"><div><div class="label">Pending Dispensing</div><div class="value"><?=$queue?></div><div class="hint">Prescriptions awaiting action</div></div><div class="hms-icon"><i class="fas fa-clipboard-check"></i></div></div></div></div><div class="col-lg-4 col-md-6 mb-3"><div class="card"><div class="hms-stat d-flex justify-content-between"><div><div class="label">Stock Items</div><div class="value"><?=$stock?></div><div class="hint">Medicine records</div></div><div class="hms-icon"><i class="fas fa-boxes-stacked"></i></div></div></div></div><div class="col-lg-4 col-md-6 mb-3"><div class="card"><div class="hms-stat d-flex justify-content-between"><div><div class="label">Low Stock</div><div class="value"><?=$low?></div><div class="hint">Below replenishment threshold</div></div><div class="hms-icon"><i class="fas fa-arrow-trend-down"></i></div></div></div></div></div><div class="d-none"><a href="dispensing_queue.php" class="pc text-decoration-none"><small>Pending Dispensing</small><strong><?= $queue ?></strong></a><a href="view_stock.php" class="pc text-decoration-none"><small>Stock Items</small><strong><?= $stock ?></strong></a><a href="view_stock.php" class="pc text-decoration-none"><small>Low Stock</small><strong><?= $low ?></strong></a></div></div>
<div class="row"><div class="card mb-4"><div class="card-header py-3"><strong>Pharmacy Operations</strong></div><div class="card-body"><div class="plinks"><a href="dispensing_queue.php"><i class="fas fa-clipboard-check"></i><strong>Dispensing Queue</strong></a><?php if($canCreate): ?><a href="sell_medicine.php"><i class="fas fa-prescription"></i><strong>Sell Medicine</strong></a><?php endif; ?><?php if($canEdit): ?><a href="add_stock.php"><i class="fas fa-box-open"></i><strong>Add Stock</strong></a><?php endif; ?><a href="view_stock.php"><i class="fas fa-capsules"></i><strong>View Stock</strong></a></div></div></div><div class="pp"><div class="pph"><strong>Queue</strong></div><div class="ppb"><h2><?= $queue ?></h2><small class="text-muted">prescriptions pending dispensing</small><?php if($canApprove): ?><a href="dispensing_queue.php" class="btn btn-primary btn-block mt-3">Open Queue</a><?php endif; ?></div></div></div></div></div><?php include __DIR__.'/../includes/footer.php'; ?>