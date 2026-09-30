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
.rad-page{background:#f5f7fb;min-height:calc(100vh - 72px);padding:28px 24px 42px}
.rad-shell{max-width:1500px;margin:auto}
.rad-head{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:18px;padding:26px 30px;display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:20px;box-shadow:0 12px 30px rgba(6,59,115,.18)}
.rad-head h1{margin:4px 0;font-size:27px}.rad-head p{margin:0;color:rgba(255,255,255,.82);font-size:13px}
.rad-kicker{text-transform:uppercase;letter-spacing:1.4px;font-size:10px;font-weight:800;color:#d8e7f7}
.rad-card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 18px rgba(31,45,61,.05);overflow:hidden}
.rad-toolbar{padding:16px 20px;border-bottom:1px solid #edf0f5;display:flex;justify-content:space-between;align-items:center;gap:12px}
.rad-table{margin:0}.rad-table thead th{background:#f8fafc;border:0;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.35px}
.rad-table td{border-color:#edf0f5;vertical-align:middle;font-size:13px}.rad-table tbody tr:hover{background:#f8fbff}
.patient-name{font-weight:800;color:#25324a;display:block}.patient-no{font-size:11px;color:#7b8798}
.service-name{font-weight:700;color:#344054}.service-code{font-size:10px;color:#98a2b3}
.rad-badge{display:inline-block;padding:5px 10px;border-radius:999px;font-size:10px;font-weight:800}
.rad-pending{background:#fff4d6;color:#8a5b00}.rad-completed{background:#e4f6ea;color:#176b38}.rad-cancelled{background:#fbe5e5;color:#a12b2b}
@media(max-width:800px){.rad-page{padding:18px 12px}.rad-head{flex-direction:column;align-items:flex-start}.rad-card{overflow-x:auto}.rad-table{min-width:850px}}
</style>

<div class="rad-page"><div class="rad-shell">
    <div class="rad-head">
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
                    <tr><td colspan="6" class="text-center text-muted py-5">No radiology requests found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div></div>
<?php $stmt->close(); include __DIR__ . '/../includes/footer.php'; ?>