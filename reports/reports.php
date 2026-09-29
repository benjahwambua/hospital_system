<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_module_access($conn, 'administration', 'view');

function report_count(mysqli $conn, string $sql): int {
    $result = $conn->query($sql);
    return $result ? (int)($result->fetch_assoc()['total'] ?? 0) : 0;
}

$patients = report_count($conn, "SELECT COUNT(*) AS total FROM patients WHERE COALESCE(is_walkin,0)=0");
$visits = report_count($conn, "SELECT COUNT(*) AS total FROM visits WHERE DATE(visit_date)=CURDATE()");
$appointments = report_count($conn, "SELECT COUNT(*) AS total FROM appointments WHERE DATE(appointment_date)=CURDATE()");
$admissions = report_count($conn, "SELECT COUNT(*) AS total FROM admissions WHERE LOWER(COALESCE(status,'')) IN ('admitted','active','inpatient')");
$labPending = report_count($conn, "SELECT COUNT(*) AS total FROM lab_requests WHERE LOWER(COALESCE(status,'')) IN ('pending','requested','in progress')");
$radPending = report_count($conn, "SELECT COUNT(*) AS total FROM radiology_requests WHERE LOWER(COALESCE(status,'')) IN ('pending','requested','in progress')");
$pharmacyPending = report_count($conn, "SELECT COUNT(*) AS total FROM pharmacy_queue WHERE LOWER(COALESCE(status,''))='pending'");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.report-page{padding:28px 24px 42px;background:#f5f7fb;min-height:calc(100vh - 60px)}
.report-wrap{max-width:1500px;margin:auto}
.report-hero{background:linear-gradient(135deg,#0f4c81,#1677c8);color:#fff;border-radius:18px;padding:28px 30px;margin-bottom:20px;box-shadow:0 12px 30px rgba(15,76,129,.2)}
.report-hero h1{margin:5px 0;font-size:28px}
.report-hero p{margin:0;color:rgba(255,255,255,.82);font-size:14px}
.report-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.5px;font-weight:800;color:#bde7ff}
.report-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.report-card,.report-links{background:#fff;border:1px solid #e7ebf2;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05)}
.report-card{padding:20px}
.report-card small{display:block;color:#7b8798;text-transform:uppercase;font-size:10px;font-weight:800;margin-bottom:6px}
.report-card strong{font-size:28px;color:#25324a}
.report-links{padding:20px}
.report-links h2{font-size:17px;margin:0 0 14px;color:#25324a}
.report-list{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.report-list a{padding:16px;border:1px solid #e7ebf2;border-radius:12px;text-decoration:none;color:#344054;background:#fff}
.report-list a:hover{background:#f8fafc;text-decoration:none}
.report-list i{margin-right:9px;color:#1677c8}
@media(max-width:900px){.report-grid{grid-template-columns:repeat(2,1fr)}.report-list{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.report-page{padding:18px 12px}.report-hero{padding:22px}.report-grid,.report-list{grid-template-columns:1fr}}
</style>
<div class="report-page">
  <div class="report-wrap">
    <div class="report-hero">
      <div class="report-kicker">System Reports</div>
      <h1>Hospital Reports</h1>
      <p>Operational activity and clinical reporting.</p>
    </div>

    <div class="report-grid">
      <div class="report-card"><small>Total Patients</small><strong><?= $patients ?></strong></div>
      <div class="report-card"><small>Today's Visits</small><strong><?= $visits ?></strong></div>
      <div class="report-card"><small>Today's Appointments</small><strong><?= $appointments ?></strong></div>
      <div class="report-card"><small>Active Admissions</small><strong><?= $admissions ?></strong></div>
      <div class="report-card"><small>Pending Laboratory</small><strong><?= $labPending ?></strong></div>
      <div class="report-card"><small>Pending Radiology</small><strong><?= $radPending ?></strong></div>
      <div class="report-card"><small>Pending Pharmacy</small><strong><?= $pharmacyPending ?></strong></div>
    </div>

    <div class="report-links">
      <h2>Reports & Records</h2>
      <div class="report-list">
        <a href="../reports/patient_medical_report.php"><i class="fas fa-file-medical"></i><strong>Patient Medical Report</strong></a>
        <a href="../clinical/index.php"><i class="fas fa-stethoscope"></i><strong>Clinical Activity</strong></a>
        <a href="../lab/dashboard.php"><i class="fas fa-microscope"></i><strong>Laboratory Activity</strong></a>
        <a href="../radiology/dashboard.php"><i class="fas fa-x-ray"></i><strong>Radiology Activity</strong></a>
        <a href="../pharmacy/dashboard.php"><i class="fas fa-pills"></i><strong>Pharmacy Activity</strong></a>
        <a href="../maternity/stats.php"><i class="fas fa-baby"></i><strong>Maternity Statistics</strong></a>
        <a href="../procurement/dashboard.php"><i class="fas fa-boxes"></i><strong>Procurement Activity</strong></a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>