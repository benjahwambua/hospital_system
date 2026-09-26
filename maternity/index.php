<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'maternity', 'view');

$patientId = max(0, (int)($_GET['patient_id'] ?? 0));
$patientContext = null;
if ($patientId > 0) {
    $ps = $conn->prepare("SELECT id,full_name,patient_number,clinic_category FROM patients WHERE id=? LIMIT 1");
    if ($ps) { $ps->bind_param('i',$patientId); $ps->execute(); $patientContext=$ps->get_result()->fetch_assoc(); $ps->close(); }
    if (!$patientContext) { http_response_code(404); exit('Patient not found.'); }
}

$canCreate = can_module_action($conn,'maternity','create');
$total=0;$deliveries=0;$babies=0;$upcoming=0;
if($q=$conn->query("SELECT COUNT(*) c FROM maternity m JOIN patients p ON p.id=m.patient_id WHERE p.clinic_category IN ('ANC','PNC','Maternity')")) $total=(int)$q->fetch_assoc()['c'];
if($q=$conn->query("SELECT COUNT(*) c FROM maternity_delivery md JOIN maternity m ON m.id=md.maternity_id JOIN patients p ON p.id=m.patient_id WHERE p.clinic_category IN ('ANC','PNC','Maternity')")) $deliveries=(int)$q->fetch_assoc()['c'];
if($q=$conn->query("SELECT COUNT(*) c FROM maternity_baby mb JOIN maternity m ON m.id=mb.maternity_id JOIN patients p ON p.id=m.patient_id WHERE p.clinic_category IN ('ANC','PNC','Maternity')")) $babies=(int)$q->fetch_assoc()['c'];
if($q=$conn->query("SELECT COUNT(*) c FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.appointment_date>=NOW() AND p.clinic_category IN ('ANC','PNC','Maternity') AND COALESCE(a.status,'') NOT IN ('Cancelled','Completed')")) $upcoming=(int)$q->fetch_assoc()['c'];

