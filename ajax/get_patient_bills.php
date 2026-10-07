<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'finance', 'view');

$pid = (int)($_GET['patient_id'] ?? 0);
if ($pid <= 0) { http_response_code(400); exit('Invalid patient.'); }

$stmt = $conn->prepare("SELECT invoice_number, total, status FROM invoices WHERE patient_id = ? ORDER BY id DESC");
$stmt->bind_param('i', $pid);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
    echo "<div class='card mb-2 p-2'>
        <strong>".htmlspecialchars($r['invoice_number'] ?? '')."</strong><br>
        Total: KES ".number_format((float)$r['total'],2)."<br>
        Status: ".htmlspecialchars($r['status'] ?? '')."
    </div>";
}
$stmt->close();