<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../helpers/billing.php';
require_once __DIR__.'/../helpers/cashier.php';
require_once __DIR__.'/../config/mpesa.php';

require_login();
// Viewing an invoice requires View; recording a payment requires Create.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_module_access($conn, 'finance', 'create');
} else {
    require_module_access($conn, 'finance', 'view');
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$id = (int)($_POST['invoice_id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid invoice ID.');
}

$stmt = $conn->prepare("SELECT * FROM invoices WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    http_response_code(404);
    exit('Invoice not found.');
}

$customer = invoice_get_customer_info($conn, $invoice);
$patientId = (int)($customer['patient_id'] ?? 0);
$patientName = (string)($customer['patient_name'] ?? 'Unknown');
$patientNumber = (string)($customer['patient_number'] ?? '');
$walkinId = (int)($invoice['walkin_id'] ?? 0);

$items = invoice_load_items($conn, $invoice);
$invoiceTotal = (float)($invoice['total'] ?? 0);
if ($items) {
    $itemTotal = 0.0;
    foreach ($items as $item) $itemTotal += (float)$item['amount'];
    if ($itemTotal > 0) $invoiceTotal = $itemTotal;
}

$paidStmt = $conn->prepare("SELECT
    COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id=?),0)
    - COALESCE((SELECT SUM(amount) FROM payment_refunds WHERE invoice_id=? AND status='Approved'),0) AS total_paid");
$paidStmt->bind_param('ii', $id, $id);
$paidStmt->execute();
$totalPaid = (float)($paidStmt->get_result()->fetch_assoc()['total_paid'] ?? 0);
$paidStmt->close();
$totalPaid = max($totalPaid, 0);
$outstanding = max($invoiceTotal - $totalPaid, 0);

$paymentHistory = [];
$hist = $conn->prepare("SELECT id, amount, method, reference, created_at FROM payments WHERE invoice_id=? ORDER BY created_at DESC, id DESC");
if ($hist) {
    $hist->bind_param('i', $id);
    $hist->execute();
    $res = $hist->get_result();
    while ($row = $res->fetch_assoc()) $paymentHistory[] = $row;
    $hist->close();
}

