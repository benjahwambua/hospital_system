<?php
require_once __DIR__.'/../config/config.php'; require_once __DIR__.'/../includes/session.php'; require_once __DIR__.'/../includes/auth.php';
require_login(); require_module_access($conn,'pharmacy','view');
function ph_count(mysqli $c,string $s):int{$q=$c->query($s);return $q?(int)($q->fetch_assoc()['total']??0):0;}
$queue=ph_count($conn,"SELECT COUNT(*) total FROM pharmacy_queue WHERE status='pending'");
$stock=ph_count($conn,"SELECT COUNT(*) total FROM pharmacy_stock");
$low=ph_count($conn,"SELECT COUNT(*) total FROM pharmacy_stock WHERE quantity<15");
$out=ph_count($conn,"SELECT COUNT(*) total FROM pharmacy_stock WHERE quantity<=0");
$canCreate=can_module_action($conn,'pharmacy','create'); $canEdit=can_module_action($conn,'pharmacy','edit'); $canApprove=can_module_action($conn,'pharmacy','approve');
include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php'; ?>
<style>
.pharmacy-page{padding:26px 24px 42px;background:#f5f7fb;min-height:calc(100vh - 60px)}
.pharmacy-shell{max-width:1500px;margin:0 auto}
.pharmacy-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#0b3d91 0%,#1261c9 55%,#13a8b8 100%);color:#fff;border-radius:22px;padding:30px 32px;display:flex;justify-content:space-between;align-items:center;gap:22px;margin-bottom:22px;box-shadow:0 16px 38px rgba(16,77,153,.18)}.pharmacy-hero:after{content:"";position:absolute;width:210px;height:210px;right:-65px;top:-95px;border-radius:50%;background:rgba(255,255,255,.08);pointer-events:none}.pharmacy-hero>*{position:relative;z-index:1}
.pharmacy-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.8px;font-weight:800;color:#bfe8ff;margin-bottom:5px}
.pharmacy-hero h1{font-size:29px;font-weight:800;margin:5px 0 8px;color:#fff}
.pharmacy-hero p{margin:0;color:rgba(255,255,255,.82);font-size:14px}
.pharmacy-actions{display:grid;grid-template-columns:repeat(2,minmax(145px,1fr));gap:10px;min-width:350px}
.pharmacy-action{display:flex;align-items:center;gap:11px;padding:12px 14px;border:1px solid rgba(255,255,255,.22);border-radius:11px;text-decoration:none;color:#fff;background:rgba(255,255,255,.09);transition:.15s}
.pharmacy-action:hover{transform:translateY(-1px);background:rgba(255,255,255,.15);color:#fff;text-decoration:none}
.pharmacy-action.primary{background:#fff;color:#063b73;border-color:#fff}
.pharmacy-action.primary:hover{background:#f5f9fd;color:#063b73}
.pharmacy-action i{width:28px;text-align:center;font-size:16px}.pharmacy-action span{font-weight:800;font-size:12px;flex:1}.pharmacy-action small{display:block;font-size:9px;color:rgba(255,255,255,.68)}
.pharmacy-action.primary small{color:#667085}
.pharmacy-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:22px}
.pharmacy-stat{background:#fff;border:1px solid #e7ebf2;border-radius:14px;padding:19px 20px;box-shadow:0 4px 15px rgba(31,45,61,.05);display:flex;justify-content:space-between;align-items:flex-start;text-decoration:none;transition:.18s}
.pharmacy-stat:hover{transform:translateY(-1px);box-shadow:0 7px 20px rgba(31,45,61,.08);text-decoration:none}
.pharmacy-stat small{display:block;color:#667085;text-transform:uppercase;font-size:10px;font-weight:800;letter-spacing:.7px}
.pharmacy-stat strong{font-size:28px;color:#25324a;display:block;margin-top:7px}
.pharmacy-stat span{font-size:11px;color:#98a2b3;display:block;margin-top:2px}
.pharmacy-stat .icon{width:40px;height:40px;border-radius:10px;background:#eef4ff;color:#075b9d;display:flex;align-items:center;justify-content:center}
.pharmacy-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:18px}
.pharmacy-panel{background:#fff;border:1px solid #e7ebf2;border-radius:15px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden}
.pharmacy-head{padding:17px 20px;border-bottom:1px solid #edf0f5;display:flex;justify-content:space-between;align-items:center}
.pharmacy-head strong{color:#25324a;font-size:15px}.pharmacy-head small{color:#8792a5}
.pharmacy-body{padding:20px}
.pharmacy-nav{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.pharmacy-nav a{display:flex;align-items:center;gap:12px;padding:14px 15px;border:1px solid #e6ebf2;border-radius:11px;background:#fbfcfe;color:#344054;text-decoration:none;font-size:13px;font-weight:700;transition:.18s}
.pharmacy-nav a:hover{background:#f5faff;border-color:#b9d5ea;transform:translateY(-1px);box-shadow:0 4px 12px rgba(31,45,61,.06);text-decoration:none}
.pharmacy-nav i{width:34px;height:34px;border-radius:9px;background:#f0f6fb;color:#075b9d;display:flex;align-items:center;justify-content:center}
.pharmacy-nav span{display:block}.pharmacy-nav small{display:block;margin-top:3px;color:#98a2b3;font-size:10px;font-weight:500}
.pharmacy-queue-card{height:100%;display:flex;flex-direction:column;justify-content:center;padding:24px}
.pharmacy-queue-icon{width:50px;height:50px;border-radius:13px;background:#eef4ff;color:#075b9d;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:13px}
.pharmacy-queue-card .count{font-size:38px;font-weight:800;color:#25324a;line-height:1}
.pharmacy-queue-card p{color:#7b8798;font-size:13px;margin:8px 0 18px}
.pharmacy-alert{margin-top:18px;padding:13px 15px;border-radius:10px;background:#fff7e6;border:1px solid #f5d79b;color:#8a5a00;font-size:12px}
@media(max-width:1050px){.pharmacy-hero{align-items:flex-start;flex-direction:column}.pharmacy-actions{min-width:0;width:100%}.pharmacy-metrics{grid-template-columns:repeat(2,1fr)}.pharmacy-grid{grid-template-columns:1fr}}
@media(max-width:600px){.pharmacy-page{padding:18px 12px 35px}.pharmacy-hero{padding:24px 20px;border-radius:17px}.pharmacy-hero h1{font-size:24px}.pharmacy-actions{grid-template-columns:1fr}.pharmacy-metrics{grid-template-columns:1fr}.pharmacy-nav{grid-template-columns:1fr}}
</style>
<div class="main-content">
<div class="pharmacy-page"><div class="pharmacy-shell">
    <div class="pharmacy-hero">
        <div>
            <div class="pharmacy-kicker">Pharmacy · Operations</div>
            <h1><i class="fas fa-prescription-bottle-medical mr-2"></i>Pharmacy</h1>
            <p>Manage dispensing, stock and medicine sales from one operational workspace.</p>
        </div>
        <div class="pharmacy-actions">
            <a href="dispensing_queue.php" class="pharmacy-action primary"><i class="fas fa-clipboard-check"></i><span>Dispensing Queue<small>Process pending prescriptions</small></span></a>
            <?php if($canCreate): ?><a href="sell_medicine.php" class="pharmacy-action"><i class="fas fa-prescription"></i><span>Sell Medicine<small>Process pharmacy sales</small></span></a><?php endif; ?>
            <?php if($canEdit): ?><a href="add_stock.php" class="pharmacy-action"><i class="fas fa-box-open"></i><span>Add Stock<small>Receive or record stock</small></span></a><?php endif; ?>
            <a href="view_stock.php" class="pharmacy-action"><i class="fas fa-capsules"></i><span>View Stock<small>Monitor quantity and expiry</small></span></a>
        </div>
    </div>

    <div class="pharmacy-metrics">
        <a class="pharmacy-stat" href="dispensing_queue.php"><div><small>Pending Dispensing</small><strong><?=$queue?></strong><span>Prescriptions awaiting action</span></div><div class="icon"><i class="fas fa-clipboard-check"></i></div></a>
        <a class="pharmacy-stat" href="view_stock.php"><div><small>Stock Items</small><strong><?=$stock?></strong><span>Medicine records</span></div><div class="icon"><i class="fas fa-boxes-stacked"></i></div></a>
        <a class="pharmacy-stat" href="view_stock.php"><div><small>Low Stock</small><strong><?=$low?></strong><span>Below replenishment threshold</span></div><div class="icon"><i class="fas fa-arrow-trend-down"></i></div></a>
        <a class="pharmacy-stat" href="view_stock.php"><div><small>Out of Stock</small><strong><?=$out?></strong><span>Requires replenishment</span></div><div class="icon"><i class="fas fa-circle-exclamation"></i></div></a>
    </div>

    <div class="pharmacy-grid">
        <div class="pharmacy-panel">
            <div class="pharmacy-head"><div><strong>Pharmacy Operations</strong><br><small>Daily dispensing and inventory workflows.</small></div></div>
            <div class="pharmacy-body">
                <div class="pharmacy-nav">
                    <a href="dispensing_queue.php"><i class="fas fa-clipboard-check"></i><span>Dispensing Queue<small>Process prescribed medicines</small></span></a>
                    <?php if($canCreate): ?><a href="sell_medicine.php"><i class="fas fa-prescription"></i><span>Sell Medicine<small>Process pharmacy sales</small></span></a><?php endif; ?>
                    <?php if($canEdit): ?><a href="add_stock.php"><i class="fas fa-box-open"></i><span>Add Stock<small>Receive or record stock</small></span></a><?php endif; ?>
                    <a href="view_stock.php"><i class="fas fa-capsules"></i><span>View Stock<small>Monitor quantities and expiry</small></span></a>
                </div>
                <?php if($low>0): ?><div class="pharmacy-alert"><i class="fas fa-triangle-exclamation mr-1"></i><strong><?=$low?> stock item(s)</strong> are below the replenishment threshold. Review stock before the next dispensing cycle.</div><?php endif; ?>
            </div>
        </div>
        <div class="pharmacy-panel">
            <div class="pharmacy-queue-card">
                <div class="pharmacy-queue-icon"><i class="fas fa-clipboard-check"></i></div>
                <div class="count"><?=$queue?></div>
                <p>prescriptions pending dispensing</p>
                <?php if($canApprove): ?><a href="dispensing_queue.php" class="btn btn-primary btn-block">Open Dispensing Queue</a><?php else: ?><a href="dispensing_queue.php" class="btn btn-outline-primary btn-block">View Dispensing Queue</a><?php endif; ?>
            </div>
        </div>
    </div>
</div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>