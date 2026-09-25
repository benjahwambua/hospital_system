<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../helpers/billing.php';
require_once __DIR__.'/../helpers/cashier.php';
require_login();

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD']==='POST' && !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    exit('Invalid security token.');
}

$role=strtolower(trim((string)($_SESSION['role']??'')));
$isSuper=!empty($_SESSION['is_super']) && (int)$_SESSION['is_super']===1;
if(!$isSuper && !in_array($role,['admin','cashier'],true)){ http_response_code(403); die('Access denied.'); }

$cashierId=(int)($_SESSION['user_id']??0);
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $action=$_POST['action']??'';
        if($action==='open'){
            open_cashier_shift($conn,$cashierId,(float)($_POST['opening_cash']??0),trim((string)($_POST['opening_notes']??'')));
            $message='<div class="alert alert-success"><i class="fas fa-check-circle"></i> Cashier shift opened successfully.</div>';
        }elseif($action==='close'){
            $shiftId=(int)($_POST['shift_id']??0);
            $result=close_cashier_shift($conn,$shiftId,$cashierId,(float)($_POST['closing_cash']??0),trim((string)($_POST['closing_notes']??'')));
            $message='<div class="alert alert-success"><i class="fas fa-check-circle"></i> Shift closed. Expected cash: KSH '.number_format($result['expected_cash'],2).'. Physical cash: KSH '.number_format($result['closing_cash'],2).'. Variance: KSH '.number_format($result['variance'],2).'.</div>';
        }
    }catch(Throwable $e){ $message='<div class="alert alert-danger">'.htmlspecialchars($e->getMessage()).'</div>'; }
}
$open=get_open_cashier_shift($conn,$cashierId);
$totals=$open?cashier_shift_totals($conn,(int)$open['id']):['cash'=>0,'mpesa'=>0,'other'=>0,'total'=>0];
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="h3 mb-1 text-gray-800"><i class="fas fa-door-open"></i> Cashier Shift</h2><p class="text-muted mb-0">Open, monitor and close your collection shift.</p></div><a href="/hospital_system/cashier/index.php" class="btn btn-outline-primary"><i class="fas fa-cash-register"></i> Cashier</a></div>
<?= $message ?>
<?php if(!$open): ?>
<div class="card shadow"><div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Open New Shift</h6></div><div class="card-body">
<form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="open"><div class="form-row">
<div class="form-group col-md-4"><label>Opening Cash (KSH)</label><input type="number" name="opening_cash" class="form-control" min="0" step="0.01" value="0" required></div>
<div class="form-group col-md-8"><label>Opening Notes</label><input type="text" name="opening_notes" class="form-control" maxlength="500"></div></div>
<button class="btn btn-success"><i class="fas fa-play"></i> Open Shift</button></form></div></div>
<?php else: ?>
<div class="row mb-4">
<div class="col-md-3"><div class="card shadow h-100"><div class="card-body"><small class="text-muted">Opened</small><h5><?=htmlspecialchars($open['opened_at'])?></h5></div></div></div>
<div class="col-md-3"><div class="card shadow h-100"><div class="card-body"><small class="text-muted">Opening Cash</small><h5>KSH <?=number_format((float)$open['opening_cash'],2)?></h5></div></div></div>
<div class="col-md-3"><div class="card shadow h-100"><div class="card-body"><small class="text-muted">Cash Collected</small><h5>KSH <?=number_format($totals['cash'],2)?></h5></div></div></div>
<div class="col-md-3"><div class="card shadow h-100"><div class="card-body"><small class="text-muted">M-Pesa Collected</small><h5>KSH <?=number_format($totals['mpesa'],2)?></h5></div></div></div>
</div>
<div class="card shadow"><div class="card-header"><h6 class="m-0 font-weight-bold text-danger">Close Shift & Reconcile</h6></div><div class="card-body">
<div class="alert alert-info">Expected physical cash = opening cash + cash collections. M-Pesa is reconciled separately and is not included in physical cash.</div>
<form method="post" onsubmit="return confirm('Close this cashier shift? This action cannot be undone.');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="close"><input type="hidden" name="shift_id" value="<?= (int)$open['id']?>">
<div class="form-row"><div class="form-group col-md-4"><label>Physical Closing Cash (KSH)</label><input type="number" name="closing_cash" class="form-control" min="0" step="0.01" required></div><div class="form-group col-md-8"><label>Closing Notes</label><input type="text" name="closing_notes" class="form-control" maxlength="500"></div></div>
<button class="btn btn-danger"><i class="fas fa-lock"></i> Close Shift</button></form></div></div>
<?php endif; ?>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>