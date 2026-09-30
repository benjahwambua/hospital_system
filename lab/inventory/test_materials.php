<?php
require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../includes/session.php';
require_once __DIR__.'/../../includes/auth.php';
require_login();
require_module_access($conn,'laboratory','edit');
require_role(['admin','lab','lab_tech']);

if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token'];$error='';$success='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($csrf,(string)($_POST['csrf_token']??''))){$error='Invalid security token.';}
    else{
        $action=$_POST['action']??'add';
        if($action==='delete'){
            $id=(int)($_POST['id']??0);
            $s=$conn->prepare("UPDATE lab_service_materials SET active=0 WHERE id=?");$s->bind_param('i',$id);$s->execute();$s->close();$success='Material mapping disabled.';
        }else{
            $serviceId=(int)($_POST['service_id']??0);$inventoryId=(int)($_POST['inventory_id']??0);
            $qty=(float)($_POST['quantity_per_test']??0);$unit=trim((string)($_POST['unit']??''));
            if($serviceId<=0||$inventoryId<=0||$qty<=0)$error='Select a laboratory service, inventory item and positive quantity.';
            else{
                $s=$conn->prepare("INSERT INTO lab_service_materials(service_id,inventory_id,quantity_per_test,unit,active,created_by) VALUES(?,?,?,?,1,?) ON DUPLICATE KEY UPDATE quantity_per_test=VALUES(quantity_per_test),unit=VALUES(unit),active=1");
                $uid=(int)($_SESSION['user_id']??0);$s->bind_param('iid si',$serviceId,$inventoryId,$qty,$unit,$uid);
                // mysqli does not accept spaces in a bind type string.
                $s->bind_param('iidsi',$serviceId,$inventoryId,$qty,$unit,$uid);
                if(!$s->execute())$error=$s->error;else$success='Laboratory material mapping saved.';
                $s->close();
            }
        }
    }
}

$services=$conn->query("SELECT id,service_code,service_name FROM services_master WHERE active=1 AND category='lab' ORDER BY service_name");
$inventory=$conn->query("SELECT id,item_name,unit,quantity FROM lab_inventory WHERE status='active' ORDER BY item_name");
$map=$conn->query("SELECT lsm.id,sm.service_code,sm.service_name,li.item_name,li.unit,lsm.quantity_per_test,li.quantity stock
                   FROM lab_service_materials lsm
                   INNER JOIN services_master sm ON sm.id=lsm.service_id
                   INNER JOIN lab_inventory li ON li.id=lsm.inventory_id
                   WHERE lsm.active=1 ORDER BY sm.service_name,li.item_name");
include __DIR__.'/../../includes/header.php';include __DIR__.'/../../includes/sidebar.php';
?>
<style>
.lab-map{background:#f5f7fb;min-height:calc(100vh - 72px);padding:28px 24px 42px}.lab-shell{max-width:1400px;margin:auto}
.lab-hero{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:18px;padding:26px 30px;margin-bottom:20px}.lab-hero h1{margin:4px 0}.lab-card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 18px rgba(31,45,61,.05);overflow:hidden}.lab-card-head{padding:16px 20px;border-bottom:1px solid #edf0f5}.lab-card-body{padding:20px}.lab-table th{background:#f8fafc;color:#667085;font-size:11px;text-transform:uppercase}.lab-table td{vertical-align:middle;border-color:#edf0f5}.form-control{border-radius:9px;border-color:#d7dee8}
</style>
<div class="lab-map"><div class="lab-shell">
<div class="lab-hero"><div style="font-size:10px;text-transform:uppercase;letter-spacing:1.4px;font-weight:800">Laboratory Inventory Control</div><h1>Test Materials</h1><p class="mb-0">Define what reagents and consumables each laboratory test uses. Stock is deducted when the test is completed.</p></div>
<?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?><?php if($success):?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif;?>
<div class="lab-card mb-4"><div class="lab-card-head"><strong>Map Material to Laboratory Test</strong></div><div class="lab-card-body">
<form method="post" class="row">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
<div class="col-md-4 mb-3"><label>Laboratory Test</label><select name="service_id" class="form-control" required><option value="">Select test...</option><?php while($s=$services->fetch_assoc()):?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['service_code'].' — '.$s['service_name'])?></option><?php endwhile;?></select></div>
<div class="col-md-4 mb-3"><label>Material / Reagent</label><select name="inventory_id" class="form-control" required><option value="">Select inventory item...</option><?php while($i=$inventory->fetch_assoc()):?><option value="<?=$i['id']?>"><?=htmlspecialchars($i['item_name'])?> — Stock <?=number_format((float)$i['quantity'],2).' '.htmlspecialchars($i['unit'])?></option><?php endwhile;?></select></div>
<div class="col-md-2 mb-3"><label>Qty / Test</label><input name="quantity_per_test" type="number" step="0.0001" min="0.0001" class="form-control" required></div>
<div class="col-md-2 mb-3"><label>Unit</label><input name="unit" class="form-control" placeholder="mL, test, piece"></div>
<div class="col-12"><button class="btn btn-primary"><i class="fas fa-link mr-1"></i>Save Material Mapping</button></div>
</form></div></div>
<div class="lab-card"><div class="lab-card-head"><strong>Active Test Material Mappings</strong></div><div class="table-responsive"><table class="table lab-table mb-0"><thead><tr><th>Test</th><th>Material</th><th>Qty / Test</th><th>Current Stock</th><th>Action</th></tr></thead><tbody>
<?php if($map&&$map->num_rows):while($m=$map->fetch_assoc()):?><tr><td><strong><?=htmlspecialchars($m['service_name'])?></strong><small class="d-block text-muted"><?=htmlspecialchars($m['service_code'])?></small></td><td><?=htmlspecialchars($m['item_name'])?></td><td><?=number_format((float)$m['quantity_per_test'],4).' '.htmlspecialchars($m['unit']??'')?></td><td><?=number_format((float)$m['stock'],2).' '.htmlspecialchars($m['unit']??'')?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$m['id']?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Disable this mapping?')">Disable</button></form></td></tr><?php endwhile;else:?><tr><td colspan="5" class="text-center text-muted py-4">No material mappings yet.</td></tr><?php endif;?>
</tbody></table></div></div>
</div></div>
<?php include __DIR__.'/../../includes/footer.php'; ?>