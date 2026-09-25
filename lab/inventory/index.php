<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_role(['admin','lab']);

$items=$conn->query("SELECT * FROM lab_inventory WHERE status='active' ORDER BY item_name ASC");
include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php';
?>
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4"><h2><i class="fas fa-boxes"></i> Laboratory Inventory</h2><a class="btn btn-primary" href="add_item.php"><i class="fas fa-plus"></i> Add Stock Item</a></div>
<div class="alert alert-info">Laboratory reagents, test kits, tubes, gloves and other consumables are managed here separately from pharmacy medicines.</div>
<div class="card shadow"><div class="card-body table-responsive">
<table class="table table-bordered table-hover"><thead><tr><th>Item</th><th>Category</th><th>Unit</th><th>Qty</th><th>Reorder</th><th>Buying Price</th><th>Expiry</th><th>Status</th></tr></thead><tbody>
<?php while($r=$items->fetch_assoc()): $low=(float)$r['quantity']<=(float)$r['reorder_level']; ?>
<tr class="<?=$low?'table-warning':''?>"><td><strong><?=htmlspecialchars($r['item_name'])?></strong></td><td><?=htmlspecialchars($r['category'])?></td><td><?=htmlspecialchars($r['unit'])?></td><td><?=number_format($r['quantity'],2)?> <?=$low?'<span class="badge badge-warning">Reorder</span>':''?></td><td><?=number_format($r['reorder_level'],2)?></td><td>KES <?=number_format($r['buying_price'],2)?></td><td><?=htmlspecialchars($r['expiry_date']?:'—')?></td><td><a href="stock_movements.php?id=<?=$r['id']?>" class="btn btn-sm btn-info">Movements</a></td></tr>
<?php endwhile; ?></tbody></table></div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>