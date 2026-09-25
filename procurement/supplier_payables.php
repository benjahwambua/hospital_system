<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_role(['admin']);

if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token']; $message=''; $error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf_token']??''))){$error='Invalid security token.';}
 elseif(isset($_POST['pay_supplier'])){
  $payableId=(int)($_POST['payable_id']??0); $amount=(float)($_POST['amount']??0);
  $method=trim((string)($_POST['payment_method']??'Cash')); $reference=trim((string)($_POST['reference']??'')); $notes=trim((string)($_POST['notes']??''));
  if($payableId<=0||$amount<=0) $error='Select a payable and enter a valid amount.';
  elseif(!in_array($method,['Cash','M-Pesa','Bank Transfer'],true)) $error='Invalid payment method.';
  else{
   $conn->begin_transaction();
   try{
    $s=$conn->prepare("SELECT id,supplier_id,amount,paid_amount,balance,status FROM supplier_payables WHERE id=? FOR UPDATE");
    $s->bind_param('i',$payableId);$s->execute();$p=$s->get_result()->fetch_assoc();$s->close();
    if(!$p) throw new Exception('Supplier payable not found.');
    $balance=(float)$p['balance']; if($balance<=0) throw new Exception('This payable is already fully paid.');
    if($amount>$balance) $amount=$balance;
    if($reference!==''){
        $dup=$conn->prepare("SELECT id FROM supplier_payments WHERE supplier_id=? AND reference=? LIMIT 1");
        $dup->bind_param('is',$p['supplier_id'],$reference); $dup->execute(); $duplicate=$dup->get_result()->fetch_assoc(); $dup->close();
        if($duplicate) throw new Exception('This supplier payment reference has already been used.');
    }
    $uid=(int)$_SESSION['user_id'];
    $s=$conn->prepare("INSERT INTO supplier_payments (payable_id,supplier_id,amount,payment_method,reference,notes,paid_by) VALUES (?,?,?,?,?,?,?)");
    $s->bind_param('iidsssi',$payableId,$p['supplier_id'],$amount,$method,$reference,$notes,$uid); if(!$s->execute()) throw new Exception('Unable to save supplier payment: '.$s->error); $paymentId=(int)$s->insert_id;$s->close();
    $newPaid=(float)$p['paid_amount']+$amount;$newBal=max((float)$p['amount']-$newPaid,0);$status=$newBal<=0.00001?'Paid':'Partially Paid';
    $u=$conn->prepare("UPDATE supplier_payables SET paid_amount=?,balance=?,status=?,updated_at=NOW() WHERE id=?");$u->bind_param('ddsi',$newPaid,$newBal,$status,$payableId);$u->execute();$u->close();
    $account=stripos($method,'mpesa')!==false?'M-Pesa':(stripos($method,'bank')!==false?'Bank':'Cash');
    $note="Supplier payment #$paymentId for payable #$payableId";
    $refAp="SPAY-$paymentId-AP"; $refCash="SPAY-$paymentId-$account";
    $hasRef=false; $cr=$conn->query("SHOW COLUMNS FROM accounting_entries LIKE 'reference_id'"); $hasRef=($cr&&$cr->num_rows>0);
    if($hasRef){
        $a=$conn->prepare("INSERT INTO accounting_entries (account,debit,credit,note,reference_id,created_at) VALUES ('Accounts Payable',?,0,?,?,NOW())");
        $a->bind_param('dss',$amount,$note,$refAp); if(!$a->execute()) throw new Exception('Unable to post AP payment entry: '.$a->error); $a->close();
        $a=$conn->prepare("INSERT INTO accounting_entries (account,debit,credit,note,reference_id,created_at) VALUES (?,0,?,?,NOW())");
        $a->bind_param('sds',$account,$amount,$refCash); if(!$a->execute()) throw new Exception('Unable to post supplier payment entry: '.$a->error); $a->close();
    } else {
        $a=$conn->prepare("INSERT INTO accounting_entries (account,debit,credit,note,created_at) VALUES ('Accounts Payable',?,0,?,NOW())");$a->bind_param('ds',$amount,$note);$a->execute();$a->close();
        $a=$conn->prepare("INSERT INTO accounting_entries (account,debit,credit,note,created_at) VALUES (?,0,?,?,NOW())");$a->bind_param('sds',$account,$amount,$note);$a->execute();$a->close();
    }
    $conn->commit();$message='Supplier payment recorded. Payable balance: KES '.number_format($newBal,2).'.';
   }catch(Throwable $e){$conn->rollback();$error=$e->getMessage();}
  }
 }
}

$rows=$conn->query("SELECT sp.*,s.name supplier_name,po.id po_id,ir.id grn_id FROM supplier_payables sp JOIN suppliers s ON s.id=sp.supplier_id LEFT JOIN purchase_orders po ON po.id=sp.po_id LEFT JOIN inventory_receipts ir ON ir.id=sp.receipt_id WHERE sp.status<>'Paid' AND sp.status<>'Cancelled' AND sp.balance>0 ORDER BY sp.id DESC");
include __DIR__.'/../includes/header.php';include __DIR__.'/../includes/sidebar.php';
?>
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h3">Supplier Payables & Payments</h2><div><a href="supplier_statement.php" class="btn btn-outline-secondary btn-sm mr-1">Supplier Statement</a><a href="receive_inventory.php" class="btn btn-outline-primary btn-sm">GRN / Receiving</a></div></div>
<?php if($message):?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="card shadow"><div class="card-body table-responsive"><table class="table table-bordered table-hover"><thead class="thead-light"><tr><th>Supplier</th><th>PO</th><th>GRN</th><th>Supplier Invoice</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Receive Payment</th></tr></thead><tbody>
<?php if($rows&&$rows->num_rows):while($p=$rows->fetch_assoc()):?>
<tr><td><?=htmlspecialchars($p['supplier_name'])?></td><td>#<?=htmlspecialchars($p['po_id']??'')?></td><td>#<?=htmlspecialchars($p['grn_id']??'')?></td><td><?=htmlspecialchars($p['supplier_invoice_no'])?></td><td>KES <?=number_format($p['amount'],2)?></td><td>KES <?=number_format($p['paid_amount'],2)?></td><td class="font-weight-bold text-danger">KES <?=number_format($p['balance'],2)?></td><td><?=htmlspecialchars($p['due_date']??'—')?></td><td><form method="post" class="form-row"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="payable_id" value="<?=$p['id']?>"><div class="col-md-3"><input name="amount" type="number" step="0.01" min="0.01" max="<?=number_format($p['balance'],2,'.','')?>" value="<?=number_format($p['balance'],2,'.','')?>" class="form-control form-control-sm" required></div><div class="col-md-3"><select name="payment_method" class="form-control form-control-sm"><option>Cash</option><option>M-Pesa</option><option>Bank Transfer</option></select></div><div class="col-md-3"><input name="reference" class="form-control form-control-sm" placeholder="Reference"></div><div class="col-md-3"><button name="pay_supplier" class="btn btn-success btn-sm btn-block">Pay Supplier</button></div></form></td></tr>
<?php endwhile;else:?><tr><td colspan="9" class="text-center text-muted py-4">No outstanding supplier payables.</td></tr><?php endif;?>
</tbody></table></div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>