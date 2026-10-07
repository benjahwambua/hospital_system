<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../helpers/billing.php';
require_login();
require_module_access($conn, 'finance', 'approve');

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$paymentId=(int)($_GET['id'] ?? $_POST['payment_id'] ?? 0);
$message=''; $error=''; $transactionStarted=false;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!can_module_action($conn, 'finance', 'approve')) { http_response_code(403); exit('Forbidden'); }
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); exit('Invalid security token.'); }
    try {
        $amount=(float)($_POST['amount'] ?? 0);
        $reason=trim((string)($_POST['reason'] ?? ''));
        $method=trim((string)($_POST['refund_method'] ?? 'Original'));
        $reference=trim((string)($_POST['reference'] ?? ''));
        $conn->begin_transaction();
        $transactionStarted=true;
        $result=refund_payment($conn,$paymentId,$amount,$reason,$method,$reference!==''?$reference:null,(int)$_SESSION['user_id']);
        $conn->commit();
        $transactionStarted=false;
        $message='Refund approved. Refund #'.$result['refund_id'].' recorded and the invoice balance was reconciled.';
    } catch(Throwable $e) {
        if ($transactionStarted) { $conn->rollback(); $transactionStarted=false; }
        error_log('HMS refund error: '.$e->getMessage());
        $error='Unable to process the refund. Please verify the refund details and try again.';
    }
}

$stmt=$conn->prepare("SELECT pay.*, i.invoice_number, i.total AS invoice_total, p.full_name, p.patient_number,
    COALESCE((SELECT SUM(r.amount) FROM payment_refunds r WHERE r.payment_id=pay.id AND r.status='Approved'),0) refunded
    FROM payments pay
    LEFT JOIN invoices i ON i.id=pay.invoice_id
    LEFT JOIN patients p ON p.id=pay.patient_id
    WHERE pay.id=? LIMIT 1");
if(!$stmt){ http_response_code(500); exit('Unable to load payment.'); }
$stmt->bind_param('i',$paymentId); $stmt->execute(); $payment=$stmt->get_result()->fetch_assoc(); $stmt->close();
if(!$payment){ http_response_code(404); exit('Payment not found.'); }
$refundable=max((float)$payment['amount']-(float)$payment['refunded'],0);

$refunds=[];
$rs=$conn->prepare("SELECT r.*, u.full_name AS refunded_by_name FROM payment_refunds r LEFT JOIN users u ON u.id=r.refunded_by WHERE r.payment_id=? ORDER BY r.id DESC");
if($rs){$rs->bind_param('i',$paymentId);$rs->execute();$rr=$rs->get_result();while($row=$rr->fetch_assoc())$refunds[]=$row;$rs->close();}

include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<link rel="stylesheet" href="../assets/css/finance_modules.css">
<div class="main-content finance-workspace"><div class="container-fluid">
<section class="finance-hero">
 <div><div class="finance-kicker">Finance &amp; Controls</div><h1>Refund &amp; Reversal</h1><p>Reverse approved payments while preserving the original transaction and audit trail.</p></div>
 <div class="finance-hero-actions"><a href="/hospital_system/cashier/payment_history.php" class="btn btn-light"><i class="fas fa-receipt"></i> Payment History</a><a href="/hospital_system/cashier/index.php" class="btn btn-outline-light"><i class="fas fa-cash-register"></i> Central Cashier</a></div>
</section>
<?php if($message): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<div class="row">
 <div class="col-xl-5">
  <div class="finance-panel card shadow mb-4"><div class="card-header finance-section-header"><div><div class="finance-section-kicker">Transaction details</div><h3 class="m-0">Original Payment</h3><div class="finance-section-subtitle">Payment #<?= $paymentId ?></div></div><span class="finance-status info"><?= htmlspecialchars($payment['method']??'') ?></span></div><div class="card-body">
   <p><strong>Patient:</strong> <?=htmlspecialchars($payment['full_name']??'Unknown')?> (<?=htmlspecialchars($payment['patient_number']??'N/A')?>)</p>
   <p><strong>Invoice:</strong> <?=htmlspecialchars($payment['invoice_number']??('#'.$payment['invoice_id']))?></p>
   <p><strong>Payment:</strong> KSH <?=number_format((float)$payment['amount'],2)?></p>
   <p><strong>Method:</strong> <?=htmlspecialchars($payment['method']??'')?></p>
   <p><strong>Reference:</strong> <?=htmlspecialchars($payment['reference']??'')?></p>
   <p><strong>Already refunded:</strong> KSH <?=number_format((float)$payment['refunded'],2)?></p>
   <p class="mb-0"><strong>Remaining refundable:</strong> <span class="text-danger font-weight-bold">KSH <?=number_format($refundable,2)?></span></p>
  </div></div>
 </div>
 <div class="col-xl-7">
  <div class="finance-panel card shadow mb-4"><div class="card-header finance-section-header"><div><div class="finance-section-kicker">Controlled adjustment</div><h3 class="m-0">Record Refund</h3><div class="finance-section-subtitle">Approval is required and the original payment remains in the audit trail.</div></div><span class="finance-status danger">Approval Required</span></div><div class="card-body">
  <?php if($refundable<=0): ?><div class="alert alert-info">This payment has been fully refunded.</div>
  <?php else: ?>
  <form method="post" onsubmit="return confirm('Approve this refund? The original payment will remain in the audit trail and accounting will be reversed.');">
   <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
   <input type="hidden" name="payment_id" value="<?=$paymentId?>">
   <div class="form-group"><label>Refund Amount (KSH)</label><input type="number" name="amount" class="form-control" min="0.01" max="<?=number_format($refundable,2,'.','')?>" step="0.01" required></div>
   <div class="form-group"><label>Refund Method</label><select name="refund_method" class="form-control"><option>Original</option><option>Cash</option><option>M-Pesa</option><option>Bank</option></select></div>
   <div class="form-group"><label>Refund Reference (optional)</label><input type="text" name="reference" class="form-control" maxlength="100"></div>
   <div class="form-group"><label>Reason</label><textarea name="reason" class="form-control" rows="3" maxlength="500" required></textarea></div>
   <button class="btn btn-danger"><i class="fas fa-undo"></i> Approve Refund</button>
  </form>
  <?php endif; ?>
  </div></div>
 </div>
</div>
<div class="finance-panel card shadow"><div class="card-header finance-section-header"><div><div class="finance-section-kicker">Audit trail</div><h3 class="m-0">Refund History</h3><div class="finance-section-subtitle">All approved reversals linked to this payment</div></div><span class="finance-status info"><?= count($refunds) ?> record(s)</span></div><div class="card-body">
<table class="table table-bordered table-sm"><thead><tr><th>#</th><th>Amount</th><th>Method</th><th>Reference</th><th>Reason</th><th>By</th><th>Date</th></tr></thead><tbody>
<?php foreach($refunds as $r): ?><tr><td><?= (int)$r['id']?></td><td>KSH <?=number_format((float)$r['amount'],2)?></td><td><?=htmlspecialchars($r['refund_method'])?></td><td><?=htmlspecialchars($r['reference']??'')?></td><td><?=htmlspecialchars($r['reason'])?></td><td><?=htmlspecialchars($r['refunded_by_name']??'')?></td><td><?=htmlspecialchars($r['created_at'])?></td></tr><?php endforeach; ?>
<?php if(!$refunds): ?><tr><td colspan="7" class="text-center text-muted">No refunds recorded.</td></tr><?php endif; ?>
</tbody></table>
</div></div>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>