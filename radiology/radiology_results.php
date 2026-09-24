<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token']; $message='';
$hasVisit=$conn->query("SHOW COLUMNS FROM patient_services LIKE 'visit_id'") && $conn->query("SHOW COLUMNS FROM patient_services LIKE 'visit_id'")->num_rows>0;

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_radiology_result'])){
 if(!hash_equals($csrfToken,$_POST['csrf_token']??'')){ $message='Invalid security token.'; }
 else{
  $id=(int)($_POST['record_id']??0); $findings=trim($_POST['findings']??'');
  if($id<=0 || $findings==='') $message='Enter the radiology findings before saving.';
  else{
   $stmt=$conn->prepare("UPDATE patient_services SET results=?, status='Completed' WHERE id=? AND category='radiology'");
   if($stmt){$stmt->bind_param('si',$findings,$id); if($stmt->execute() && $stmt->affected_rows>=0)$message='Radiology result saved and order completed.'; else $message='Unable to save the result.'; $stmt->close();}
  }
 }
}
$sql="SELECT ps.id,ps.patient_id,ps.results,ps.status,ps.created_at,p.full_name,p.patient_number,sm.service_name";
if($hasVisit)$sql.=",v.visit_number,v.visit_type";
$sql.=" FROM patient_services ps JOIN patients p ON p.id=ps.patient_id JOIN services_master sm ON sm.id=ps.service_id";
if($hasVisit)$sql.=" LEFT JOIN visits v ON v.id=ps.visit_id";
$sql.=" WHERE ps.category='radiology' ORDER BY (ps.status='Pending') DESC, ps.created_at DESC";
$jobs=$conn->query($sql);
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4">
<div class="card shadow-sm"><div class="card-header bg-white"><h4 class="mb-0 font-weight-bold">Radiology Results Worklist</h4><small class="text-muted">Complete imaging findings here so Clinical Care can review them.</small></div>
<div class="card-body"><?= $message ? '<div class="alert alert-info">'.htmlspecialchars($message).'</div>' : '' ?>
<div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>Date</th><th>Patient</th><th>Visit</th><th>Investigation</th><th>Findings</th><th>Action</th></tr></thead><tbody>
<?php if($jobs && $jobs->num_rows): while($j=$jobs->fetch_assoc()): ?><tr>
<td><?=htmlspecialchars($j['created_at'])?></td><td><strong><?=htmlspecialchars($j['full_name'])?></strong><br><small><?=htmlspecialchars($j['patient_number'])?></small></td>
<td><?=htmlspecialchars($j['visit_number']??'Legacy')?></td><td><?=htmlspecialchars($j['service_name'])?><br><span class="badge badge-<?=($j['status']==='Completed'?'success':'warning')?>"><?=htmlspecialchars($j['status']??'Pending')?></span></td>
<form method="post"><td><textarea name="findings" class="form-control" rows="2" placeholder="Radiologist findings..."><?=htmlspecialchars($j['results']??'')?></textarea></td><td><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>"><input type="hidden" name="record_id" value="<?=$j['id']?>"><button name="save_radiology_result" class="btn btn-sm btn-success">Save & Close</button></td></form>
</tr><?php endwhile; else: ?><tr><td colspan="6" class="text-center text-muted py-4">No radiology orders found.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>