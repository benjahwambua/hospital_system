<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_module_access($conn, 'pharmacy', 'create');
require_role(['admin','pharmacist','cashier']);

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token'];
$message=''; $error='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($csrf,(string)($_POST['csrf_token']??''))) {
        $error='Invalid security token.';
    } else {
        $stockId=(int)($_POST['stock_id']??0);
        $qty=(int)($_POST['quantity']??0);
        $customer=trim((string)($_POST['customer_name']??''));
        if($customer==='') $customer='Walk-in Customer';
        if($stockId<=0||$qty<=0) $error='Select a medicine and enter a valid quantity.';

        if($error==='') {
            $conn->begin_transaction();
            try {
                $stmt=$conn->prepare("SELECT id,drug_name,unit,selling_price,quantity FROM pharmacy_stock WHERE id=? FOR UPDATE");
                if(!$stmt) throw new Exception('Unable to load medicine.');
                $stmt->bind_param('i',$stockId); $stmt->execute(); $stock=$stmt->get_result()->fetch_assoc(); $stmt->close();
                if(!$stock) throw new Exception('Medicine not found.');
                if((int)$stock['quantity']<$qty) throw new Exception('Insufficient stock. Available: '.(int)$stock['quantity'].'.');

                // No registration form is required. A minimal internal walk-in patient
                // record is created only because the existing invoice/payment schema
                // requires a patient key.
                $patientNo='WS-'.date('YmdHis').'-'.strtoupper(bin2hex(random_bytes(2)));
                $gender=''; $phone='';
                $p=$conn->prepare("INSERT INTO patients (patient_number,full_name,gender,phone,is_walkin,created_at) VALUES (?,?,?,?,1,NOW())");
                if(!$p) throw new Exception('Unable to create walk-in customer: '.$conn->error);
                $p->bind_param('ssss',$patientNo,$customer,$gender,$phone);
                if(!$p->execute()) throw new Exception('Unable to create walk-in customer: '.$p->error);
                $patientId=(int)$p->insert_id; $p->close();

                $invoiceId=get_or_create_invoice($conn,$patientId);
                $unit=(float)$stock['selling_price'];
                $itemId=add_invoice_item($conn,$invoiceId,'Pharmacy: '.$stock['drug_name'],$qty,$unit,'pharmacy',$stockId);
                post_invoice_journal($conn,$invoiceId,$patientId,$qty*$unit,'Walk-in pharmacy sale',$itemId);

                $newQty=(int)$stock['quantity']-$qty;
                $u=$conn->prepare("UPDATE pharmacy_stock SET quantity=? WHERE id=?");
                $u->bind_param('ii',$newQty,$stockId);
                if(!$u->execute()) throw new Exception('Unable to update stock.');
                $u->close();

                $m=$conn->prepare("INSERT INTO stock_movements (stock_id,movement_type,quantity_change,balance_after,note,user_id,created_at) VALUES (?, 'out', ?, ?, ?, ?, NOW())");
                if($m){
                    $uid=(int)($_SESSION['user_id']??0); $change=-$qty;
                    $note='Walk-in sale Invoice #'.$invoiceId.' - '.$stock['drug_name'];
                    $m->bind_param('iiisi',$stockId,$change,$newQty,$note,$uid);
                    if(!$m->execute()) throw new Exception('Unable to record stock movement: '.$m->error);
                    $m->close();
                }
                $conn->commit();
                $message='Sale created successfully. Invoice #'.$invoiceId.' — KES '.number_format($qty*$unit,2).'. Customer can pay at Central Cashier.';
            } catch(Throwable $e) { $conn->rollback(); $error=$e->getMessage(); }
        }
    }
}
$stocks=$conn->query("SELECT id,drug_name,unit,quantity,selling_price FROM pharmacy_stock WHERE quantity>0 ORDER BY drug_name ASC");
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<div class="container-fluid">
<h2 class="mb-4"><i class="fas fa-cash-register"></i> Walk-in Pharmacy Sale</h2>
<?php if($message): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<div class="card shadow"><div class="card-body">
<form method="post" class="row g-3">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
<div class="col-md-4"><label>Customer name (optional)</label><input class="form-control" name="customer_name" placeholder="Walk-in Customer"></div>
<div class="col-md-5"><label>Medicine</label><select class="form-control" name="stock_id" required><option value="">Select medicine</option>
<?php while($s=$stocks->fetch_assoc()): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['drug_name'])?> — <?=$s['unit']?> — Stock: <?=$s['quantity']?> — KES <?=number_format($s['selling_price'],2)?></option><?php endwhile; ?>
</select></div>
<div class="col-md-2"><label>Quantity</label><input class="form-control" type="number" name="quantity" min="1" required></div>
<div class="col-md-1 d-flex align-items-end"><button class="btn btn-success w-100" type="submit"><i class="fas fa-cart-plus"></i></button></div>
</form>
<hr><p class="mb-0 text-muted">No patient registration is required. The system keeps a minimal internal walk-in record so the sale, invoice, stock movement and payment remain traceable.</p>
</div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>