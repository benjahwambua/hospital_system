<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';

require_login();
require_module_access($conn, 'finance', 'view');

$paymentId = (int)($_GET['id'] ?? 0);
if ($paymentId <= 0) { http_response_code(400); exit('Invalid payment ID.'); }

$stmt = $conn->prepare('SELECT p.*, i.id invoice_id FROM payments p INNER JOIN invoices i ON i.id=p.invoice_id WHERE p.id=? LIMIT 1');
$stmt->bind_param('i', $paymentId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$payment) { http_response_code(404); exit('Payment not found.'); }

$invoiceId = (int)$payment['invoice_id'];
$stmt = $conn->prepare('SELECT * FROM invoices WHERE id=? LIMIT 1');
$stmt->bind_param('i', $invoiceId);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

$customer = invoice_get_customer_info($conn, $invoice);
$patientId = (int)($customer['patient_id'] ?? 0);
$patientName = (string)($customer['patient_name'] ?? 'Walk-in Customer');
$patientNumber = (string)($customer['patient_number'] ?? '');

$coverage = $patientId ? get_patient_active_coverage($conn, $patientId) : null;
$payerName = $planName = $memberNumber = '';
if ($coverage) {
    $payerId=(int)$coverage['payer_id']; $planId=(int)($coverage['plan_id']??0);
    $memberNumber=(string)($coverage['member_number']??'');
    $s=$conn->prepare('SELECT p.payer_name, pp.plan_name FROM payers p LEFT JOIN payer_plans pp ON pp.id=? AND pp.payer_id=p.id WHERE p.id=? LIMIT 1');
    if($s){$s->bind_param('ii',$planId,$payerId);$s->execute();$r=$s->get_result()->fetch_assoc();$s->close();$payerName=(string)($r['payer_name']??'');$planName=(string)($r['plan_name']??'');}
}

