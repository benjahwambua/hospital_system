<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_module_access($conn, 'procurement', 'view');

$poId=max(0,(int)($_GET['id']??0));
if($poId<=0){header('Location: purchase_orders.php');exit;}

$stmt=$conn->prepare("SELECT po.*,s.name supplier_name,s.phone,s.email,u.username FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id LEFT JOIN users u ON po.user_id=u.id WHERE po.id=? LIMIT 1");
$stmt->bind_param('i',$poId);$stmt->execute();$po=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$po){header('Location: purchase_orders.php?error=not_found');exit;}

$items=[];
$stmt=$conn->prepare("SELECT item_name,inventory_type,inventory_item_id,quantity,COALESCE(received_qty,0) received_qty,unit_price,line_total FROM purchase_order_items WHERE purchase_order_id=? ORDER BY id ASC");
$stmt->bind_param('i',$poId);$stmt->execute();$res=$stmt->get_result();while($r=$res->fetch_assoc())$items[]=$r;$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.proc-page{padding:28px 24px 42px;background:#f5f7fb;min-height:calc(100vh - 60px)}
.proc-shell{max-width:1200px;margin:auto}.proc-hero{background:linear-gradient(135deg,#344e41,#588157);color:#fff;border-radius:18px;padding:26px 30px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:18px;box-shadow:0 12px 30px rgba(52,78,65,.16)}.proc-hero h1{margin:4px 0;font-size:27px}.proc-hero p{margin:0;color:rgba(255,255,255,.8);font-size:13px}.proc-kicker{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:800;color:#d9f0da}.proc-actions{display:flex;gap:8px;flex-wrap:wrap}.po-card{background:#fff;border:1px solid #e5eaf1;border-radius:16px;box-shadow:0 6px 24px rgba(31,45,61,.07);overflow:hidden}.po-content{padding:28px}.po-branding{display:flex;gap:18px;align-items:center;padding-bottom:18px;border-bottom:1px solid #edf0f5}.po-branding img{max-height:60px;max-width:150px}.po-hospital h2{margin:0;color:#25324a}.po-hospital div{font-size:12px;color:#667085}.po-top{display:flex;justify-content:space-between;gap:20px;padding:22px 0}.status-pill{display:inline-flex;padding:5px 9px;border-radius:20px;background:#eef5ff;color:#245ea8;font-size:11px;font-weight:800}.po-table{width:100%;border-collapse:collapse}.po-table th,.po-table td{padding:12px;border-bottom:1px solid #edf0f5}.po-table th{background:#f8fafc;color:#667085;font-size:11px;text-transform:uppercase;text-align:left}.po-table .text-right{text-align:right}.po-total{text-align:right;font-size:20px;font-weight:800;color:#075b9d;padding-top:18px}.po-signatures{display:grid;grid-template-columns:1fr 1fr 1fr;gap:30px;margin-top:60px}.stamp-space,.sig-box{min-height:80px;text-align:center}.sig-line{border-top:1px solid #667085;margin-top:50px;padding-top:8px}@media(max-width:700px){.proc-page{padding:18px 12px}.proc-hero{padding:22px;align-items:flex-start;flex-direction:column}.po-content{padding:18px}.po-top{flex-direction:column}.po-signatures{grid-template-columns:1fr}}
@media print{.no-print{display:none!important}.proc-page{padding:0;background:#fff}.proc-hero{box-shadow:none}.po-card{box-shadow:none;border:0}}
</style>
<div class="proc-page"><div class="proc-shell">
<div class="proc-hero"><div><div class="proc-kicker">Supply Chain · Purchase Order</div><h1>PO-<?=str_pad((string)$po['id'],5,'0',STR_PAD_LEFT)?></h1><p><?=htmlspecialchars($po['supplier_name'])?> · <?=!empty($po['order_date'])?date('d M Y',strtotime($po['order_date'])):'N/A'?></p></div>
<div class="proc-actions no-print"><a href="purchase_orders.php" class="btn btn-light">Back to Orders</a><?php if(can_edit($conn,'procurement') && in_array($po['status'],['Pending','Cancelled'],true)): ?><a href="edit_po.php?id=<?=$poId?>" class="btn btn-warning">Edit</a><?php endif; ?><?php if(can_delete($conn,'procurement') && in_array($po['status'],['Pending','Cancelled'],true)): ?><form method="post" action="delete_po.php" class="d-inline" onsubmit="return confirm('Delete this purchase order? This cannot be undone.');"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token']??'')?>"><input type="hidden" name="id" value="<?=$poId?>"><button class="btn btn-danger">Delete</button></form><?php endif; ?><button onclick="window.print()" class="btn btn-outline-light">Print</button></div></div>
<div class="po-card"><div class="po-content">
<div class="po-branding"><img src="../assets/img/logo.png" alt="Hospital Logo" onerror="this.style.display='none'"><div class="po-hospital"><h2>Emaqure Medical Centre</h2><div>Biashara Street, Opposite Old Naiwe School, Mlolongo</div><div>Contact: +254793069565</div><div>emaquremedicalcentre@gmail.com</div></div></div>
<div class="po-top"><div><h3 style="margin:0;color:#1d4ed8">Official Purchase Order</h3><div style="color:#6b7280">PO-<?=str_pad((string)$po['id'],5,'0',STR_PAD_LEFT)?></div></div><div style="text-align:right"><div><strong>Date:</strong> <?=!empty($po['order_date'])?date('d M Y',strtotime($po['order_date'])):'N/A'?></div><div><strong>Status:</strong> <span class="status-pill"><?=htmlspecialchars($po['status']??'Pending')?></span></div><div><strong>Issued By:</strong> <?=htmlspecialchars($po['username']??'System')?></div></div></div>
<div style="margin-bottom:18px"><small style="text-transform:uppercase;color:#6b7280">Supplier</small><div><strong><?=htmlspecialchars($po['supplier_name'])?></strong></div><div><?=htmlspecialchars($po['phone']??'')?></div><div><?=htmlspecialchars($po['email']??'')?></div></div>
<table class="po-table"><thead><tr><th>Item</th><th>Inventory</th><th class="text-right">Ordered</th><th class="text-right">Received</th><th class="text-right">Unit Price</th><th class="text-right">Line Total</th></tr></thead><tbody>
<?php foreach($items as $item): ?><tr><td><?=htmlspecialchars($item['item_name'])?></td><td><?=htmlspecialchars(strtoupper($item['inventory_type']??'pharmacy'))?></td><td class="text-right"><?=number_format((int)$item['quantity'])?></td><td class="text-right"><?=number_format((int)$item['received_qty'])?></td><td class="text-right">KES <?=number_format((float)$item['unit_price'],2)?></td><td class="text-right">KES <?=number_format((float)$item['line_total'],2)?></td></tr><?php endforeach; ?>
<?php if(!$items): ?><tr><td colspan="6" style="text-align:center">No line items found.</td></tr><?php endif; ?></tbody></table>
<div class="po-total">Grand Total: KES <?=number_format((float)$po['total_amount'],2)?></div>
<div style="margin-top:8px;text-align:right"><small>Accounting is posted when goods are received through GRN.</small></div>
<div class="po-signatures"><div class="stamp-space">Hospital Stamp</div><div class="sig-box"><div class="sig-line"></div><strong>Hospital Authorized Signature</strong></div><div class="sig-box"><div class="sig-line"></div><strong>Supplier Signature</strong></div></div>
</div></div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>