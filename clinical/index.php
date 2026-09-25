<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

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
<div class="main-content"><div class="container-fluid pt-4">
<div class="d-flex justify-content-between align-items-center mb-4">
 <div><h4 class="font-weight-bold text-gray-800 mb-1">Clinical Dashboard</h4><div class="text-muted">Clinical workflow starts with appointments and continues from the patient dashboard.</div></div>
 <div><a href="../patients/appointments.php" class="btn btn-primary mr-2"><i class="fas fa-calendar-check mr-1"></i> Appointments</a><a href="../patients/patient_list.php" class="btn btn-outline-primary"><i class="fas fa-address-book mr-1"></i> Patient List</a></div>
</div>

<div class="row">
 <?php foreach([
  ['Today\'s Visits',$todayVisits,'fa-users','primary','../patients/patient_list.php'],
  ['In Progress',$inProgress,'fa-user-md','info','../patients/patient_list.php'],
  ['Admitted',$admitted,'fa-bed','success','ward_management.php'],
  ['Pending Lab',$pendingLab,'fa-vial','warning','../lab/lab_results.php'],
  ['Pending Radiology',$pendingRad,'fa-x-ray','info','../radiology/radiology_requests.php'],
  ['Pending Pharmacy',$pendingRx,'fa-pills','danger','../pharmacy/dispensing_queue.php']
 ] as $c): ?>
 <div class="col-xl-2 col-md-4 col-sm-6 mb-4"><a href="<?=$c[4]?>" class="text-decoration-none"><div class="card shadow-sm border-left-<?=$c[3]?> h-100"><div class="card-body"><div class="text-xs font-weight-bold text-<?=$c[3]?> text-uppercase mb-1"><?=htmlspecialchars($c[0])?></div><div class="h4 mb-0 font-weight-bold text-gray-800"><?=$c[1]?></div><i class="fas <?=$c[2]?> fa-2x text-gray-300 mt-2"></i></div></div></a></div>
 <?php endforeach; ?>
</div>

<div class="row">
 <div class="col-lg-8 mb-4">
  <div class="card shadow-sm h-100">
   <div class="card-header bg-white"><strong>Patient Clinical Workflow</strong></div>
   <div class="card-body">
    <div class="row">
     <div class="col-md-4 mb-3"><div class="border rounded p-3 h-100"><div class="text-primary mb-2"><i class="fas fa-calendar-check fa-2x"></i></div><h6 class="font-weight-bold">1. Appointments</h6><p class="small text-muted">Review today's and upcoming appointments and select the patient to attend.</p><a href="../patients/appointments.php" class="btn btn-sm btn-primary">Open Appointments</a></div></div>
     <div class="col-md-4 mb-3"><div class="border rounded p-3 h-100"><div class="text-success mb-2"><i class="fas fa-address-book fa-2x"></i></div><h6 class="font-weight-bold">2. Patient List</h6><p class="small text-muted">Find a registered patient when an appointment is not the starting point.</p><a href="../patients/patient_list.php" class="btn btn-sm btn-outline-success">Open Patient List</a></div></div>
     <div class="col-md-4 mb-3"><div class="border rounded p-3 h-100"><div class="text-info mb-2"><i class="fas fa-notes-medical fa-2x"></i></div><h6 class="font-weight-bold">3. Patient Dashboard</h6><p class="small text-muted">Perform clinical documentation and access services, investigations, prescriptions and billing.</p><span class="badge badge-info">Opened from patient record</span></div></div>
    </div>
   </div>
  </div>
 </div>
 <div class="col-lg-4 mb-4">
  <div class="card shadow-sm h-100"><div class="card-header bg-white"><strong>Clinical Actions</strong></div><div class="card-body">
   <a class="btn btn-primary btn-block mb-2" href="../patients/appointments.php"><i class="fas fa-calendar-check mr-2"></i>Appointments</a>
   <a class="btn btn-outline-primary btn-block mb-2" href="../patients/patient_list.php"><i class="fas fa-address-book mr-2"></i>Patient List</a>
   <a class="btn btn-outline-info btn-block mb-2" href="../diagnostics/diagnostics.php"><i class="fas fa-diagnoses mr-2"></i>Diagnostics</a>
   <a class="btn btn-outline-success btn-block" href="ward_management.php"><i class="fas fa-bed mr-2"></i>Ward / IPD</a>
  </div></div>
 </div>
</div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>