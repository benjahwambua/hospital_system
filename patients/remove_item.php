<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'clinical', 'delete');
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); exit('Invalid security token.'); }

$id=(int)($_POST['id']??0);$type=(string)($_POST['type']??'');$patientId=(int)($_POST['patient_id']??0);
if($id<=0||$patientId<=0||!in_array($type,['service','prescription'],true)){http_response_code(400);exit('Invalid item.');}

$table=$type==='service'?'patient_services':'prescriptions';
$conn->begin_transaction();
try{
 $s=$conn->prepare("SELECT * FROM {$table} WHERE id=? AND patient_id=? LIMIT 1 FOR UPDATE");if(!$s)throw new Exception('Unable to load item.');
 $s->bind_param('ii',$id,$patientId);$s->execute();$item=$s->get_result()->fetch_assoc();$s->close();if(!$item)throw new Exception('Item not found.');
 $invoiceId=(int)($item['invoice_id']??0);
 if($invoiceId<=0&&isset($item['visit_id'])){$v=$conn->prepare("SELECT id FROM invoices WHERE patient_id=? AND visit_id=? ORDER BY id DESC LIMIT 1");if($v){$vid=(int)$item['visit_id'];$v->bind_param('ii',$patientId,$vid);$v->execute();$invoiceId=(int)($v->get_result()->fetch_assoc()['id']??0);$v->close();}}
 if($invoiceId>0){
  $p=$conn->prepare("SELECT COALESCE(SUM(p.amount),0)-COALESCE((SELECT SUM(r.amount) FROM payment_refunds r WHERE r.invoice_id=p.invoice_id AND r.status='Approved'),0) paid FROM payments p WHERE p.invoice_id=?");if(!$p)throw new Exception('Unable to verify invoice payments.');
  $p->bind_param('i',$invoiceId);$p->execute();$paid=(float)($p->get_result()->fetch_assoc()['paid']??0);$p->close();
  if($paid>0.00001)throw new Exception('This item cannot be deleted because its invoice has financial activity. Use a reversal/refund workflow instead.');

  $hasType=$conn->query("SHOW COLUMNS FROM invoice_items LIKE 'item_type'");
  $hasMed=$conn->query("SHOW COLUMNS FROM invoice_items LIKE 'med_id'");
  $hasSource=$conn->query("SHOW COLUMNS FROM invoice_items LIKE 'source'");
  $hasSourceId=$conn->query("SHOW COLUMNS FROM invoice_items LIKE 'source_id'");
  $source=$type==='service'?'service':'pharmacy';$sourceId=$type==='service'?(int)($item['service_id']??0):(int)($item['medicine_id']??0);
  if($hasType&&$hasType->num_rows&&$hasMed&&$hasMed->num_rows){
   $line=$conn->prepare("DELETE FROM invoice_items WHERE id=(SELECT id FROM (SELECT id FROM invoice_items WHERE invoice_id=? AND item_type=? AND med_id=? ORDER BY id DESC LIMIT 1) x)");
   if($line){$line->bind_param('isi',$invoiceId,$source,$sourceId);$line->execute();$line->close();}
  }elseif($hasSource&&$hasSource->num_rows&&$hasSourceId&&$hasSourceId->num_rows){
   $line=$conn->prepare("DELETE FROM invoice_items WHERE id=(SELECT id FROM (SELECT id FROM invoice_items WHERE invoice_id=? AND source=? AND source_id=? ORDER BY id DESC LIMIT 1) x)");
   if($line){$line->bind_param('isi',$invoiceId,$source,$sourceId);$line->execute();$line->close();}
  }
 }
 $d=$conn->prepare("DELETE FROM {$table} WHERE id=? AND patient_id=?");if(!$d)throw new Exception('Unable to remove item.');$d->bind_param('ii',$id,$patientId);if(!$d->execute()||$d->affected_rows!==1)throw new Exception('Item could not be removed.');$d->close();
 if($type==='prescription'){$q=$conn->prepare("UPDATE pharmacy_queue SET status='cancelled',completed_at=NOW() WHERE prescription_id=? AND status='pending'");if($q){$q->bind_param('i',$id);$q->execute();$q->close();}}
 if(function_exists('audit'))audit('clinical_item_removed',"type={$type},item_id={$id},patient_id={$patientId},invoice_id={$invoiceId}");
 $conn->commit();header("Location: patient_dashboard.php?id={$patientId}&tab=billing&success=Item+Removed");exit;
}catch(Throwable $e){$conn->rollback();http_response_code(409);exit(htmlspecialchars($e->getMessage()));}
