<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_module_access($conn,'procurement','edit');
if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token'];$poId=max(0,(int)($_GET['id']??$_POST['po_id']??0));$error='';
if($poId<=0){header('Location: purchase_orders.php');exit;}

$stmt=$conn->prepare("SELECT * FROM purchase_orders WHERE id=? LIMIT 1");$stmt->bind_param('i',$poId);$stmt->execute();$po=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$po){header('Location: purchase_orders.php?error=not_found');exit;}
if(!in_array($po['status'],['Pending','Cancelled'],true)){header('Location: view_po.php?id='.$poId.'&error=locked');exit;}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_po'])){
 if(!hash_equals($csrf,(string)($_POST['csrf_token']??''))){$error='Invalid security token. Please refresh and try again.';}else{
  $supplierId=(int)($_POST['supplier_id']??0);$orderDate=trim((string)($_POST['order_date']??''));$itemIds=$_POST['item_id']??[];$names=$_POST['item_name']??[];$qtys=$_POST['qty']??[];$prices=$_POST['price']??[];$types=$_POST['inventory_type']??[];$invIds=$_POST['inventory_item_id']??[];
  if($supplierId<=0||$orderDate==='')$error='Supplier and order date are required.';
  elseif(!$names||count($names)!==count($qtys)||count($names)!==count($prices)||count($names)!==count($types)||count($names)!==count($invIds))$error='Please provide at least one valid item.';
  else{
   $conn->begin_transaction();
   try{
    $check=$conn->prepare("SELECT COUNT(*) c FROM purchase_order_items WHERE purchase_order_id=? AND COALESCE(received_qty,0)>0");$check->bind_param('i',$poId);$check->execute();$received=(int)$check->get_result()->fetch_assoc()['c'];$check->close();if($received>0)throw new Exception('This PO has received quantities and cannot be edited.');
    $s=$conn->prepare("UPDATE purchase_orders SET supplier_id=?,order_date=? WHERE id=?");$s->bind_param('isi',$supplierId,$orderDate,$poId);if(!$s->execute())throw new Exception('Unable to update purchase order.');$s->close();
    $d=$conn->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id=?");$d->bind_param('i',$poId);if(!$d->execute())throw new Exception('Unable to replace PO items.');$d->close();
    $ins=$conn->prepare("INSERT INTO purchase_order_items (purchase_order_id,item_name,inventory_type,inventory_item_id,quantity,unit_price,line_total) VALUES (?,?,?,?,?,?,?)");$grand=0;$count=0;
    foreach($names as $i=>$name){$name=trim((string)$name);$type=strtolower(trim((string)($types[$i]??'pharmacy')));if(!in_array($type,['pharmacy','lab'],true))$type='pharmacy';$invId=(int)($invIds[$i]??0);$qty=max(1,(int)($qtys[$i]??0));$price=max(0,(float)($prices[$i]??0));$line=round($qty*$price,2);if($name===''||$invId<=0)continue;$ins->bind_param('isssiid',$poId,$name,$type,$invId,$qty,$price,$line);if(!$ins->execute())throw new Exception('Unable to save PO item.');$grand+=$line;$count++;}
    $ins->close();if($count<1)throw new Exception('At least one valid item is required.');
    $u=$conn->prepare("UPDATE purchase_orders SET total_amount=? WHERE id=?");$u->bind_param('di',$grand,$poId);$u->execute();$u->close();$conn->commit();header('Location: view_po.php?id='.$poId.'&updated=1');exit;
   }catch(Throwable $e){$conn->rollback();$error=$e->getMessage();}
  }
 }
}
$items=[];$s=$conn->prepare("SELECT item_name,inventory_type,inventory_item_id,quantity,unit_price FROM purchase_order_items WHERE purchase_order_id=? ORDER BY id ASC");$s->bind_param('i',$poId);$s->execute();$r=$s->get_result();while($x=$r->fetch_assoc())$items[]=$x;$s->close();
$stockItems=[];$r=$conn->query("SELECT id,drug_name item_name,buying_price,quantity FROM pharmacy_stock ORDER BY drug_name");if($r)while($x=$r->fetch_assoc()){$x['inventory_type']='pharmacy';$stockItems[]=$x;}$r=$conn->query("SELECT id,item_name,buying_price,quantity FROM lab_inventory WHERE status='active' ORDER BY item_name");if($r)while($x=$r->fetch_assoc()){$x['inventory_type']='lab';$stockItems[]=$x;}
include __DIR__.'/../includes/header.php';include __DIR__.'/../includes/sidebar.php';
?>
<style>
.proc-page{padding:28px 24px 42px;background:#f5f7fb;min-height:calc(100vh - 60px)}.proc-shell{max-width:1200px;margin:auto}.proc-hero{background:#fff;border:1px solid #e5eaf1;border-radius:14px;padding:21px 24px;margin-bottom:18px;box-shadow:0 4px 18px rgba(31,45,61,.06);display:flex;justify-content:space-between;align-items:center;gap:18px}.proc-hero h1{font-size:24px;color:#25324a;margin:0 0 5px}.proc-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.3px;font-weight:700;color:#6c7a91}.proc-card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05);padding:22px}.proc-table{width:100%;border-collapse:collapse}.proc-table th,.proc-table td{padding:10px;border-bottom:1px solid #edf0f5}.proc-table th{background:#f8fafc;color:#667085;font-size:11px;text-transform:uppercase}.form-control{border:1px solid #d7dee8;border-radius:8px;min-height:40px}@media(max-width:700px){.proc-page{padding:18px 12px}.proc-hero{align-items:flex-start;flex-direction:column}}
</style>
<div class="proc-page"><div class="proc-shell"><div class="proc-hero"><div><div class="proc-kicker">Procurement · Edit</div><h1>Edit PO-<?=str_pad((string)$poId,5,'0',STR_PAD_LEFT)?></h1><div class="text-muted">Pending purchase orders can be corrected before approval or receipt.</div></div><a href="view_po.php?id=<?=$poId?>" class="btn btn-light border">Cancel</a></div>
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="po_id" value="<?=$poId?>">
<div class="proc-card mb-3"><div class="row"><div class="col-md-6 form-group"><label>Supplier</label><select name="supplier_id" class="form-control" required><?php $sr=$conn->query("SELECT id,name FROM suppliers ORDER BY name");while($s=$sr->fetch_assoc()): ?><option value="<?=$s['id']?>" <?=$po['supplier_id']==$s['id']?'selected':''?>><?=htmlspecialchars($s['name'])?></option><?php endwhile; ?></select></div><div class="col-md-6 form-group"><label>Order Date</label><input type="date" name="order_date" class="form-control" value="<?=htmlspecialchars($po['order_date'])?>" required></div></div></div>
<div class="proc-card"><div class="table-responsive"><table class="proc-table"><thead><tr><th>Item</th><th>Inventory</th><th>Quantity</th><th>Unit Price</th><th>Line Total</th><th></th></tr></thead><tbody id="rows">
<?php foreach($items as $item): ?><tr><td><input type="hidden" name="item_name[]" value="<?=htmlspecialchars($item['item_name'])?>"><input type="hidden" name="inventory_item_id[]" value="<?=$item['inventory_item_id']?>"><input type="text" class="form-control item-name" list="stock-options" value="<?=htmlspecialchars($item['item_name'])?>" required></td><td><select name="inventory_type[]" class="form-control"><option value="pharmacy" <?=$item['inventory_type']==='pharmacy'?'selected':''?>>Pharmacy</option><option value="lab" <?=$item['inventory_type']==='lab'?'selected':''?>>Laboratory</option></select></td><td><input name="qty[]" type="number" min="1" class="form-control qty" value="<?=$item['quantity']?>" required></td><td><input name="price[]" type="number" step="0.01" min="0" class="form-control price" value="<?=number_format((float)$item['unit_price'],2,'.','')?>" required></td><td><input class="form-control total" readonly value="0.00"></td><td><button type="button" class="btn btn-danger btn-sm remove">×</button></td></tr><?php endforeach; ?>
</tbody></table></div><button type="button" id="add" class="btn btn-info btn-sm mt-3">Add Item</button><div class="text-right mt-3"><strong>Grand Total: KES <span id="grand">0.00</span></strong></div><button name="save_po" class="btn btn-success mt-3 float-right">Save Changes</button></div></form></div></div>
<datalist id="stock-options"><?php foreach($stockItems as $s): ?><option value="<?=htmlspecialchars($s['item_name'])?>" data-id="<?=$s['id']?>" data-type="<?=$s['inventory_type']?>" data-price="<?=number_format((float)$s['buying_price'],2,'.','')?>"><?=htmlspecialchars($s['item_name'])?></option><?php endforeach; ?></datalist>
<script>
document.addEventListener('DOMContentLoaded',()=>{const rows=document.getElementById('rows'),tpl=()=>{const tr=document.createElement('tr');tr.innerHTML='<td><input type="hidden" name="item_name[]"><input type="hidden" name="inventory_item_id[]"><input type="text" class="form-control item-name" list="stock-options" required></td><td><select name="inventory_type[]" class="form-control"><option value="pharmacy">Pharmacy</option><option value="lab">Laboratory</option></select></td><td><input name="qty[]" type="number" min="1" class="form-control qty" value="1" required></td><td><input name="price[]" type="number" step="0.01" min="0" class="form-control price" value="0" required></td><td><input class="form-control total" readonly></td><td><button type="button" class="btn btn-danger btn-sm remove">×</button></td>';rows.appendChild(tr);};
function calc(){let g=0;rows.querySelectorAll('tr').forEach(r=>{let t=(parseFloat(r.querySelector('.qty').value)||0)*(parseFloat(r.querySelector('.price').value)||0);r.querySelector('.total').value=t.toFixed(2);g+=t});document.getElementById('grand').textContent=g.toFixed(2)}
rows.addEventListener('input',e=>{if(e.target.classList.contains('qty')||e.target.classList.contains('price'))calc()});rows.addEventListener('change',e=>{if(e.target.classList.contains('item-name')){let o=[...document.querySelectorAll('#stock-options option')].find(x=>x.value.toLowerCase()===e.target.value.toLowerCase()),r=e.target.closest('tr');if(o){r.querySelector('[name="item_name[]"]').value=o.value;r.querySelector('[name="inventory_item_id[]"]').value=o.dataset.id;r.querySelector('[name="inventory_type[]"]').value=o.dataset.type;r.querySelector('.price').value=o.dataset.price;calc()}}});rows.addEventListener('click',e=>{if(e.target.closest('.remove')){e.target.closest('tr').remove();calc()}});document.getElementById('add').onclick=tpl;calc();});
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>