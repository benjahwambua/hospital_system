<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$invoiceId = max(0, (int)($_GET['id'] ?? 0));
if ($invoiceId <= 0) {
    die('Invalid invoice ID.');
}

$stmt = $conn->prepare('SELECT * FROM invoices WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $invoiceId);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    die('Invoice not found.');
}

$patientName = 'Walk-in Customer';
$patientId = null;
$patientNumber = 'N/A';

if (!empty($invoice['patient_id'])) {
    $stmt = $conn->prepare('SELECT id, patient_number, full_name FROM patients WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $invoice['patient_id']);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($patient) {
        $patientName = $patient['full_name'];
        $patientNumber = $patient['patient_number'] ?? 'N/A';
        $patientId = (int)$patient['id'];
    }
} elseif (!empty($invoice['walkin_id'])) {
    $stmt = $conn->prepare('SELECT full_name FROM walkin_customers WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $invoice['walkin_id']);
    $stmt->execute();
    $walkin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($walkin) {
        $patientName = $walkin['full_name'];
    }
}

$items = [];
$stmt = $conn->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $invoiceId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $items[] = [
        'description' => $row['description'] ?? 'Invoice Item',
        'quantity' => (float)($row['quantity'] ?? ($row['qty'] ?? 1)),
        'price' => (float)($row['price'] ?? ($row['unit_price'] ?? 0)),
        'amount' => (float)($row['total'] ?? ($row['amount'] ?? 0)),
        'source' => $row['source'] ?? 'invoice',
    ];
}
$stmt->close();

if (!$items && $patientId) {
    $svcRes = $conn->query("SELECT sm.service_name AS description, 1 AS quantity, ps.price AS price, ps.price AS amount, 'service' AS source FROM patient_services ps JOIN services_master sm ON ps.service_id = sm.id WHERE ps.patient_id = {$patientId} AND ps.status != 'Cancelled' ORDER BY ps.created_at ASC");
    if ($svcRes) {
        while ($row = $svcRes->fetch_assoc()) {
            $items[] = [
                'description' => $row['description'],
                'quantity' => (float)$row['quantity'],
                'price' => (float)$row['price'],
                'amount' => (float)$row['amount'],
                'source' => $row['source'],
            ];
        }
    }

    $rxRes = $conn->query("SELECT s.drug_name AS description, pr.quantity AS quantity, s.selling_price AS price, (pr.quantity * s.selling_price) AS amount, 'pharmacy' AS source FROM prescriptions pr JOIN pharmacy_stock s ON pr.medicine_id = s.id WHERE pr.patient_id = {$patientId} ORDER BY pr.created_at ASC");
    if ($rxRes) {
        while ($row = $rxRes->fetch_assoc()) {
            $items[] = [
                'description' => $row['description'],
                'quantity' => (float)$row['quantity'],
                'price' => (float)$row['price'],
                'amount' => (float)$row['amount'],
                'source' => $row['source'],
            ];
        }
    }
}


if ($patientId) {
    $hasConsultation = false;
    foreach ($items as $item) {
        if (stripos((string)($item['description'] ?? ''), 'consultation') !== false) {
            $hasConsultation = true;
            break;
        }
    }

    if (!$hasConsultation) {
        $items[] = [
            'description' => 'Consultation Fee',
            'quantity' => 1.0,
            'price' => 200.0,
            'amount' => 200.0,
            'source' => 'service',
        ];
    }
}

$invoiceTotal = 0.0;
foreach ($items as $item) {
    $invoiceTotal += (float)$item['amount'];
}
if ($invoiceTotal <= 0 && isset($invoice['total'])) {
    $invoiceTotal = (float)$invoice['total'];
}

