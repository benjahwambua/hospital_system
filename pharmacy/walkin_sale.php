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
                $patientNo='TEMP-WLK-'.date('YmdHis').'-'.strtoupper(bin2hex(random_bytes(2)));
                $gender=''; $phone='';
                $p=$conn->prepare("INSERT INTO patients (patient_number,full_name,gender,phone,is_walkin,created_at) VALUES (?,?,?,?,1,NOW())");
                if(!$p) throw new Exception('Unable to create walk-in customer: '.$conn->error);
                $p->bind_param('ssss',$patientNo,$customer,$gender,$phone);
                if(!$p->execute()) throw new Exception('Unable to create walk-in customer: '.$p->error);
                $patientId=(int)$p->insert_id; $p->close();

                // Walk-in patient numbers use the short WLK format, e.g. WLK0011.
                $patientNo='WLK'.str_pad((string)$patientId,4,'0',STR_PAD_LEFT);
                $numberStmt=$conn->prepare('UPDATE patients SET patient_number=? WHERE id=?');
                if(!$numberStmt) throw new Exception('Unable to assign walk-in patient number: '.$conn->error);
                $numberStmt->bind_param('si',$patientNo,$patientId);
                if(!$numberStmt->execute()) throw new Exception('Unable to assign walk-in patient number: '.$numberStmt->error);
                $numberStmt->close();

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
                $message='Sale created successfully. Invoice #'.$invoiceId.' — KES '.number_format($qty*$unit,2).'.';
            } catch(Throwable $e) { $conn->rollback(); $error=$e->getMessage(); }
        }
    }
}
$stocks=$conn->query("SELECT id,drug_name,unit,quantity,selling_price FROM pharmacy_stock WHERE quantity>0 ORDER BY drug_name ASC");
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<style>
.sale-page{background:#f5f7fb;min-height:calc(100vh - 60px);padding:26px 24px 42px}
.sale-shell{max-width:1400px;margin:0 auto}
.sale-hero{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:20px;padding:28px 30px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:22px;box-shadow:0 12px 30px rgba(7,91,157,.16)}
.sale-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.7px;font-weight:800;color:#bfe8ff}
.sale-hero h1{font-size:28px;font-weight:800;margin:5px 0 7px;color:#fff}.sale-hero p{margin:0;color:rgba(255,255,255,.82);font-size:13px}
.sale-hero-badge{padding:12px 16px;border:1px solid rgba(255,255,255,.2);border-radius:12px;background:rgba(255,255,255,.1);font-size:11px;font-weight:800;white-space:nowrap}
.sale-grid{display:grid;grid-template-columns:2fr 1fr;gap:18px}
.sale-card{background:#fff;border:1px solid #e7ebf2;border-radius:15px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden}
.sale-head{padding:17px 20px;border-bottom:1px solid #edf0f5;display:flex;align-items:center;justify-content:space-between}
.sale-head strong{font-size:15px;color:#25324a}.sale-head span{font-size:11px;color:#98a2b3}
.sale-body{padding:22px}
.sale-label{display:block;color:#344054;font-size:11px;text-transform:uppercase;letter-spacing:.45px;font-weight:800;margin-bottom:7px}
.sale-input,.sale-select{width:100%;border:1px solid #d7dee8;border-radius:9px;padding:11px 12px;font-size:13px;color:#344054;background:#fff;outline:0}
.sale-input:focus,.sale-select:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}
.sale-field{margin-bottom:18px}.sale-help{font-size:10px;color:#98a2b3;margin-top:6px}
.sale-medicine-wrap{position:relative}.sale-medicine-wrap i{position:absolute;left:13px;top:12px;color:#98a2b3}.sale-medicine-wrap select{padding-left:35px}
.sale-summary{background:#f8fafc;border:1px solid #e7ebf2;border-radius:12px;padding:18px}
.sale-summary-row{display:flex;justify-content:space-between;gap:15px;padding:8px 0;font-size:12px;color:#667085}.sale-summary-row strong{color:#25324a}.sale-total{border-top:1px solid #dfe5ed;margin-top:8px;padding-top:14px;font-size:14px}.sale-total strong{font-size:24px;color:#075b9d}
.sale-stock{display:inline-flex;padding:4px 8px;border-radius:7px;background:#eef7f1;color:#198754;font-size:10px;font-weight:800;margin-top:7px}
.sale-submit{width:100%;border:0;border-radius:9px;padding:12px 15px;background:#075b9d;color:#fff;font-weight:800;font-size:12px;margin-top:14px;box-shadow:0 4px 10px rgba(7,91,157,.15)}.sale-submit:hover{background:#064d86}
.sale-info{height:100%;display:flex;flex-direction:column}.sale-info-icon{width:48px;height:48px;border-radius:12px;background:#eef4ff;color:#075b9d;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:13px}.sale-info h3{font-size:17px;color:#25324a;font-weight:800}.sale-info p{font-size:12px;color:#667085;line-height:1.6}.sale-points{margin:8px 0 0;padding:0;list-style:none}.sale-points li{font-size:11px;color:#475467;padding:9px 0;border-bottom:1px solid #edf0f5}.sale-points i{color:#198754;margin-right:7px}.sale-alert{border-radius:10px;font-size:12px;margin-bottom:16px}
@media(max-width:900px){.sale-grid{grid-template-columns:1fr}.sale-hero{align-items:flex-start;flex-direction:column}.sale-hero-badge{width:100%}}
@media(max-width:600px){.sale-page{padding:18px 12px 35px}.sale-hero{padding:22px 20px}.sale-hero h1{font-size:23px}.sale-body{padding:18px}}
</style>
<div class="main-content"><div class="sale-page"><div class="sale-shell">
<div class="sale-hero">
  <div><div class="sale-kicker">Pharmacy · Sales</div><h1><i class="fas fa-cash-register mr-2"></i>Sell Medicine</h1><p>Create a traceable walk-in pharmacy sale and update stock automatically.</p></div>
  <div class="sale-hero-badge"><i class="fas fa-receipt mr-1"></i> Invoice generated automatically</div>
</div>
<?php if($message): ?><div class="alert alert-success sale-alert"><i class="fas fa-circle-check mr-1"></i><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger sale-alert"><i class="fas fa-circle-exclamation mr-1"></i><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="post" id="saleForm">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
<div class="sale-grid">
<div class="sale-card">
  <div class="sale-head"><strong>Sale details</strong><span>Walk-in customer</span></div>
  <div class="sale-body">
    <div class="sale-field">
      <label class="sale-label">Customer name</label>
      <input class="sale-input" name="customer_name" placeholder="Walk-in Customer">
      <div class="sale-help">Optional. Leave blank to use “Walk-in Customer”.</div>
    </div>
    <div class="sale-field">
      <label class="sale-label">Medicine</label>
      <div class="sale-medicine-wrap"><i class="fas fa-pills"></i><select class="sale-select" name="stock_id" id="medicine" required>
        <option value="" data-price="0" data-stock="0">Select medicine</option>
        <?php while($s=$stocks->fetch_assoc()): ?><option value="<?=$s['id']?>" data-price="<?=htmlspecialchars($s['selling_price'])?>" data-stock="<?=$s['quantity']?>"><?=htmlspecialchars($s['drug_name'])?> — <?=$s['unit']?> — KES <?=number_format($s['selling_price'],2)?></option><?php endwhile; ?>
      </select></div>
      <div id="stockInfo" class="sale-stock" style="display:none"></div>
    </div>
    <div class="sale-field">
      <label class="sale-label">Quantity</label>
      <input class="sale-input" type="number" name="quantity" id="quantity" min="1" value="1" required>
      <div class="sale-help">Quantity cannot exceed available stock.</div>
    </div>
  </div>
</div>
<div class="sale-card">
  <div class="sale-body sale-info">
    <div class="sale-info-icon"><i class="fas fa-cart-shopping"></i></div>
    <h3>Sale summary</h3>
    <p>Review the selected medicine before creating the sale. The system will generate the invoice and record the stock movement.</p>
    <div class="sale-summary">
      <div class="sale-summary-row"><span>Medicine</span><strong id="summaryMedicine">—</strong></div>
      <div class="sale-summary-row"><span>Unit price</span><strong id="summaryPrice">KES 0.00</strong></div>
      <div class="sale-summary-row"><span>Quantity</span><strong id="summaryQty">1</strong></div>
      <div class="sale-summary-row sale-total"><span>Total</span><strong id="summaryTotal">KES 0.00</strong></div>
    </div>
    <button class="sale-submit" type="submit"><i class="fas fa-receipt mr-1"></i> Create Sale &amp; Invoice</button>
    <ul class="sale-points">
      <li><i class="fas fa-check"></i>Stock is deducted after successful sale</li>
      <li><i class="fas fa-check"></i>Invoice is linked to the walk-in customer</li>
      <li><i class="fas fa-check"></i>Stock movement is recorded for audit</li>
    </ul>
  </div>
</div>
</div>
</form>
</div></div></div>
<script>
(function(){
 const med=document.getElementById('medicine'), qty=document.getElementById('quantity');
 const info=document.getElementById('stockInfo'), name=document.getElementById('summaryMedicine'), price=document.getElementById('summaryPrice'), sumQty=document.getElementById('summaryQty'), total=document.getElementById('summaryTotal');
 function update(){
   const o=med.options[med.selectedIndex], p=parseFloat(o?.dataset.price||0), stock=parseInt(o?.dataset.stock||0), q=Math.max(1,parseInt(qty.value||1));
   if(stock>0){info.style.display='inline-flex';info.textContent='Available stock: '+stock;qty.max=stock;}
   else{info.style.display='none';qty.removeAttribute('max');}
   name.textContent=o?.value?o.textContent.split(' — ')[0]:'—';price.textContent='KES '+p.toLocaleString('en-KE',{minimumFractionDigits:2});sumQty.textContent=q;total.textContent='KES '+(p*q).toLocaleString('en-KE',{minimumFractionDigits:2});
 }
 med.addEventListener('change',update);qty.addEventListener('input',update);update();
 document.getElementById('saleForm').addEventListener('submit',function(e){const stock=parseInt(med.options[med.selectedIndex]?.dataset.stock||0),q=parseInt(qty.value||0);if(!med.value||q<1||q>stock){e.preventDefault();alert('Please select a medicine and enter a quantity within available stock.');}});
})();
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>