$records = null;
if ($patientId > 0) {
    $rs = $conn->prepare("SELECT m.id,m.patient_id,m.anc_number,m.expected_delivery,m.gravida,m.parity,m.created_at,p.full_name,p.patient_number FROM maternity m JOIN patients p ON p.id=m.patient_id WHERE m.patient_id=? AND p.clinic_category IN ('ANC','PNC','Maternity') ORDER BY m.created_at DESC LIMIT 12");
    $rs->bind_param('i',$patientId); $rs->execute(); $records=$rs->get_result(); $rs->close();
} else {
    $records=$conn->query("SELECT m.id,m.patient_id,m.anc_number,m.expected_delivery,m.gravida,m.parity,m.created_at,p.full_name,p.patient_number FROM maternity m JOIN patients p ON p.id=m.patient_id WHERE p.clinic_category IN ('ANC','PNC','Maternity') ORDER BY m.created_at DESC LIMIT 12");
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.maternity-page{padding:28px 24px 40px;background:#f5f7fb;min-height:calc(100vh - 60px)}.maternity-shell{max-width:1500px;margin:0 auto}
.maternity-hero{background:#fff;border:1px solid #e7ebf2;border-radius:14px;padding:24px 26px;display:flex;justify-content:space-between;align-items:center;gap:18px;box-shadow:0 4px 18px rgba(31,45,61,.06);margin-bottom:20px}.maternity-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.4px;color:#6c7a91;font-weight:700}.maternity-hero h1{font-size:25px;color:#25324a;margin:4px 0}.maternity-hero p{margin:0;color:#718096;font-size:14px}.maternity-actions{display:flex;gap:8px;flex-wrap:wrap}
.maternity-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}.m-stat{background:#fff;border:1px solid #e7ebf2;border-radius:12px;padding:18px;box-shadow:0 3px 12px rgba(31,45,61,.045)}.m-stat small{color:#7b8798;text-transform:uppercase;font-weight:700;font-size:11px}.m-stat strong{display:block;font-size:27px;color:#25324a;margin-top:8px}
.m-grid{display:grid;grid-template-columns:2fr 1fr;gap:18px}.m-panel{background:#fff;border:1px solid #e7ebf2;border-radius:14px;overflow:hidden;box-shadow:0 3px 14px rgba(31,45,61,.05)}.m-head{padding:16px 20px;border-bottom:1px solid #edf0f5;display:flex;justify-content:space-between;align-items:center}.m-head strong{color:#25324a}.m-body{padding:18px}.m-table{width:100%;border-collapse:collapse}.m-table th,.m-table td{padding:11px 10px;border-bottom:1px solid #edf0f5;text-align:left;font-size:13px}.m-table th{font-size:11px;text-transform:uppercase;color:#7b8798;background:#fbfcfe}.m-table td{color:#344054}.m-table tr:hover{background:#fbfcfe}.m-nav{display:grid;gap:10px}.m-nav a{display:flex;align-items:center;padding:13px;border:1px solid #e7ebf2;border-radius:10px;color:#344054;text-decoration:none}.m-nav a:hover{background:#f8fafc;text-decoration:none}.m-nav i{width:30px;color:#2f6fed}.badge-edd{background:#fff4e5;color:#a15c00;padding:4px 8px;border-radius:20px;font-size:11px}
@media(max-width:950px){.m-grid{grid-template-columns:1fr}.maternity-metrics{grid-template-columns:repeat(2,1fr)}.maternity-hero{flex-direction:column;align-items:flex-start}}@media(max-width:550px){.maternity-page{padding:18px 12px}.maternity-metrics{grid-template-columns:1fr}.m-table{min-width:700px}.m-body{overflow-x:auto}}
</style>
<div class="maternity-page"><div class="maternity-shell">
  <div class="maternity-hero"><div><div class="maternity-kicker">Maternal & Newborn Care</div><h1><?= $patientContext ? "Maternity Care" : "Maternity" ?></h1><p><?= $patientContext ? "Patient-specific maternity care for ".htmlspecialchars($patientContext['full_name'])." · ".htmlspecialchars($patientContext['patient_number']) : "Manage ANC, labour, delivery, PNC, admissions and newborn records from one module." ?></p></div><div class="maternity-actions"><?php if($canCreate): ?><a href="add.php<?= $patientId ? "?patient_id=".$patientId : "" ?>" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>New Maternity Visit</a><?php endif; ?><a href="<?= $patientId ? "../patients/patient_dashboard.php?id=".$patientId : "../patients/patient_list.php" ?>" class="btn btn-outline-primary"><i class="fas fa-users mr-1"></i>Patient List</a></div></div>
  <div class="maternity-metrics"><div class="m-stat"><small>ANC Records</small><strong><?= $total ?></strong></div><div class="m-stat"><small>Deliveries</small><strong><?= $deliveries ?></strong></div><div class="m-stat"><small>Babies Recorded</small><strong><?= $babies ?></strong></div><div class="m-stat"><small>Upcoming Maternal Appointments</small><strong><?= $upcoming ?></strong></div></div>
  <div class="m-grid">
    <div class="m-panel"><div class="m-head"><strong><?= $patientContext ? "Current Patient Maternity Record" : "Recent Maternity Patients" ?></strong><a href="index.php<?= $patientId ? "?patient_id=".$patientId : "" ?>" class="small text-muted">Refresh</a></div><div class="m-body"><div style="overflow-x:auto"><table class="m-table"><thead><tr><th>Patient</th><th>ANC No.</th><th>EDD</th><th>G/P</th><th>Actions</th></tr></thead><tbody>
    <?php if($records && $records->num_rows): while($r=$records->fetch_assoc()): ?><tr><td><strong><?= htmlspecialchars($r['full_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($r['patient_number']) ?></small></td><td><?= htmlspecialchars($r['anc_number']) ?></td><td><?php if(!empty($r['expected_delivery'])): ?><span class="badge-edd"><?= date('d M Y',strtotime($r['expected_delivery'])) ?></span><?php else: ?>—<?php endif; ?></td><td><?= htmlspecialchars((string)$r['gravida']) ?>/<?= htmlspecialchars((string)$r['parity']) ?></td><td><a href="view.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">Open</a> <a href="visit_history.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-light">Visits</a></td></tr><?php endwhile; else: ?><tr><td colspan="5" class="text-center text-muted py-4">No maternity records found.</td></tr><?php endif; ?></tbody></table></div></div></div>
    <div class="m-panel"><div class="m-head"><strong>Maternity Workspace</strong></div><div class="m-body"><div class="m-nav">
      <a href="add.php"><i class="fas fa-notes-medical"></i><span>ANC / Labour / PNC Visits</span></a>
      <a href="deliveries.php<?= $patientId ? "?patient_id=".$patientId : "" ?>"><i class="fas fa-baby"></i><span>Delivery <?= $patientContext ? "Records" : "Register" ?></span></a>
      <a href="admissions.php<?= $patientId ? "?patient_id=".$patientId : "" ?>"><i class="fas fa-procedures"></i><span>Maternity Admissions</span></a>
      <a href="antenatal.php<?= $patientId ? "?patient_id=".$patientId : "" ?>"><i class="fas fa-heartbeat"></i><span>ANC Clinic</span></a>
      <a href="postnatal.php<?= $patientId ? "?patient_id=".$patientId : "" ?>"><i class="fas fa-female"></i><span>PNC Clinic</span></a>
      <a href="stats.php"><i class="fas fa-chart-bar"></i><span>Maternity Reports</span></a>
    </div></div></div>
  </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>