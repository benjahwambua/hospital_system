<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'radiology', 'view');

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$canCreate = can_module_action($conn, 'radiology', 'create');
$patientId = (int)($_GET['patient_id'] ?? 0);
$visitId = (int)($_GET['visit_id'] ?? 0);

$where = "ps.category='radiology'";
$params = [];
$types = '';
if ($patientId > 0) { $where .= " AND ps.patient_id=?"; $params[]=$patientId; $types.='i'; }

$sql = "SELECT ps.id, ps.patient_id, ps.visit_id, ps.service_id, ps.service_name_snapshot,
               ps.service_code_snapshot, ps.status, ps.created_at, ps.results,
               p.full_name, p.patient_number, sm.service_name, sm.service_code,
               v.visit_number
        FROM patient_services ps
        INNER JOIN patients p ON p.id=ps.patient_id
        INNER JOIN services_master sm ON sm.id=ps.service_id
        LEFT JOIN visits v ON v.id=ps.visit_id
        WHERE $where
        ORDER BY CASE WHEN COALESCE(ps.status,'Pending') IN ('Pending','In Progress') THEN 0 ELSE 1 END,
                 ps.created_at DESC
        LIMIT 250";
$stmt=$conn->prepare($sql);
if($params) $stmt->bind_param($types,...$params);
$stmt->execute();
$rows=$stmt->get_result();
?>
<style>
.rad-page{background:#f5f7fb;min-height:calc(100vh - 72px);padding:28px 24px 44px}
.rad-shell{max-width:1500px;margin:0 auto}
.rad-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#063b73,#075b9d 55%,#2b78b8);color:#fff;border-radius:18px;padding:28px 30px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:20px;box-shadow:0 14px 32px rgba(6,59,115,.18)}
.rad-hero:before,.rad-hero:after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.07);pointer-events:none}
.rad-hero:before{width:260px;height:260px;right:-90px;top:-150px}.rad-hero:after{width:130px;height:130px;right:150px;bottom:-105px}
.rad-hero>div{position:relative;z-index:1}.rad-kicker{text-transform:uppercase;letter-spacing:1.8px;font-size:10px;font-weight:800;color:#d9ebfb}
.rad-hero h1{margin:4px 0 7px;font-size:29px;font-weight:800}.rad-hero p{margin:0;color:rgba(255,255,255,.82);font-size:13px}
.rad-actions{display:flex;gap:9px;flex-wrap:wrap;position:relative;z-index:2}
.rad-actions .btn{height:40px;border-radius:9px;padding:9px 15px;font-size:11px;font-weight:800}
.rad-actions .btn-light{color:#063b73;background:#fff;border-color:#fff}.rad-actions .btn-outline-light{color:#fff;background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.48)}
.rad-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}
.rad-metric{background:#fff;border:1px solid #e5e9f0;border-radius:14px;padding:16px 18px;box-shadow:0 5px 18px rgba(31,45,61,.045)}
.rad-metric-label{font-size:10px;text-transform:uppercase;letter-spacing:.65px;color:#7b8798;font-weight:800}.rad-metric-value{font-size:25px;color:#25324a;font-weight:800;margin-top:5px}.rad-metric-note{font-size:10px;color:#98a2b3;margin-top:2px}
.rad-card{background:#fff;border:1px solid #e5e9f0;border-radius:14px;box-shadow:0 5px 18px rgba(31,45,61,.045);overflow:hidden}
.rad-toolbar{padding:16px 20px;border-bottom:1px solid #edf0f4;display:flex;justify-content:space-between;align-items:center;gap:14px}
.rad-toolbar strong{font-size:14px;color:#25324a}.rad-toolbar .small{font-size:10px}
.rad-table{margin:0}.rad-table thead th{height:45px;padding:0 16px;vertical-align:middle;background:#fafbfc;border:0;border-bottom:1px solid #e5e9ef;color:#7b8798;font-size:9.5px;text-transform:uppercase;letter-spacing:.7px}
.rad-table td{height:68px;padding:9px 16px;border:0;border-bottom:1px solid #edf0f4;vertical-align:middle;font-size:12px;color:#475467}.rad-table tbody tr:hover td{background:#f5f9fd}.rad-table tbody tr:last-child td{border-bottom:0}
.patient-name{font-weight:800;color:#25324a;display:block;font-size:13px}.patient-no{font-size:10px;color:#7b8798}
.service-name{font-weight:700;color:#344054;display:block}.service-code{font-size:10px;color:#98a2b3}
.rad-badge{display:inline-flex;align-items:center;justify-content:center;min-width:78px;padding:6px 10px;border-radius:20px;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.35px}
.rad-pending{background:#fff7e6;color:#996300;border:1px solid #f1dfb5}.rad-completed{background:#edf9f1;color:#19713a;border:1px solid #cdebd6}.rad-cancelled{background:#fff1f1;color:#c92a2a;border:1px solid #ffd6d6}
.rad-table td:last-child{white-space:nowrap}.rad-table td .btn{border-radius:8px;font-size:9.5px;font-weight:800;padding:7px 10px}
.rad-empty{padding:55px 20px!important;text-align:center;color:#98a2b3}
.rad-empty i{display:block;font-size:30px;color:#c7d0db;margin-bottom:10px}.rad-empty strong{display:block;color:#475467;font-size:13px;margin-bottom:3px}
@media(max-width:900px){.rad-page{padding:20px 14px}.rad-hero{flex-direction:column;align-items:flex-start}.rad-actions{width:100%}.rad-metrics{grid-template-columns:1fr}.rad-card{overflow-x:auto}.rad-table{min-width:900px}}
@media(max-width:600px){.rad-page{padding:16px 10px}.rad-hero{padding:21px;border-radius:15px}.rad-hero h1{font-size:24px}.rad-actions{display:grid;grid-template-columns:1fr;width:100%}.rad-actions .btn{width:100%}}
</style>

<div class="rad-page"><div class="rad-shell">
    <div class="rad-hero">
        <div>
            <div class="rad-kicker">Radiology Operations</div>
            <h1>Radiology Requests</h1>
            <p>Review imaging requests generated from patient encounters.</p>
        </div>
        <div>
            <?php if($canCreate): ?>
                <a href="/hospital_system/clinical/orders.php<?= $patientId ? '?patient_id='.$patientId.($visitId ? '&visit_id='.$visitId : '') : '' ?>" class="btn btn-light">
                    <i class="fas fa-plus mr-1"></i> New Request
                </a>
            <?php endif; ?>
            <a href="radiology_results.php" class="btn btn-outline-light ml-1">Results</a>
        </div>
    </div>

    <div class="rad-metrics">
        <div class="rad-metric"><div class="rad-metric-label">Requests Loaded</div><div class="rad-metric-value"><?= $rows ? $rows->num_rows : 0 ?></div><div class="rad-metric-note">Current worklist</div></div>
        <div class="rad-metric"><div class="rad-metric-label">Patient Filter</div><div class="rad-metric-value"><?= $patientId ? 'Active' : 'All' ?></div><div class="rad-metric-note"><?= $patientId ? 'Showing selected patient' : 'All radiology patients' ?></div></div>
        <div class="rad-metric"><div class="rad-metric-label">Workflow</div><div class="rad-metric-value">Imaging</div><div class="rad-metric-note">Requests → Results</div></div>
    </div>

    <div class="rad-card">
        <div class="rad-toolbar">
            <div><strong>Imaging Worklist</strong><div class="small text-muted">Latest 250 requests</div></div>
            <?php if($patientId): ?><a href="radiology_requests.php" class="btn btn-sm btn-light">Show All</a><?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table rad-table">
                <thead><tr><th>Patient</th><th>Visit</th><th>Study</th><th>Requested</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if($rows && $rows->num_rows): while($row=$rows->fetch_assoc()):
                    $status=(string)($row['status']??'Pending');
                    $cls=$status==='Completed'?'rad-completed':($status==='Cancelled'?'rad-cancelled':'rad-pending');
                ?>
                    <tr>
                        <td><span class="patient-name"><?=htmlspecialchars($row['full_name'])?></span><span class="patient-no"><?=htmlspecialchars($row['patient_number'])?></span></td>
                        <td><?=htmlspecialchars($row['visit_number']??'—')?></td>
                        <td><span class="service-name"><?=htmlspecialchars($row['service_name'])?></span><span class="service-code"><?=htmlspecialchars($row['service_code']??$row['service_code_snapshot']??'')?></span></td>
                        <td><?=htmlspecialchars(date('d M Y H:i',strtotime($row['created_at'])))?></td>
                        <td><span class="rad-badge <?=$cls?>"><?=htmlspecialchars($status)?></span></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="radiology_results.php?patient_id=<?=intval($row['patient_id'])?>"><i class="fas fa-images mr-1"></i>Open Results</a></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" class="rad-empty"><i class="fas fa-x-ray"></i><strong>No radiology requests found</strong><span>The imaging worklist is currently clear.</span></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div></div>
<?php $stmt->close(); include __DIR__ . '/../includes/footer.php'; ?>