$totalPaid = 0.0;
$paymentHistory = [];
if ($patientId) {
    $stmt = $conn->prepare('SELECT amount, method, created_at, paid FROM billing WHERE patient_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $patientId);
    $stmt->execute();
    $payRes = $stmt->get_result();
    while ($row = $payRes->fetch_assoc()) {
        $paymentHistory[] = $row;
        if (!empty($row['paid'])) {
            $totalPaid += (float)$row['amount'];
        }
    }
    $stmt->close();
} elseif (strtolower((string)($invoice['status'] ?? '')) === 'paid') {
    $totalPaid = $invoiceTotal;
}

$outstandingBalance = max($invoiceTotal - $totalPaid, 0);
$paymentMode = trim((string)($invoice['payment_mode'] ?? ''));
if ($paymentMode === '' && $paymentHistory) {
    $paymentMode = (string)($paymentHistory[0]['method'] ?? 'Not recorded');
}
if ($paymentMode === '') {
    $paymentMode = 'Not recorded';
}

$normalizePaymentMethod = static function (?string $method): string {
    $value = strtolower(trim((string)$method));
    if ($value === '') {
        return 'cash';
    }
    if (str_contains($value, 'mpesa') || str_contains($value, 'm-pesa')) {
        return 'mpesa';
    }
    if (str_contains($value, 'insurance') || str_contains($value, 'sha') || str_contains($value, 'nhif')) {
        return 'insurance';
    }
    return 'cash';
};

$receivedByMethod = [
    'cash' => 0.0,
    'mpesa' => 0.0,
    'insurance' => 0.0,
];

if ($paymentHistory) {
    foreach ($paymentHistory as $payment) {
        if (!empty($payment['paid'])) {
            $bucket = $normalizePaymentMethod((string)($payment['method'] ?? ''));
            $receivedByMethod[$bucket] += (float)$payment['amount'];
        }
    }
}

if ($totalPaid > 0 && array_sum($receivedByMethod) <= 0.00001) {
    $bucket = $normalizePaymentMethod($paymentMode);
    $receivedByMethod[$bucket] = $totalPaid;
}

$receivedRows = [];
foreach (['cash' => 'Cash', 'mpesa' => 'Mpesa', 'insurance' => 'Insurance'] as $key => $label) {
    if (($receivedByMethod[$key] ?? 0) > 0) {
        $receivedRows[] = ['label' => $label, 'amount' => (float)$receivedByMethod[$key]];
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
.invoice-container { max-width: 980px; margin: 30px auto; padding: 0 20px; }
.invoice-card { background:#fff; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.08); padding:30px; position:relative; overflow:hidden; }
.watermark { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%) rotate(-30deg); opacity:.05; width:60%; pointer-events:none; z-index:0; }
.hospital-branding,.invoice-header,.invoice-meta,.table-container,.total-section,.invoice-extra,.invoice-footer,.payment-panel { position:relative; z-index:2; }
.hospital-branding { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:20px; }
.hospital-logo img { max-height:75px; }
.hospital-details { text-align:right; }
.hospital-details h2 { margin:0; font-size:22px; text-transform:uppercase; }
.hospital-details p { margin:2px 0; font-size:13px; color:#555; }
.invoice-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; padding-bottom:20px; border-bottom:2px solid #007bff; }
.invoice-title { font-size:28px; color:#007bff; margin:0; }
.invoice-number { font-size:14px; color:#666; }
.invoice-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; margin-bottom:24px; padding:18px; background:rgba(248,249,250,.9); border-radius:8px; }
.meta-label { font-size:12px; font-weight:700; color:#666; text-transform:uppercase; margin-bottom:5px; }
.meta-value { font-size:16px; color:#333; font-weight:500; }
.payment-chip { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:999px; background:#e8f5e9; color:#1b5e20; font-weight:700; text-transform:uppercase; font-size:12px; }
.status-badge { padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600; text-transform:uppercase; }
.status-paid { background:#d4edda; color:#155724; }
.status-unpaid,.status-partial { background:#fff3cd; color:#856404; }
.invoice-table { width:100%; border-collapse:collapse; }
.invoice-table thead { background:#007bff; color:#fff; }
.invoice-table th,.invoice-table td { padding:12px; border-bottom:1px solid #eee; }
.amount-col { text-align:right; }
.total-section { display:flex; justify-content:flex-end; margin-top:24px; }
.total-box { width:360px; padding:20px; background:#f8f9fa; border-radius:8px; border:1px solid #ddd; }
.total-row { display:flex; justify-content:space-between; margin-bottom:8px; }
.total-final { margin-top:10px; padding-top:10px; border-top:2px solid #007bff; font-size:22px; font-weight:700; color:#007bff; }
.payment-panel { margin-top:28px; display:grid; grid-template-columns:1fr; gap:20px; }
.panel-card { background:#f8fbff; border:1px solid #dbeafe; border-radius:10px; padding:18px; }
.panel-card h4 { margin:0 0 12px; color:#1d4ed8; }
.history-table { width:100%; border-collapse:collapse; font-size:13px; }
.history-table th,.history-table td { padding:10px 8px; border-bottom:1px solid #e5e7eb; text-align:left; }
.invoice-extra { display:flex; justify-content:space-between; align-items:flex-end; margin-top:40px; }
.qr-section { text-align:center; font-size:10px; color:#666; }
.signature-section { text-align:center; width:250px; }
.signature-line { border-top:1px solid #333; margin-bottom:5px; }
.stamp-circle { width:100px; height:100px; border:2px dashed #007bff; border-radius:50%; margin:0 auto 10px; display:flex; align-items:center; justify-content:center; color:#007bff; font-size:10px; font-weight:bold; opacity:.3; }
.invoice-footer { margin-top:30px; padding-top:15px; border-top:1px dashed #ddd; display:flex; justify-content:space-between; font-size:12px; color:#777; }
.action-buttons { display:flex; gap:10px; margin-top:30px; padding-top:20px; border-top:2px solid #eee; flex-wrap:wrap; }
.btn { padding:10px 20px; border-radius:6px; cursor:pointer; font-size:14px; font-weight:600; text-decoration:none; display:inline-block; border:none; }
.btn-primary { background:#007bff; color:#fff; } .btn-secondary { background:#6c757d; color:#fff; } .btn-success { background:#28a745; color:#fff; }
@media print {
    header, footer, nav, aside, .sidebar, .navbar, .action-buttons, .main-footer, .btn { display:none !important; }
    html, body, .content-wrapper, .main-content, .container-fluid, .content { width:100% !important; margin:0 !important; padding:0 !important; background:#fff !important; }
    .invoice-container { width:100% !important; max-width:100% !important; margin:0 !important; padding:10mm !important; }
    .invoice-card { box-shadow:none !important; border:none !important; padding:0 !important; }
    .invoice-table thead { background-color:#007bff !important; color:#fff !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>

<div class="invoice-container">
    <div class="invoice-card">
        <img src="/hospital_system/assets/img/logo.png" class="watermark" alt="Watermark" onerror="this.style.display='none'">

        <div class="hospital-branding">
            <div class="hospital-logo"><img src="/hospital_system/assets/img/logo.png" alt="Hospital Logo" onerror="this.style.display='none'"></div>
            <div class="hospital-details">
                <h2>Emaqure Medical Centre</h2>
                <p>Biashara Street, Opposite Old Naiwe School, Mlolongo</p>
                <p>Contact: +254793069565</p>
                <p>emaquremedicalcentre@gmail.com</p>
            </div>
        </div>

        <div class="invoice-header">
            <div>
                <h1 class="invoice-title">Official Invoice</h1>
                <p class="invoice-number">Invoice #<?= htmlspecialchars((string)$invoice['id']) ?></p>
            </div>
            <div class="status-badge status-<?= htmlspecialchars(strtolower((string)$invoice['status'])) ?>">
                <?= htmlspecialchars((string)$invoice['status']) ?>
            </div>
        </div>

        <div class="invoice-meta">
            <div><span class="meta-label">Customer Name</span><span class="meta-value"><?= htmlspecialchars($patientName) ?></span></div>
            <div><span class="meta-label">Invoice Date</span><span class="meta-value"><?= date('d-m-Y H:i', strtotime($invoice['created_at'])) ?></span></div>
            <div><span class="meta-label">Patient Number</span><span class="meta-value"><?= htmlspecialchars($patientNumber) ?></span></div>
            <div>
                <span class="meta-label">Payment Mode</span>
                <span class="meta-value"><span class="payment-chip"><?= htmlspecialchars($paymentMode) ?></span></span>
            </div>
        </div>

        <div class="table-container">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Description</th>
                        <th>Source</th>
                        <th>Quantity</th>
                        <th class="amount-col">Price (KSH)</th>
                        <th class="amount-col">Amount (KSH)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($items as $row): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars((string)$row['description']) ?></td>
                            <td><?= htmlspecialchars(ucfirst((string)$row['source'])) ?></td>
                            <td class="amount-col"><?= number_format((float)$row['quantity'], 0) ?></td>
                            <td class="amount-col"><?= number_format((float)$row['price'], 2) ?></td>
                            <td class="amount-col"><?= number_format((float)$row['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$items): ?>
                        <tr><td colspan="6">No invoice items were found for this invoice.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row"><span>Total Bill:</span><span>KSH <?= number_format($invoiceTotal, 2) ?></span></div>
                <div class="total-row" style="color:#28a745; border-bottom:1px solid #eee; padding-bottom:5px;"><span>Amount Paid:</span><span>- KSH <?= number_format($totalPaid, 2) ?></span></div>
                <div class="total-final">
                    <div style="font-size:11px; color:#666; font-weight:400; text-transform:uppercase; letter-spacing:1px;">Outstanding Balance</div>
                    <span>KSH <?= number_format($outstandingBalance, 2) ?></span>
                </div>
            </div>
        </div>

        <div class="payment-panel">
            <div class="panel-card">
                <h4>Received By</h4>
                <table class="history-table">
                    <thead><tr><th>Method</th><th>Amount</th></tr></thead>
                    <tbody>
                        <?php if ($receivedRows): ?>
                            <?php foreach ($receivedRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['label']) ?></td>
                                    <td>KSH <?= number_format($row['amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2">No payment received yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="invoice-extra">
            <div class="qr-section"><div id="qrcode"></div><p>Scan to verify</p></div>
            <div class="signature-section"><div class="stamp-circle">OFFICIAL STAMP</div><div class="signature-line"></div><p style="font-size:12px; color:#333; margin:0;">Authorized Signature</p></div>
        </div>

        <div class="invoice-footer">
            <span>Generated By: <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'System Administrator') ?></strong></span>
            <span>Printed On: <?= date('d-m-Y H:i:s') ?></span>
        </div>

        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
            <?php if ($patientId): ?><a href="/hospital_system/patients/patient_dashboard.php?id=<?= $patientId ?>" class="btn btn-success">View Patient Profile</a><?php endif; ?>
            <a href="/hospital_system/pharmacy/sell_medicine.php" class="btn btn-secondary">Back to Sales</a>
        </div>
    </div>
</div>

<script>
var qrData = "Invoice: <?= addslashes((string)$invoice['id']) ?>\nCustomer: <?= addslashes($patientName) ?>\nPayment Mode: <?= addslashes($paymentMode) ?>\nBalance: KSH <?= number_format($outstandingBalance, 2) ?>";
new QRCode(document.getElementById('qrcode'), { text: qrData, width: 100, height: 100, colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.H });
<?php if (isset($_GET['print']) && $_GET['print'] == '1'): ?>window.print();<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
