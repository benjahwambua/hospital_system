<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isSuper = !empty($_SESSION['is_super']) && (int)$_SESSION['is_super'] === 1;
if (!$isSuper && !in_array($role, ['admin', 'cashier'], true)) {
    http_response_code(403);
    die('Access denied. Only the cashier or administrator can print payment receipts.');
}

$paymentId = (int)($_GET['id'] ?? 0);
if ($paymentId <= 0) die('Invalid payment ID.');

$stmt = $conn->prepare("SELECT pay.*, p.full_name AS patient_name, p.patient_number, p.phone,
                               i.total AS invoice_total, i.visit_id,
                               v.visit_number, v.visit_type, v.clinic_category,
                               cs.id AS shift_id, cs.opened_at, cs.closed_at,
                               u.full_name AS cashier_name
                        FROM payments pay
                        LEFT JOIN patients p ON p.id = pay.patient_id
                        LEFT JOIN invoices i ON i.id = pay.invoice_id
                        LEFT JOIN visits v ON v.id = i.visit_id
                        LEFT JOIN cashier_shifts cs ON cs.id = pay.cashier_shift_id
                        LEFT JOIN users u ON u.id = cs.cashier_id
                        WHERE pay.id=? LIMIT 1");
if (!$stmt) die('Unable to load payment receipt.');
$stmt->bind_param('i', $paymentId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) die('Payment not found.');

$receiptRef = trim((string)($payment['reference'] ?? ''));
$invoiceId = (int)($payment['invoice_id'] ?? 0);
$patientId = (int)($payment['patient_id'] ?? 0);

$mpesaReceipt = '';
if (stripos((string)$payment['method'], 'mpesa') !== false && $invoiceId > 0) {
    $m = $conn->prepare("SELECT mpesa_receipt, phone FROM mpesa_transactions WHERE invoice_id=? AND amount=? ORDER BY id DESC LIMIT 1");
    if ($m) {
        $amt = (float)$payment['amount'];
        $m->bind_param('id', $invoiceId, $amt);
        $m->execute();
        $mr = $m->get_result()->fetch_assoc();
        $m->close();
        if ($mr) {
            $mpesaReceipt = trim((string)($mr['mpesa_receipt'] ?? ''));
            if (empty($payment['phone'])) $payment['phone'] = $mr['phone'] ?? '';
        }
    }
}

$hospitalName = 'Emaqure Medical Centre';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Receipt #<?= $paymentId ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:Arial,sans-serif;background:#f2f4f7;margin:0;color:#222}
.receipt{width:80mm;max-width:100%;margin:20px auto;background:#fff;padding:12mm 8mm;box-sizing:border-box}
.center{text-align:center}.muted{color:#666;font-size:11px}.line{border-top:1px dashed #777;margin:10px 0}
h2{margin:0 0 4px;font-size:18px}h3{margin:12px 0 6px;font-size:15px}
.row{display:flex;justify-content:space-between;gap:12px;margin:5px 0;font-size:12px}
.amount{font-size:22px;font-weight:700;margin:12px 0}
.badge{display:inline-block;padding:4px 9px;border:1px solid #333;border-radius:12px;font-size:11px;font-weight:700}
.footer{margin-top:15px;text-align:center;font-size:10px;color:#666}
.actions{text-align:center;margin:15px auto;max-width:80mm}
button{border:0;padding:9px 14px;border-radius:5px;cursor:pointer}
@media print{body{background:#fff}.receipt{margin:0;box-shadow:none}.actions{display:none}}
</style>
</head>
<body>
<div class="receipt">
    <div class="center">
        <h2><?= htmlspecialchars($hospitalName) ?></h2>
        <div class="muted">Official Payment Receipt</div>
        <div class="muted">Biashara Street, Opposite Old Naiwe School, Mlolongo</div>
        <div class="muted">+254793069565</div>
    </div>

    <div class="line"></div>
    <div class="row"><span>Receipt No.</span><strong>RCPT-<?= str_pad((string)$paymentId, 6, '0', STR_PAD_LEFT) ?></strong></div>
    <div class="row"><span>Date</span><span><?= date('d-M-Y H:i:s', strtotime($payment['created_at'])) ?></span></div>
    <div class="row"><span>Invoice</span><span>#<?= str_pad((string)$invoiceId, 5, '0', STR_PAD_LEFT) ?></span></div>
    <div class="row"><span>Patient</span><span><?= htmlspecialchars($payment['patient_name'] ?: 'Unknown') ?></span></div>
    <div class="row"><span>Patient No.</span><span><?= htmlspecialchars($payment['patient_number'] ?: 'N/A') ?></span></div>
    <?php if (!empty($payment['visit_number'])): ?><div class="row"><span>Visit</span><span><?= htmlspecialchars($payment['visit_number']) ?></span></div><?php endif; ?>

    <div class="line"></div>
    <div class="center">
        <div class="muted">AMOUNT RECEIVED</div>
        <div class="amount">KSH <?= number_format((float)$payment['amount'], 2) ?></div>
        <span class="badge"><?= htmlspecialchars((string)$payment['method']) ?></span>
    </div>

    <?php if ($mpesaReceipt || $receiptRef): ?>
        <div class="line"></div>
        <?php if ($mpesaReceipt): ?><div class="row"><span>M-Pesa Receipt</span><strong><?= htmlspecialchars($mpesaReceipt) ?></strong></div><?php endif; ?>
        <?php if ($receiptRef && $receiptRef !== $mpesaReceipt): ?><div class="row"><span>Reference</span><span><?= htmlspecialchars($receiptRef) ?></span></div><?php endif; ?>
        <?php if (!empty($payment['phone'])): ?><div class="row"><span>Phone</span><span><?= htmlspecialchars((string)$payment['phone']) ?></span></div><?php endif; ?>
    <?php endif; ?>

    <div class="line"></div>
    <div class="row"><span>Cashier</span><span><?= htmlspecialchars($payment['cashier_name'] ?: 'Legacy / Unassigned') ?></span></div>
    <?php if (!empty($payment['shift_id'])): ?><div class="row"><span>Shift</span><span>#<?= (int)$payment['shift_id'] ?></span></div><?php endif; ?>
    <div class="footer">Thank you for choosing <?= htmlspecialchars($hospitalName) ?>.<br>This receipt confirms payment recorded in the hospital system.</div>
</div>
<div class="actions"><button onclick="window.print()">Print Receipt</button></div>
</body>
</html>