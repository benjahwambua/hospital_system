<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';

require_login();
require_module_access($conn, 'finance', 'view');

$invoiceId = max(0, (int)($_GET['id'] ?? 0));
if ($invoiceId <= 0) { http_response_code(400); exit('Invalid invoice ID.'); }

$stmt = $conn->prepare('SELECT * FROM invoices WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $invoiceId);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$invoice) { http_response_code(404); exit('Invoice not found.'); }

$customer = invoice_get_customer_info($conn, $invoice);
$patientName = (string)($customer['patient_name'] ?? 'Walk-in Customer');
$patientId = (int)($customer['patient_id'] ?? 0);
$patientNumber = (string)($customer['patient_number'] ?? '');

$coverage = $patientId ? get_patient_active_coverage($conn, $patientId) : null;
$payerName = $planName = $memberNumber = '';
if ($coverage) {
    $payerId = (int)$coverage['payer_id'];
    $planId = (int)($coverage['plan_id'] ?? 0);
    $memberNumber = (string)($coverage['member_number'] ?? '');
    $stmt = $conn->prepare('SELECT p.payer_name, pp.plan_name FROM payers p LEFT JOIN payer_plans pp ON pp.id=? AND pp.payer_id=p.id WHERE p.id=? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('ii', $planId, $payerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $payerName = (string)($row['payer_name'] ?? '');
        $planName = (string)($row['plan_name'] ?? '');
    }
}

$items = invoice_load_items($conn, $invoice);
$invoiceTotal = (float)($invoice['total'] ?? 0);
if ($invoiceTotal <= 0) foreach ($items as $item) $invoiceTotal += (float)($item['amount'] ?? 0);

$totalPaid = 0.0; $totalRefunded = 0.0; $paymentHistory = []; $refundHistory = [];

$stmt = $conn->prepare('SELECT amount, method, reference, created_at FROM payments WHERE invoice_id=? ORDER BY created_at ASC, id ASC');
if ($stmt) {
    $stmt->bind_param('i', $invoiceId); $stmt->execute(); $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $paymentHistory[] = $row; $totalPaid += (float)$row['amount']; }
    $stmt->close();
}
$stmt = $conn->prepare("SELECT amount, reference, created_at FROM payment_refunds WHERE invoice_id=? AND status='Approved' ORDER BY created_at ASC, id ASC");
if ($stmt) {
    $stmt->bind_param('i', $invoiceId); $stmt->execute(); $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $refundHistory[] = $row; $totalRefunded += (float)$row['amount']; }
    $stmt->close();
}

