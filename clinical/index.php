<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

$todayVisits=0; $waiting=0; $inProgress=0; $admitted=0; $pendingLab=0; $pendingRad=0; $pendingRx=0;

if (hms_visits_available($conn)) {
    $q=$conn->query("SELECT
        COUNT(*) total,
        SUM(status='Open') waiting,
        SUM(status='In Progress') in_progress
        FROM visits WHERE visit_date=CURDATE() AND status <> 'Cancelled'");
    if($q && ($r=$q->fetch_assoc())){ $todayVisits=(int)$r['total']; $waiting=(int)$r['waiting']; $inProgress=(int)$r['in_progress']; }
}
if ($q=$conn->query("SELECT COUNT(*) total FROM admissions WHERE status='Admitted'")) { $admitted=(int)($q->fetch_assoc()['total']??0); }
if ($q=$conn->query("SELECT COUNT(*) total FROM patient_services WHERE category='lab' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')")) { $pendingLab=(int)($q->fetch_assoc()['total']??0); }
if ($q=$conn->query("SELECT COUNT(*) total FROM patient_services WHERE category='radiology' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')")) { $pendingRad=(int)($q->fetch_assoc()['total']??0); }
$qc=$conn->query("SHOW TABLES LIKE 'pharmacy_queue'");
if($qc && $qc->num_rows && ($q=$conn->query("SELECT COUNT(*) total FROM pharmacy_queue WHERE status='pending'"))) $pendingRx=(int)($q->fetch_assoc()['total']??0);

$recent=[];
if(hms_visits_available($conn)){
 $q=$conn->query("SELECT v.id,v.visit_number,v.visit_type,v.clinic_category,v.status,v.visit_time,p.id patient_id,p.full_name,p.patient_number
                  FROM visits v JOIN patients p ON p.id=v.patient_id
                  WHERE v.visit_date=CURDATE() AND v.status IN ('Open','In Progress')
                  ORDER BY v.visit_time ASC LIMIT 12");
 if($q) while($r=$q->fetch_assoc()) $recent[]=$r;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4">
<div class="d-flex justify-content-between align-items-center mb-4">
 <div><h4 class="font-weight-bold text-gray-800 mb-1">Clinical Care</h4><div class="text-muted">Patient journey, consultation, diagnostics and inpatient care</div></div>
 <div>
  <a href="triage.php" class="btn btn-primary mr-2"><i class="fas fa-heartbeat"></i> Triage</a>
  <a href="consultations.php" class="btn btn-outline-primary"><i class="fas fa-user-md"></i> Doctor Queue</a>
 </div>
</div>
<div class="row">
 <?php foreach([
  ['Today\'s Visits',$todayVisits,'fa-users','primary','reception/index.php'],
  ['Waiting',$waiting,'fa-hourglass-half','warning','consultations.php'],
  ['In Progress',$inProgress,'fa-user-md','info','consultations.php'],
  ['Admitted',$admitted,'fa-bed','success','ward_management.php'],
  ['Pending Lab',$pendingLab,'fa-vial','warning','../lab/lab_results.php'],
  ['Pending Radiology',$pendingRad,'fa-x-ray','info','../radiology/radiology_requests.php'],
  ['Pending Pharmacy',$pendingRx,'fa-pills','danger','../pharmacy/dispensing_queue.php']
 ] as $c): ?>
 <div class="col-xl-3 col-md-4 col-sm-6 mb-4"><a href="<?=$c[4]?>" class="text-decoration-none"><div class="card shadow-sm border-left-<?=$c[3]?> h-100"><div class="card-body"><div class="row align-items-center"><div class="col"><div class="text-xs font-weight-bold text-<?=$c[3]?> text-uppercase mb-1"><?=htmlspecialchars($c[0])?></div><div class="h4 mb-0 font-weight-bold text-gray-800"><?=$c[1]?></div></div><div class="col-auto"><i class="fas <?=$c[2]?> fa-2x text-gray-300"></i></div></div></div></div></a></div>
 <?php endforeach; ?>
</div>
<div class="row">
 <div class="col-lg-8 mb-4"><div class="card shadow-sm"><div class="card-header bg-white d-flex justify-content-between"><strong>Today's Clinical Queue</strong><a href="consultations.php">Open queue</a></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Time</th><th>Patient</th><th>Visit</th><th>Clinic</th><th>Status</th><th></th></tr></thead><tbody>
 <?php if($recent): foreach($recent as $r): ?><tr><td><?=htmlspecialchars($r['visit_time'])?></td><td><strong><?=htmlspecialchars($r['full_name'])?></strong><br><small><?=htmlspecialchars($r['patient_number'])?></small></td><td><?=htmlspecialchars($r['visit_number'])?></td><td><?=htmlspecialchars($r['clinic_category'])?></td><td><?=htmlspecialchars($r['status'])?></td><td><a class="btn btn-sm btn-primary" href="care.php?patient_id=<?=$r['patient_id']?>&visit_id=<?=$r['id']?>">Open</a></td></tr><?php endforeach; else: ?><tr><td colspan="6" class="text-center text-muted py-4">No active visits today.</td></tr><?php endif; ?>
 </tbody></table></div></div></div></div>
 <div class="col-lg-4 mb-4"><div class="card shadow-sm"><div class="card-header bg-white"><strong>Clinical Modules</strong></div><div class="card-body">
 <a class="btn btn-outline-primary btn-block mb-2" href="triage.php"><i class="fas fa-heartbeat mr-2"></i>Triage & Vitals</a>
 <a class="btn btn-outline-primary btn-block mb-2" href="consultations.php"><i class="fas fa-user-md mr-2"></i>Doctor's Queue</a>
 <a class="btn btn-outline-primary btn-block mb-2" href="care.php"><i class="fas fa-notes-medical mr-2"></i>Clinical Care</a>
 <a class="btn btn-outline-success btn-block mb-2" href="ward_management.php"><i class="fas fa-bed mr-2"></i>Ward / IPD</a>
 <a class="btn btn-outline-info btn-block" href="../diagnostics/diagnostics.php"><i class="fas fa-diagnoses mr-2"></i>Diagnostics</a>
 </div></div></div>
</div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>