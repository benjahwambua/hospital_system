<?php
require_once __DIR__.'/../config/config.php'; require_once __DIR__.'/../includes/session.php'; require_once __DIR__.'/../includes/auth.php'; require_login();
require_module_access($conn,'insurance','view'); if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));$csrf=$_SESSION['csrf_token'];$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $a=$_POST['action']??''; require_module_access($conn,'insurance',$a==='submit'?'approve':'create');
 $transactionStarted=false;
 if(!verify_csrf_token($_POST['csrf_token']??null))$err='Invalid security token.'; else try{
  if($a==='create'){
   $conn->begin_transaction();$transactionStarted=true;
   $inv=(int)($_POST['invoice_id']??0);if($inv<=0)throw new Exception('Select a valid invoice.');
   $q=$conn->prepare("SELECT i.id,i.patient_id,i.created_at,i.total,pc.id coverage_id,pc.payer_id,pc.plan_id,pp.consultation_cover_percent,pp.pharmacy_cover_percent,pp.lab_cover_percent,pp.radiology_cover_percent,pp.procedure_cover_percent,pp.default_copay_amount FROM invoices i JOIN patient_coverages pc ON pc.patient_id=i.patient_id AND pc.eligibility_status='Verified' AND (pc.start_date IS NULL OR pc.start_date<=CURDATE()) AND (pc.end_date IS NULL OR pc.end_date>=CURDATE()) LEFT JOIN payer_plans pp ON pp.id=pc.plan_id AND pp.payer_id=pc.payer_id AND pp.active=1 WHERE i.id=? ORDER BY pc.is_primary DESC,pc.id DESC LIMIT 1 FOR UPDATE");
   if(!$q)throw new Exception('Unable to prepare invoice and coverage validation.');
   $q->bind_param('i',$inv);if(!$q->execute())throw new Exception('Unable to validate invoice and patient coverage.');$v=$q->get_result()->fetch_assoc();$q->close();
   if(!$v)throw new Exception('Invoice has no verified patient coverage.');
   $q=$conn->prepare("SELECT id,description AS item_name,quantity,unit_price,total,source,item_type,med_id FROM invoice_items WHERE invoice_id=? AND NOT (COALESCE(source,'manual')='manual' AND LOWER(COALESCE(description,'')) LIKE 'patient payment -%')");
   if(!$q)throw new Exception('Unable to prepare invoice claim items.');
   $q->bind_param('i',$inv);if(!$q->execute())throw new Exception('Unable to load invoice items.');$items=$q->get_result()->fetch_all(MYSQLI_ASSOC);$q->close();
   $claimTotal=0.0;foreach($items as $line)$claimTotal+=(float)$line['total'];
   if(!$items||$claimTotal<=0)throw new Exception('The invoice has no positive billable service or product lines to claim.');
   $q=$conn->prepare("SELECT COUNT(*) c FROM claim_headers WHERE invoice_id=?");if(!$q)throw new Exception('Unable to check for an existing claim.');
   $q->bind_param('i',$inv);if(!$q->execute())throw new Exception('Unable to check for an existing claim.');$exists=(int)$q->get_result()->fetch_assoc()['c'];$q->close();if($exists)throw new Exception('A claim already exists for this invoice.');
   $num='CLM-'.date('YmdHis').'-'.random_int(100,999);
   $s=$conn->prepare("INSERT INTO claim_headers(claim_number,patient_id,patient_coverage_id,payer_id,plan_id,invoice_id,encounter_date,claim_status,total_claim_amount,created_by) VALUES(?,?,?,?,?,?,DATE(?), 'Draft',?,?)");
   if(!$s)throw new Exception('Unable to prepare claim header.');
   $createdBy=(int)($_SESSION['user_id']??0);
   $s->bind_param('siiiiisdi',$num,$v['patient_id'],$v['coverage_id'],$v['payer_id'],$v['plan_id'],$v['id'],$v['created_at'],$claimTotal,$createdBy);
   if(!$s->execute())throw new Exception('Unable to create claim header: '.$s->error);$cid=(int)$s->insert_id;$s->close();
   // Calculate benefits from the selected active plan. Unsupported benefit categories
   // default to 0% rather than assuming full payer coverage.
   $preparedLines=[];$payerCoverageTotal=0.0;
   foreach($items as $it){
    $type='Other';$source=strtolower((string)($it['source']??'manual'));$itemType=strtolower((string)($it['item_type']??''));
    if($source==='pharmacy'||$itemType==='pharmacy')$type='Pharmacy';
    elseif($source==='lab'||$itemType==='lab')$type='Lab';
    elseif($source==='radiology'||$itemType==='radiology')$type='Radiology';
    elseif($itemType==='procedure')$type='Procedure';
    elseif($itemType==='consultation')$type='Consultation';
    elseif($itemType==='admission')$type='Admission';
    elseif(in_array($itemType,['service','treatment','maternity'],true))$type='Service';

    $rate=0.0;
    if($type==='Consultation')$rate=max(0.0,min(100.0,(float)($v['consultation_cover_percent']??0)));
    elseif($type==='Pharmacy')$rate=max(0.0,min(100.0,(float)($v['pharmacy_cover_percent']??0)));
    elseif($type==='Lab')$rate=max(0.0,min(100.0,(float)($v['lab_cover_percent']??0)));
    elseif($type==='Radiology')$rate=max(0.0,min(100.0,(float)($v['radiology_cover_percent']??0)));
    elseif($type==='Procedure')$rate=max(0.0,min(100.0,(float)($v['procedure_cover_percent']??0)));

    $src='invoice_items';$sourceId=(int)$it['id'];$itemName=(string)$it['item_name'];
    $quantity=max(0.0,(float)$it['quantity']);$unitPrice=max(0.0,(float)$it['unit_price']);$gross=max(0.0,(float)$it['total']);
    $allowed=$gross;$tariffMatched=false;
    // Invoice rows do not consistently retain a canonical service code. Match a
    // tariff only by exact item name and benefit type; prefer plan-specific rows.
    $tariff=$conn->prepare("SELECT COALESCE(NULLIF(approved_price,0),base_price) AS allowed_price FROM payer_tariffs WHERE payer_id=? AND item_name=? AND item_type=? AND active=1 AND (plan_id=? OR plan_id IS NULL) ORDER BY CASE WHEN plan_id=? THEN 1 ELSE 2 END,id DESC LIMIT 1");
    if(!$tariff)throw new Exception('Unable to prepare payer tariff lookup.');
    $payerId=(int)$v['payer_id'];$planId=$v['plan_id']!==null?(int)$v['plan_id']:0;
    $tariff->bind_param('issii',$payerId,$itemName,$type,$planId,$planId);
    if(!$tariff->execute())throw new Exception('Unable to look up payer tariff.');
    $tariffRow=$tariff->get_result()->fetch_assoc();$tariff->close();
    if($tariffRow){$allowed=min($gross,max(0.0,(float)$tariffRow['allowed_price']*$quantity));$tariffMatched=true;}
    $baseCovered=round(min($gross,$allowed)*$rate/100,2);
    $payerCoverageTotal+=$baseCovered;
    $preparedLines[]=['type'=>$type,'src'=>$src,'source_id'=>$sourceId,'item_name'=>$itemName,'quantity'=>$quantity,'unit_price'=>$unitPrice,'gross'=>$gross,'allowed'=>$allowed,'base_covered'=>$baseCovered,'tariff_matched'=>$tariffMatched];
   }
   // Apply the plan copay once per claim, against the payer-covered portion.
   $copayRemaining=max(0.0,(float)($v['default_copay_amount']??0));
   $is=$conn->prepare("INSERT INTO claim_items(claim_id,item_type,source_table,source_id,item_code,item_name,quantity,unit_price,gross_amount,allowed_amount,contractual_adjustment,covered_amount,copay_amount,patient_responsibility,status) VALUES(?,?,?,?,NULL,?,?,?,?,?,?,?,?,?,'Draft')");
   if(!$is)throw new Exception('Unable to prepare claim item creation.');
   $totalPatientResponsibility=0.0;$totalPayerClaim=0.0;
   foreach($preparedLines as $line){
    $copay=min($copayRemaining,$line['base_covered']);$copayRemaining=round($copayRemaining-$copay,2);
    $covered=round(max(0.0,$line['base_covered']-$copay),2);
    $allowedAmount=round(min($line['gross'],max(0.0,$line['allowed'])),2);
    $contractualAdjustment=round(max(0.0,$line['gross']-$allowedAmount),2);
    $responsibility=round(max(0.0,$allowedAmount-$covered),2);$totalPatientResponsibility+=$responsibility;$totalPayerClaim+=$covered;
    $itemTypeValue=$line['type'];$src=$line['src'];$sourceId=$line['source_id'];$itemName=$line['item_name'];$quantity=$line['quantity'];$unitPrice=$line['unit_price'];$gross=$line['gross'];
    $is->bind_param('issisdddddddd',$cid,$itemTypeValue,$src,$sourceId,$itemName,$quantity,$unitPrice,$gross,$allowedAmount,$contractualAdjustment,$covered,$copay,$responsibility);
    if(!$is->execute())throw new Exception('Unable to create claim item: '.$is->error);
   }
   $is->close();
   $h=$conn->prepare("UPDATE claim_headers SET total_claim_amount=?,total_patient_responsibility=? WHERE id=?");
   if(!$h)throw new Exception('Unable to prepare claim totals.');
   $h->bind_param('ddi',$totalPayerClaim,$totalPatientResponsibility,$cid);
   if(!$h->execute())throw new Exception('Unable to save claim totals.');
   $h->close();
   if(function_exists('audit'))audit('insurance_claim_created',"claim_id={$cid},invoice_id={$inv}");
   $conn->commit();$transactionStarted=false;$msg='Claim created from invoice.';
  }elseif($a==='submit'){ $id=(int)$_POST['id'];$s=$conn->prepare("UPDATE claim_headers SET claim_status='Submitted',submission_date=CURDATE() WHERE id=? AND claim_status='Draft'");$s->bind_param('i',$id);$s->execute();if(!$s->affected_rows)throw new Exception('Claim is not in Draft status.');$s->close();if(function_exists('audit'))audit('insurance_claim_submitted',"claim_id={$id}");$msg='Claim submitted.';}
 }catch(Throwable $e){if($transactionStarted){$conn->rollback();$transactionStarted=false;}$err=$e->getMessage();error_log('Insurance claims workflow failed: '.$e->getMessage());}
}
$invoices=[];$q=$conn->query("SELECT i.id,i.created_at,i.total,p.full_name FROM invoices i JOIN patients p ON p.id=i.patient_id JOIN patient_coverages pc ON pc.patient_id=i.patient_id AND pc.eligibility_status='Verified' AND (pc.start_date IS NULL OR pc.start_date<=CURDATE()) AND (pc.end_date IS NULL OR pc.end_date>=CURDATE()) LEFT JOIN claim_headers ch ON ch.invoice_id=i.id WHERE ch.id IS NULL ORDER BY i.created_at DESC LIMIT 100");if($q)while($x=$q->fetch_assoc())$invoices[]=$x;
$claims=[];$q=$conn->query("SELECT ch.*,p.full_name,py.payer_name FROM claim_headers ch JOIN patients p ON p.id=ch.patient_id JOIN payers py ON py.id=ch.payer_id ORDER BY ch.created_at DESC LIMIT 100");if($q)while($x=$q->fetch_assoc())$claims[]=$x;
include __DIR__.'/../includes/header.php';include __DIR__.'/../includes/sidebar.php';?>
<div class="main-content"><div class="container-fluid p-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="hms-module-hero mb-4"><div><div class="hms-module-kicker">Insurance &amp; SHA</div><h1>Insurance Claims</h1><p>Generate claims from billed services, review and submit to payers.</p></div></div>
<?php if($err):?><div class="alert alert-danger"><?=htmlspecialchars($err)?></div><?php endif;if($msg):?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif;?>
<form method="post" class="form-inline mb-4"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="create"><select name="invoice_id" class="form-control mr-2" required><option value="">Select eligible invoice</option><?php foreach($invoices as $i):?><option value="<?=$i['id']?>">#<?=$i['id']?> — <?=htmlspecialchars($i['full_name'])?> — KES <?=number_format($i['total'],2)?></option><?php endforeach;?></select><button class="btn btn-primary">Generate Claim</button></form>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Claim</th><th>Patient</th><th>Payer</th><th>Invoice</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($claims as $c):?><tr><td><?=htmlspecialchars($c['claim_number'])?></td><td><?=htmlspecialchars($c['full_name'])?></td><td><?=htmlspecialchars($c['payer_name'])?></td><td>#<?=htmlspecialchars($c['invoice_id'])?></td><td>KES <?=number_format($c['total_claim_amount'],2)?></td><td><?=htmlspecialchars($c['claim_status'])?></td><td><?php if($c['claim_status']==='Draft'):?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="submit"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn btn-sm btn-success">Submit</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div>
</div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