$netPaid = max($totalPaid - $totalRefunded, 0);
$outstanding = max($invoiceTotal - $netPaid, 0);
$status = ($invoiceTotal > 0 && $outstanding <= 0.00001) ? 'PAID' : ($netPaid > 0 ? 'PARTIAL' : 'UNPAID');
$invoiceNumber = 'INV-' . str_pad((string)$invoiceId, 6, '0', STR_PAD_LEFT);
$invoiceDate = !empty($invoice['created_at']) ? date('d M Y', strtotime((string)$invoice['created_at'])) : date('d M Y');
$invoiceTime = !empty($invoice['created_at']) ? date('H:i', strtotime((string)$invoice['created_at'])) : '';
$lastPayment = $paymentHistory ? end($paymentHistory) : null;
$paymentMethod = trim((string)($invoice['payment_mode'] ?? ''));
if ($paymentMethod === '' && $lastPayment) $paymentMethod = (string)($lastPayment['method'] ?? '');
if ($paymentMethod === '') $paymentMethod = 'Not recorded';
$logoExists = is_file(__DIR__ . '/../assets/img/logo.png');
$logoSrc = '/hospital_system/assets/img/logo.png';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($invoiceNumber) ?> - Emaqure Medical Centre</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
@page{size:A4 portrait;margin:12mm}*{box-sizing:border-box}html,body{margin:0;padding:0;background:#f1f5f9;color:#172033;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.45}.toolbar{width:210mm;margin:18px auto 10px;display:flex;justify-content:flex-end;gap:8px}.toolbar a,.toolbar button{border:0;border-radius:5px;padding:9px 14px;text-decoration:none;cursor:pointer;font-weight:700;font-size:12px}.back{background:#e2e8f0;color:#334155}.print{background:#075b9d;color:#fff}.paper{width:210mm;min-height:297mm;margin:0 auto 20px;padding:12mm;background:#fff;box-shadow:0 5px 25px rgba(15,23,42,.12)}.topbar{height:5px;background:#075b9d;margin:-12mm -12mm 10mm}.branding{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;padding-bottom:12px;border-bottom:1px solid #cbd5e1}.brand-left{display:flex;align-items:center;gap:12px;min-width:0}.logo{width:62px;height:62px;object-fit:contain}.hospital-name{margin:0;color:#075b9d;font-size:22px;line-height:1.1;text-transform:uppercase}.hospital-subtitle{margin:4px 0 0;color:#475569;font-size:10.5px}.contact{text-align:right;color:#475569;font-size:10px;line-height:1.55}.contact strong{color:#172033;display:block;font-size:11px;margin-bottom:2px}.document-head{display:flex;justify-content:space-between;align-items:flex-end;margin:17px 0 13px}.document-title{margin:0;color:#172033;font-size:24px;text-transform:uppercase;letter-spacing:.5px}.document-label{color:#64748b;font-size:10px;margin-top:3px}.status{border:1px solid #94a3b8;border-radius:4px;padding:6px 12px;font-weight:800;font-size:10px;letter-spacing:.5px}.status-paid{color:#166534;border-color:#86efac;background:#f0fdf4}.status-partial{color:#92400e;border-color:#fcd34d;background:#fffbeb}.status-unpaid{color:#991b1b;border-color:#fca5a5;background:#fef2f2}.info-grid{display:grid;grid-template-columns:1fr 1fr;border:1px solid #cbd5e1;margin-bottom:16px}.info-box{padding:10px 12px;min-height:56px}.info-box:nth-child(odd){border-right:1px solid #cbd5e1}.info-box:nth-child(-n+2){border-bottom:1px solid #cbd5e1}.label{display:block;color:#64748b;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px}.value{font-size:12px;font-weight:700;color:#172033}table{width:100%;border-collapse:collapse}.items thead th{background:#075b9d;color:#fff;padding:8px 7px;font-size:10px;text-transform:uppercase;text-align:left;border:1px solid #075b9d}.items td{padding:8px 7px;border:1px solid #dbe2ea;vertical-align:top}.items tbody tr:nth-child(even){background:#f8fafc}.right{text-align:right!important}.center{text-align:center!important}.totals-area{display:flex;justify-content:flex-end;margin-top:12px}.totals{width:88mm}.total-row{display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #e2e8f0}.balance{margin-top:5px;padding:9px 0;border-top:2px solid #075b9d;border-bottom:2px solid #075b9d;display:flex;justify-content:space-between;color:#075b9d;font-size:15px;font-weight:800}.settlement{margin-top:18px;border:1px solid #cbd5e1;page-break-inside:avoid}.section-title{background:#f1f5f9;color:#075b9d;padding:7px 10px;border-bottom:1px solid #cbd5e1;font-weight:800;font-size:10px;text-transform:uppercase;letter-spacing:.5px}.settlement td,.settlement th{padding:7px 9px;border-bottom:1px solid #e2e8f0;text-align:left;font-size:10.5px}.settlement th{color:#64748b;font-size:9px;text-transform:uppercase}.notes{margin-top:18px;font-size:9.5px;color:#475569}.signatures{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-top:34px;page-break-inside:avoid}.signature{text-align:center;padding-top:25px;border-top:1px solid #334155;color:#475569;font-size:9.5px}.stamp{width:78px;height:78px;margin:-5px auto 8px;border:2px dashed #075b9d;border-radius:50%;display:flex;align-items:center;justify-content:center;text-align:center;color:#075b9d;font-size:8px;font-weight:800;opacity:.55}.footer{margin-top:22px;padding-top:8px;border-top:1px solid #cbd5e1;display:flex;justify-content:space-between;gap:10px;color:#64748b;font-size:8.5px}.empty{padding:15px!important;text-align:center;color:#64748b}@media print{html,body{background:#fff}.toolbar{display:none!important}.paper{width:100%;min-height:0;margin:0;padding:0;box-shadow:none}.topbar{margin:0 0 10mm}.items thead th{background:#075b9d!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.status,.section-title,.items tbody tr:nth-child(even){-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
</head>
<body>
<div class="toolbar">
<a class="back" href="/hospital_system/billing/view_invoice.php?id=<?= $invoiceId ?>">Back to Invoice</a>
<button class="print" type="button" onclick="window.print()">Print / Save PDF</button>
</div>
<main class="paper">
<div class="topbar"></div>
<section class="branding">
<div class="brand-left">
<?php if ($logoExists): ?><img class="logo" src="<?= htmlspecialchars($logoSrc) ?>" alt="Emaqure Medical Centre"><?php endif; ?>
<div><h1 class="hospital-name">Emaqure Medical Centre</h1><p class="hospital-subtitle">Quality Healthcare • Patient-Centred Service</p></div>
</div>
<div class="contact"><strong>Medical Centre</strong>Biashara Street, Opposite Old Naiwe School, Mlolongo<br>+254 793 069 565<br>emaquremedicalcentre@gmail.com</div>
</section>
<section class="document-head"><div><h2 class="document-title">Official Invoice</h2><div class="document-label"><?= htmlspecialchars($invoiceNumber) ?></div></div><div class="status status-<?= strtolower($status) ?>"><?= htmlspecialchars($status) ?></div></section>
<section class="info-grid">
<div class="info-box"><span class="label">Patient / Customer</span><span class="value"><?= htmlspecialchars($patientName) ?></span></div>
<div class="info-box"><span class="label">Invoice Date</span><span class="value"><?= htmlspecialchars($invoiceDate) ?><?= $invoiceTime ? ' at '.htmlspecialchars($invoiceTime) : '' ?></span></div>
<div class="info-box"><span class="label">Patient Number</span><span class="value"><?= htmlspecialchars($patientNumber ?: '—') ?></span></div>
<div class="info-box"><span class="label">Payment Method</span><span class="value"><?= htmlspecialchars($paymentMethod) ?></span></div>
<?php if ($payerName || $planName || $memberNumber): ?>
<div class="info-box"><span class="label">Payer / Plan</span><span class="value"><?= htmlspecialchars($payerName ?: '—') ?><?= $planName ? ' / '.htmlspecialchars($planName) : '' ?></span></div>
<div class="info-box"><span class="label">Member Number</span><span class="value"><?= htmlspecialchars($memberNumber ?: '—') ?></span></div>
<?php endif; ?>
</section>
<table class="items">
<thead><tr><th style="width:8%">#</th><th>Description</th><th style="width:13%" class="center">Qty</th><th style="width:18%" class="right">Unit Price (KES)</th><th style="width:20%" class="right">Amount (KES)</th></tr></thead>
<tbody>
<?php if ($items): foreach ($items as $index=>$item): ?>
<tr><td class="center"><?= $index+1 ?></td><td><?= htmlspecialchars((string)($item['description'] ?? 'Invoice Item')) ?></td><td class="center"><?= rtrim(rtrim(number_format((float)($item['quantity'] ?? 1),4),'0'),'.') ?></td><td class="right"><?= number_format((float)($item['price'] ?? 0),2) ?></td><td class="right"><?= number_format((float)($item['amount'] ?? 0),2) ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="5" class="empty">No invoice items were found for this invoice.</td></tr><?php endif; ?>
</tbody>
</table>
<section class="totals-area"><div class="totals">
<div class="total-row"><span>Invoice Total</span><strong>KES <?= number_format($invoiceTotal,2) ?></strong></div>
<div class="total-row"><span>Total Payments</span><strong>KES <?= number_format($totalPaid,2) ?></strong></div>
<?php if ($totalRefunded>0): ?><div class="total-row"><span>Approved Refunds</span><strong>- KES <?= number_format($totalRefunded,2) ?></strong></div><?php endif; ?>
<div class="total-row"><span>Net Paid</span><strong>KES <?= number_format($netPaid,2) ?></strong></div>
<div class="balance"><span>Outstanding</span><span>KES <?= number_format($outstanding,2) ?></span></div>
</div></section>
<section class="settlement">
<div class="section-title">Payment / Receipt Record</div>
<table><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="right">Amount (KES)</th></tr></thead><tbody>
<?php foreach ($paymentHistory as $payment): ?><tr><td><?= htmlspecialchars((string)($payment['created_at'] ?? '')) ?></td><td><?= htmlspecialchars((string)($payment['method'] ?? '')) ?></td><td><?= htmlspecialchars((string)($payment['reference'] ?? '—')) ?></td><td class="right"><?= number_format((float)$payment['amount'],2) ?></td></tr><?php endforeach; ?>
<?php foreach ($refundHistory as $refund): ?><tr><td><?= htmlspecialchars((string)($refund['created_at'] ?? '')) ?></td><td>Approved Refund</td><td><?= htmlspecialchars((string)($refund['reference'] ?? '—')) ?></td><td class="right">- <?= number_format((float)$refund['amount'],2) ?></td></tr><?php endforeach; ?>
<?php if (!$paymentHistory && !$refundHistory): ?><tr><td colspan="4" class="empty">No payment has been recorded against this invoice.</td></tr><?php endif; ?>
</tbody></table>
</section>
<div class="notes"><strong>Important:</strong> This document is generated from the invoice's recorded financial transactions. Please retain it for your records. Payment references should be quoted when making enquiries.</div>
<section class="signatures"><div class="signature">Cashier / Authorized Officer</div><div><div class="stamp">OFFICIAL<br>HOSPITAL<br>STAMP</div></div><div class="signature">Patient / Guardian</div></section>
<footer class="footer"><span>Invoice <?= htmlspecialchars($invoiceNumber) ?> • Emaqure Medical Centre</span><span>Generated <?= date('d M Y H:i') ?></span></footer>
</main>
</body>
</html>