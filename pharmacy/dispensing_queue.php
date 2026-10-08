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
            }catch(Throwable $e){$conn->rollback();error_log('Pharmacy dispensing error: '.$e->getMessage());$message='Unable to complete dispensing. Please verify the prescription and stock details.';}
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
<style>
.dq-page{background:#f5f7fb;min-height:calc(100vh - 60px);padding:26px 24px 42px}
.dq-shell{max-width:1500px;margin:auto}
.dq-hero{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:20px;padding:28px 30px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 12px 30px rgba(7,91,157,.16)}
.dq-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.7px;font-weight:800;color:#bfe8ff}
.dq-hero h1{font-size:28px;font-weight:800;margin:5px 0 7px;color:#fff}
.dq-hero p{margin:0;color:rgba(255,255,255,.82);font-size:13px}
.dq-count{min-width:145px;padding:15px 20px;border:1px solid rgba(255,255,255,.2);border-radius:14px;background:rgba(255,255,255,.1);text-align:center}
.dq-count strong{display:block;font-size:32px;line-height:1.05}.dq-count span{font-size:10px;text-transform:uppercase;letter-spacing:.7px;color:#d8efff;font-weight:800}
.dq-toolbar{background:#fff;border:1px solid #e7ebf2;border-radius:14px;padding:14px 16px;margin-bottom:16px;display:flex;gap:12px;align-items:center;justify-content:space-between;box-shadow:0 4px 15px rgba(31,45,61,.05)}
.dq-search{position:relative;flex:1;max-width:520px}.dq-search i{position:absolute;left:13px;top:11px;color:#98a2b3}.dq-search input{width:100%;border:1px solid #d7dee8;border-radius:9px;padding:9px 12px 9px 36px;font-size:13px;outline:0}.dq-search input:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}
.dq-note{font-size:11px;color:#667085;font-weight:600}
.dq-table-card{background:#fff;border:1px solid #e7ebf2;border-radius:15px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden}
.dq-table-head{padding:16px 20px;border-bottom:1px solid #edf0f5;display:flex;align-items:center;justify-content:space-between}
.dq-table-head strong{font-size:15px;color:#25324a}.dq-table-head span{font-size:11px;color:#98a2b3}
.dq-table-wrap{overflow-x:auto}.dq-table{width:100%;border-collapse:collapse;margin:0}.dq-table th{background:#f8fafc;color:#667085;font-size:10px;text-transform:uppercase;letter-spacing:.55px;font-weight:800;padding:12px 16px;border-bottom:1px solid #e7ebf2;white-space:nowrap}.dq-table td{padding:15px 16px;border-bottom:1px solid #edf0f5;vertical-align:middle;color:#344054;font-size:12px}.dq-table tbody tr:hover{background:#f8fbff}.dq-table tbody tr:last-child td{border-bottom:0}
.dq-patient{font-weight:800;color:#25324a;font-size:13px}.dq-patient small{display:block;color:#98a2b3;font-size:10px;font-weight:600;margin-top:3px}
.dq-medicine{font-weight:800;color:#25324a}.dq-qty{display:inline-flex;min-width:34px;justify-content:center;padding:5px 9px;border-radius:8px;background:#eef4ff;color:#075b9d;font-weight:800}
.dq-dosage{max-width:250px;line-height:1.45;color:#475467}.dq-muted{color:#98a2b3;font-style:italic}
.dq-time{font-size:11px;color:#667085;white-space:nowrap}.dq-action{border:0;border-radius:8px;padding:8px 13px;background:#198754;color:#fff;font-size:11px;font-weight:800;white-space:nowrap;box-shadow:0 3px 8px rgba(25,135,84,.12)}.dq-action:hover{filter:brightness(.96)}
.dq-empty{padding:55px 20px;text-align:center}.dq-empty .icon{width:54px;height:54px;border-radius:14px;background:#eef7f1;color:#198754;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:21px}.dq-empty strong{display:block;color:#25324a;font-size:15px}.dq-empty p{margin:5px 0 0;color:#98a2b3;font-size:12px}
.dq-message{border-radius:10px;font-size:12px;margin-bottom:16px}
@media(max-width:800px){.dq-page{padding:18px 12px 35px}.dq-hero{padding:22px 20px;align-items:flex-start;flex-direction:column}.dq-hero h1{font-size:23px}.dq-count{width:100%;text-align:left}.dq-toolbar{align-items:stretch;flex-direction:column}.dq-search{max-width:none}.dq-note{display:none}.dq-table th,.dq-table td{padding:12px 11px}}
</style>
<div class="main-content"><div class="dq-page"><div class="dq-shell">
  <div class="dq-hero">
    <div><div class="dq-kicker">Pharmacy · Fulfilment</div><h1><i class="fas fa-clipboard-check mr-2"></i>Dispensing Queue</h1><p>Review prescribed medicines, confirm dosage instructions and complete dispensing.</p></div>
    <div class="dq-count"><strong><?=($rows?$rows->num_rows:0)?></strong><span>Pending prescriptions</span></div>
  </div>
  <?php if($message): ?><div class="alert alert-info dq-message"><?=htmlspecialchars($message)?></div><?php endif; ?>
  <div class="dq-toolbar">
    <div class="dq-search"><i class="fas fa-search"></i><input id="queueSearch" type="search" placeholder="Search patient, number or medicine..."></div>
    <div class="dq-note"><i class="fas fa-circle-info mr-1"></i>Oldest requests appear first</div>
  </div>
  <div class="dq-table-card">
    <div class="dq-table-head"><strong>Pending prescriptions</strong><span><?=($rows?$rows->num_rows:0)?> awaiting dispensing</span></div>
    <div class="dq-table-wrap">
      <table class="dq-table" id="dispensingTable">
        <thead><tr><th>Patient</th><th>Medicine</th><th>Qty</th><th>Dosage / Instructions</th><th>Requested</th><th>Action</th></tr></thead>
        <tbody>
        <?php if($rows&&$rows->num_rows): while($r=$rows->fetch_assoc()): ?>
          <tr>
            <td><div class="dq-patient"><?=htmlspecialchars($r['full_name'])?><small><?=htmlspecialchars($r['patient_number'])?></small></div></td>
            <td><div class="dq-medicine"><?=htmlspecialchars($r['drug_name'])?></div></td>
            <td><span class="dq-qty"><?=$r['quantity']?></span></td>
            <td><div class="dq-dosage"><?=!empty(trim((string)($r['dosage_instructions']??'')))?htmlspecialchars($r['dosage_instructions']):'<span class="dq-muted">No instructions</span>'?></div></td>
            <td><span class="dq-time"><i class="far fa-clock mr-1"></i><?=htmlspecialchars($r['created_at'])?></span></td>
            <td><form method="post" class="m-0"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>"><input type="hidden" name="dispense_id" value="<?=$r['id']?>"><button class="dq-action" type="submit"><i class="fas fa-check mr-1"></i>Dispense</button></form></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="6"><div class="dq-empty"><div class="icon"><i class="fas fa-check"></i></div><strong>Dispensing queue is clear</strong><p>There are no pending prescriptions waiting for pharmacy action.</p></div></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div></div>
<script>
document.getElementById('queueSearch')?.addEventListener('input',function(){
  const term=this.value.toLowerCase().trim();
  document.querySelectorAll('#dispensingTable tbody tr').forEach(row=>{
    if(row.querySelector('.dq-empty')) return;
    row.style.display=row.innerText.toLowerCase().includes(term)?'':'none';
  });
});
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>