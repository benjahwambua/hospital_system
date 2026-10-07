<?php
require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../includes/session.php';
require_once __DIR__.'/../../includes/auth.php';
require_login();
require_module_access($conn, 'laboratory', 'edit');
require_role(['admin','lab']);

if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token'];
$id=(int)($_GET['id']??0);
if($id<=0){http_response_code(400);exit('Invalid inventory item.');}

$itemStmt=$conn->prepare("SELECT id,item_name,quantity FROM lab_inventory WHERE id=? LIMIT 1");
$itemStmt->bind_param('i',$id);$itemStmt->execute();
$item=$itemStmt->get_result()->fetch_assoc();$itemStmt->close();
if(!$item){http_response_code(404);exit('Item not found.');}

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($csrf,(string)($_POST['csrf_token']??''))) {
        $error='Invalid security token.';
    } else {
        $type=(string)($_POST['movement_type']??'');
        $qty=(float)($_POST['quantity']??0);
        $note=trim((string)($_POST['note']??''));
        if(!in_array($type,['in','out','adjustment'],true)||$qty<=0) {
            $error='Invalid movement.';
        } elseif(strlen($note)>500) {
            $error='The movement note is too long.';
        } else {
            $conn->begin_transaction();
            try {
                $s=$conn->prepare("SELECT quantity FROM lab_inventory WHERE id=? FOR UPDATE");
                $s->bind_param('i',$id);$s->execute();
                $locked=$s->get_result()->fetch_assoc();$s->close();
                if(!$locked) throw new Exception('Inventory item no longer exists.');

                $current=(float)$locked['quantity'];
                $new=$type==='in'?$current+$qty:($type==='out'?$current-$qty:$qty);
                if($new<0) throw new Exception('Insufficient stock.');

                $u=$conn->prepare("UPDATE lab_inventory SET quantity=? WHERE id=?");
                $u->bind_param('di',$new,$id);
                if(!$u->execute()) throw new Exception('Unable to update laboratory stock.');
                $u->close();

                $uid=(int)($_SESSION['user_id']??0);
                $m=$conn->prepare("INSERT INTO lab_inventory_movements(inventory_id,movement_type,quantity,balance_after,note,user_id) VALUES(?,?,?,?,?,?)");
                if(!$m) throw new Exception('Unable to prepare movement log.');
                $m->bind_param('isddsi',$id,$type,$qty,$new,$note,$uid);
                if(!$m->execute()) throw new Exception('Unable to log movement.');
                $m->close();

                if(function_exists('audit')) audit('lab_inventory_adjusted','inventory_id='.$id.',type='.$type.',quantity='.$qty.',balance='.$new);
                $conn->commit();
                header('Location: stock_movements.php?id='.$id);exit;
            } catch(Throwable $e) {
                $conn->rollback();
                error_log('HMS lab inventory movement error: '.$e->getMessage());
                $error='Unable to save the stock movement right now.';
            }
        }
    }
}

$rowsStmt=$conn->prepare("SELECT * FROM lab_inventory_movements WHERE inventory_id=? ORDER BY id DESC");
$rowsStmt->bind_param('i',$id);$rowsStmt->execute();$rows=$rowsStmt->get_result();$rowsStmt->close();

include __DIR__.'/../../includes/header.php';include __DIR__.'/../../includes/sidebar.php';?>
<div class="container-fluid">
<h2><?=htmlspecialchars($item['item_name'])?> — Stock Movements</h2>
<?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="card shadow mb-4"><div class="card-body"><form method="post" class="row">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
<div class="col-md-3"><select name="movement_type" class="form-control"><option value="in">Stock In</option><option value="out">Stock Out</option><option value="adjustment">Set Balance</option></select></div>
<div class="col-md-3"><input name="quantity" type="number" step="0.01" min="0.01" class="form-control" placeholder="Quantity" required></div>
<div class="col-md-4"><input name="note" maxlength="500" class="form-control" placeholder="Reason / reference"></div>
<div class="col-md-2"><button class="btn btn-success">Save</button></div>
</form></div></div>
<div class="card shadow"><div class="card-body table-responsive"><table class="table"><thead><tr><th>Date</th><th>Type</th><th>Qty</th><th>Balance</th><th>Note</th></tr></thead><tbody>
<?php while($r=$rows->fetch_assoc()):?><tr><td><?=htmlspecialchars($r['created_at'])?></td><td><?=htmlspecialchars(strtoupper($r['movement_type']))?></td><td><?=number_format((float)$r['quantity'],2)?></td><td><?=number_format((float)$r['balance_after'],2)?></td><td><?=htmlspecialchars($r['note']??'')?></td></tr><?php endwhile;?>
</tbody></table></div></div></div>
<?php include __DIR__.'/../../includes/footer.php'; ?>