$amount=(float)$payment['amount'];
$method=(string)($payment['method']??'');
$reference=trim((string)($payment['reference']??''));
$paymentDate=!empty($payment['created_at'])?date('d M Y H:i',strtotime($payment['created_at'])):date('d M Y H:i');
$receiptNumber='RCT-'.str_pad((string)$paymentId,6,'0',STR_PAD_LEFT);
$invoiceNumber='INV-'.str_pad((string)$invoiceId,6,'0',STR_PAD_LEFT);
$logoExists=is_file(__DIR__.'/../assets/img/logo.png');
$logoSrc='/hospital_system/assets/img/logo.png';
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title><?=htmlspecialchars($receiptNumber)?> - Emaqure Medical Centre</title>
<style>
@page{size:A4 portrait;margin:12mm}*{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#172033;font:12px Arial,Helvetica,sans-serif}.toolbar{width:210mm;margin:18px auto 10px;text-align:right}.toolbar a,.toolbar button{border:0;border-radius:5px;padding:9px 14px;text-decoration:none;cursor:pointer;font-weight:700;font-size:12px}.back{background:#e2e8f0;color:#334155}.print{background:#075b9d;color:#fff;margin-left:7px}.paper{width:210mm;min-height:297mm;margin:auto;background:#fff;padding:12mm;box-shadow:0 5px 25px rgba(15,23,42,.12)}.topbar{height:5px;background:#075b9d;margin:-12mm -12mm 10mm}.branding{display:flex;justify-content:space-between;gap:18px;padding-bottom:12px;border-bottom:1px solid #cbd5e1}.brand{display:flex;align-items:center;gap:12px}.logo{width:62px;height:62px;object-fit:contain}.name{margin:0;color:#075b9d;font-size:22px;text-transform:uppercase}.sub{margin:4px 0 0;color:#475569;font-size:10.5px}.contact{text-align:right;color:#475569;font-size:10px;line-height:1.55}.contact strong{display:block;color:#172033}.head{display:flex;justify-content:space-between;align-items:end;margin:18px 0}.title{margin:0;font-size:25px;text-transform:uppercase}.receiptno{color:#64748b;margin-top:4px}.paid{border:1px solid #86efac;background:#f0fdf4;color:#166534;padding:7px 13px;border-radius:4px;font-weight:800}.grid{display:grid;grid-template-columns:1fr 1fr;border:1px solid #cbd5e1}.box{padding:11px 13px;border-bottom:1px solid #cbd5e1}.box:nth-child(odd){border-right:1px solid #cbd5e1}.box:nth-last-child(-n+2){border-bottom:0}.label{display:block;color:#64748b;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px}.value{font-weight:700;font-size:12px}.amount{margin:28px 0;border:2px solid #075b9d;text-align:center;padding:22px}.amount small{display:block;color:#64748b;text-transform:uppercase;font-weight:800;letter-spacing:1px}.amount strong{display:block;color:#075b9d;font-size:31px;margin-top:6px}.section{margin-top:18px;border:1px solid #cbd5e1}.section h3{margin:0;padding:8px 10px;background:#f1f5f9;color:#075b9d;font-size:10px;text-transform:uppercase}.table{width:100%;border-collapse:collapse}.table td{padding:8px 10px;border-top:1px solid #e2e8f0}.right{text-align:right}.note{margin-top:20px;color:#475569;font-size:10px}.sign{display:grid;grid-template-columns:1fr 1fr;gap:35px;margin-top:60px}.line{text-align:center;border-top:1px solid #334155;padding-top:7px;color:#475569;font-size:10px}.stamp{width:82px;height:82px;margin:0 auto 12px;border:2px dashed #075b9d;border-radius:50%;display:flex;align-items:center;justify-content:center;text-align:center;color:#075b9d;font-size:8px;font-weight:800;opacity:.55}.footer{margin-top:30px;border-top:1px solid #cbd5e1;padding-top:8px;display:flex;justify-content:space-between;color:#64748b;font-size:8.5px}@media print{body{background:#fff}.toolbar{display:none}.paper{width:100%;min-height:0;margin:0;padding:0;box-shadow:none}.topbar{margin:0 0 10mm}.paid{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style></head><body>
<div class="toolbar"><a class="back" href="/hospital_system/billing/view_invoice.php?id=<?=$invoiceId?>">Back to Invoice</a><button class="print" onclick="window.print()">Print / Save PDF</button></div>
<main class="paper"><div class="topbar"></div>
<section class="branding"><div class="brand"><?php if($logoExists):?><img class="logo" src="<?=htmlspecialchars($logoSrc)?>" alt="Emaqure Medical Centre"><?php endif;?><div><h1 class="name">Emaqure Medical Centre</h1><p class="sub">Quality Healthcare • Patient-Centred Service</p></div></div><div class="contact"><strong>Medical Centre</strong>Biashara Street, Opposite Old Naiwe School, Mlolongo<br>+254 793 069 565<br>emaquremedicalcentre@gmail.com</div></section>
<section class="head"><div><h2 class="title">Payment Receipt</h2><div class="receiptno"><?=htmlspecialchars($receiptNumber)?></div></div><div class="paid">PAYMENT RECEIVED</div></section>
<section class="grid">
<div class="box"><span class="label">Patient / Customer</span><span class="value"><?=htmlspecialchars($patientName)?></span></div>
<div class="box"><span class="label">Payment Date</span><span class="value"><?=htmlspecialchars($paymentDate)?></span></div>
<div class="box"><span class="label">Patient Number</span><span class="value"><?=htmlspecialchars($patientNumber?:'—')?></span></div>
<div class="box"><span class="label">Invoice</span><span class="value"><?=htmlspecialchars($invoiceNumber)?></span></div>
<div class="box"><span class="label">Payment Method</span><span class="value"><?=htmlspecialchars($method?:'Not recorded')?></span></div>
<div class="box"><span class="label">Reference</span><span class="value"><?=htmlspecialchars($reference?:'—')?></span></div>
<?php if($payerName||$planName||$memberNumber):?><div class="box"><span class="label">Payer / Plan</span><span class="value"><?=htmlspecialchars($payerName?:'—')?><?= $planName?' / '.htmlspecialchars($planName):''?></span></div><div class="box"><span class="label">Member Number</span><span class="value"><?=htmlspecialchars($memberNumber?:'—')?></span></div><?php endif;?>
</section>
<div class="amount"><small>Amount Received</small><strong>KES <?=number_format($amount,2)?></strong></div>
<section class="section"><h3>Transaction Details</h3><table class="table"><tr><td>Payment Receipt</td><td class="right"><strong><?=htmlspecialchars($receiptNumber)?></strong></td></tr><tr><td>Invoice</td><td class="right"><?=htmlspecialchars($invoiceNumber)?></td></tr><tr><td>Method</td><td class="right"><?=htmlspecialchars($method?:'Not recorded')?></td></tr><tr><td>Reference</td><td class="right"><?=htmlspecialchars($reference?:'—')?></td></tr></table></section>
<div class="note"><strong>Note:</strong> This receipt confirms the payment recorded against the invoice shown above. It is not a replacement for the invoice. Please quote the receipt or transaction reference for payment enquiries.</div>
<section class="sign"><div><div class="stamp">OFFICIAL<br>HOSPITAL<br>STAMP</div><div class="line">Hospital Stamp / Authorized Officer</div></div><div><div style="height:94px"></div><div class="line">Patient / Guardian</div></div></section>
<footer class="footer"><span>Receipt <?=htmlspecialchars($receiptNumber)?> • Emaqure Medical Centre</span><span>Generated <?=date('d M Y H:i')?></span></footer>
</main></body></html>