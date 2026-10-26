<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_module_access($conn,'insurance','view');
if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); $csrf=$_SESSION['csrf_token'];
$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=(string)($_POST['action']??''); require_module_access($conn,'insurance',$action==='decision'?'approve':'create');
 if(!hash_equals($csrf,(string)($_POST['csrf_token']??''))) $err='Invalid security token.';
 else try{
  if($action==='create'){
   $pid=(int)($_POST['patient_id']??0);$cid=(int)($_POST['coverage_id']??0);$type=(string)($_POST['service_type']??'');$amt=(float)($_POST['amount']??0);$notes=trim((string)($_POST['notes']??''));
   if($pid<=0||$cid<=0||$amt<=0||!in_array($type,['Consultation','Service','Lab','Radiology','Procedure','Pharmacy','Admission','Other'],true)||strlen($notes)>5000)throw new Exception('Enter a valid patient, verified coverage, service type and positive requested amount.');
   $q=$conn->prepare("SELECT patient_id,payer_id,plan_id FROM patient_coverages WHERE id=? AND eligibility_status='Verified'");if(!$q)throw new Exception('Unable to prepare coverage validation.');$q->bind_param('i',$cid);if(!$q->execute())throw new Exception('Unable to validate patient coverage.');$v=$q->get_result()->fetch_assoc();$q->close();
   if(!$v||(int)$v['patient_id']!==$pid)throw new Exception('A verified patient coverage record is required.');
   $num='PA-'.date('YmdHis').'-'.random_int(100,999);$s=$conn->prepare("INSERT INTO preauthorizations(patient_id,patient_coverage_id,payer_id,plan_id,request_number,requested_service_type,requested_amount,status,clinical_notes,created_by) VALUES(?,?,?,?,?,?,?,'Draft',?,?)");if(!$s)throw new Exception('Unable to prepare preauthorization request.');
   $createdBy=(int)($_SESSION['user_id']??0);$s->bind_param('iiiissdsi',$pid,$cid,$v['payer_id'],$v['plan_id'],$num,$type,$amt,$notes,$createdBy);if(!$s->execute())throw new Exception('Unable to create preauthorization request: '.$s->error);$id=(int)$s->insert_id;$s->close();if(function_exists('audit'))audit('insurance_preauthorization_created',"id={$id},request={$num}");$msg='Preauthorization created.';
  }elseif($action==='decision'){
   $id=(int)($_POST['id']??0);$status=(string)($_POST['status']??'');$approved=(float)($_POST['approved_amount']??0);$auth=trim((string)($_POST['authorization_number']??''));
   if($id<=0||!in_array($status,['Approved','Partially Approved','Rejected'],true))throw new Exception('Invalid preauthorization decision.');
   $conn->begin_transaction();
   try{
    $s=$conn->prepare("SELECT id,status,requested_amount FROM preauthorizations WHERE id=? FOR UPDATE");if(!$s)throw new Exception('Unable to prepare preauthorization validation.');$s->bind_param('i',$id);if(!$s->execute())throw new Exception('Unable to load preauthorization.');$request=$s->get_result()->fetch_assoc();$s->close();
    if(!$request)throw new Exception('Preauthorization not found.');
    if(!in_array((string)$request['status'],['Draft','Submitted'],true))throw new Exception('Only draft or submitted preauthorizations can receive a decision.');
    if($status==='Rejected')$approved=0.0;
    elseif($approved<=0||(float)$approved>(float)$request['requested_amount'])throw new Exception('Approved amount must be positive and cannot exceed the requested amount.');
    if($status!=='Rejected'&&$auth==='')throw new Exception('Authorization number is required for approved requests.');
    $s=$conn->prepare("UPDATE preauthorizations SET status=?,approved_amount=?,authorization_number=?,valid_from=IF(? IN ('Approved','Partially Approved'),NOW(),NULL),valid_to=IF(? IN ('Approved','Partially Approved'),DATE_ADD(NOW(),INTERVAL 30 DAY),NULL),updated_at=NOW() WHERE id=? AND status IN ('Draft','Submitted')");if(!$s)throw new Exception('Unable to prepare preauthorization decision update.');
    $s->bind_param('sdsssi',$status,$approved,$auth,$status,$status,$id);if(!$s->execute()||$s->affected_rows!==1)throw new Exception('Preauthorization decision was not saved. Refresh and verify its status.');$s->close();
    if(function_exists('audit'))audit('insurance_preauthorization_decision',"id={$id},status={$status},approved={$approved}");
    $conn->commit();$msg='Decision saved.';
   }catch(Throwable $e){$conn->rollback();throw $e;}
  }
 }catch(Throwable $e){$err=$e->getMessage();}
}
$patients=[];$cov=[];$rows=[];
$r=$conn->query("SELECT id,patient_number,full_name FROM patients ORDER BY full_name LIMIT 500");if($r)while($x=$r->fetch_assoc())$patients[]=$x;
$r=$conn->query("SELECT pc.id,pc.patient_id,pc.member_number,p.full_name,py.payer_name FROM patient_coverages pc JOIN patients p ON p.id=pc.patient_id JOIN payers py ON py.id=pc.payer_id WHERE pc.eligibility_status='Verified' ORDER BY p.full_name");if($r)while($x=$r->fetch_assoc())$cov[]=$x;
$r=$conn->query("SELECT pa.*,p.full_name,py.payer_name FROM preauthorizations pa JOIN patients p ON p.id=pa.patient_id JOIN payers py ON py.id=pa.payer_id ORDER BY pa.created_at DESC LIMIT 100");if($r)while($x=$r->fetch_assoc())$rows[]=$x;
include __DIR__.'/../includes/header.php';include __DIR__.'/../includes/sidebar.php';?>
<div class="main-content"><div class="container-fluid p-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="hms-module-hero mb-4"><div><div class="hms-module-kicker">Insurance &amp; SHA</div><h1>Preauthorizations</h1><p>Control payer approval before chargeable services proceed.</p></div></div>
<?php if($err):?><div class="alert alert-danger"><?=htmlspecialchars($err)?></div><?php endif;if($msg):?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif;?>
<form method="post" class="row mb-4"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="create">
<div class="col-md-3"><label>Patient</label><select name="patient_id" class="form-control" required><?php foreach($patients as $p):?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['full_name'].' — '.$p['patient_number'])?></option><?php endforeach;?></select></div>
<div class="col-md-3"><label>Verified Coverage</label><select name="coverage_id" class="form-control" required><?php foreach($cov as $c):?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['full_name'].' — '.$c['payer_name'].' / '.$c['member_number'])?></option><?php endforeach;?></select></div>
<div class="col-md-2"><label>Service</label><select name="service_type" class="form-control"><?php foreach(['Consultation','Service','Lab','Radiology','Procedure','Pharmacy','Admission','Other'] as $t):?><option><?=$t?></option><?php endforeach;?></select></div><div class="col-md-2"><label>Amount</label><input type="number" step=".01" name="amount" class="form-control" required></div><div class="col-md-2"><label>Notes</label><input name="notes" class="form-control"></div><div class="col-12 mt-3"><button class="btn btn-primary">Create Request</button></div></form>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Request</th><th>Patient</th><th>Payer</th><th>Service</th><th>Amount</th><th>Status</th><th>Decision</th></tr></thead><tbody><?php foreach($rows as $x):?><tr><td><?=htmlspecialchars($x['request_number'])?></td><td><?=htmlspecialchars($x['full_name'])?></td><td><?=htmlspecialchars($x['payer_name'])?></td><td><?=htmlspecialchars($x['requested_service_type'])?></td><td>KES <?=number_format($x['requested_amount'],2)?></td><td><?=htmlspecialchars($x['status'])?></td><td><?php if(in_array($x['status'],['Draft','Submitted'],true)):?><form method="post" class="form-inline"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="decision"><input type="hidden" name="id" value="<?=$x['id']?>"><input name="approved_amount" type="number" step=".01" class="form-control form-control-sm mr-1" required><input name="authorization_number" class="form-control form-control-sm mr-1" placeholder="Auth #"><select name="status" class="form-control form-control-sm mr-1"><option>Approved</option><option>Partially Approved</option><option>Rejected</option></select><button class="btn btn-sm btn-success">Save</button></form><?php else:?><?=htmlspecialchars((string)$x['authorization_number'])?><?php endif;?></td></tr><?php endforeach;?></tbody></table></div>
</div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
