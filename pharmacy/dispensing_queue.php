<style>
/* HMS operational page styling */
.main-content{background:#f5f7fb;min-height:calc(100vh - 72px)}
.main-content>.container-fluid{max-width:1500px}
.main-content .card{border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 18px rgba(31,45,61,.05);overflow:hidden}
.main-content .card-header{background:#fff;border-bottom:1px solid #edf0f5;color:#25324a}
.main-content .table thead th{background:#f8fafc;border-top:0;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.35px}
.main-content .table td{border-color:#edf0f5;vertical-align:middle}
.main-content .table tbody tr:hover{background:#f8fbff}
.main-content .form-control{border-color:#d7dee8;border-radius:9px}
.main-content .form-control:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}
.main-content .btn{border-radius:8px;font-weight:700}
</style>
<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../helpers/billing.php';
require_login();
require_module_access($conn,'pharmacy','approve');
require_role(['admin','pharmacist']);

$message='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['dispense_id'])){
    if(!verify_csrf_token($_POST['csrf_token']??null)){ $message='Invalid security token.'; }
    else{
        $id=(int)$_POST['dispense_id'];
        $q=$conn->prepare("SELECT q.*,p.full_name,s.drug_name,s.quantity stock_quantity,pr.invoice_id prescription_invoice_id
            FROM pharmacy_queue q
            JOIN patients p ON p.id=q.patient_id
            JOIN pharmacy_stock s ON s.id=q.medicine_id
            LEFT JOIN prescriptions pr ON pr.id=q.prescription_id
            WHERE q.id=? AND q.status='pending' LIMIT 1");
        $q->bind_param('i',$id);$q->execute();$row=$q->get_result()->fetch_assoc();$q->close();
        if(!$row)$message='Queue item is no longer pending.';
        elseif((int)$row['stock_quantity']<(int)$row['quantity'])$message='Insufficient stock. Dispensing was not completed.';
        else{
            $conn->begin_transaction();
            try{
                $claim=$conn->prepare("UPDATE pharmacy_queue SET status='completed',completed_at=NOW() WHERE id=? AND status='pending'");
                $claim->bind_param('i',$id);
                if(!$claim->execute()||$claim->affected_rows!==1)throw new Exception('Queue item is no longer pending.');
                $claim->close();

                $qty=(int)$row['quantity'];$mid=(int)$row['medicine_id'];
                $u=$conn->prepare("UPDATE pharmacy_stock SET quantity=quantity-?,updated_at=NOW() WHERE id=? AND quantity>=?");
                $u->bind_param('iii',$qty,$mid,$qty);
                if(!$u->execute()||$u->affected_rows!==1)throw new Exception('Unable to deduct stock.');
                $u->close();

                $b=$conn->prepare("SELECT quantity FROM pharmacy_stock WHERE id=? LIMIT 1");
                $b->bind_param('i',$mid);$b->execute();$balanceAfter=(int)($b->get_result()->fetch_assoc()['quantity']??0);$b->close();

                // Clinical prescription creation is the billing event. Dispensing only
                // fulfills the prescription and must never create a second pharmacy charge.
                // Legacy prescriptions with no invoice are safely billed once here.
                $invoiceId=(int)($row['prescription_invoice_id']??0);
                $visitId=(int)($row['visit_id']??0);
                if($invoiceId<=0){
                    $invoiceId=$visitId>0
                        ? get_or_create_visit_invoice($conn,(int)$row['patient_id'],$visitId)
                        : get_or_create_invoice($conn,(int)$row['patient_id']);
                    $unitPrice=(float)($row['unit_price']??0);
                    if($unitPrice<=0){
                        $ps=$conn->prepare("SELECT selling_price FROM pharmacy_stock WHERE id=? LIMIT 1");
                        $ps->bind_param('i',$mid);$ps->execute();$unitPrice=(float)($ps->get_result()->fetch_assoc()['selling_price']??0);$ps->close();
                    }
                    if($unitPrice>0){
                        $itemId=add_invoice_item($conn,$invoiceId,'Pharmacy: '.$row['drug_name'],$qty,$unitPrice,'pharmacy',$mid);
                        post_invoice_journal($conn,$invoiceId,(int)$row['patient_id'],$qty*$unitPrice,'Legacy pharmacy dispensing',$itemId);
                        $link=$conn->prepare("UPDATE prescriptions SET invoice_id=? WHERE id=? AND (invoice_id IS NULL OR invoice_id=0)");
                        if($link){$link->bind_param('ii',$invoiceId,$row['prescription_id']);$link->execute();$link->close();}
                    }
                }

                $move=$conn->prepare("INSERT INTO stock_movements (stock_id,movement_type,quantity_change,balance_after,note,user_id,created_at) VALUES (?,?,?,?,?,?,NOW())");
                if($move){
                    $movementType='out';$note='Dispensed prescription #'.(int)$row['prescription_id'].' (Queue #'.$id.')';$uid=(int)($_SESSION['user_id']??0);
                    $move->bind_param('isiisi',$mid,$movementType,$qty,$balanceAfter,$note,$uid);
                    if(!$move->execute())throw new Exception('Unable to log stock movement: '.$move->error);
                    $move->close();
                }else throw new Exception('Unable to prepare stock movement log.');

                if(function_exists('audit'))audit('pharmacy_dispensed',"queue_id={$id},prescription_id=".(int)$row['prescription_id'].",patient_id=".(int)$row['patient_id'].",quantity={$qty}");
                $conn->commit();$message='Medicine dispensed and stock updated successfully.';
            }catch(Throwable $e){$conn->rollback();$message=$e->getMessage();}
        }
    }
}

$hasVisit=$conn->query("SHOW COLUMNS FROM pharmacy_queue LIKE 'visit_id'");
if($hasVisit&&$hasVisit->num_rows){
 $sql="SELECT q.id,q.prescription_id,q.quantity,q.status,q.created_at,q.completed_at,q.visit_id,p.full_name,p.patient_number,s.drug_name,s.selling_price unit_price,pr.frequency dosage_instructions,v.visit_number
       FROM pharmacy_queue q JOIN patients p ON p.id=q.patient_id JOIN pharmacy_stock s ON s.id=q.medicine_id LEFT JOIN prescriptions pr ON pr.id=q.prescription_id LEFT JOIN visits v ON v.id=q.visit_id
       WHERE q.status='pending' ORDER BY q.created_at ASC";
}else{
 $sql="SELECT q.id,q.prescription_id,q.quantity,q.status,q.created_at,q.completed_at,NULL visit_id,p.full_name,p.patient_number,s.drug_name,s.selling_price unit_price,pr.frequency dosage_instructions,NULL visit_number
       FROM pharmacy_queue q JOIN patients p ON p.id=q.patient_id JOIN pharmacy_stock s ON s.id=q.medicine_id LEFT JOIN prescriptions pr ON pr.id=q.prescription_id
       WHERE q.status='pending' ORDER BY q.created_at ASC";
}
$rows=$conn->query($sql);
include __DIR__.'/../includes/header.php';include __DIR__.'/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4">
<div class="card shadow-sm"><div class="card-header"><h4 class="mb-0">Pharmacy Dispensing Queue</h4></div><div class="card-body">
<?php if($message):?><div class="alert alert-info"><?=htmlspecialchars($message)?></div><?php endif;?>

<table class="table table-bordered"><thead><tr><th>Client / Patient Name</th><th>Visit</th><th>Medicine</th><th>Qty</th><th>Dosage / Instructions</th><th>Requested</th><th>Action</th></tr></thead><tbody>
<?php if($rows&&$rows->num_rows):while($r=$rows->fetch_assoc()):?><tr>
<td><strong><?=htmlspecialchars($r['full_name'])?></strong><br><small><?=htmlspecialchars($r['patient_number'])?></small></td>
<td><?=htmlspecialchars($r['visit_number']??'Legacy')?></td><td><strong><?=htmlspecialchars($r['drug_name'])?></strong></td><td><?=$r['quantity']?></td><td><?=!empty(trim((string)($r['dosage_instructions']??'')))?htmlspecialchars($r['dosage_instructions']):'<span class="text-muted">No instructions</span>'?></td><td><?=htmlspecialchars($r['created_at'])?></td>
<td><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>"><input type="hidden" name="dispense_id" value="<?=$r['id']?>"><button class="btn btn-success" type="submit">Dispense</button></form></td>
</tr><?php endwhile;else:?><tr><td colspan="7" class="text-center text-muted">No pending pharmacy orders.</td></tr><?php endif;?>
</tbody></table></div></div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>