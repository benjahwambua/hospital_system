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
    }catch(Throwable $e){ $message='<div class="alert alert-danger">'.'Unable to close cashier shift. No changes were saved.'.'</div>'; }
}
$open=get_open_cashier_shift($conn,$cashierId);
$totals=$open?cashier_shift_totals($conn,(int)$open['id']):['cash'=>0,'mpesa'=>0,'other'=>0,'total'=>0];
$closedShifts=[];
$historyStmt=$conn->prepare("SELECT cs.*,u.full_name AS cashier_name FROM cashier_shifts cs LEFT JOIN users u ON u.id=cs.cashier_id WHERE cs.cashier_id=? AND cs.status='Closed' ORDER BY cs.closed_at DESC LIMIT 10");
if($historyStmt){$historyStmt->bind_param('i',$cashierId);$historyStmt->execute();$hr=$historyStmt->get_result();while($row=$hr->fetch_assoc())$closedShifts[]=$row;$historyStmt->close();}
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<link rel="stylesheet" href="../assets/css/finance_modules.css">
<style>
.cashier-shifts{padding:28px 24px 50px;background:#f4f7fb;min-height:calc(100vh - 72px)}
.cashier-shifts .finance-shell{max-width:1500px;margin:0 auto}
.shift-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#0b3d91 0%,#1261c9 55%,#13a8b8 100%);color:#fff;border-radius:22px;padding:30px 32px;margin-bottom:22px;box-shadow:0 16px 38px rgba(16,77,153,.22);display:flex;justify-content:space-between;align-items:center;gap:24px}
.shift-hero:after{content:"";position:absolute;width:280px;height:280px;border:1px solid rgba(255,255,255,.13);border-radius:50%;right:-90px;top:-130px;box-shadow:0 0 0 35px rgba(255,255,255,.025),0 0 0 70px rgba(255,255,255,.015);pointer-events:none}
.shift-hero>*{position:relative;z-index:1}
.shift-eyebrow{font-size:11px;text-transform:uppercase;letter-spacing:2px;font-weight:800;opacity:.72}
.shift-hero h1{color:#fff!important;font-size:30px;font-weight:800;margin:5px 0 7px}
.shift-hero p{color:rgba(255,255,255,.82);margin:0;font-size:14px}
.shift-hero .btn{background:#fff;border:0;color:#0b3d91;border-radius:10px;font-weight:800;padding:11px 17px}
.shift-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.shift-metric{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px 20px;box-shadow:0 8px 25px rgba(20,40,70,.065)}
.shift-metric small{display:block;color:#697586;font-size:10px;text-transform:uppercase;letter-spacing:.07em;font-weight:800;margin-bottom:7px}
.shift-metric strong{display:block;color:#182334;font-size:21px;font-weight:800}
.shift-card{background:#fff;border:1px solid #e2e8f0;border-radius:17px;box-shadow:0 8px 25px rgba(20,40,70,.065);overflow:hidden;margin-bottom:20px}
.shift-card-head{padding:17px 21px;border-bottom:1px solid #e8edf3;background:linear-gradient(180deg,#fff,#f8fafc);display:flex;justify-content:space-between;align-items:center;gap:12px}
.shift-card-head h2{margin:0;color:#182334;font-size:16px;font-weight:800}
.shift-card-head span{font-size:11px;color:#697586}
.shift-card-body{padding:22px}
.shift-form-grid{display:grid;grid-template-columns:1fr 2fr;gap:18px}
.shift-form-actions{margin-top:4px}
.shift-label{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.07em;font-weight:800;color:#697586;margin-bottom:7px}
.shift-input{width:100%;min-height:44px;padding:10px 12px;border:1px solid #d8e0ea;border-radius:9px;background:#fff;color:#344054}
.shift-input:focus{border-color:#2f78c8;box-shadow:0 0 0 3px rgba(47,120,200,.10);outline:0}
.shift-btn{border:0;border-radius:9px;padding:11px 17px;font-weight:800}
.shift-btn-open{background:#0f9d70;color:#fff}.shift-btn-close{background:#dc3545;color:#fff}
.shift-alert{border-radius:12px;margin-bottom:20px;padding:13px 16px;border:1px solid}
.shift-alert.alert-success{background:#ecfdf3;border-color:#a7f3d0;color:#087443}
.shift-alert.alert-danger{background:#fff1f2;border-color:#fecdd3;color:#b42318}
.shift-status{display:inline-flex;align-items:center;gap:7px;padding:6px 10px;border-radius:999px;background:#eaf4ff;color:#1261c9;font-size:10px;text-transform:uppercase;letter-spacing:.05em;font-weight:800}
.shift-status.open{background:#ecfdf3;color:#087443}.shift-status.closed{background:#f1f5f9;color:#475467}
.shift-table-wrap{overflow-x:auto}.shift-table{width:100%;border-collapse:separate;border-spacing:0}
.shift-table th{background:#f1f5f9;color:#64748b;text-transform:uppercase;letter-spacing:.06em;font-size:10px;font-weight:800;padding:12px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.shift-table td{padding:13px 12px;border-bottom:1px solid #edf1f5;color:#344054;font-size:12px;white-space:nowrap}
.shift-table tbody tr:hover{background:#f7fbff}.shift-table tbody tr:last-child td{border-bottom:0}
.shift-reconcile{background:#f8fafc;border:1px solid #e5eaf1;border-radius:12px;padding:14px 16px;margin-bottom:20px}
.shift-reconcile-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.shift-reconcile small{display:block;color:#697586;font-size:10px;text-transform:uppercase;font-weight:800}.shift-reconcile strong{display:block;color:#182334;font-size:15px;margin-top:4px}
@media(max-width:900px){.shift-metrics{grid-template-columns:1fr 1fr}.shift-form-grid{grid-template-columns:1fr}.shift-hero{align-items:flex-start;flex-direction:column}}
@media(max-width:600px){.cashier-shifts{padding:18px 12px 35px}.shift-hero{padding:23px;border-radius:17px}.shift-hero h1{font-size:24px}.shift-metrics{grid-template-columns:1fr}.shift-card-body{padding:16px}.shift-reconcile-grid{grid-template-columns:1fr}}
</style>

<div class="cashier-shifts">
  <div class="finance-shell">
    <div class="shift-hero">
      <div>
        <div class="shift-eyebrow">Finance &amp; Controls / Cash Management</div>
        <h1>Cashier Shifts</h1>
        <p>Open, monitor and reconcile your daily collection shift.</p>
      </div>
      <a href="/hospital_system/cashier/index.php" class="btn"><i class="fas fa-cash-register"></i>&nbsp; Central Cashier</a>
    </div>

    <?= $message ?>

    <?php if(!$open): ?>
      <div class="shift-card">
        <div class="shift-card-head">
          <h2><i class="fas fa-play-circle"></i>&nbsp; Open New Shift</h2>
          <span class="shift-status"><i class="fas fa-lock-open"></i> No active shift</span>
        </div>
        <div class="shift-card-body">
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="open">
            <div class="shift-form-grid">
              <div>
                <label class="shift-label">Opening Cash (KSH)</label>
                <input type="number" name="opening_cash" class="shift-input" min="0" step="0.01" value="0" required>
              </div>
              <div>
                <label class="shift-label">Opening Notes</label>
                <input type="text" name="opening_notes" class="shift-input" maxlength="500" placeholder="Optional opening notes">
              </div>
            </div>
            <div class="shift-form-actions">
              <button class="shift-btn shift-btn-open"><i class="fas fa-play"></i>&nbsp; Open Shift</button>
            </div>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="shift-metrics">
        <div class="shift-metric"><small>Shift Opened</small><strong><?=htmlspecialchars($open['opened_at'])?></strong></div>
        <div class="shift-metric"><small>Opening Cash</small><strong>KSH <?=number_format((float)$open['opening_cash'],2)?></strong></div>
        <div class="shift-metric"><small>Cash Collected</small><strong>KSH <?=number_format($totals['cash'],2)?></strong></div>
        <div class="shift-metric"><small>M-Pesa Collected</small><strong>KSH <?=number_format($totals['mpesa'],2)?></strong></div>
      </div>

      <div class="shift-reconcile">
        <div class="shift-reconcile-grid">
          <div><small>Other Collections</small><strong>KSH <?=number_format($totals['other'],2)?></strong></div>
          <div><small>Total Collections</small><strong>KSH <?=number_format($totals['total'],2)?></strong></div>
          <div><small>Shift Status</small><strong><span class="shift-status open"><i class="fas fa-circle"></i> Open</span></strong></div>
        </div>
      </div>

      <div class="shift-card">
        <div class="shift-card-head">
          <h2><i class="fas fa-balance-scale"></i>&nbsp; Close Shift &amp; Reconcile</h2>
          <span>Physical cash is compared with expected cash at closure.</span>
        </div>
        <div class="shift-card-body">
          <form method="post" onsubmit="return confirm('Close this cashier shift? This action cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="close">
            <input type="hidden" name="shift_id" value="<?= (int)$open['id']?>">
            <div class="shift-form-grid">
              <div>
                <label class="shift-label">Physical Closing Cash (KSH)</label>
                <input type="number" name="closing_cash" class="shift-input" min="0" step="0.01" required placeholder="Enter counted cash">
              </div>
              <div>
                <label class="shift-label">Closing Notes</label>
                <input type="text" name="closing_notes" class="shift-input" maxlength="500" placeholder="Optional reconciliation notes">
              </div>
            </div>
            <div class="shift-form-actions">
              <button class="shift-btn shift-btn-close"><i class="fas fa-lock"></i>&nbsp; Close &amp; Reconcile Shift</button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="shift-card">
      <div class="shift-card-head">
        <h2><i class="fas fa-history"></i>&nbsp; Recent Reconciled Shifts</h2>
        <span>Last 10 closed shifts</span>
      </div>
      <div class="shift-table-wrap">
        <table class="shift-table">
          <thead><tr><th>Opened</th><th>Closed</th><th>Opening</th><th>Cash</th><th>M-Pesa</th><th>Other</th><th>Closing Cash</th><th>Variance</th></tr></thead>
          <tbody>
          <?php foreach($closedShifts as $s): $closedTotals=cashier_shift_totals($conn,(int)$s['id']); ?>
            <tr>
              <td><?=htmlspecialchars($s['opened_at'])?></td>
              <td><?=htmlspecialchars($s['closed_at'])?></td>
              <td>KSH <?=number_format((float)$s['opening_cash'],2)?></td>
              <td>KSH <?=number_format($closedTotals['cash'],2)?></td>
              <td>KSH <?=number_format($closedTotals['mpesa'],2)?></td>
              <td>KSH <?=number_format($closedTotals['other'],2)?></td>
              <td>KSH <?=number_format((float)$s['closing_cash'],2)?></td>
              <td class="<?=abs((float)$s['cash_variance'])<0.01?'text-success':'text-danger'?> font-weight-bold">KSH <?=number_format((float)$s['cash_variance'],2)?></td>
            </tr>
          <?php endforeach; ?>
          <?php if(!$closedShifts): ?>
            <tr><td colspan="8" style="text-align:center;padding:28px;color:#98a2b3">No closed shifts yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__.'/../includes/footer.php'; ?>