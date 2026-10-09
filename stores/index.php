<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'central_stores', 'view');

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];
$message = '';
$error = '';
$uid = (int)($_SESSION['user_id'] ?? 0);
$canCreate = can_module_action($conn, 'central_stores', 'create');
$canEdit = can_module_action($conn, 'central_stores', 'edit');
$canApprove = can_module_action($conn, 'central_stores', 'approve');

function stores_balance(mysqli $conn, int $itemId, int $locationId): float {
    $sql = "SELECT COALESCE(SUM(CASE WHEN movement_type IN ('Opening','Receipt','Transfer In','Return','Adjustment In') THEN quantity ELSE -quantity END),0) balance FROM stores_movements WHERE item_id=? AND location_id=?";
    $s = $conn->prepare($sql);
    $s->bind_param('ii', $itemId, $locationId);
    $s->execute();
    $v = (float)($s->get_result()->fetch_assoc()['balance'] ?? 0);
    $s->close();
    return $v;
}
function stores_audit(string $action, string $details): void {
    if (function_exists('audit')) audit($action, $details);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Security token expired. Refresh and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'add_item') {
                require_module_access($conn, 'central_stores', 'create');
                $code = strtoupper(trim((string)($_POST['item_code'] ?? '')));
                $name = trim((string)($_POST['item_name'] ?? ''));
                $category = trim((string)($_POST['category'] ?? ''));
                $unit = trim((string)($_POST['unit'] ?? 'Each'));
                $reorder = filter_var($_POST['reorder_level'] ?? 0, FILTER_VALIDATE_FLOAT);
                if ($code === '' || $name === '' || $unit === '' || $reorder === false || $reorder < 0) throw new RuntimeException('Enter a code, name, unit and valid non-negative reorder level.');
                $s = $conn->prepare("INSERT INTO stores_items (item_code,item_name,category,unit,reorder_level,created_by) VALUES (?,?,?,?,?,?)");
                $s->bind_param('ssssdi', $code, $name, $category, $unit, $reorder, $uid);
                if (!$s->execute()) throw new RuntimeException('Item code may already exist.');
                $id = $s->insert_id; $s->close();
                stores_audit('central_stores_item_created', "item_id=$id;code=$code");
                $message = 'Stock item created.';
            } elseif ($action === 'receive') {
                require_module_access($conn, 'central_stores', 'create');
                $itemId = (int)($_POST['item_id'] ?? 0);
                $locationId = (int)($_POST['location_id'] ?? 0);
                $qty = filter_var($_POST['quantity'] ?? 0, FILTER_VALIDATE_FLOAT);
                $batch = trim((string)($_POST['batch_number'] ?? ''));
                $expiry = trim((string)($_POST['expiry_date'] ?? ''));
                $notes = trim((string)($_POST['notes'] ?? ''));
                if ($itemId <= 0 || $locationId <= 0 || $qty === false || $qty <= 0) throw new RuntimeException('Select an item and location and enter a quantity greater than zero.');
                $expiryValue = $expiry === '' ? null : $expiry;
                $s = $conn->prepare("INSERT INTO stores_movements (item_id,location_id,movement_type,quantity,reference_type,batch_number,expiry_date,notes,created_by) VALUES (?,?,'Receipt',?,'Manual Receipt',?,?,?,?)");
                $s->bind_param('iidsssi', $itemId, $locationId, $qty, $batch, $expiryValue, $notes, $uid);
                if (!$s->execute()) throw new RuntimeException('Unable to record receipt.');
                $movementId = $s->insert_id; $s->close();
                stores_audit('central_stores_receipt', "movement_id=$movementId;item_id=$itemId;qty=$qty;location_id=$locationId");
                $message = 'Receipt recorded in the stock ledger.';
            } elseif ($action === 'create_requisition') {
                require_module_access($conn, 'central_stores', 'create');
                $department = trim((string)($_POST['department'] ?? ''));
                $dest = (int)($_POST['destination_location_id'] ?? 0);
                $itemIds = $_POST['req_item_id'] ?? [];
                $quantities = $_POST['req_quantity'] ?? [];
                $notes = trim((string)($_POST['request_notes'] ?? ''));
                if (!is_array($itemIds) || !is_array($quantities) || count($itemIds) < 1 || count($itemIds) !== count($quantities)) {
                    throw new RuntimeException('Add at least one item with a matching quantity.');
                }
                $lines = [];
                $seenItems = [];
                foreach ($itemIds as $index => $postedItemId) {
                    $lineItemId = filter_var($postedItemId, FILTER_VALIDATE_INT);
                    $lineQty = filter_var($quantities[$index] ?? null, FILTER_VALIDATE_FLOAT);
                    if ($lineItemId === false || $lineItemId <= 0 || $lineQty === false || $lineQty <= 0) {
                        throw new RuntimeException('Every requisition line must have a valid item and quantity greater than zero.');
                    }
                    if (isset($seenItems[$lineItemId])) {
                        throw new RuntimeException('Select each item only once per requisition; combine quantities on the same line.');
                    }
                    $seenItems[$lineItemId] = true;
                    $lines[] = [(int)$lineItemId, (float)$lineQty];
                }
                if ($department === '' || $dest <= 0) throw new RuntimeException('Complete the requesting department and destination.');
                $conn->begin_transaction();
                $number = 'REQ-' . date('Ymd-His') . '-' . random_int(100,999);
                $s = $conn->prepare("INSERT INTO stores_requisitions (requisition_number,requesting_department,destination_location_id,requested_by,status,request_notes,submitted_at) VALUES (?,?,?,?,'Submitted',?,NOW())");
                $s->bind_param('ssiis', $number, $department, $dest, $uid, $notes);
                if (!$s->execute()) throw new RuntimeException('Unable to create requisition.');
                $reqId = $s->insert_id; $s->close();
                $itemCheck = $conn->prepare("SELECT id FROM stores_items WHERE id=? AND active=1");
                $i = $conn->prepare("INSERT INTO stores_requisition_items (requisition_id,item_id,quantity_requested) VALUES (?,?,?)");
                foreach ($lines as [$lineItemId, $lineQty]) {
                    $itemCheck->bind_param('i', $lineItemId);
                    $itemCheck->execute();
                    if (!$itemCheck->get_result()->fetch_assoc()) throw new RuntimeException('A selected stock item is no longer active.');
                    $i->bind_param('iid', $reqId, $lineItemId, $lineQty);
                    if (!$i->execute()) throw new RuntimeException('Unable to add a requested item.');
                }
                $itemCheck->close();
                $i->close();
                $conn->commit();
                stores_audit('central_stores_requisition_submitted', "requisition_id=$reqId;number=$number");
                $message = "Requisition $number submitted for approval.";
            } elseif ($action === 'approve_requisition') {
                require_module_access($conn, 'central_stores', 'approve');
                $reqId = (int)($_POST['requisition_id'] ?? 0);
                $decision = (string)($_POST['decision'] ?? 'Approved');
                $notes = trim((string)($_POST['approval_notes'] ?? ''));
                if ($reqId <= 0 || !in_array($decision, ['Approved','Rejected'], true)) throw new RuntimeException('Invalid requisition decision.');
                $s = $conn->prepare("UPDATE stores_requisitions SET status=?,approved_by=?,approved_at=NOW(),approval_notes=? WHERE id=? AND status='Submitted'");
                $s->bind_param('sisi', $decision, $uid, $notes, $reqId);
                $s->execute(); $changed = $s->affected_rows; $s->close();
                if ($changed !== 1) throw new RuntimeException('Only submitted requisitions can be approved or rejected.');
                stores_audit('central_stores_requisition_decision', "requisition_id=$reqId;status=$decision");
                $message = "Requisition $decision.";
            } elseif ($action === 'issue_requisition') {
                require_module_access($conn, 'central_stores', 'approve');
                $reqId = (int)($_POST['requisition_id'] ?? 0);
                if ($reqId <= 0) throw new RuntimeException('Invalid requisition.');
                $conn->begin_transaction();
                $q = $conn->prepare("SELECT * FROM stores_requisitions WHERE id=? FOR UPDATE");
                $q->bind_param('i', $reqId); $q->execute(); $req = $q->get_result()->fetch_assoc(); $q->close();
                if (!$req || !in_array($req['status'], ['Approved','Partially Issued'], true)) throw new RuntimeException('Only approved requisitions can be issued.');
                $items = $conn->prepare("SELECT * FROM stores_requisition_items WHERE requisition_id=? FOR UPDATE");
                $items->bind_param('i', $reqId); $items->execute(); $rs = $items->get_result(); $lineItems = [];
                while ($row = $rs->fetch_assoc()) $lineItems[] = $row;
                $items->close();
                if (!$lineItems) throw new RuntimeException('Requisition has no line items.');
                $main = $conn->query("SELECT id FROM stores_locations WHERE location_code='MAIN' AND active=1 LIMIT 1")->fetch_assoc();
                if (!$main) throw new RuntimeException('Main store location is missing.');
                $mainId = (int)$main['id'];
                foreach ($lineItems as $line) {
                    $remaining = (float)$line['quantity_requested'] - (float)$line['quantity_issued'];
                    if ($remaining <= 0) continue;
                    $balance = stores_balance($conn, (int)$line['item_id'], $mainId);
                    if ($balance < $remaining) throw new RuntimeException('Insufficient Main Store stock for item ID ' . (int)$line['item_id'] . '. Available: ' . $balance . ', requested: ' . $remaining);
                    $m = $conn->prepare("INSERT INTO stores_movements (item_id,location_id,movement_type,quantity,reference_type,reference_id,notes,created_by) VALUES (?,?,'Issue',?,'Requisition',?,?,?)");
                    $m->bind_param('iidisi', $line['item_id'], $mainId, $remaining, $reqId, $req['requisition_number'], $uid);
                    if (!$m->execute()) throw new RuntimeException('Unable to record stock issue.');
                    $m->close();
                    $d = $conn->prepare("INSERT INTO stores_movements (item_id,location_id,movement_type,quantity,reference_type,reference_id,notes,created_by) VALUES (?,?,'Transfer In',?,'Requisition',?,?,?)");
                    $d->bind_param('iidisi', $line['item_id'], $req['destination_location_id'], $remaining, $reqId, $req['requisition_number'], $uid);
                    if (!$d->execute()) throw new RuntimeException('Unable to record destination stock.');
                    $d->close();
                    $u = $conn->prepare("UPDATE stores_requisition_items SET quantity_issued=quantity_issued+? WHERE id=?");
                    $u->bind_param('di', $remaining, $line['id']); $u->execute(); $u->close();
                }
                $u = $conn->prepare("UPDATE stores_requisitions SET status='Issued',issued_by=?,issued_at=NOW() WHERE id=?");
                $u->bind_param('ii', $uid, $reqId); $u->execute(); $u->close();
                $conn->commit();
                stores_audit('central_stores_requisition_issued', "requisition_id=$reqId");
                $message = 'Requisition issued and stock ledger updated.';
            } elseif ($action === 'transfer_stock' || $action === 'return_stock' || $action === 'adjust_stock') {
                require_module_access($conn, 'central_stores', $action === 'adjust_stock' ? 'approve' : 'create');
                $itemId = (int)($_POST['movement_item_id'] ?? 0);
                $fromId = (int)($_POST['from_location_id'] ?? 0);
                $toId = (int)($_POST['to_location_id'] ?? 0);
                $qty = filter_var($_POST['movement_quantity'] ?? 0, FILTER_VALIDATE_FLOAT);
                $notes = trim((string)($_POST['movement_notes'] ?? ''));
                if ($itemId <= 0 || $fromId <= 0 || $qty === false || $qty <= 0) throw new RuntimeException('Select an item, source location and positive quantity.');
                if ($action === 'transfer_stock' && ($toId <= 0 || $toId === $fromId)) throw new RuntimeException('Choose a different destination location.');
                if ($action === 'adjust_stock' && !in_array((string)($_POST['adjustment_direction'] ?? ''), ['in','out'], true)) throw new RuntimeException('Choose adjustment in or out.');
                $conn->begin_transaction();
                $itemCheck = $conn->prepare("SELECT id FROM stores_items WHERE id=? AND active=1 FOR UPDATE");
                $itemCheck->bind_param('i', $itemId); $itemCheck->execute(); $validItem = $itemCheck->get_result()->fetch_assoc(); $itemCheck->close();
                if (!$validItem) throw new RuntimeException('Stock item is not active.');
                $sourceBalance = stores_balance($conn, $itemId, $fromId);
                if ($action !== 'return_stock' && $action !== 'adjust_stock' && $sourceBalance < $qty) throw new RuntimeException('Insufficient source stock. Available: ' . $sourceBalance);
                if ($action === 'adjust_stock' && ($_POST['adjustment_direction'] ?? '') === 'out' && $sourceBalance < $qty) throw new RuntimeException('Adjustment would create negative stock.');
                $ref = $action === 'transfer_stock' ? 'Transfer' : ($action === 'return_stock' ? 'Department Return' : 'Stock Adjustment');
                $outType = $action === 'transfer_stock' ? 'Transfer Out' : ($action === 'return_stock' ? 'Return' : (($_POST['adjustment_direction'] ?? '') === 'in' ? 'Adjustment In' : 'Adjustment Out'));
                if ($action === 'return_stock') {
                    $outType = 'Return';
                    $insert = $conn->prepare("INSERT INTO stores_movements (item_id,location_id,movement_type,quantity,reference_type,notes,created_by) VALUES (?,?,'Return',?,'Department Return',?,?)");
                    $insert->bind_param('iidsi', $itemId, $fromId, $qty, $notes, $uid);
                } else {
                    $insert = $conn->prepare("INSERT INTO stores_movements (item_id,location_id,movement_type,quantity,reference_type,notes,created_by) VALUES (?,?,?,?,?,?,?)");
                    $insert->bind_param('iisdssi', $itemId, $fromId, $outType, $qty, $ref, $notes, $uid);
                }
                if (!$insert->execute()) throw new RuntimeException('Unable to record stock movement.');
                $sourceMovement = $insert->insert_id; $insert->close();
                if ($action === 'transfer_stock') {
                    $dest = $conn->prepare("INSERT INTO stores_movements (item_id,location_id,movement_type,quantity,reference_type,reference_id,notes,created_by) VALUES (?,?,'Transfer In',?,'Transfer',?,?,?)");
                    $dest->bind_param('iidisi', $itemId, $toId, $qty, $sourceMovement, $notes, $uid);
                    if (!$dest->execute()) throw new RuntimeException('Unable to record destination transfer.');
                    $dest->close();
                }
                $conn->commit();
                stores_audit('central_stores_' . $action, "item_id=$itemId;qty=$qty;from=$fromId;to=$toId;movement_id=$sourceMovement");
                $message = $action === 'transfer_stock' ? 'Stock transfer recorded.' : ($action === 'return_stock' ? 'Department return recorded.' : 'Stock adjustment recorded.');
            } else {
                throw new RuntimeException('Unsupported action.');
            }
        } catch (Throwable $e) {
            if ($conn->errno || $conn->connect_errno === 0) { try { $conn->rollback(); } catch (Throwable $ignored) {} }
            error_log('Central stores workflow: ' . $e->getMessage());
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'The action could not be completed. Check the input and try again.';
        }
    }
}

