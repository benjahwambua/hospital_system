<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token']; $message='';

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['request_radiology'])){
 if(!hash_equals($csrfToken,$_POST['csrf_token']??'')) $message='Invalid security token.';
 else{
  $encId=(int)($_POST['encounter_id']??0); $serviceId=(int)($_POST['service_id']??0);
  try{
   $s=$conn->prepare("SELECT e.patient_id,e.visit_id,p.full_name FROM encounters e JOIN patients p ON p.id=e.patient_id WHERE e.id=? LIMIT 1");
   if(!$s) throw new Exception('Unable to load encounter.'); $s->bind_param('i',$encId);$s->execute();$enc=$s->get_result()->fetch_assoc();$s->close();
   if(!$enc) throw new Exception('Select a valid clinical encounter.');
   $svc=$conn->prepare("SELECT id,service_name,price FROM services_master WHERE id=? AND category='radiology' AND active=1 LIMIT 1");
   if(!$svc) throw new Exception('Unable to load radiology service.'); $svc->bind_param('i',$serviceId);$svc->execute();$service=$svc->get_result()->fetch_assoc();$svc->close();
   if(!$service) throw new Exception('Select a valid active radiology service.');
   $visitId=(int)($enc['visit_id']??0); if($visitId<=0)$visitId=get_or_create_current_visit($conn,(int)$enc['patient_id'],'Outpatient','Radiology');
   $uid=(int)($_SESSION['user_id']??0); $procedure=$service['service_name'];
   $stmt=$conn->prepare("INSERT INTO procedures (encounter_id,procedure_name,requested_by,requested_at) VALUES (?,?,?,NOW())");
   if(!$stmt) throw new Exception('Unable to create procedure request.');$stmt->bind_param('isi',$encId,$procedure,$uid);if(!$stmt->execute())throw new Exception($stmt->error);$stmt->close();
   if($visitId>0){
    $ps=$conn->prepare("INSERT INTO patient_services (patient_id,service_id,category,price,visit_id,created_at,status) VALUES (?,?,?,?,?,NOW(),'Pending')");
    if(!$ps)throw new Exception('Unable to create radiology order: '.$conn->error);$pid=(int)$enc['patient_id'];$sid=(int)$service['id'];$price=(float)$service['price'];$cat='radiology';$ps->bind_param('iisdi',$pid,$sid,$cat,$price,$visitId);if(!$ps->execute())throw new Exception($ps->error);$ps->close();
    $invoice=get_or_create_visit_invoice($conn,$pid,$visitId);$invoiceItemId=add_invoice_item($conn,$invoice,'Radiology: '.$procedure,1,$price,'radiology',$sid);post_invoice_journal($conn,$invoice,$pid,$price,'Radiology order',$invoiceItemId);
   }
   $message='Radiology request placed. The charge has been sent to Central Cashier.';
  }catch(Throwable $e){$message=$e->getMessage();}
 }
}

$encounters=$conn->query("SELECT e.id,e.patient_id,e.visit_id,p.full_name,p.patient_number FROM encounters e JOIN patients p ON p.id=e.patient_id ORDER BY e.created_at DESC");
$services=$conn->query("SELECT id,service_name,price FROM services_master WHERE category='radiology' AND active=1 ORDER BY service_name");
$procedures=$conn->query("SELECT pr.*,p.full_name FROM procedures pr JOIN encounters e ON e.id=pr.encounter_id JOIN patients p ON p.id=e.patient_id ORDER BY pr.requested_at DESC LIMIT 100");
include __DIR__ . '/../includes/header.php';include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4"><div class="row"><div class="col-lg-5 mb-4"><div class="card shadow-sm"><div class="card-header bg-white"><h4 class="mb-0 font-weight-bold">Radiology Request</h4></div><div class="card-body">
<?php if($message): ?><div class="alert alert-info"><?=htmlspecialchars($message)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
<div class="form-group"><label>Clinical Encounter</label><select name="encounter_id" class="form-control" required><option value="">Select encounter</option><?php if($encounters)while($e=$encounters->fetch_assoc()): ?><option value="<?=$e['id']?>"><?=htmlspecialchars($e['full_name'])?> — <?=htmlspecialchars($e['patient_number'])?> · Encounter <?=$e['id']?></option><?php endwhile; ?></select></div>
<div class="form-group"><label>Imaging / Radiology Service</label><select name="service_id" class="form-control" required><option value="">Select service</option><?php if($services)while($s=$services->fetch_assoc()): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['service_name'])?> — KES <?=number_format($s['price'],2)?></option><?php endwhile; ?></select></div>
<button name="request_radiology" class="btn btn-primary btn-block">Request & Send to Radiology</button>
</form></div></div></div>
<div class="col-lg-7"><div class="card shadow-sm"><div class="card-header bg-white"><strong>Recent Procedure Requests</strong></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Patient</th><th>Procedure</th><th>Requested</th></tr></thead><tbody><?php if($procedures)while($p=$procedures->fetch_assoc()): ?><tr><td><?=htmlspecialchars($p['full_name'])?></td><td><?=htmlspecialchars($p['procedure_name'])?></td><td><?=htmlspecialchars($p['requested_at'])?></td></tr><?php endwhile; ?></tbody></table></div></div></div></div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>