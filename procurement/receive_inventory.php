<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin']);

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token'];
$message=''; $type='success'; $printId=0;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($csrf,(string)($_POST['csrf_token']??''))) {
        $message='Invalid security token.'; $type='danger';
    } elseif (isset($_POST['receive_stock'])) {
        $poId=(int)($_POST['po_id']??0);
        $poItemId=(int)($_POST['po_item_id']??0);
        $qty=(int)($_POST['actual_qty']??0);
        $supplierInvoice=trim((string)($_POST['supplier_invoice_no']??''));
        $paymentMethod='Credit';
        $unitCost=(float)($_POST['unit_cost']??0);
        $batchNo=trim((string)($_POST['batch_no']??''));
        $expiryDate=trim((string)($_POST['expiry_date']??''));
        $storeName=trim((string)($_POST['store_name']??'Main Store'));
        $remarks=trim((string)($_POST['remarks']??''));
        $dueDate=trim((string)($_POST['due_date']??''));

        if($poId<=0||$poItemId<=0||$qty<=0||$supplierInvoice==='') {
            $message='PO item, quantity and supplier invoice number are required.'; $type='danger';
        } else {
            $conn->begin_transaction();
            try {
                $po=$conn->prepare("SELECT id,supplier_id,status FROM purchase_orders WHERE id=? FOR UPDATE");
                $po->bind_param('i',$poId); $po->execute(); $poRow=$po->get_result()->fetch_assoc(); $po->close();
                if(!$poRow) throw new Exception('Purchase order not found.');
                if(in_array($poRow['status'],['Pending','Cancelled'],true)) throw new Exception('This PO must be approved before goods can be received.');

                $line=$conn->prepare("SELECT id,item_name,inventory_type,inventory_item_id,quantity,COALESCE(received_qty,0) received_qty,unit_price FROM purchase_order_items WHERE id=? AND purchase_order_id=? FOR UPDATE");
                $line->bind_param('ii',$poItemId,$poId); $line->execute(); $item=$line->get_result()->fetch_assoc(); $line->close();
                if(!$item) throw new Exception('Purchase order item not found.');
                $remaining=(int)$item['quantity']-(int)$item['received_qty'];
                if($qty>$remaining) throw new Exception("Received quantity exceeds remaining PO quantity of $remaining.");
                if($unitCost<=0) $unitCost=(float)$item['unit_price'];

                $inventoryType=strtolower(trim((string)($item['inventory_type']??'pharmacy')));
                if(!in_array($inventoryType,['pharmacy','lab'],true)) $inventoryType='pharmacy';
                if($inventoryType==='pharmacy' && ($batchNo==='' || $expiryDate==='')) {
                    throw new Exception('Batch number and expiry date are required for pharmacy/medicine receipts.');
                }
                if($expiryDate!=='' && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$expiryDate)) {
                    throw new Exception('Invalid expiry date.');
                }
                if($dueDate!=='' && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$dueDate)) {
                    throw new Exception('Invalid supplier due date.');
                }
                $inventoryId=(int)($item['inventory_item_id']??0);
                $newBalance=0;

                if($inventoryType==='pharmacy') {
                    // Stock is batch-specific. Never merge two different medicine batches.
                    $stock=null; $name=$item['item_name'];
                    if($inventoryId>0) {
                        $s=$conn->prepare("SELECT id,quantity FROM pharmacy_stock WHERE id=? AND drug_name=? AND batch_no=? AND expiry_date=? FOR UPDATE");
                        $s->bind_param('isss',$inventoryId,$name,$batchNo,$expiryDate); $s->execute(); $stock=$s->get_result()->fetch_assoc(); $s->close();
                    }
                    if(!$stock) {
                        $s=$conn->prepare("SELECT id,quantity FROM pharmacy_stock WHERE drug_name=? AND batch_no=? AND expiry_date=? LIMIT 1 FOR UPDATE");
                        $s->bind_param('sss',$name,$batchNo,$expiryDate); $s->execute(); $stock=$s->get_result()->fetch_assoc(); $s->close();
                    }
                    if($stock) {
                        $newBalance=(int)$stock['quantity']+$qty;
                        $u=$conn->prepare("UPDATE pharmacy_stock SET quantity=?, buying_price=? WHERE id=?");
                        $u->bind_param('idi',$newBalance,$unitCost,$stock['id']); if(!$u->execute()) throw new Exception('Unable to update pharmacy stock.'); $u->close();
                        $inventoryId=(int)$stock['id'];
                    } else {
                        $sell=$unitCost;
                        $invoiceNo=$supplierInvoice;
                        $supplierName='';
                        $sup=$conn->prepare("SELECT name FROM suppliers WHERE id=? LIMIT 1"); $sup->bind_param('i',$poRow['supplier_id']); $sup->execute(); $sr=$sup->get_result()->fetch_assoc(); $sup->close();
                        if($sr) $supplierName=(string)$sr['name'];
                        $u=$conn->prepare("INSERT INTO pharmacy_stock (drug_name,unit,quantity,buying_price,selling_price,invoice_no,supplier,batch_no,expiry_date) VALUES (?, 'Piece', ?, ?, ?, ?, ?, ?, ?)");
                        $u->bind_param('siddssss',$name,$qty,$unitCost,$sell,$invoiceNo,$supplierName,$batchNo,$expiryDate);
                        if(!$u->execute()) throw new Exception('Unable to create pharmacy stock batch: '.$u->error);
                        $inventoryId=(int)$u->insert_id; $u->close(); $newBalance=$qty;
                    }
                    $m=$conn->prepare("INSERT INTO stock_movements (stock_id,movement_type,quantity_change,balance_after,note,user_id,created_at) VALUES (?,'in',?,?,?, ?,NOW())");
                    if($m){$note="GRN receipt for PO #$poId / Supplier Invoice $supplierInvoice";$uid=(int)$_SESSION['user_id'];$change=$qty;$m->bind_param('iiisi',$inventoryId,$change,$newBalance,$note,$uid);$m->execute();$m->close();}
                } else {
                    $stock=null;
                    if($inventoryId>0){$s=$conn->prepare("SELECT id,quantity FROM lab_inventory WHERE id=? FOR UPDATE");$s->bind_param('i',$inventoryId);$s->execute();$stock=$s->get_result()->fetch_assoc();$s->close();}
                    if(!$stock){$s=$conn->prepare("SELECT id,quantity FROM lab_inventory WHERE item_name=? LIMIT 1 FOR UPDATE");$name=$item['item_name'];$s->bind_param('s',$name);$s->execute();$stock=$s->get_result()->fetch_assoc();$s->close();}
                    if($stock){$newBalance=(float)$stock['quantity']+$qty;$u=$conn->prepare("UPDATE lab_inventory SET quantity=?, buying_price=?, batch_no=?, expiry_date=? WHERE id=?");$u->bind_param('ddssi',$newBalance,$unitCost,$batchNo,$expiryDate,$stock['id']);if(!$u->execute())throw new Exception('Unable to update laboratory inventory.');$u->close();$inventoryId=(int)$stock['id'];}
                    else{$name=$item['item_name'];$cat='Laboratory Consumable';$unit='Piece';$reorder=0;$status='active';$u=$conn->prepare("INSERT INTO lab_inventory (item_name,category,unit,quantity,reorder_level,buying_price,status,batch_no,expiry_date) VALUES (?,?,?,?,?,?,?,?,?)");$u->bind_param('sssddsdss',$name,$cat,$unit,$qty,$reorder,$unitCost,$status,$batchNo,$expiryDate);if(!$u->execute())throw new Exception('Unable to create laboratory inventory item: '.$u->error);$inventoryId=(int)$u->insert_id;$u->close();$newBalance=$qty;}
                    $m=$conn->prepare("INSERT INTO lab_inventory_movements (inventory_id,movement_type,quantity,balance_after,reference_no,note,user_id) VALUES (?,'in',?,?,?, ?,?)");
                    if($m){$ref="PO-$poId-GRN";$note="Supplier Invoice $supplierInvoice";$uid=(int)$_SESSION['user_id'];$m->bind_param('idsssi',$inventoryId,$qty,$newBalance,$ref,$note,$uid);$m->execute();$m->close();}
                }

                $upd=$conn->prepare("UPDATE purchase_order_items SET received_qty=COALESCE(received_qty,0)+? , inventory_item_id=? WHERE id=?");
                $upd->bind_param('iii',$qty,$inventoryId,$poItemId); if(!$upd->execute()) throw new Exception('Unable to update PO receiving balance.'); $upd->close();

                $receiptTotal=round($qty*$unitCost,2);
                $uid=(int)$_SESSION['user_id'];
                $receipt=$conn->prepare("INSERT INTO inventory_receipts (po_id,po_item_id,inventory_type,supplier_invoice_no,batch_no,expiry_date,store_name,remarks,qty_received,unit_cost,total_cost,payment_method,received_by,received_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
                $receipt->bind_param('iissssssdddsi',$poId,$poItemId,$inventoryType,$supplierInvoice,$batchNo,$expiryDate,$storeName,$remarks,$qty,$unitCost,$receiptTotal,$paymentMethod,$uid);
                if(!$receipt->execute()) throw new Exception('Unable to create GRN: '.$receipt->error);
                $printId=(int)$receipt->insert_id; $receipt->close();

                $grnNo='GRN-'.date('Ymd').'-'.str_pad((string)$printId,5,'0',STR_PAD_LEFT);
                $grnUpdate=$conn->prepare("UPDATE inventory_receipts SET grn_no=? WHERE id=?");
                $grnUpdate->bind_param('si',$grnNo,$printId); $grnUpdate->execute(); $grnUpdate->close();

                $pay=$conn->prepare("INSERT INTO supplier_payables (supplier_id,po_id,receipt_id,supplier_invoice_no,amount,paid_amount,balance,status,due_date,created_by) VALUES (?,?,?,?,?,0,?,'Unpaid',?,?)");
                $pay->bind_param('iiisddsi',$poRow['supplier_id'],$poId,$printId,$supplierInvoice,$receiptTotal,$receiptTotal,$dueDate,$uid);
                if(!$pay->execute()) throw new Exception('Unable to create supplier payable: '.$pay->error); $pay->close();

                $hasRef=false;$cr=$conn->query("SHOW COLUMNS FROM accounting_entries LIKE 'reference_id'");$hasRef=($cr&&$cr->num_rows>0);
                $inventoryAccount=$inventoryType==='lab'?'Laboratory Inventory':'Pharmacy Inventory';
                $note="GRN $grnNo - PO #$poId - $supplierInvoice";
                if($hasRef){
                    $refInventory="GRN-$printId-INV"; $refPayable="GRN-$printId-AP";
                    $check=$conn->prepare("SELECT COUNT(*) c FROM accounting_entries WHERE reference_id IN (?,?)");
                    $check->bind_param('ss',$refInventory,$refPayable); $check->execute(); $alreadyPosted=(int)$check->get_result()->fetch_assoc()['c']>0; $check->close();
                    if(!$alreadyPosted){
                        $a=$conn->prepare("INSERT INTO accounting_entries (account,debit,credit,note,reference_id,created_at) VALUES (?, ?, 0, ?, ?, NOW())");$a->bind_param('sdss',$inventoryAccount,$receiptTotal,$note,$refInventory);if(!$a->execute())throw new Exception('Unable to post inventory accounting entry: '.$a->error);$a->close();
                        $a=$conn->prepare("INSERT INTO accounting_entries (account,debit,credit,note,reference_id,created_at) VALUES ('Accounts Payable',0,?,?,NOW())");$a->bind_param('ds',$receiptTotal,$refPayable);if(!$a->execute())throw new Exception('Unable to post payable accounting entry: '.$a->error);$a->close();
                    }
                }else{
                    $a=$conn->prepare("INSERT INTO accounting_entries (account,debit,credit,note,created_at) VALUES (?, ?, 0, ?, NOW())");$a->bind_param('sds',$inventoryAccount,$receiptTotal,$note);$a->execute();$a->close();
                    $a=$conn->prepare("INSERT INTO accounting_entries (account,debit,credit,note,created_at) VALUES ('Accounts Payable',0,?, ?,NOW())");$a->bind_param('ds',$receiptTotal,$note);$a->execute();$a->close();
                }

                $remainingStmt=$conn->prepare("SELECT COUNT(*) c FROM purchase_order_items WHERE purchase_order_id=? AND quantity>COALESCE(received_qty,0)");
                $remainingStmt->bind_param('i',$poId);$remainingStmt->execute();$hasRemaining=(int)$remainingStmt->get_result()->fetch_assoc()['c']>0;$remainingStmt->close();
                $newStatus=$hasRemaining?'Partial':'Received';
                $pou=$conn->prepare("UPDATE purchase_orders SET status=? WHERE id=?");$pou->bind_param('si',$newStatus,$poId);$pou->execute();$pou->close();

                $conn->commit(); $message="GRN #$printId created. $qty unit(s) added to ".strtoupper($inventoryType)." inventory and KES ".number_format($receiptTotal,2)." added to Supplier Payables.";
            } catch(Throwable $e){$conn->rollback();$message=$e->getMessage();$type='danger';}
        }
    }
}

