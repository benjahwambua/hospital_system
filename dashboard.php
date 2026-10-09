<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/permissions.php';
require_login();

// The Command Centre adapts its operational view to the signed-in user's module access.
$canFrontDesk = can_access_module($conn, 'front_desk');
$canClinical = can_access_module($conn, 'clinical');
$canLaboratory = can_access_module($conn, 'laboratory');
$canRadiology = can_access_module($conn, 'radiology');
$canPharmacy = can_access_module($conn, 'pharmacy');
$canFinance = can_access_module($conn, 'finance');
$canProcurement = can_access_module($conn, 'procurement');
$canMaternity = can_access_module($conn, 'maternity');
$canInsurance = can_access_module($conn, 'insurance');
$canNursing = can_access_module($conn, 'nursing');
$canPatientOverview = $canFrontDesk || $canClinical;
$canDiagnosticOverview = $canLaboratory || $canRadiology;
$canServiceOverview = $canClinical || $canLaboratory || $canRadiology;

$currentUserId=(int)($_SESSION['user_id']??0);
$isSuper=false;
if($currentUserId>0){
    $s=$conn->prepare("SELECT is_super FROM users WHERE id=? LIMIT 1");
    if($s){$s->bind_param('i',$currentUserId);$s->execute();$isSuper=(bool)($s->get_result()->fetch_assoc()['is_super']??0);$s->close();}
}
function dash_count(mysqli $c,string $sql):int{$q=$c->query($sql);return $q?(int)($q->fetch_assoc()['total']??0):0;}
function dash_amount(mysqli $c,string $sql):float{$q=$c->query($sql);return $q?(float)($q->fetch_assoc()['total']??0):0.0;}

$patients=dash_count($conn,"SELECT COUNT(*) total FROM patients");
$todayVisits=dash_count($conn,"SELECT COUNT(*) total FROM visits WHERE visit_date=CURDATE() AND COALESCE(status,'')<>'Cancelled'");
$appointments=dash_count($conn,"SELECT COUNT(*) total FROM appointments WHERE DATE(appointment_date)=CURDATE() AND COALESCE(status,'') NOT IN ('Cancelled','Closed','Completed')");
$labPending=dash_count($conn,"SELECT COUNT(*) total FROM patient_services WHERE category='lab' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')");
$radPending=dash_count($conn,"SELECT COUNT(*) total FROM patient_services WHERE category='radiology' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')");
$rxPending=dash_count($conn,"SELECT COUNT(*) total FROM pharmacy_queue WHERE status='pending'");
$admitted=dash_count($conn,"SELECT COUNT(*) total FROM admissions WHERE status='Admitted'");
$maternityUpcoming = $canMaternity ? dash_count($conn,"SELECT COUNT(*) total FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.appointment_date>=NOW() AND p.clinic_category IN ('ANC','PNC','Maternity') AND COALESCE(a.status,'') NOT IN ('Cancelled','Completed')") : 0;
$pendingPurchaseOrders = $canProcurement ? dash_count($conn,"SELECT COUNT(*) total FROM purchase_orders WHERE status='Pending'") : 0;
$openInsuranceClaims = 0;
if ($canInsurance) {
    $claimsTable = $conn->query("SHOW TABLES LIKE 'claim_headers'");
    if ($claimsTable && $claimsTable->num_rows > 0) {
        $openInsuranceClaims = dash_count($conn,"SELECT COUNT(*) total FROM claim_headers WHERE claim_status IN ('Draft','Submitted','Under Review','Partially Approved')");
    }
}
$lowStock=dash_count($conn,"SELECT COUNT(*) total FROM pharmacy_stock WHERE quantity<15");
$revenue=$isSuper?dash_amount($conn,"SELECT COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.created_at>=CURDATE() AND p.created_at<CURDATE()+INTERVAL 1 DAY),0)-COALESCE((SELECT SUM(r.amount) FROM payment_refunds r WHERE r.created_at>=CURDATE() AND r.created_at<CURDATE()+INTERVAL 1 DAY AND r.status='Approved'),0) total"):0;

