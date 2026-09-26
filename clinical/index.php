<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'clinical', 'view');

$todayVisits=0; $inProgress=0; $admitted=0; $pendingLab=0; $pendingRad=0; $pendingRx=0;
if (hms_visits_available($conn)) {
    $q=$conn->query("SELECT COUNT(*) total, SUM(status='In Progress') in_progress FROM visits WHERE visit_date=CURDATE() AND status <> 'Cancelled'");
    if($q && ($row=$q->fetch_assoc())) { $todayVisits=(int)($row['total']??0); $inProgress=(int)($row['in_progress']??0); }
}
if ($q=$conn->query("SELECT COUNT(*) total FROM admissions WHERE status='Admitted'")) $admitted=(int)($q->fetch_assoc()['total']??0);
if ($q=$conn->query("SELECT COUNT(*) total FROM patient_services WHERE category='lab' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')")) $pendingLab=(int)($q->fetch_assoc()['total']??0);
if ($q=$conn->query("SELECT COUNT(*) total FROM patient_services WHERE category='radiology' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')")) $pendingRad=(int)($q->fetch_assoc()['total']??0);
$qc=$conn->query("SHOW TABLES LIKE 'pharmacy_queue'");
if($qc && $qc->num_rows && ($q=$conn->query("SELECT COUNT(*) total FROM pharmacy_queue WHERE status='pending'"))) $pendingRx=(int)($q->fetch_assoc()['total']??0);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.clinical-page{padding:28px 24px 40px;background:#f5f7fb;min-height:calc(100vh - 60px)}
.clinical-shell{max-width:1500px;margin:0 auto}
.clinical-hero{background:#fff;border:1px solid #e7ebf2;border-radius:14px;padding:24px 26px;box-shadow:0 4px 18px rgba(31,45,61,.06);display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:22px}
.clinical-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.4px;font-weight:700;color:#6c7a91;margin-bottom:5px}
.clinical-hero h1{font-size:25px;margin:0 0 6px;color:#25324a;font-weight:700}
.clinical-hero p{margin:0;color:#718096;font-size:14px}
.clinical-actions{display:flex;gap:9px;flex-wrap:wrap}
.clinical-actions a{white-space:nowrap}
.metric-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:22px}
.metric-card{background:#fff;border:1px solid #e7ebf2;border-radius:12px;padding:18px;min-height:118px;box-shadow:0 3px 12px rgba(31,45,61,.045);text-decoration:none;display:block;transition:transform .15s,box-shadow .15s}
.metric-card:hover{transform:translateY(-2px);box-shadow:0 7px 20px rgba(31,45,61,.09);text-decoration:none}
.metric-top{display:flex;justify-content:space-between;align-items:center;color:#748198;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.35px}
.metric-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:#eef4ff;color:#2f6fed}
.metric-value{font-size:27px;font-weight:700;color:#25324a;margin-top:13px}
.dashboard-grid{display:grid;grid-template-columns:2fr 1fr;gap:18px}
.panel{background:#fff;border:1px solid #e7ebf2;border-radius:14px;box-shadow:0 3px 14px rgba(31,45,61,.05);overflow:hidden}
.panel-head{padding:17px 20px;border-bottom:1px solid #edf0f5;display:flex;justify-content:space-between;align-items:center}
.panel-head strong{color:#25324a;font-size:15px}.panel-head small{color:#8792a5}
.panel-body{padding:20px}
.workflow{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.workflow-step{border:1px solid #e7ebf2;border-radius:11px;padding:18px;background:#fbfcfe;position:relative}
.workflow-step .num{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#edf3ff;color:#2f6fed;font-weight:700;font-size:13px;margin-bottom:12px}
.workflow-step h3{font-size:14px;color:#2d3748;margin:0 0 6px}.workflow-step p{font-size:12px;color:#7b8798;min-height:42px;margin-bottom:13px}
.action-list{display:grid;gap:10px}
.action-link{display:flex;align-items:center;justify-content:space-between;padding:13px 14px;border:1px solid #e7ebf2;border-radius:10px;text-decoration:none;color:#344054;background:#fff}
.action-link:hover{background:#f7f9fc;text-decoration:none}
.action-link i{width:28px;color:#2f6fed}.action-link span{flex:1}.action-link small{color:#98a2b3}
.queue-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:18px}
.queue-item{padding:15px;border-radius:10px;background:#f8fafc;border:1px solid #edf0f5}.queue-item strong{display:block;font-size:21px;color:#25324a}.queue-item span{font-size:12px;color:#7b8798}
@media(max-width:1200px){.metric-grid{grid-template-columns:repeat(3,1fr)}}@media(max-width:800px){.clinical-page{padding:18px 12px}.clinical-hero{align-items:flex-start;flex-direction:column}.dashboard-grid{grid-template-columns:1fr}.workflow{grid-template-columns:1fr}.queue-grid{grid-template-columns:1fr 1fr}}@media(max-width:520px){.metric-grid{grid-template-columns:1fr 1fr}.queue-grid{grid-template-columns:1fr}.clinical-hero h1{font-size:21px}}
</style>

<div class="clinical-page"><div class="clinical-shell">
  <div class="clinical-hero">
    <div><div class="clinical-kicker">Clinical Care</div><h1>Clinical Dashboard</h1><p>Start with appointments, open the patient record, and continue the clinical encounter from one workspace.</p></div>
    <div class="clinical-actions"><a href="../patients/appointments.php" class="btn btn-primary"><i class="fas fa-calendar-check mr-1"></i>Appointments</a><a href="../patients/patient_list.php" class="btn btn-outline-primary"><i class="fas fa-users mr-1"></i>Patient List</a></div>
  </div>

  <div class="metric-grid">
    <a class="metric-card" href="../patients/appointments.php"><div class="metric-top"><span>Today's Visits</span><span class="metric-icon"><i class="fas fa-users"></i></span></div><div class="metric-value"><?= $todayVisits ?></div></a>
    <a class="metric-card" href="../patients/patient_list.php"><div class="metric-top"><span>In Progress</span><span class="metric-icon"><i class="fas fa-user-md"></i></span></div><div class="metric-value"><?= $inProgress ?></div></a>
    <a class="metric-card" href="ward_management.php"><div class="metric-top"><span>Admitted</span><span class="metric-icon"><i class="fas fa-bed"></i></span></div><div class="metric-value"><?= $admitted ?></div></a>
    <a class="metric-card" href="../lab/lab_results.php"><div class="metric-top"><span>Pending Lab</span><span class="metric-icon"><i class="fas fa-vial"></i></span></div><div class="metric-value"><?= $pendingLab ?></div></a>
    <a class="metric-card" href="../radiology/radiology_requests.php"><div class="metric-top"><span>Pending Radiology</span><span class="metric-icon"><i class="fas fa-x-ray"></i></span></div><div class="metric-value"><?= $pendingRad ?></div></a>
    <a class="metric-card" href="../pharmacy/dispensing_queue.php"><div class="metric-top"><span>Pending Pharmacy</span><span class="metric-icon"><i class="fas fa-pills"></i></span></div><div class="metric-value"><?= $pendingRx ?></div></a>
  </div>

  <div class="dashboard-grid">
    <div class="panel">
      <div class="panel-head"><strong>Clinical Workflow</strong><small>Odoo-style care flow</small></div>
      <div class="panel-body">
        <div class="workflow">
          <div class="workflow-step"><div class="num">1</div><h3>Appointments</h3><p>Review scheduled patients and open the correct encounter.</p><a href="../patients/appointments.php" class="btn btn-sm btn-primary">Open Appointments</a></div>
          <div class="workflow-step"><div class="num">2</div><h3>Patient Record</h3><p>Search registered patients and open their complete clinical workspace.</p><a href="../patients/patient_list.php" class="btn btn-sm btn-outline-primary">Patient List</a></div>
          <div class="workflow-step"><div class="num">3</div><h3>Clinical Care</h3><p>Document assessment, orders, prescriptions, investigations and follow-up.</p><span class="badge badge-info">Patient Dashboard</span></div>
        </div>
        <div class="queue-grid">
          <div class="queue-item"><strong><?= $pendingLab ?></strong><span>Laboratory items awaiting completion</span></div>
          <div class="queue-item"><strong><?= $pendingRad ?></strong><span>Radiology items awaiting completion</span></div>
          <div class="queue-item"><strong><?= $pendingRx ?></strong><span>Prescriptions awaiting dispensing</span></div>
        </div>
      </div>
    </div>
    <div class="panel">
      <div class="panel-head"><strong>Clinical Actions</strong><small>Quick access</small></div>
      <div class="panel-body"><div class="action-list">
        <a class="action-link" href="../patients/appointments.php"><i class="fas fa-calendar-check"></i><span>Appointments</span><small>Schedule / attend</small></a>
        <a class="action-link" href="../patients/patient_list.php"><i class="fas fa-address-book"></i><span>Patient List</span><small>Find patient</small></a>
        <a class="action-link" href="../clinical/orders.php"><i class="fas fa-flask"></i><span>Orders & Referrals</span><small>Clinical orders</small></a>
        <a class="action-link" href="ward_management.php"><i class="fas fa-bed"></i><span>Ward / IPD</span><small>Inpatients</small></a>
        <a class="action-link" href="../diagnostics/diagnostics.php"><i class="fas fa-diagnoses"></i><span>Diagnostics</span><small>Investigations</small></a>
      </div></div>
    </div>
  </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>