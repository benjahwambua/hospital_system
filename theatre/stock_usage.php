<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'theatre', 'view');
function theatre_stock_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$canEdit = can_module_action($conn, 'theatre', 'edit') && can_module_action($conn, 'central_stores', 'edit');
$error=''; $message='';
$caseId=filter_input(INPUT_GET,'case_id',FILTER_VALIDATE_INT)?:0;
if($_SERVER['REQUEST_METHOD']==='POST'){
 $caseId=filter_input(INPUT_POST,'case_id',FILTER_VALIDATE_INT)?:0;
 $itemId=filter_input(INPUT_POST,'item_id',FILTER_VALIDATE_INT)?:0;
 $locationId=filter_input(INPUT_POST,'location_id',FILTER_VALIDATE_INT)?:0;
 $quantityRaw=trim((string)($_POST['quantity']??''));
 $batch=trim((string)($_POST['batch_number']??''));
 $expiryRaw=trim((string)($_POST['expiry_date']??''));
 $notes=trim((string)($_POST['notes']??''));
 if(!verify_csrf_token($_POST['csrf_token']??null)) $error='Your session token expired. Refresh and try again.';
 elseif(!$canEdit){http_response_code(403);exit('Forbidden: theatre edit and central stores edit permissions are required.');}
 elseif($caseId<=0||$itemId<=0||$locationId<=0) $error='Select a valid theatre case, store item and location.';
 elseif(!is_numeric($quantityRaw)||(float)$quantityRaw<=0||(float)$quantityRaw>1000000) $error='Enter a positive quantity.';
 elseif(!preg_match('/^\d+(\.\d{1,3})?$/',$quantityRaw)) $error='Quantity may have up to three decimal places.';
 elseif(strlen($batch)>100||strlen($notes)>500) $error='Batch or notes exceed the allowed length.';
 elseif($expiryRaw!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$expiryRaw)||!checkdate((int)substr($expiryRaw,5,2),(int)substr($expiryRaw,8,2),(int)substr($expiryRaw,0,4)))) $error='Enter a valid expiry date.';
 elseif($expiryRaw!==''&&$expiryRaw<date('Y-m-d')) $error='Expired stock cannot be issued.';
 else {
  $started=false;
  try{
   if(!$conn->begin_transaction()) throw new RuntimeException('Unable to start stock issue transaction.');
   $started=true;
   $caseStmt=$conn->prepare('SELECT id,case_number,status FROM theatre_cases WHERE id=? FOR UPDATE');
   if(!$caseStmt) throw new RuntimeException('Theatre case lookup is unavailable.');
   $caseStmt->bind_param('i',$caseId);
   if(!$caseStmt->execute()) throw new RuntimeException('Unable to load the theatre case.');
   $case=$caseStmt->get_result()->fetch_assoc();$caseStmt->close();
   if(!$case) throw new RuntimeException('Theatre case not found.');
   if(!in_array($case['status'],['Pre-op','In Theatre','Recovery'],true)) throw new RuntimeException('Stock can be issued only for a case in Pre-op, In Theatre or Recovery status. Completed or cancelled cases cannot consume additional stock.');
   $itemStmt=$conn->prepare('SELECT id FROM stores_items WHERE id=? AND active=1 FOR UPDATE');
   if(!$itemStmt) throw new RuntimeException('Central Stores item catalogue is unavailable.');
   $itemStmt->bind_param('i',$itemId);
   if(!$itemStmt->execute()||!$itemStmt->get_result()->fetch_assoc()){$itemStmt->close();throw new RuntimeException('Select an active Central Stores item.');}
   $itemStmt->close();
   $locStmt=$conn->prepare('SELECT id FROM stores_locations WHERE id=? AND active=1 LIMIT 1');
   if(!$locStmt) throw new RuntimeException('Store locations are unavailable.');
   $locStmt->bind_param('i',$locationId);
   if(!$locStmt->execute()||!$locStmt->get_result()->fetch_assoc()){$locStmt->close();throw new RuntimeException('Select an active store location.');}
   $locStmt->close();
   $expiry=$expiryRaw===''?null:$expiryRaw;
   $balanceStmt=$conn->prepare("SELECT COALESCE(SUM(CASE WHEN movement_type IN ('Opening','Receipt','Transfer In','Return','Adjustment In') THEN quantity ELSE -quantity END),0) AS balance FROM stores_movements WHERE item_id=? AND location_id=? AND COALESCE(batch_number,'')=COALESCE(?, '') AND expiry_date <=> ?");
   if(!$balanceStmt) throw new RuntimeException('Unable to verify stock balance.');
   $balanceStmt->bind_param('iiss',$itemId,$locationId,$batch,$expiry);
   if(!$balanceStmt->execute()){$balanceStmt->close();throw new RuntimeException('Unable to verify stock balance.');}
   $balance=(float)($balanceStmt->get_result()->fetch_assoc()['balance']??0);$balanceStmt->close();
   $quantity=(float)$quantityRaw;
   if($balance+0.000001<$quantity) throw new RuntimeException('Insufficient available stock for the selected item, location, batch and expiry. Available: '.number_format(max(0,$balance),3).'.');
   $costStmt=$conn->prepare("SELECT unit_cost FROM stores_movements WHERE item_id=? AND location_id=? AND COALESCE(batch_number,'')=COALESCE(?, '') AND expiry_date <=> ? AND unit_cost IS NOT NULL AND movement_type IN ('Opening','Receipt','Transfer In','Return','Adjustment In') ORDER BY id DESC LIMIT 1");
   $unitCost=null;
   if($costStmt){$costStmt->bind_param('iiss',$itemId,$locationId,$batch,$expiry);if($costStmt->execute()){$costRow=$costStmt->get_result()->fetch_assoc();if($costRow)$unitCost=(float)$costRow['unit_cost'];}$costStmt->close();}
   $newBalance=round($balance-$quantity,3);$totalCost=$unitCost===null?null:round($unitCost*$quantity,2);
   $refType='theatre_case';$refId=(int)$caseId;$uid=(int)($_SESSION['user_id']??0);
   $movement=$conn->prepare("INSERT INTO stores_movements (item_id,location_id,movement_type,quantity,unit_cost,total_cost,reference_type,reference_id,batch_number,expiry_date,notes,created_by,created_at) VALUES (?,?,'Issue',?,?,?,?,?,?,?,?,?,NOW())");
   if(!$movement) throw new RuntimeException('Unable to prepare Central Stores movement. Confirm the stores schema.');
   $movement->bind_param('iidddsisssi',$itemId,$locationId,$quantity,$unitCost,$totalCost,$refType,$refId,$batch,$expiry,$notes,$uid);
   if(!$movement->execute()){$msg=$movement->error;$movement->close();throw new RuntimeException('Stock issue could not be saved: '.$msg);}
   $movementId=(int)$movement->insert_id;$movement->close();
   $map=$conn->prepare('INSERT INTO theatre_case_stock_issues (case_id,item_id,location_id,movement_id,batch_number,expiry_date,quantity,recorded_by) VALUES (?,?,?,?,?,?,?,?)');
   if(!$map) throw new RuntimeException('Theatre stock issue migration missing. Apply database/theatre_stock_usage_migration.sql.');
   $map->bind_param('iiiissdi',$caseId,$itemId,$locationId,$movementId,$batch,$expiry,$quantity,$uid);
   if(!$map->execute()){$msg=$map->error;$map->close();throw new RuntimeException('Unable to link the stock issue to the theatre case: '.$msg);}
   $map->close();
   if(!$conn->commit()) throw new RuntimeException('The stock issue transaction could not be confirmed. Refresh before retrying.');
   $started=false;$message='Stock issue recorded in Central Stores and linked to theatre case '.$case['case_number'].'. Remaining balance: '.number_format($newBalance,3).'.';
   if(function_exists('audit'))audit('theatre_stock_issued','case_id='.$caseId.',item_id='.$itemId.',location_id='.$locationId.',movement_id='.$movementId);
  }catch(Throwable $e){if($started)$conn->rollback();if(!($e instanceof RuntimeException))error_log('Theatre stock issue error: '.$e->getMessage());$error=$e instanceof RuntimeException?$e->getMessage():'Unable to record the theatre stock issue.';}
 }
}
$case=null;$stmt=$conn->prepare('SELECT tc.id,tc.case_number,tc.procedure_name,tc.status,p.patient_number,p.full_name FROM theatre_cases tc JOIN patients p ON p.id=tc.patient_id WHERE tc.id=? LIMIT 1');
if($stmt){$stmt->bind_param('i',$caseId);if($stmt->execute())$case=$stmt->get_result()->fetch_assoc();$stmt->close();}
$items=$conn->query('SELECT id,item_code,item_name,unit FROM stores_items WHERE active=1 ORDER BY item_name');
$locations=$conn->query('SELECT id,location_code,location_name FROM stores_locations WHERE active=1 ORDER BY location_name');
$issues=[];
$stmt=$conn->prepare('SELECT x.*,i.item_code,i.item_name,i.unit,l.location_name,m.created_at,m.notes AS movement_notes FROM theatre_case_stock_issues x JOIN stores_items i ON i.id=x.item_id JOIN stores_locations l ON l.id=x.location_id JOIN stores_movements m ON m.id=x.movement_id WHERE x.case_id=? ORDER BY x.id DESC');
if($stmt){$stmt->bind_param('i',$caseId);if($stmt->execute()){$rs=$stmt->get_result();while($r=$rs->fetch_assoc())$issues[]=$r;}$stmt->close();}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Theatre Stock Usage | HMS</title>
<style>body{margin:0;background:#f3f6fa;color:#172b45;font:15px/1.5 Arial,sans-serif}.wrap{max-width:1100px;margin:24px auto;padding:0 16px}.hero,.panel{background:#fff;border:1px solid #dbe4ef;border-radius:12px;padding:20px;margin-bottom:16px}.hero{background:linear-gradient(120deg,#102b49,#075b9d);color:#fff}.hero h1{margin:0}.muted{color:#65758b}.hero .muted{color:#dceafa}.notice{padding:12px 14px;border-radius:8px;margin:12px 0}.error{background:#fff0ef;color:#9c2923}.success{background:#eaf7ed;color:#216537}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.field label{display:block;font-weight:700}.field input,.field select,.field textarea{width:100%;padding:10px;border:1px solid #c7d3e1;border-radius:7px;box-sizing:border-box;margin:5px 0 12px}.btn{display:inline-block;background:#075b9d;color:#fff;border:0;border-radius:7px;padding:10px 14px;text-decoration:none;font-weight:700;cursor:pointer}table{border-collapse:collapse;width:100%}th,td{text-align:left;padding:10px;border-bottom:1px solid #e4ebf3}.links{display:flex;gap:10px;flex-wrap:wrap}@media(max-width:650px){.grid{grid-template-columns:1fr}}</style></head><body><main class="wrap">
<section class="hero"><h1>Theatre Stock Usage</h1><div class="muted">Issue consumables from Central Stores with lot, expiry and case traceability.</div></section>
<?php if($error!==''):?><div class="notice error"><?=theatre_stock_h($error)?></div><?php endif;?><?php if($message!==''):?><div class="notice success"><?=theatre_stock_h($message)?></div><?php endif;?>
<?php if(!$case):?><section class="panel">Theatre case not found. <a href="index.php">Return to Theatre</a></section><?php else:?>
<section class="panel"><h2><?=theatre_stock_h($case['case_number'])?> — <?=theatre_stock_h($case['procedure_name'])?></h2><p><?=theatre_stock_h($case['patient_number'])?> · <?=theatre_stock_h($case['full_name'])?> · Status: <strong><?=theatre_stock_h($case['status'])?></strong></p><div class="links"><a class="btn" href="index.php">Back to Theatre</a><a class="btn" href="clinical_records.php?case_id=<?=(int)$caseId?>">PACU &amp; Implants</a></div></section>
<section class="panel"><h2>Issue item from Central Stores</h2><?php if(!$canEdit):?><p class="muted">Both Theatre edit and Central Stores edit permissions are required to issue stock.</p><?php elseif(!in_array($case['status'],['Pre-op','In Theatre','Recovery'],true)):?><p class="muted">Stock issue is available during Pre-op, In Theatre and Recovery only. Planned, Completed and Cancelled cases cannot consume additional stock.</p><?php else:?><form method="post"><input type="hidden" name="csrf_token" value="<?=theatre_stock_h(csrf_token())?>"><input type="hidden" name="case_id" value="<?=(int)$caseId?>"><div class="grid"><div class="field"><label for="item_id">Item</label><select id="item_id" name="item_id" required><option value="">Choose item</option><?php if($items):while($it=$items->fetch_assoc()):?><option value="<?=(int)$it['id']?>"><?=theatre_stock_h($it['item_code'].' — '.$it['item_name'].' ('.$it['unit'].')')?></option><?php endwhile;endif;?></select></div><div class="field"><label for="location_id">Issue from location</label><select id="location_id" name="location_id" required><option value="">Choose location</option><?php if($locations):while($loc=$locations->fetch_assoc()):?><option value="<?=(int)$loc['id']?>"><?=theatre_stock_h($loc['location_code'].' — '.$loc['location_name'])?></option><?php endwhile;endif;?></select></div><div class="field"><label for="quantity">Quantity</label><input id="quantity" name="quantity" type="number" min="0.001" step="0.001" required></div><div class="field"><label for="batch_number">Batch / lot number (if tracked)</label><input id="batch_number" name="batch_number" maxlength="100"></div><div class="field"><label for="expiry_date">Expiry date (if tracked)</label><input id="expiry_date" name="expiry_date" type="date"></div><div class="field"><label for="notes">Usage notes</label><input id="notes" name="notes" maxlength="500" placeholder="Optional reason or usage note"></div></div><p class="muted">Available stock is checked for the exact item, location, batch and expiry. Expired lots and over-issues are blocked. This page requires both Theatre and Central Stores edit rights.</p><button class="btn" type="submit">Issue stock to case</button></form><?php endif;?></section>
<section class="panel"><h2>Stock issued to this case</h2><?php if(!$issues):?><p class="muted">No Central Stores issues are linked to this theatre case.</p><?php else:?><table><thead><tr><th>Item</th><th>Location</th><th>Qty</th><th>Batch / expiry</th><th>Recorded</th></tr></thead><tbody><?php foreach($issues as $x):?><tr><td><?=theatre_stock_h($x['item_code'].' — '.$x['item_name'])?></td><td><?=theatre_stock_h($x['location_name'])?></td><td><?=number_format((float)$x['quantity'],3).' '.theatre_stock_h($x['unit'])?></td><td><?=theatre_stock_h($x['batch_number']?:'Not recorded')?> / <?=theatre_stock_h($x['expiry_date']?:'Not recorded')?></td><td><?=theatre_stock_h($x['created_at'])?></td></tr><?php endforeach;?></tbody></table><?php endif;?></section>
<?php endif;?></main></body></html>