$cashierId = (int)($_SESSION['user_id'] ?? 0);
$openShift = get_open_cashier_shift($conn, $cashierId);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(419);
        exit('Invalid security token.');
    }

    if (!$openShift) {
        $error = 'No open cashier shift. Open your cashier shift before receiving a payment.';
    } else {
        $amount = round((float)($_POST['amount'] ?? 0), 2);
        $modeRaw = trim((string)($_POST['payment_mode'] ?? 'Cash'));
        $modeKey = strtolower($modeRaw);
        $mode = $modeKey === 'mpesa' ? 'Mpesa' : ($modeKey === 'other' ? 'Other' : 'Cash');
        $reference = strtoupper(trim((string)($_POST['reference'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $markPaid = ($_POST['mark_paid'] ?? '') === '1';

        try {
            if ($outstanding <= 0.00001) throw new Exception('This invoice is already fully paid.');
            if (!$markPaid && $amount <= 0) throw new Exception('Enter a payment amount.');
            $paymentAmount = $markPaid ? $outstanding : $amount;
            if ($paymentAmount > $outstanding + 0.00001) {
                throw new Exception('Payment cannot exceed the outstanding balance of KES '.number_format($outstanding, 2).'.');
            }
            if ($mode === 'Mpesa' && $reference === '') {
                throw new Exception('Enter the M-Pesa transaction/receipt number.');
            }

            $conn->begin_transaction();

            $payment = record_payment(
                $conn,
                $id,
                $paymentAmount,
                $mode,
                $reference !== '' ? $reference : null,
                (int)$openShift['id']
            );

            if ($mode === 'Mpesa') {
                record_manual_mpesa_transaction(
                    $conn,
                    $id,
                    $patientId,
                    $payment['amount'],
                    $phone,
                    $reference,
                    'Manually recorded M-Pesa payment'
                );
            }

            post_payment_journal($conn, $id, $payment['amount'], $mode, $payment['payment_id']);
            $conn->commit();

            $returnTo = $_POST['return_to'] ?? '';
            if ($returnTo === 'cashier') {
                header("Location: /hospital_system/cashier/index.php?success=1&paid_invoice=".$id."&payment_id=".$payment['payment_id']);
            } elseif ($returnTo === 'aged_receivables') {
                header("Location: /hospital_system/cashier/aged_receivables.php?success=1&paid_invoice=".$id."&payment_id=".$payment['payment_id']);
            } else {
                header("Location: /hospital_system/billing/view_invoice.php?id=".$id."&success=1");
            }
            exit;
        } catch (Throwable $e) {
            if ($conn->errno || $conn->connect_errno) {
                try { $conn->rollback(); } catch (Throwable $ignored) {}
            }
            error_log('HMS payment error: '.$e->getMessage());
            $error = 'Unable to record the payment. Please verify the payment details and try again.';
        }
    }
}

$defaultAmount = number_format($outstanding, 2, '.', '');
$invoiceNumber = (string)($invoice['invoice_number'] ?? ('#'.str_pad((string)$id, 5, '0', STR_PAD_LEFT)));
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<link rel="stylesheet" href="../assets/css/finance_modules.css">
<style>
.payment-page{padding:26px 24px 42px;background:#f5f7fb;min-height:calc(100vh - 72px)}
.payment-shell{width:100%;max-width:1180px;margin:0 auto}
.payment-hero{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:16px;padding:24px 26px;margin-bottom:20px;box-shadow:0 10px 26px rgba(6,59,115,.14)}
.payment-hero h1{font-size:25px;font-weight:800;margin:0 0 4px}.payment-hero p{margin:0;color:#d9edff;font-size:13px}
.payment-hero .invoice-ref{font-size:12px;font-weight:700;color:#9edcff;margin-top:8px}
.payment-card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden}
.payment-card .card-title{font-size:15px;font-weight:800;color:#25324a;margin:0}
.customer-panel{padding:20px}.meta-label{font-size:10px;text-transform:uppercase;letter-spacing:.7px;font-weight:800;color:#667085}.meta-value{font-size:15px;font-weight:700;color:#25324a;margin-top:3px}
.balance-panel{background:#f8fafc;border-top:1px solid #edf0f5;border-bottom:1px solid #edf0f5;padding:18px 20px}
.balance-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;font-size:13px}.balance-row:last-child{margin-bottom:0}
.balance-due{font-size:25px;font-weight:900;color:#b42318}
.form-panel{padding:22px}.form-panel label{font-size:10px;text-transform:uppercase;letter-spacing:.7px;font-weight:800;color:#667085}
.form-control{border-color:#d7dee8;border-radius:8px}.form-control:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}
.amount-input{font-size:24px;font-weight:800;height:54px}
.method-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}.method-option{position:relative}.method-option input{position:absolute;opacity:0}.method-option label{display:block;border:1px solid #d7dee8;border-radius:9px;padding:12px;text-align:center;cursor:pointer;font-size:12px!important;color:#344054!important;text-transform:none!important;letter-spacing:0!important}.method-option input:checked+label{border-color:#075b9d;background:#eef6fd;color:#075b9d!important;box-shadow:0 0 0 2px rgba(7,91,157,.08)}
.mpesa-fields{display:none;background:#f8fbff;border:1px solid #dbeafe;border-radius:10px;padding:14px;margin-top:12px}
.payment-actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:20px}.payment-actions .btn{border-radius:8px;font-weight:800;padding:10px 18px}
.history-card{margin-top:20px}.history-table{margin:0}.history-table th{font-size:10px;text-transform:uppercase;color:#667085;background:#f8fafc;border-top:0}.history-table td{font-size:13px;vertical-align:middle;border-color:#edf0f5}
.empty-history{padding:30px;text-align:center;color:#98a2b3}
@media(max-width:768px){.payment-page{padding:18px 12px}.method-grid{grid-template-columns:1fr}.payment-shell{max-width:none}}
</style>

<div class="payment-page">
<div class="payment-shell">
    <div class="payment-hero">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="small text-uppercase font-weight-bold" style="letter-spacing:1.3px;color:#9edcff">Finance & Billing</div>
                <h1>Receive Payment</h1>
                <p>Record a payment against the selected invoice and update the patient's balance.</p>
                <div class="invoice-ref"><?= htmlspecialchars($invoiceNumber) ?></div>
            </div>
            <i class="fas fa-cash-register fa-2x" style="color:#9edcff"></i>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$openShift): ?>
        <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap">
            <span><i class="fas fa-lock mr-1"></i><strong>No open cashier shift.</strong> A shift must be opened before money can be received.</span>
            <a href="/hospital_system/cashier/shifts.php" class="btn btn-warning btn-sm mt-2 mt-md-0">Open Cashier Shift</a>
        </div>
    <?php endif; ?>

    <div class="payment-card">
        <div class="customer-panel">
            <div class="row">
                <div class="col-md-5 mb-3 mb-md-0"><div class="meta-label">Customer</div><div class="meta-value"><?= htmlspecialchars($patientName) ?></div><?php if ($patientNumber): ?><div class="small text-muted"><?= htmlspecialchars($patientNumber) ?></div><?php else: ?><div class="small text-muted">Walk-in / non-registered customer</div><?php endif; ?></div>
                <div class="col-md-3 mb-3 mb-md-0"><div class="meta-label">Invoice Date</div><div class="meta-value"><?= htmlspecialchars(date('d-M-Y H:i', strtotime($invoice['created_at']))) ?></div></div>
                <div class="col-md-4"><div class="meta-label">Invoice</div><div class="meta-value"><?= htmlspecialchars($invoiceNumber) ?></div></div>
            </div>
        </div>

        <div class="balance-panel">
            <div class="balance-row"><span>Total Bill</span><strong>KES <?= number_format($invoiceTotal,2) ?></strong></div>
            <div class="balance-row"><span>Already Paid</span><strong class="text-success">KES <?= number_format($totalPaid,2) ?></strong></div>
            <div class="balance-row"><span>Outstanding Balance</span><strong class="balance-due">KES <?= number_format($outstanding,2) ?></strong></div>
        </div>

        <div class="form-panel">
            <?php if ($outstanding > 0.00001): ?>
            <form method="POST" id="paymentForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="invoice_id" value="<?= $id ?>">
                <?php if (!empty($_GET['return_to'])): ?><input type="hidden" name="return_to" value="<?= htmlspecialchars((string)$_GET['return_to']) ?>"><?php endif; ?>

                <div class="form-group">
                    <label>Payment Amount (KES)</label>
                    <input type="number" name="amount" id="paymentAmount" class="form-control amount-input" min="0.01" max="<?= $defaultAmount ?>" step="0.01" value="<?= $defaultAmount ?>" required>
                    <small class="text-muted">Maximum accepted: KES <?= number_format($outstanding,2) ?></small>
                </div>

                <div class="form-group mt-4">
                    <label>Payment Method</label>
                    <div class="method-grid">
                        <div class="method-option"><input type="radio" id="methodCash" name="payment_mode" value="Cash" checked><label for="methodCash"><i class="fas fa-money-bill-wave mr-1"></i> Cash</label></div>
                        <div class="method-option"><input type="radio" id="methodMpesa" name="payment_mode" value="Mpesa"><label for="methodMpesa"><i class="fas fa-mobile-alt mr-1"></i> M-Pesa</label></div>
                        <div class="method-option"><input type="radio" id="methodOther" name="payment_mode" value="Other"><label for="methodOther"><i class="fas fa-wallet mr-1"></i> Other</label></div>
                    </div>
                </div>

                <div class="mpesa-fields" id="mpesaFields">
                    <div class="row">
                        <div class="col-md-6"><label>M-Pesa Receipt / Transaction No.</label><input type="text" name="reference" id="mpesaReference" class="form-control" maxlength="100" placeholder="e.g. QGH7XXXXXX"></div>
                        <div class="col-md-6"><label>Phone Number</label><input type="text" name="phone" class="form-control" maxlength="30" placeholder="e.g. 07XXXXXXXX"></div>
                    </div>
                </div>

                <div class="payment-actions">
                    <button type="submit" class="btn btn-primary" <?= !$openShift ? 'disabled' : '' ?>><i class="fas fa-check-circle mr-1"></i> Receive Payment</button>
                    <button type="submit" name="mark_paid" value="1" class="btn btn-success" <?= !$openShift ? 'disabled' : '' ?> onclick="return confirm('Record the full outstanding balance of KES <?= number_format($outstanding,2) ?> as paid?')"><i class="fas fa-check-double mr-1"></i> Pay Full Balance</button>
                    <a href="/hospital_system/billing/view_invoice.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-success mb-0"><i class="fas fa-check-circle mr-1"></i><strong>This invoice is fully paid.</strong> No further payment is required.</div>
                <div class="payment-actions"><a href="/hospital_system/billing/view_invoice.php?id=<?= $id ?>" class="btn btn-primary">View Invoice</a></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="payment-card history-card">
        <div class="p-3 border-bottom"><h2 class="card-title">Payment History</h2></div>
        <?php if ($paymentHistory): ?>
        <div class="table-responsive"><table class="table history-table mb-0"><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="text-right">Amount</th></tr></thead><tbody>
        <?php foreach ($paymentHistory as $payment): ?>
            <tr><td><?= htmlspecialchars(date('d-M-Y H:i', strtotime($payment['created_at']))) ?></td><td><?= htmlspecialchars((string)$payment['method']) ?></td><td><?= htmlspecialchars((string)($payment['reference'] ?: '—')) ?></td><td class="text-right font-weight-bold">KES <?= number_format((float)$payment['amount'],2) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <?php else: ?><div class="empty-history"><i class="fas fa-receipt fa-2x mb-2"></i><div>No payments have been recorded against this invoice.</div></div><?php endif; ?>
    </div>
</div>
</div>

<script>
(function(){
    const form=document.getElementById('paymentForm');
    if(!form) return;
    const mpesaFields=document.getElementById('mpesaFields');
    const mpesaReference=document.getElementById('mpesaReference');
    const radios=form.querySelectorAll('input[name="payment_mode"]');
    function updateMethod(){
        const selected=form.querySelector('input[name="payment_mode"]:checked');
        const isMpesa=selected && selected.value.toLowerCase()==='mpesa';
        mpesaFields.style.display=isMpesa?'block':'none';
        if(mpesaReference) mpesaReference.required=isMpesa;
    }
    radios.forEach(function(r){r.addEventListener('change',updateMethod);});
    updateMethod();
    form.addEventListener('submit',function(e){
        const amount=parseFloat(document.getElementById('paymentAmount').value||0);
        const max=parseFloat(document.getElementById('paymentAmount').max||0);
        if(amount<=0 || amount>max+0.00001){e.preventDefault();alert('Enter a valid amount not exceeding the outstanding balance.');}
    });
})();
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>