<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_module_access($conn, 'theatre', 'view');
function theatre_billing_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$canEdit = can_module_action($conn, 'theatre', 'edit');
$error = ''; $message = '';
$caseId = filter_input(INPUT_GET, 'case_id', FILTER_VALIDATE_INT) ?: 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $caseId = filter_input(INPUT_POST, 'case_id', FILTER_VALIDATE_INT) ?: 0;
 $serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT) ?: 0;
 if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $error = 'Your session token expired. Refresh and try again.';
 elseif (!$canEdit) { http_response_code(403); exit('Forbidden: theatre edit permission required.'); }
 elseif ($caseId <= 0 || $serviceId <= 0) $error = 'Select a valid case and billable procedure service.';
 else {
  $started = false;
  try {
   if (!$conn->begin_transaction()) throw new RuntimeException('Unable to start the theatre billing transaction.');
   $started = true;
   $caseStmt = $conn->prepare("SELECT id,patient_id,visit_id,case_number,procedure_name,status FROM theatre_cases WHERE id=? FOR UPDATE");
   if (!$caseStmt) throw new RuntimeException('Theatre case lookup is unavailable.');
   $caseStmt->bind_param('i',$caseId);
   if (!$caseStmt->execute()) throw new RuntimeException('Unable to load the theatre case.');
   $case = $caseStmt->get_result()->fetch_assoc(); $caseStmt->close();
   if (!$case) throw new RuntimeException('Theatre case not found.');
   if (!in_array($case['status'], ['Recovery','Completed'], true)) throw new RuntimeException('A theatre procedure can only be charged after the operation reaches Recovery or Completed.');
   $serviceStmt=$conn->prepare("SELECT id,service_name,category,active,billable FROM services_master WHERE id=? LIMIT 1 FOR UPDATE");
   if (!$serviceStmt) throw new RuntimeException('The service catalogue is unavailable.');
   $serviceStmt->bind_param('i',$serviceId);
   if (!$serviceStmt->execute()) throw new RuntimeException('Unable to validate the selected service.');
   $service=$serviceStmt->get_result()->fetch_assoc(); $serviceStmt->close();
   if (!$service || (int)$service['active']!==1 || (int)$service['billable']!==1 || strtolower((string)$service['category'])!=='procedure') throw new RuntimeException('Select an active, billable service in the Procedure category.');
   $dupe=$conn->prepare('SELECT id FROM theatre_case_service_charges WHERE case_id=? AND service_id=? LIMIT 1');
   if (!$dupe) throw new RuntimeException('Theatre billing migration is not installed. Apply database/theatre_billing_migration.sql.');
   $dupe->bind_param('ii',$caseId,$serviceId);
   if (!$dupe->execute()) throw new RuntimeException('Unable to check for an existing charge.');
   $already=(bool)$dupe->get_result()->fetch_assoc(); $dupe->close();
   if ($already) throw new RuntimeException('This service has already been charged to this theatre case. Duplicate charges are blocked.');
   $patientId=(int)$case['patient_id']; $visitId=(int)($case['visit_id']??0);
   if ($visitId <= 0) $visitId = (int)get_or_create_current_visit($conn, $patientId);
   $priceData=get_service_price_for_patient($conn,$patientId,$serviceId);
   $price=(float)$priceData['price'];
   if ($price < 0) throw new RuntimeException('The service price is invalid.');
   $invoiceId=get_or_create_visit_invoice($conn,$patientId,$visitId);
   $patientServiceId=add_patient_service($conn,$patientId,$serviceId,$visitId,(isset($priceData['payer_id'])?(int)$priceData['payer_id']:null),(isset($priceData['plan_id'])?(int)$priceData['plan_id']:null),1,0,'Completed','Theatre case '.$case['case_number']);
   $description='Theatre procedure: '.$service['service_name'].' ('.$case['case_number'].')';
   $total=round($price,2);
   $itemStmt=$conn->prepare("INSERT INTO invoice_items (invoice_id,description,qty,unit_price,total,item_type,source,created_at) VALUES (?,?,?,?,?,'procedure','manual',NOW())");
   if (!$itemStmt) throw new RuntimeException('Unable to prepare the theatre invoice line. Confirm invoice_items columns.');
   $qty=1;
   $itemStmt->bind_param('isidd',$invoiceId,$description,$qty,$price,$total);
   if (!$itemStmt->execute()) { $msg=$itemStmt->error; $itemStmt->close(); throw new RuntimeException('Unable to save the invoice line: '.$msg); }
   $invoiceItemId=(int)$itemStmt->insert_id; $itemStmt->close();
   $update=$conn->prepare('UPDATE invoices SET total=COALESCE(total,0)+? WHERE id=?');
   if (!$update) throw new RuntimeException('Unable to prepare invoice total update.');
   $update->bind_param('di',$total,$invoiceId);
   if (!$update->execute()) { $update->close(); throw new RuntimeException('Unable to update the invoice total.'); }
   $update->close();
   $map=$conn->prepare('INSERT INTO theatre_case_service_charges (case_id,service_id,patient_service_id,invoice_id,invoice_item_id,unit_price,recorded_by) VALUES (?,?,?,?,?,?,?)');
   if (!$map) throw new RuntimeException('Theatre billing migration is not installed. Apply database/theatre_billing_migration.sql.');
   $uid=(int)($_SESSION['user_id']??0);
   $map->bind_param('iiiiidi',$caseId,$serviceId,$patientServiceId,$invoiceId,$invoiceItemId,$price,$uid);
   if (!$map->execute()) { $msg=$map->error; $map->close(); throw new RuntimeException('Unable to link the charge to the case: '.$msg); }
   $map->close();
   if (function_exists('post_invoice_journal')) post_invoice_journal($conn,$invoiceId,$patientId,$total,'Theatre procedure '.$case['case_number'],$invoiceItemId);
   if (!$conn->commit()) throw new RuntimeException('The billing transaction could not be confirmed. Refresh before retrying.');
   $started=false; $message='Procedure charge added to the patient invoice and linked to this theatre case.';
   if (function_exists('audit')) audit('theatre_procedure_charge_added','case_id='.$caseId.',service_id='.$serviceId.',invoice_id='.$invoiceId);
  } catch (Throwable $e) {
   if ($started) $conn->rollback();
   if (!($e instanceof RuntimeException)) error_log('Theatre billing error: '.$e->getMessage());
   $error=$e instanceof RuntimeException?$e->getMessage():'Unable to record the theatre procedure charge.';
  }
 }
}
$case=null;
$stmt=$conn->prepare('SELECT tc.id,tc.case_number,tc.patient_id,tc.visit_id,tc.procedure_name,tc.status,p.patient_number,p.full_name FROM theatre_cases tc JOIN patients p ON p.id=tc.patient_id WHERE tc.id=? LIMIT 1');
if($stmt){$stmt->bind_param('i',$caseId);if($stmt->execute())$case=$stmt->get_result()->fetch_assoc();$stmt->close();}
$services=$conn->query("SELECT id,service_code,service_name,price FROM services_master WHERE active=1 AND billable=1 AND LOWER(category)='procedure' ORDER BY service_name");
$charges=[];
$stmt=$conn->prepare('SELECT c.*,s.service_name,i.invoice_number,i.invoice_no FROM theatre_case_service_charges c JOIN services_master s ON s.id=c.service_id JOIN invoices i ON i.id=c.invoice_id WHERE c.case_id=? ORDER BY c.id DESC');
if($stmt){$stmt->bind_param('i',$caseId);if($stmt->execute()){$rs=$stmt->get_result();while($row=$rs->fetch_assoc())$charges[]=$row;}$stmt->close();}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Theatre Procedure Billing | HMS</title>
<style>body{margin:0;background:#f3f6fa;color:#172b45;font:15px/1.5 Arial,sans-serif}.wrap{max-width:1000px;margin:24px auto;padding:0 16px}.hero,.panel{background:#fff;border:1px solid #dbe4ef;border-radius:12px;padding:20px;margin-bottom:16px}.hero{background:linear-gradient(120deg,#102b49,#075b9d);color:#fff}.hero h1{margin:0}.muted{color:#65758b}.hero .muted{color:#dceafa}.notice{padding:12px 14px;border-radius:8px;margin:12px 0}.error{background:#fff0ef;color:#9c2923}.success{background:#eaf7ed;color:#216537}.btn{display:inline-block;background:#075b9d;color:#fff;border:0;border-radius:7px;padding:10px 14px;text-decoration:none;font-weight:700;cursor:pointer}select{max-width:100%;width:100%;padding:10px;border:1px solid #c7d3e1;border-radius:7px;margin:8px 0 14px}table{border-collapse:collapse;width:100%}th,td{text-align:left;padding:10px;border-bottom:1px solid #e4ebf3} .links{display:flex;gap:10px;flex-wrap:wrap}</style></head><body><main class="wrap">
<section class="hero"><h1>Theatre Procedure Billing</h1><div class="muted">Post an approved billable procedure to the patient's invoice, with a case-level duplicate-charge guard.</div></section>
<?php if($error!==''):?><div class="notice error"><?=theatre_billing_h($error)?></div><?php endif;?><?php if($message!==''):?><div class="notice success"><?=theatre_billing_h($message)?></div><?php endif;?>
<?php if(!$case):?><section class="panel">Theatre case not found. <a href="index.php">Return to Theatre</a></section><?php else:?>
<section class="panel"><h2><?=theatre_billing_h($case['case_number'])?> — <?=theatre_billing_h($case['procedure_name'])?></h2><p><?=theatre_billing_h($case['patient_number'])?> · <?=theatre_billing_h($case['full_name'])?> · Status: <strong><?=theatre_billing_h($case['status'])?></strong></p><div class="links"><a class="btn" href="index.php">Back to Theatre</a><a class="btn" href="clinical_records.php?case_id=<?=(int)$caseId?>">PACU &amp; Implants</a></div></section>
<section class="panel"><h2>Add a procedure charge</h2><?php if(!$canEdit):?><p class="muted">Edit permission is required to add charges.</p><?php elseif(!in_array($case['status'],['Recovery','Completed'],true)):?><p class="muted">Charges can be posted after the case reaches Recovery or Completed. No charge has been posted from this screen.</p><?php else:?><form method="post"><input type="hidden" name="csrf_token" value="<?=theatre_billing_h(csrf_token())?>"><input type="hidden" name="case_id" value="<?=(int)$caseId?>"><label for="service_id">Billable procedure service</label><select id="service_id" name="service_id" required><option value="">Choose a procedure service</option><?php if($services):while($sv=$services->fetch_assoc()):?><option value="<?=(int)$sv['id']?>"><?=theatre_billing_h($sv['service_name'])?> — KES <?=number_format((float)$sv['price'],2)?></option><?php endwhile;endif;?></select><p class="muted">The patient's active payer/plan pricing is applied by the billing helper. The charge is posted once per service per case; verify the correct service and tariff before submission.</p><button class="btn" type="submit">Add to patient invoice</button></form><?php endif;?></section>
<section class="panel"><h2>Charges linked to this case</h2><?php if(!$charges):?><p class="muted">No theatre procedure charges have been recorded for this case.</p><?php else:?><table><thead><tr><th>Service</th><th>Price</th><th>Invoice</th><th>Recorded</th></tr></thead><tbody><?php foreach($charges as $charge):?><tr><td><?=theatre_billing_h($charge['service_name'])?></td><td>KES <?=number_format((float)$charge['unit_price'],2)?></td><td><?=theatre_billing_h($charge['invoice_number']?:$charge['invoice_no']?:('#'.$charge['invoice_id']))?></td><td><?=theatre_billing_h($charge['created_at']??'Linked')?></td></tr><?php endforeach;?></tbody></table><?php endif;?></section>
<?php endif;?></main></body></html>