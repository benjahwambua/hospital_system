<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn,'maternity','view');
$canCreate=can_module_action($conn,'maternity','create');
$patient_id=max(0,(int)($_GET['patient_id']??0));
if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token']; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!$canCreate){http_response_code(403);exit('You do not have permission to record maternity visits.');}
 if(!hash_equals($csrfToken,(string)($_POST['csrf_token']??''))){$error='Security token mismatch.';} else {
  $maternity_id=(int)($_POST['maternity_id']??0);$bp=trim((string)($_POST['bp']??''));$temp=trim((string)($_POST['temp']??''));$pulse=trim((string)($_POST['pulse']??''));$weight=trim((string)($_POST['weight']??''));$fhr=trim((string)($_POST['fetal_heart_rate']??''));$cervix=trim((string)($_POST['cervix']??''));$membrane=trim((string)($_POST['membrane_status']??''));$drugs=trim((string)($_POST['drugs_given']??''));$notes=trim((string)($_POST['notes']??''));
  $stmt=$conn->prepare("INSERT INTO maternity_visits (maternity_id,visit_type,bp,temp,pulse,weight,fetal_heart_rate,cervix,membrane_status,drugs_given,notes,created_at) VALUES (?, 'ANC', ?,?,?,?,?,?,?,?,?,NOW())");
  if($stmt){$stmt->bind_param('isssssssss',$maternity_id,$bp,$temp,$pulse,$weight,$fhr,$cervix,$membrane,$drugs,$notes);if($stmt->execute()){if(function_exists('audit'))audit('maternity_anc_add',"maternity_id={$maternity_id}");header("Location: antenatal.php?patient_id={$patient_id}&saved=1");exit;}$error=$stmt->error;$stmt->close();}else $error=$conn->error;
 }
}
$res=$conn->query("SELECT v.*,p.full_name,m.anc_number,m.patient_id FROM maternity_visits v JOIN maternity m ON m.id=v.maternity_id JOIN patients p ON p.id=m.patient_id WHERE v.visit_type='ANC' ".($patient_id?"AND m.patient_id=".((int)$patient_id):"")." ORDER BY v.created_at DESC LIMIT 200");
$matList=$conn->query("SELECT m.id,p.full_name,p.patient_number,m.anc_number FROM maternity m JOIN patients p ON p.id=m.patient_id ORDER BY p.full_name ASC");
include __DIR__ . '/../includes/header.php';include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4">
<div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="h4 mb-1 text-gray-800"><i class="fas fa-heartbeat text-primary mr-2"></i>Antenatal Care</h2><p class="text-muted mb-0">Record ANC assessments and review recent antenatal visits.</p></div><a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i>Maternity Dashboard</a></div>
<?php if(isset($_GET['saved'])):?><div class="alert alert-success">ANC visit recorded successfully.</div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="row"><div class="col-lg-5 mb-4"><div class="card shadow-sm"><div class="card-header bg-white"><strong>Record ANC Visit</strong></div><div class="card-body"><?php if($canCreate):?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
<label>Maternity Patient</label><select name="maternity_id" class="form-control mb-3" required><option value="">Select patient</option><?php if($matList):while($m=$matList->fetch_assoc()):?><option value="<?=$m['id']?>"><?=htmlspecialchars($m['full_name'])?> — <?=htmlspecialchars($m['patient_number'])?> (<?=htmlspecialchars($m['anc_number'])?>)</option><?php endwhile;endif;?></select>
<div class="row"><div class="col-md-6 form-group"><label>BP</label><input name="bp" class="form-control"></div><div class="col-md-6 form-group"><label>Temperature</label><input name="temp" class="form-control"></div><div class="col-md-6 form-group"><label>Pulse</label><input name="pulse" class="form-control"></div><div class="col-md-6 form-group"><label>Weight (kg)</label><input name="weight" class="form-control"></div><div class="col-md-6 form-group"><label>Fetal Heart Rate</label><input name="fetal_heart_rate" class="form-control"></div><div class="col-md-6 form-group"><label>Cervix</label><input name="cervix" class="form-control"></div></div>
<div class="form-group"><label>Membrane Status</label><input name="membrane_status" class="form-control"></div><div class="form-group"><label>Drugs Given</label><textarea name="drugs_given" class="form-control" rows="2"></textarea></div><div class="form-group"><label>Clinical Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div><button class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save ANC Visit</button></form><?php else:?><div class="alert alert-warning mb-0">You have view-only access to Maternity.</div><?php endif;?></div></div></div>
<div class="col-lg-7 mb-4"><div class="card shadow-sm"><div class="card-header bg-white"><strong>Recent ANC Visits</strong></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Date</th><th>Patient</th><th>BP</th><th>FHR</th><th>Weight</th><th>Notes</th></tr></thead><tbody><?php if($res&&$res->num_rows):while($r=$res->fetch_assoc()):?><tr><td><?=htmlspecialchars(date('d M Y H:i',strtotime($r['created_at'])))?></td><td><strong><?=htmlspecialchars($r['full_name'])?></strong><br><small><?=htmlspecialchars($r['anc_number'])?></small></td><td><?=htmlspecialchars($r['bp'])?></td><td><?=htmlspecialchars($r['fetal_heart_rate'])?></td><td><?=htmlspecialchars($r['weight'])?></td><td><?=nl2br(htmlspecialchars($r['notes']))?></td></tr><?php endwhile;else:?><tr><td colspan="6" class="text-center text-muted py-4">No ANC visits found.</td></tr><?php endif;?></tbody></table></div></div></div></div></div>
</div></div><?php include __DIR__ . '/../includes/footer.php'; ?>