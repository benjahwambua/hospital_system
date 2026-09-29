<style>
.workspace-page{background:#f5f7fb;min-height:calc(100vh - 72px);padding:28px 24px 40px}.workspace-shell{max-width:1500px;margin:0 auto}.workspace-card{border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 18px rgba(31,45,61,.05);background:#fff;overflow:hidden}.workspace-card .card-header{background:#fff;border-bottom:1px solid #edf0f5;color:#25324a}.workspace-page .table thead th{background:#f8fafc;border-top:0;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.35px}.workspace-page .table td{border-color:#edf0f5;vertical-align:middle}.workspace-page .form-control{border-color:#d7dee8;border-radius:9px}.workspace-page .form-control:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}.workspace-page .btn{border-radius:8px;font-weight:700}
</style>

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
<?php
// Legacy radiology request entry point consolidated into Clinical Orders.
// Doctors place Lab/Radiology/Pharmacy orders from the same visit.
require_once __DIR__ . '/../includes/session.php';
require_login();

$patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$visitId = (int)($_GET['visit_id'] ?? $_POST['visit_id'] ?? 0);
$target = '/hospital_system/clinical/orders.php';
$params = [];
if ($patientId > 0) $params[] = 'patient_id=' . $patientId;
if ($visitId > 0) $params[] = 'visit_id=' . $visitId;
header('Location: ' . $target . ($params ? '?' . implode('&', $params) : ''));
exit;
?>
