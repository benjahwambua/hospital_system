<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../helpers/billing.php';
require_login();
require_role(['admin','pharmacist']);

$message='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['dispense_id'])){
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) { $message='Invalid security token.'; }
    else {
    $id=(int)$_POST['dispense_id'];
    $q=$conn->prepare("SELECT q.*, p.full_name, s.drug_name, s.quantity AS stock_quantity FROM pharmacy_queue q JOIN patients p ON p.id=q.patient_id JOIN pharmacy_stock s ON s.id=q.medicine_id WHERE q.id=? AND q.status='pending' LIMIT 1");
    $q->bind_param('i',$id); $q->execute(); $row=$q->get_result()->fetch_assoc(); $q->close();
    if(!$row) $message='Queue item is no longer pending.';
    elseif((int)$row['stock_quantity'] < (int)$row['quantity']) $message='Insufficient stock. Dispensing was not completed.';
    else {
        $conn->begin_transaction();
        try{
            // Claim the queue row first so the same prescription cannot be
            // dispensed twice by concurrent submissions.
            $claim=$conn->prepare("UPDATE pharmacy_queue SET status='completed', completed_at=NOW() WHERE id=? AND status='pending'");
            $claim->bind_param('i',$id);
            if(!$claim->execute() || $claim->affected_rows!==1) throw new Exception('Queue item is no longer pending.');
            $claim->close();

            $qty=(int)$row['quantity']; $mid=(int)$row['medicine_id'];
            $u=$conn->prepare("UPDATE pharmacy_stock SET quantity=quantity-?, updated_at=NOW() WHERE id=? AND quantity>=?");
            $u->bind_param('iii',$qty,$mid,$qty);
            if(!$u->execute() || $u->affected_rows!==1) throw new Exception('Unable to deduct stock.');
            $u->close();

            $balanceStmt=$conn->prepare("SELECT quantity FROM pharmacy_stock WHERE id=? LIMIT 1");
            $balanceStmt->bind_param('i',$mid); $balanceStmt->execute();
            $balanceRow=$balanceStmt->get_result()->fetch_assoc(); $balanceStmt->close();
            $balanceAfter=(int)($balanceRow['quantity'] ?? 0);

            // Keep a stock movement trail for every dispensing transaction.
            $move=$conn->prepare("INSERT INTO stock_movements (stock_id,movement_type,quantity_change,balance_after,note,user_id,created_at) VALUES (?,?,?,?,?,?,NOW())");
            if($move){
                $movementType='out';
                $note='Dispensed prescription #'.(int)$row['prescription_id'].' (Queue #'.$id.')';
                $uid=(int)($_SESSION['user_id']??0);
                $move->bind_param('isisii',$mid,$movementType,$qty,$balanceAfter,$note,$uid);
                if(!$move->execute()) throw new Exception('Unable to log stock movement: '.$move->error);
                $move->close();
            }

            $conn->commit(); $message='Medicine dispensed and stock updated successfully.';
        }catch(Throwable $e){$conn->rollback();$message=$e->getMessage();}
    }
    }
}

$hasVisit=$conn->query("SHOW COLUMNS FROM pharmacy_queue LIKE 'visit_id'");
if($hasVisit && $hasVisit->num_rows){
    $sql="SELECT q.id,q.quantity,q.status,q.created_at,q.completed_at,q.visit_id,p.full_name,p.patient_number,s.drug_name,v.visit_number FROM pharmacy_queue q JOIN patients p ON p.id=q.patient_id JOIN pharmacy_stock s ON s.id=q.medicine_id LEFT JOIN visits v ON v.id=q.visit_id WHERE q.status='pending' ORDER BY q.created_at ASC";
}else{
    $sql="SELECT q.id,q.quantity,q.status,q.created_at,q.completed_at,NULL AS visit_id,p.full_name,p.patient_number,s.drug_name,NULL AS visit_number FROM pharmacy_queue q JOIN patients p ON p.id=q.patient_id JOIN pharmacy_stock s ON s.id=q.medicine_id WHERE q.status='pending' ORDER BY q.created_at ASC";
}
$rows=$conn->query($sql);

include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4">
<div class="card shadow-sm"><div class="card-header"><h4 class="mb-0">Pharmacy Dispensing Queue</h4></div><div class="card-body">
<?php if($message): ?><div class="alert alert-info"><?=htmlspecialchars($message)?></div><?php endif; ?>
<p class="text-muted">Prescriptions arrive here from Clinical Care. Stock is deducted only when Pharmacy dispenses. Payment remains with Central Cashier.</p>
<table class="table table-bordered"><thead><tr><th>Patient</th><th>Visit</th><th>Medicine</th><th>Qty</th><th>Requested</th><th>Action</th></tr></thead><tbody>
<?php if($rows && $rows->num_rows): while($r=$rows->fetch_assoc()): ?><tr>
<td><strong><?=htmlspecialchars($r['full_name'])?></strong><br><small><?=htmlspecialchars($r['patient_number'])?></small></td>
<td><?=htmlspecialchars($r['visit_number']??'Legacy')?></td><td><?=htmlspecialchars($r['drug_name'])?></td><td><?=$r['quantity']?></td><td><?=htmlspecialchars($r['created_at'])?></td>
<td><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>"><input type="hidden" name="dispense_id" value="<?=$r['id']?>"><button class="btn btn-success" type="submit">Dispense</button></form></td>
</tr><?php endwhile; else: ?><tr><td colspan="6" class="text-center text-muted">No pending pharmacy orders.</td></tr><?php endif; ?>
</tbody></table>
</div></div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>