$chartLabels=[];$chartData=[];
if($isSuper){
    $q=$conn->query("SELECT DATE_FORMAT(d.day,'%d %b') day, COALESCE(p.total,0)-COALESCE(r.total,0) total FROM (SELECT DATE_SUB(CURDATE(),INTERVAL 6 DAY) day UNION ALL SELECT DATE_SUB(CURDATE(),INTERVAL 5 DAY) UNION ALL SELECT DATE_SUB(CURDATE(),INTERVAL 4 DAY) UNION ALL SELECT DATE_SUB(CURDATE(),INTERVAL 3 DAY) UNION ALL SELECT DATE_SUB(CURDATE(),INTERVAL 2 DAY) UNION ALL SELECT DATE_SUB(CURDATE(),INTERVAL 1 DAY) UNION ALL SELECT CURDATE()) d LEFT JOIN (SELECT DATE(created_at) day,SUM(amount) total FROM payments WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) AND created_at<CURDATE()+INTERVAL 1 DAY GROUP BY DATE(created_at)) p ON p.day=d.day LEFT JOIN (SELECT DATE(created_at) day,SUM(amount) total FROM payment_refunds WHERE status='Approved' AND created_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) AND created_at<CURDATE()+INTERVAL 1 DAY GROUP BY DATE(created_at)) r ON r.day=d.day ORDER BY d.day");
    if($q)while($r=$q->fetch_assoc()){ $chartLabels[]=$r['day'];$chartData[]=(float)$r['total']; }
}
$serviceLabels=[];$serviceData=[];
$serviceConditions=[];
if($canLaboratory) $serviceConditions[]="category='lab'";
if($canRadiology) $serviceConditions[]="category='radiology'";
if($canClinical) $serviceConditions[]="(category IS NULL OR category NOT IN ('lab','radiology'))";
if($serviceConditions){
    $serviceSql="SELECT COALESCE(category,'Other') category,COUNT(*) total FROM patient_services WHERE (".implode(' OR ',$serviceConditions).") GROUP BY category ORDER BY total DESC LIMIT 6";
    $q=$conn->query($serviceSql);
    if($q)while($r=$q->fetch_assoc()){ $serviceLabels[]=$r['category'];$serviceData[]=(int)$r['total']; }
}

