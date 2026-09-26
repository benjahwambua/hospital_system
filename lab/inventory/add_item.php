<?php
require_once __DIR__.'/../../config/config.php'; require_once __DIR__.'/../../includes/session.php'; require_once __DIR__.'/../../includes/auth.php'; require_login(); require_module_access($conn, 'laboratory', 'create'); require_role(['admin','lab']);
if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32)); $csrf=$_SESSION['csrf_token']; $error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf_token']??'')))$error='Invalid security token.';
 else{
  $name=trim($_POST['item_name']??'');$cat=trim($_POST['category']??'Laboratory Consumable');$unit=trim($_POST['unit']??'Piece');$qty=(float)($_POST['quantity']??0);$reorder=(float)($_POST['reorder_level']??0);$buy=(float)($_POST['buying_price']??0);$supplier=trim($_POST['supplier']??'');$batch=trim($_POST['batch_no']??'');$expiry=$_POST['expiry_date']??null;
  if($name==='')$error='Item name is required.'; elseif($qty<0||$reorder<0||$buy<0)$error='Quantities and price cannot be negative.';
  if($error===''){ $conn->begin_transaction(); try{$s=$conn->prepare("INSERT INTO lab_inventory(item_name,category,unit,quantity,reorder_level,buying_price,supplier,batch_no,expiry_date) VALUES(?,?,?,?,?,?,?,?,?)");$s->bind_param('sssdddsss',$name,$cat,$unit,$qty,$reorder,$buy,$supplier,$batch,$expiry);if(!$s->execute())throw new Exception($s->error);$id=$s->insert_id;$s->close();$uid=(int)($_SESSION['user_id']??0);$note='Initial stock';$m=$conn->prepare("INSERT INTO lab_inventory_movements(inventory_id,movement_type,quantity,balance_after,note,user_id) VALUES(?,'in',?,?,?,?)");if($m){$m->bind_param('iddsi',$id,$qty,$qty,$note,$uid);if(!$m->execute())throw new Exception($m->error);$m->close();}$conn->commit();$success='Laboratory stock item added.';}catch(Throwable $e){$conn->rollback();$error=$e->getMessage();}}
 }
}
include __DIR__.'/../../includes/header.php';include __DIR__.'/../../includes/sidebar.php';?><style>
.hms-form-page{padding:26px 24px 44px;background:#f5f7fb;min-height:calc(100vh - 60px)}
.hms-form-shell{max-width:1450px;margin:0 auto}
.hms-form-hero{background:#fff;border:1px solid #e7ebf2;border-radius:14px;padding:21px 24px;margin-bottom:20px;box-shadow:0 4px 18px rgba(31,45,61,.06);display:flex;justify-content:space-between;align-items:center;gap:18px}
.hms-form-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.3px;font-weight:700;color:#6c7a91;margin-bottom:4px}
.hms-form-hero h1{font-size:24px;font-weight:700;color:#25324a;margin:0 0 5px}.hms-form-hero p{margin:0;color:#718096;font-size:14px}
.hms-form-card{background:#fff;border:1px solid #e7ebf2;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden;margin-bottom:18px}
.hms-form-card .card-header{background:#fff;border-bottom:1px solid #edf0f5;padding:16px 20px;color:#25324a;font-weight:700}
.hms-form-card .card-body{padding:21px}
.hms-form-page label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#667085;margin-bottom:6px}
.hms-form-page .form-control{border:1px solid #d8dee8;border-radius:8px;background:#fff;color:#344054;min-height:42px;padding:10px 12px}
.hms-form-page .form-control:focus{border-color:#4c84ff;box-shadow:0 0 0 3px rgba(76,132,255,.10);outline:0}
.hms-form-page textarea.form-control{min-height:auto}.hms-form-page .btn{border-radius:8px;font-weight:700}
.hms-form-page hr{border-color:#edf0f5}.hms-form-page .table{margin-bottom:0}.hms-form-page .table thead th{background:#f8fafc;border-top:0;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.35px}
@media(max-width:767px){.hms-form-page{padding:18px 12px 35px}.hms-form-hero{align-items:flex-start;flex-direction:column}}
</style>
<div class="hms-form-page"><div class="hms-form-shell"><div class="hms-form-hero"><div><div class="hms-form-kicker">Laboratory · Inventory</div><h1>Add Laboratory Stock Item</h1><p>Record a new laboratory consumable and its opening stock details.</p></div></div><?php if($success):?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="hms-form-card card shadow"><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><div class="row">
<div class="col-md-6 mb-3"><label>Item Name</label><input name="item_name" class="form-control" placeholder="e.g. HIV Test Kits" required></div><div class="col-md-3 mb-3"><label>Category</label><input name="category" class="form-control" value="Laboratory Consumable"></div><div class="col-md-3 mb-3"><label>Unit</label><input name="unit" class="form-control" value="Piece" required></div>
<div class="col-md-3 mb-3"><label>Opening Quantity</label><input type="number" step="0.01" min="0" name="quantity" class="form-control" value="0" required></div><div class="col-md-3 mb-3"><label>Reorder Level</label><input type="number" step="0.01" min="0" name="reorder_level" class="form-control" value="0"></div><div class="col-md-3 mb-3"><label>Buying Price</label><input type="number" step="0.01" min="0" name="buying_price" class="form-control" value="0"></div><div class="col-md-3 mb-3"><label>Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div>
<div class="col-md-6 mb-3"><label>Supplier</label><input name="supplier" class="form-control"></div><div class="col-md-6 mb-3"><label>Batch Number</label><input name="batch_no" class="form-control"></div></div><button class="btn btn-success">Save Item</button> <a href="index.php" class="btn btn-light">Cancel</a></form></div></div></div>
<?php include __DIR__.'/../../includes/footer.php'; ?>