$pending=$conn->query("SELECT po.id po_id,s.name supplier_name,poi.id po_item_id,poi.item_name,poi.inventory_type,poi.quantity,COALESCE(poi.received_qty,0) received_qty,(poi.quantity-COALESCE(poi.received_qty,0)) balance_remaining,poi.unit_price FROM purchase_order_items poi JOIN purchase_orders po ON po.id=poi.purchase_order_id JOIN suppliers s ON s.id=po.supplier_id WHERE po.status IN ('Approved','Partial') AND poi.quantity>COALESCE(poi.received_qty,0) ORDER BY po.id DESC");

include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php';
?>
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h3">GRN / Receive Inventory</h2><a href="purchase_orders.php" class="btn btn-secondary btn-sm">PO List</a></div>
<?php if($message): ?><div class="alert alert-<?=htmlspecialchars($type)?>"><?=htmlspecialchars($message)?><?php if($printId): ?> <a class="btn btn-sm btn-light ml-2" target="_blank" href="?print_receipt=<?=$printId?>">Print GRN</a><?php endif;?></div><?php endif;?>
<div class="card shadow"><div class="card-body table-responsive"><table class="table table-bordered table-hover"><thead class="thead-light"><tr><th>PO</th><th>Supplier</th><th>Inventory</th><th>Item</th><th>Ordered</th><th>Received</th><th>Balance</th><th>Supplier Invoice</th><th>Batch</th><th>Expiry</th><th>Due Date</th><th>Qty</th><th></th></tr></thead><tbody>
<?php if($pending&&$pending->num_rows): while($row=$pending->fetch_assoc()): ?>
<tr><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="po_id" value="<?=$row['po_id']?>"><input type="hidden" name="po_item_id" value="<?=$row['po_item_id']?>"><input type="hidden" name="unit_cost" value="<?=htmlspecialchars($row['unit_price'])?>">
<td>#<?=$row['po_id']?></td><td><?=htmlspecialchars($row['supplier_name'])?></td><td><span class="badge badge-<?=$row['inventory_type']==='lab'?'info':'primary'?>"><?=strtoupper($row['inventory_type'])?></span></td><td><?=htmlspecialchars($row['item_name'])?></td><td><?=$row['quantity']?></td><td><?=$row['received_qty']?></td><td class="font-weight-bold text-danger"><?=$row['balance_remaining']?></td>
<td><input name="supplier_invoice_no" class="form-control form-control-sm" required></td><td><input name="batch_no" class="form-control form-control-sm" placeholder="Batch"></td><td><input name="expiry_date" type="date" class="form-control form-control-sm"></td><td><input name="due_date" type="date" class="form-control form-control-sm"></td><td><input type="number" name="actual_qty" class="form-control form-control-sm" min="1" max="<?=$row['balance_remaining']?>" value="<?=$row['balance_remaining']?>" required></td><td><button name="receive_stock" class="btn btn-success btn-sm">Create GRN</button></td></form></tr>
<?php endwhile; else: ?><tr><td colspan="14" class="text-center text-muted">No approved PO items awaiting receipt.</td></tr><?php endif;?>
</tbody></table></div></div></div>
<?php
if(isset($_GET['print_receipt'])&&(int)$_GET['print_receipt']>0){$rid=(int)$_GET['print_receipt'];$s=$conn->prepare("SELECT ir.*,poi.item_name,s.name supplier_name FROM inventory_receipts ir JOIN purchase_order_items poi ON poi.id=ir.po_item_id JOIN purchase_orders po ON po.id=ir.po_id JOIN suppliers s ON s.id=po.supplier_id WHERE ir.id=?");$s->bind_param('i',$rid);$s->execute();$grn=$s->get_result()->fetch_assoc();$s->close();if($grn):?>
<div class="card shadow mt-4"><div class="card-body"><h4>Goods Received Note #<?=$grn['id']?></h4><p><strong>PO:</strong> #<?=$grn['po_id']?> <strong>Supplier:</strong> <?=htmlspecialchars($grn['supplier_name'])?></p><p><strong>Supplier Invoice:</strong> <?=htmlspecialchars($grn['supplier_invoice_no'])?> <strong>GRN:</strong> <?=htmlspecialchars($grn['grn_no']??('GRN-'.$grn['id']))?></p><p><strong>Batch:</strong> <?=htmlspecialchars($grn['batch_no']??'N/A')?> <strong>Expiry:</strong> <?=htmlspecialchars($grn['expiry_date']??'N/A')?> <strong>Store:</strong> <?=htmlspecialchars($grn['store_name']??'N/A')?></p><p><strong>Inventory:</strong> <?=htmlspecialchars(strtoupper($grn['inventory_type']))?> <strong>Item:</strong> <?=htmlspecialchars($grn['item_name'])?> <strong>Qty:</strong> <?=$grn['qty_received']?></p><p><strong>Total Cost:</strong> KES <?=number_format($grn['total_cost'],2)?></p><button onclick="window.print()" class="btn btn-primary no-print">Print GRN</button></div></div>
<?php endif;} ?>
<?php include __DIR__.'/../includes/footer.php'; ?>