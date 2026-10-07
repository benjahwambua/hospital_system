<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'maternity', 'view');

$patient_id = max(0, (int)($_GET['patient_id'] ?? 0));
if (!$patient_id) {
    http_response_code(400);
    exit('Invalid patient id.');
}

$stmt = $conn->prepare("SELECT p.*, m.* FROM maternity m JOIN patients p ON p.id=m.patient_id WHERE m.patient_id=? AND COALESCE(p.is_walkin,0)=0 AND p.clinic_category IN ('ANC','PNC','Maternity') ORDER BY m.id DESC LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    exit('Unable to load maternity record.');
}
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$m = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$m) {
    http_response_code(404);
    exit('Maternity record not found.');
}

$mid = (int)$m['id'];

$visitsStmt = $conn->prepare("SELECT * FROM maternity_visits WHERE maternity_id=? ORDER BY created_at DESC");
$visits = [];
if ($visitsStmt) {
    $visitsStmt->bind_param('i', $mid);
    $visitsStmt->execute();
    $visits = $visitsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $visitsStmt->close();
}

$deliveriesStmt = $conn->prepare("SELECT * FROM maternity_delivery WHERE maternity_id=? ORDER BY created_at DESC");
$deliveries = [];
if ($deliveriesStmt) {
    $deliveriesStmt->bind_param('i', $mid);
    $deliveriesStmt->execute();
    $deliveries = $deliveriesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $deliveriesStmt->close();
}

$babiesStmt = $conn->prepare("SELECT * FROM maternity_baby WHERE maternity_id=? ORDER BY created_at ASC");
$babies = [];
if ($babiesStmt) {
    $babiesStmt->bind_param('i', $mid);
    $babiesStmt->execute();
    $babies = $babiesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $babiesStmt->close();
}

$invoiceItems = [];
$inv = $conn->prepare("SELECT i.id, i.invoice_number, ii.description, ii.quantity, ii.unit_price, ii.total FROM invoices i JOIN invoice_items ii ON ii.invoice_id=i.id WHERE i.patient_id=? ORDER BY i.created_at DESC, ii.id ASC");
if ($inv) {
    $inv->bind_param('i', $patient_id);
    $inv->execute();
    $invoiceItems = $inv->get_result()->fetch_all(MYSQLI_ASSOC);
    $inv->close();
}

ob_start();
?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maternity Summary</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #dfe4ea; padding: 6px; text-align: left; }
        h2, h3 { margin: 0 0 10px; }
        .section { margin-top: 16px; }
    </style>
</head>
<body>
    <h2>Maternity Summary</h2>
    <p><strong>Patient:</strong> <?= htmlspecialchars($m['full_name'] ?? '') ?> (<?= htmlspecialchars($m['patient_number'] ?? '') ?>)</p>
    <p><strong>ANC Number:</strong> <?= htmlspecialchars($m['anc_number'] ?? '') ?></p>

    <div class="section">
        <h3>Visits</h3>
        <?php if ($visits): ?>
            <table class="table">
                <thead><tr><th>Date</th><th>Type</th><th>BP</th><th>Temp</th><th>Weight</th><th>Notes</th></tr></thead>
                <tbody>
                    <?php foreach ($visits as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['visit_type'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['bp'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['temp'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['weight'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No visits recorded.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Deliveries</h3>
        <?php if ($deliveries): ?>
            <table class="table">
                <thead><tr><th>Date</th><th>Mode</th><th>Outcome</th><th>Notes</th></tr></thead>
                <tbody>
                    <?php foreach ($deliveries as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['delivery_mode'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['outcome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No deliveries recorded.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Babies</h3>
        <?php if ($babies): ?>
            <table class="table">
                <thead><tr><th>Name</th><th>Gender</th><th>Weight</th><th>Apgar</th><th>Alive</th></tr></thead>
                <tbody>
                    <?php foreach ($babies as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['gender'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['weight'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['apgar'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['alive'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No babies recorded.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Invoice Items</h3>
        <?php if ($invoiceItems): ?>
            <table class="table">
                <thead><tr><th>Invoice</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                <tbody>
                    <?php foreach ($invoiceItems as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['invoice_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['quantity'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['unit_price'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['total'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No invoice items recorded.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();
require_once __DIR__ . '/../vendor/autoload.php';
$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("maternity_summary_{$patient_id}.pdf");