$items = $conn->query("SELECT i.*,COALESCE(SUM(CASE WHEN m.location_id=(SELECT id FROM stores_locations WHERE location_code='MAIN' LIMIT 1) AND m.movement_type IN ('Opening','Receipt','Transfer In','Return','Adjustment In') THEN m.quantity WHEN m.location_id=(SELECT id FROM stores_locations WHERE location_code='MAIN' LIMIT 1) THEN -m.quantity ELSE 0 END),0) AS main_balance FROM stores_items i LEFT JOIN stores_movements m ON m.item_id=i.id WHERE i.active=1 GROUP BY i.id ORDER BY i.item_name");
$locations = $conn->query("SELECT id,location_code,location_name,location_type FROM stores_locations WHERE active=1 ORDER BY location_name");
$reqs = $conn->query("SELECT r.*,l.location_name FROM stores_requisitions r LEFT JOIN stores_locations l ON l.id=r.destination_location_id ORDER BY r.created_at DESC LIMIT 100");
$lowStock = 0; $itemRows = [];
if ($items) while ($row=$items->fetch_assoc()) { if ((float)$row['main_balance'] <= (float)$row['reorder_level']) $lowStock++; $itemRows[]=$row; }
$locationRows=[]; if ($locations) while($row=$locations->fetch_assoc()) $locationRows[]=$row;
$reqRows=[]; if($reqs) while($row=$reqs->fetch_assoc()) $reqRows[]=$row;
$mainLocation = null; foreach($locationRows as $loc) if($loc['location_code']==='MAIN') $mainLocation=$loc;
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Central Stores | Hospitalis</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"><style>
body{background:#f3f6fb;color:#243247;font-family:Inter,Segoe UI,Arial,sans-serif}.stores-wrap{margin-left:260px;padding:28px;max-width:1800px}.hero{background:linear-gradient(120deg,#063b73,#087bb8);color:white;border-radius:18px;padding:25px 28px;margin-bottom:22px;box-shadow:0 12px 28px #0a46751c}.hero small{letter-spacing:1.5px;text-transform:uppercase;color:#b9e5ff;font-weight:800}.metric{background:#fff;border:1px solid #e5ebf3;border-radius:14px;padding:17px;box-shadow:0 4px 16px #102c4c08}.metric strong{font-size:25px;display:block}.panel{background:#fff;border:1px solid #e5ebf3;border-radius:14px;margin-bottom:18px;overflow:hidden}.panel-head{padding:15px 18px;border-bottom:1px solid #edf0f5;font-weight:800}.panel-body{padding:18px}.form-label{font-size:11px;text-transform:uppercase;letter-spacing:.5px;font-weight:800;color:#66758b}.table th{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#68768a;background:#f8fafc}.table td,.table th{vertical-align:middle}.pill{border-radius:20px;padding:5px 9px;background:#eaf4ff;color:#075b9d;font-size:11px;font-weight:800}@media(max-width:900px){.stores-wrap{margin-left:0;padding:14px}}
</style></head><body>
<?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="stores-wrap"><section class="hero"><small>Inventory control</small><h1 class="h3 fw-bold mt-2 mb-2">Central Stores</h1><p class="mb-0">A single stock ledger for receipts, departmental requisitions and controlled issues.</p></section>
<?php if($message!==''):?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif;?><?php if($error!==''):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="row g-3 mb-4"><div class="col-md-4"><div class="metric"><span class="text-secondary small">Active stock items</span><strong><?=count($itemRows)?></strong></div></div><div class="col-md-4"><div class="metric"><span class="text-secondary small">Items at/below reorder level</span><strong><?= $lowStock ?></strong></div></div><div class="col-md-4"><div class="metric"><span class="text-secondary small">Open requisitions</span><strong><?=count(array_filter($reqRows,fn($r)=>in_array($r['status'],['Submitted','Approved','Partially Issued'],true)))?></strong></div></div></div>
<div class="row g-3"><div class="col-xl-6">
<div class="panel"><div class="panel-head">Stock Item Register</div><div class="panel-body"><?php if($canCreate):?><form method="post" class="row g-2 mb-3"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="add_item"><div class="col-md-4"><label class="form-label">Item Code</label><input class="form-control" name="item_code" required maxlength="60"></div><div class="col-md-8"><label class="form-label">Item Name</label><input class="form-control" name="item_name" required maxlength="180"></div><div class="col-md-4"><label class="form-label">Category</label><input class="form-control" name="category"></div><div class="col-md-4"><label class="form-label">Unit</label><input class="form-control" name="unit" value="Each" required></div><div class="col-md-4"><label class="form-label">Reorder Level</label><input class="form-control" type="number" min="0" step=".001" name="reorder_level" value="0"></div><div class="col-12"><button class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Add Item</button></div></form><?php endif;?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Item</th><th>Unit</th><th>Main Store</th><th>Reorder</th></tr></thead><tbody><?php foreach($itemRows as $it):?><tr><td><strong><?=htmlspecialchars($it['item_name'])?></strong><br><small class="text-secondary"><?=htmlspecialchars($it['item_code'])?><?= $it['category']?' · '.htmlspecialchars($it['category']):'' ?></small></td><td><?=htmlspecialchars($it['unit'])?></td><td><?=number_format((float)$it['main_balance'],3)?></td><td><?=number_format((float)$it['reorder_level'],3)?></td></tr><?php endforeach;?><?php if(!$itemRows):?><tr><td colspan="4" class="text-center text-secondary py-3">No stock items registered yet.</td></tr><?php endif;?></tbody></table></div></div></div>
<div class="panel"><div class="panel-head">Record Stock Receipt</div><div class="panel-body"><?php if($canCreate):?><form method="post" class="row g-2"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="receive"><div class="col-md-6"><label class="form-label">Item</label><select class="form-select" name="item_id" required><option value="">Select item</option><?php foreach($itemRows as $it):?><option value="<?=$it['id']?>"><?=htmlspecialchars($it['item_code'].' — '.$it['item_name'])?></option><?php endforeach;?></select></div><div class="col-md-6"><label class="form-label">Location</label><select class="form-select" name="location_id" required><?php foreach($locationRows as $loc):?><option value="<?=$loc['id']?>"><?=htmlspecialchars($loc['location_name'])?></option><?php endforeach;?></select></div><div class="col-md-4"><label class="form-label">Quantity</label><input class="form-control" type="number" min=".001" step=".001" name="quantity" required></div><div class="col-md-4"><label class="form-label">Batch / Lot</label><input class="form-control" name="batch_number"></div><div class="col-md-4"><label class="form-label">Expiry</label><input class="form-control" type="date" name="expiry_date"></div><div class="col-12"><label class="form-label">Notes / GRN Reference</label><input class="form-control" name="notes"></div><div class="col-12"><button class="btn btn-primary btn-sm">Record Receipt</button></div></form><?php else:?><p class="text-secondary mb-0">You do not have permission to receive stock.</p><?php endif;?></div></div>
</div><div class="col-xl-6">
<div class="panel"><div class="panel-head">Stock Movement Controls</div><div class="panel-body">
<?php if($canCreate || $canApprove):?>
<form method="post" class="row g-2">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
<div class="col-md-6"><label class="form-label">Movement</label><select class="form-select" name="action" required><option value="transfer_stock">Transfer between locations</option><option value="return_stock">Department return to stock</option><?php if($canApprove):?><option value="adjust_stock">Approved stock adjustment</option><?php endif;?></select></div>
<div class="col-md-6"><label class="form-label">Item</label><select class="form-select" name="movement_item_id" required><option value="">Select item</option><?php foreach($itemRows as $it):?><option value="<?=$it['id']?>"><?=htmlspecialchars($it['item_code'].' — '.$it['item_name'])?></option><?php endforeach;?></select></div>
<div class="col-md-6"><label class="form-label">Source / Return Location</label><select class="form-select" name="from_location_id" required><?php foreach($locationRows as $loc):?><option value="<?=$loc['id']?>"><?=htmlspecialchars($loc['location_name'])?></option><?php endforeach;?></select></div>
<div class="col-md-6"><label class="form-label">Destination (transfers only)</label><select class="form-select" name="to_location_id"><option value="">Select destination</option><?php foreach($locationRows as $loc):?><option value="<?=$loc['id']?>"><?=htmlspecialchars($loc['location_name'])?></option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label">Quantity</label><input class="form-control" type="number" min=".001" step=".001" name="movement_quantity" required></div>
<div class="col-md-4"><label class="form-label">Adjustment direction</label><select class="form-select" name="adjustment_direction"><option value="in">Increase</option><option value="out">Decrease</option></select></div>
<div class="col-md-8"><label class="form-label">Reason / Reference</label><input class="form-control" name="movement_notes" maxlength="500" required></div>
<div class="col-12"><button class="btn btn-primary btn-sm">Record Movement</button></div>
</form>
<?php else:?><p class="text-secondary mb-0">You do not have permission to record stock movements.</p><?php endif;?>
</div></div>
<div class="panel"><div class="panel-head">Department Requisition</div><div class="panel-body"><?php if($canCreate):?><form method="post" class="row g-2"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="create_requisition"><div class="col-md-6"><label class="form-label">Requesting Department</label><input class="form-control" name="department" required placeholder="e.g. Ward A"></div><div class="col-md-6"><label class="form-label">Destination</label><select class="form-select" name="destination_location_id" required><?php foreach($locationRows as $loc):?><option value="<?=$loc['id']?>"><?=htmlspecialchars($loc['location_name'])?></option><?php endforeach;?></select></div><div class="col-12"><label class="form-label">Requested Items</label><div class="row g-2"><?php for($line=0;$line<4;$line++):?><div class="col-md-8"><select class="form-select" name="req_item_id[]" <?=$line===0?'required':''?>><option value=""><?=$line===0?'Select item (required)':'Add another item (optional)'?></option><?php foreach($itemRows as $it):?><option value="<?=$it['id']?>"><?=htmlspecialchars($it['item_code'].' — '.$it['item_name'])?></option><?php endforeach;?></select></div><div class="col-md-4"><input class="form-control" type="number" min=".001" step=".001" name="req_quantity[]" placeholder="Quantity <?=$line+1?>" <?=$line===0?'required':''?>></div><?php endfor;?></div><small class="text-secondary">Add up to four different items in one requisition. Leave unused lines blank.</small></div><div class="col-12"><label class="form-label">Reason / Notes</label><input class="form-control" name="request_notes"></div><div class="col-12"><button class="btn btn-primary btn-sm">Submit Requisition</button></div></form><?php endif;?></div></div>
<div class="panel"><div class="panel-head">Recent Stock Ledger</div><div class="panel-body"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Item</th><th>Location</th><th>Movement</th><th>Qty</th><th>Reference / Notes</th><th>Recorded By</th></tr></thead><tbody>
<?php $ledger=$conn->query("SELECT m.*,i.item_code,i.item_name,l.location_name,u.full_name FROM stores_movements m JOIN stores_items i ON i.id=m.item_id JOIN stores_locations l ON l.id=m.location_id LEFT JOIN users u ON u.id=m.created_by ORDER BY m.created_at DESC,m.id DESC LIMIT 100"); if($ledger): while($mv=$ledger->fetch_assoc()):?>
<tr><td><?=htmlspecialchars($mv['created_at'])?></td><td><?=htmlspecialchars($mv['item_code'].' — '.$mv['item_name'])?></td><td><?=htmlspecialchars($mv['location_name'])?></td><td><span class="pill"><?=htmlspecialchars($mv['movement_type'])?></span></td><td><?=number_format((float)$mv['quantity'],3)?></td><td><?=htmlspecialchars(trim(($mv['reference_type']??'').' '.($mv['reference_id']??'').' '.($mv['notes']??'')))?></td><td><?=htmlspecialchars($mv['full_name']??'System')?></td></tr>
<?php endwhile; endif;?>
</tbody></table></div></div></div>
<div class="panel"><div class="panel-head">Requisition Queue</div><div class="panel-body"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Requisition</th><th>Department / Destination</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($reqRows as $rq):?><tr><td><strong><?=htmlspecialchars($rq['requisition_number'])?></strong><br><small><?=htmlspecialchars($rq['created_at'])?></small></td><td><?=htmlspecialchars($rq['requesting_department'])?><br><small class="text-secondary"><?=htmlspecialchars($rq['location_name']??'—')?></small></td><td><span class="pill"><?=htmlspecialchars($rq['status'])?></span></td><td><?php if($canApprove && $rq['status']==='Submitted'):?><form method="post" class="d-flex gap-1 mb-1"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="approve_requisition"><input type="hidden" name="requisition_id" value="<?=$rq['id']?>"><button class="btn btn-success btn-sm" name="decision" value="Approved">Approve</button><button class="btn btn-outline-danger btn-sm" name="decision" value="Rejected">Reject</button></form><?php endif;?><?php if($canApprove && in_array($rq['status'],['Approved','Partially Issued'],true)):?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="issue_requisition"><input type="hidden" name="requisition_id" value="<?=$rq['id']?>"><button class="btn btn-primary btn-sm">Issue Stock</button></form><?php endif;?></td></tr><?php endforeach;?><?php if(!$reqRows):?><tr><td colspan="4" class="text-center text-secondary py-3">No requisitions yet.</td></tr><?php endif;?></tbody></table></div></div></div>
</div></div></div></body></html>