$lowItems=$conn->query("SELECT drug_name,quantity FROM pharmacy_stock WHERE quantity<15 ORDER BY quantity ASC LIMIT 5");

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.exec-page{padding:26px 24px 48px;background:#f4f7fb;min-height:calc(100vh - 60px)}.exec-shell{width:100%;max-width:1550px;margin:0 auto}
.exec-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#0b3d91 0%,#1261c9 55%,#13a8b8 100%);border-radius:22px;padding:36px 38px;color:#fff;box-shadow:0 16px 38px rgba(16,77,153,.22);margin-bottom:22px}.exec-hero:before,.exec-hero:after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.08)}.exec-hero:before{width:240px;height:240px;right:-70px;top:-100px}.exec-hero:after{width:160px;height:160px;right:130px;bottom:-100px}.exec-hero-inner{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:flex-end;gap:25px}.exec-kicker{text-transform:uppercase;letter-spacing:2px;font-size:11px;font-weight:800;color:#bfe8ff}.exec-hero h1{font-size:clamp(27px,2.2vw,34px);line-height:1.15;margin:7px 0 8px;font-weight:800;letter-spacing:-.6px}.exec-hero p{margin:0;color:rgba(255,255,255,.82);font-size:14px}.exec-time{text-align:right;font-size:12px;color:rgba(255,255,255,.75)}.exec-time strong{display:block;color:#fff;font-size:16px;margin-bottom:3px}
.exec-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:22px}.exec-card{background:#fff;border:1px solid #e7ebf2;border-radius:15px;padding:19px;box-shadow:0 4px 15px rgba(31,45,61,.045);text-decoration:none;display:block;transition:.18s}.exec-card:hover{transform:translateY(-2px);box-shadow:0 9px 22px rgba(31,45,61,.09);text-decoration:none}.exec-card-top{display:flex;justify-content:space-between;align-items:center}.exec-card small{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#7b8798;font-weight:800}.exec-icon{width:39px;height:39px;border-radius:11px;background:#edf4ff;color:#1261c9;display:flex;align-items:center;justify-content:center}.exec-value{font-size:28px;font-weight:800;color:#24344d;margin-top:11px}.exec-sub{font-size:11px;color:#98a2b3;margin-top:2px}
.exec-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(300px,1fr);gap:18px}.exec-panel{background:#fff;border:1px solid #e7ebf2;border-radius:16px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden}.exec-head{padding:17px 20px;border-bottom:1px solid #edf0f5;display:flex;justify-content:space-between;align-items:center}.exec-head strong{color:#25324a;font-size:15px}.exec-head small{color:#98a2b3}.exec-body{padding:20px}
.quick-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:11px}.quick{border:1px solid #e7ebf2;border-radius:12px;padding:15px;text-decoration:none;color:#344054;background:#fbfcfe;display:flex;align-items:center;gap:12px}.quick:hover{background:#f3f8ff;text-decoration:none}.quick i{width:34px;height:34px;border-radius:9px;background:#edf4ff;color:#1261c9;display:flex;align-items:center;justify-content:center}.quick strong{font-size:13px}.quick span{display:block;font-size:10px;color:#98a2b3;margin-top:2px}
.alert-list{display:grid;gap:9px}.alert-row{display:flex;justify-content:space-between;align-items:center;padding:12px 13px;border:1px solid #edf0f5;border-radius:10px}.alert-row .label{font-size:12px;color:#667085}.alert-row strong{font-size:16px;color:#25324a}.alert-row.danger{border-color:#f7d7d4;background:#fff9f8}.alert-row.danger strong{color:#d64545}
.bottom-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);gap:18px;margin-top:20px}.stock-row{display:flex;justify-content:space-between;align-items:center;padding:11px 0;border-bottom:1px solid #edf0f5}.stock-row:last-child{border-bottom:0}.stock-name{font-size:12px;font-weight:700;color:#344054}.stock-qty{font-size:11px;font-weight:800;color:#d64545;background:#fff0ef;padding:4px 8px;border-radius:20px}.empty-state{padding:18px;text-align:center;color:#98a2b3;font-size:13px}
.restricted{background:#fff8e6;border:1px solid #ffe3a3;color:#805d00;border-radius:10px;padding:12px 14px;font-size:12px}
@media(max-width:1100px){.exec-grid,.bottom-grid{grid-template-columns:minmax(0,1fr)}.exec-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:700px){.exec-page{padding:18px 12px}.exec-hero{padding:25px 22px}.exec-hero-inner{align-items:flex-start;flex-direction:column}.exec-time{text-align:left}.exec-metrics{grid-template-columns:1fr 1fr}.quick-grid{grid-template-columns:1fr}}@media(max-width:450px){.exec-metrics{grid-template-columns:1fr}}
</style>

<div class="exec-page"><div class="exec-shell">
  <section class="exec-hero"><div class="exec-hero-inner"><div><div class="exec-kicker">Emaqure Medical Centre</div><h1>Hospital Command Centre</h1><p>A clear view of today's patient flow, clinical work and hospital operations.</p></div><div class="exec-time"><strong><?= date('l, d M Y') ?></strong><?= date('H:i') ?> EAT</div></div></section>

  <section class="exec-metrics">
    <?php if($canPatientOverview): ?><a class="exec-card" href="patients/patient_list.php"><div class="exec-card-top"><small>Total Patients</small><span class="exec-icon"><i class="fas fa-users"></i></span></div><div class="exec-value"><?= number_format($patients) ?></div><div class="exec-sub">Registered patients</div></a><?php endif; ?>
    <?php if($canFrontDesk || $canClinical): ?><a class="exec-card" href="patients/appointments.php"><div class="exec-card-top"><small>Today's Visits</small><span class="exec-icon"><i class="fas fa-user-md"></i></span></div><div class="exec-value"><?= number_format($todayVisits) ?></div><div class="exec-sub"><?= $appointments ?> appointments still open</div></a><?php endif; ?>
    <?php if($canClinical || $canNursing): ?><a class="exec-card" href="<?= $canClinical ? 'clinical/index.php' : 'nursing/index.php' ?>"><div class="exec-card-top"><small>Admitted</small><span class="exec-icon"><i class="fas fa-bed"></i></span></div><div class="exec-value"><?= number_format($admitted) ?></div><div class="exec-sub">Current admissions</div></a><?php endif; ?>
    <?php if($canDiagnosticOverview): ?><a class="exec-card" href="<?= $canLaboratory ? 'lab/lab_results.php' : 'radiology/radiology_results.php' ?>"><div class="exec-card-top"><small>Diagnostic Queue</small><span class="exec-icon"><i class="fas fa-vials"></i></span></div><div class="exec-value"><?= number_format(($canLaboratory ? $labPending : 0)+($canRadiology ? $radPending : 0)) ?></div><div class="exec-sub"><?php if($canLaboratory): ?><?= $labPending ?> lab<?php endif; ?><?php if($canLaboratory && $canRadiology): ?> · <?php endif; ?><?php if($canRadiology): ?><?= $radPending ?> radiology<?php endif; ?></div></a><?php endif; ?>
  </section>

  <section class="exec-grid">
    <div class="exec-panel"><div class="exec-head"><strong>Quick Access</strong><small>Common hospital actions</small></div><div class="exec-body"><div class="quick-grid">
      <?php if($canFrontDesk): ?><a class="quick" href="patients/reception_register.php"><i class="fas fa-user-plus"></i><div><strong>Register Patient</strong><span>Front Desk</span></div></a><?php endif; ?>
      <?php if($canFrontDesk || $canClinical): ?><a class="quick" href="patients/appointments.php"><i class="fas fa-calendar-check"></i><div><strong>Appointments</strong><span>Today's schedule</span></div></a><?php endif; ?>
      <?php if($canClinical): ?><a class="quick" href="clinical/index.php"><i class="fas fa-stethoscope"></i><div><strong>Clinical</strong><span>Patient care</span></div></a><?php endif; ?>
      <?php if($canLaboratory): ?><a class="quick" href="lab/dashboard.php"><i class="fas fa-microscope"></i><div><strong>Laboratory</strong><span>Requests & results</span></div></a><?php endif; ?>
      <?php if($canPharmacy): ?><a class="quick" href="pharmacy/dashboard.php"><i class="fas fa-pills"></i><div><strong>Pharmacy</strong><span>Dispensing & stock</span></div></a><?php endif; ?>
      <?php if($canFinance): ?><a class="quick" href="cashier/index.php"><i class="fas fa-cash-register"></i><div><strong>Cashier</strong><span>Patient collections</span></div></a><?php endif; ?>
      <?php if($canProcurement): ?><a class="quick" href="stores/index.php"><i class="fas fa-boxes"></i><div><strong>Central Stores</strong><span>Stock &amp; requisitions</span></div></a><?php endif; ?>
    </div></div></div>
    <div class="exec-panel"><div class="exec-head"><strong>Operational Pulse</strong><small>Live queue counts</small></div><div class="exec-body"><div class="alert-list">
      <?php if($canLaboratory): ?><a href="lab/lab_results.php" class="alert-row text-decoration-none"><span class="label">Pending laboratory</span><strong><?= $labPending ?></strong></a><?php endif; ?>
      <?php if($canRadiology): ?><a href="radiology/radiology_results.php" class="alert-row text-decoration-none"><span class="label">Pending radiology</span><strong><?= $radPending ?></strong></a><?php endif; ?>
      <?php if($canPharmacy): ?><a href="pharmacy/dispensing_queue.php" class="alert-row text-decoration-none"><span class="label">Pending pharmacy</span><strong><?= $rxPending ?></strong></a><?php endif; ?>
      <?php if($canPharmacy): ?><a href="pharmacy/view_stock.php" class="alert-row danger text-decoration-none"><span class="label">Low pharmacy stock</span><strong><?= $lowStock ?></strong></a><?php endif; ?>
      <?php if($canMaternity): ?><a href="maternity/index.php" class="alert-row text-decoration-none"><span class="label">Upcoming maternity appointments</span><strong><?= $maternityUpcoming ?></strong></a><?php endif; ?>
      <?php if($canProcurement): ?><a href="procurement/purchase_orders.php?status=Pending" class="alert-row text-decoration-none"><span class="label">Purchase orders pending approval</span><strong><?= $pendingPurchaseOrders ?></strong></a><?php endif; ?>
      <?php if($canInsurance && $claimsTable && $claimsTable->num_rows > 0): ?><a href="insurance/claims.php" class="alert-row text-decoration-none"><span class="label">Open insurance claims</span><strong><?= $openInsuranceClaims ?></strong></a><?php endif; ?>
    </div></div></div>
  </section>

  <section class="bottom-grid">
    <?php if($canPharmacy): ?><div class="exec-panel"><div class="exec-head"><strong>Low Stock Watch</strong><a href="pharmacy/view_stock.php" class="small">View stock</a></div><div class="exec-body">
      <?php if($lowItems && $lowItems->num_rows): while($item=$lowItems->fetch_assoc()): ?><div class="stock-row"><span class="stock-name"><?= htmlspecialchars($item['drug_name']) ?></span><span class="stock-qty"><?= (int)$item['quantity'] ?> left</span></div><?php endwhile; else: ?><div class="empty-state">No low-stock medicines.</div><?php endif; ?>
    </div></div><?php endif; ?>
    <?php if($canServiceOverview): ?><div class="exec-panel"><div class="exec-head"><strong>Service Activity</strong><small>Current records</small></div><div class="exec-body"><canvas id="serviceChart" height="220" role="img" aria-label="Service activity by category"></canvas></div></div>
    <?php endif; ?>
  </section>

  <?php if($isSuper): ?>
  <section class="exec-panel mt-3"><div class="exec-head"><strong>Financial Overview</strong><small>Super User</small></div><div class="exec-body"><div class="row align-items-center"><div class="col-md-4"><div class="text-muted small text-uppercase font-weight-bold">Today's Net Collections</div><div style="font-size:30px;font-weight:800;color:#24344d">KES <?= number_format($revenue,2) ?></div><a href="accounting/dashboard.php" class="btn btn-sm btn-outline-primary mt-2">Finance Dashboard</a></div><div class="col-md-8"><canvas id="revenueChart" height="120" role="img" aria-label="Collections trend over the last seven days"></canvas></div></div></div></section>
  <?php else: ?><div class="restricted mt-3"><i class="fas fa-shield-alt mr-1"></i> Financial analytics are available to Super Users.</div><?php endif; ?>
</div></div>

<script>
const serviceCtx=document.getElementById('serviceChart');
if(serviceCtx){new Chart(serviceCtx,{type:'doughnut',data:{labels:<?= json_encode($serviceLabels) ?>,datasets:[{data:<?= json_encode($serviceData) ?>,backgroundColor:['#1261c9','#13a8b8','#5b35d5','#0ca678','#d97706','#64748b'],borderWidth:0}]},options:{cutout:'68%',plugins:{legend:{position:'bottom',labels:{boxWidth:10,padding:14,font:{size:11}}}}}});}
<?php if($isSuper): ?>const revCtx=document.getElementById('revenueChart');if(revCtx){new Chart(revCtx,{type:'line',data:{labels:<?= json_encode($chartLabels) ?>,datasets:[{label:'Collections',data:<?= json_encode($chartData) ?>,borderColor:'#1261c9',backgroundColor:'rgba(18,97,201,.08)',fill:true,tension:.35,borderWidth:2,pointRadius:3}]},options:{maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});}<?php endif; ?>
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>