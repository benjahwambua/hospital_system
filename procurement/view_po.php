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
.proc-page{padding:28px 24px 48px;background:radial-gradient(circle at 8% 0%,rgba(19,168,184,.07),transparent 28%),#f4f7fb;min-height:calc(100vh - 60px)}.proc-shell{max-width:1200px;margin:auto}.proc-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#0b3d91,#1261c9 55%,#13a8b8);color:#fff;border-radius:22px;padding:28px 30px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:18px;box-shadow:0 16px 38px rgba(16,77,153,.22)}.proc-hero:after{content:"";position:absolute;width:270px;height:270px;border:1px solid rgba(255,255,255,.12);border-radius:50%;right:-80px;top:-120px}.proc-hero>*{position:relative;z-index:1}.proc-hero h1{font-size:1.8rem;color:#fff;margin:3px 0 5px;font-weight:800}.proc-kicker{font-size:.68rem;text-transform:uppercase;letter-spacing:.17em;font-weight:800;opacity:.72}.proc-actions{display:flex;gap:8px;flex-wrap:wrap}.proc-actions .btn{border-radius:9px;font-weight:800}.po-card{background:#fff;border:1px solid #e2e8f0;border-radius:17px;box-shadow:0 8px 25px rgba(20,40,70,.065);overflow:hidden}.po-content{padding:28px}.po-branding{display:flex;gap:18px;align-items:center;padding-bottom:18px;border-bottom:1px solid #edf1f5}.po-hospital h2{margin:0;color:#25324a}.po-top{display:flex;justify-content:space-between;gap:20px;padding:22px 0}.status-pill{display:inline-flex;padding:5px 9px;border-radius:999px;background:#eef4ff;color:#245ea8;font-size:11px;font-weight:800}.po-table{width:100%;border-collapse:separate;border-spacing:0}.po-table th,.po-table td{padding:12px;border-bottom:1px solid #edf1f5}.po-table th{background:#f7f9fc;color:#687386;font-size:11px;text-transform:uppercase}.po-total{color:#1458aa}@media(max-width:700px){.proc-page{padding:18px 12px}.proc-hero{align-items:flex-start;flex-direction:column}.po-content{padding:18px}.po-top{flex-direction:column}.po-signatures{grid-template-columns:1fr}}
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