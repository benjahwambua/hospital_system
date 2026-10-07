<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'finance', 'view');

$patient_id = (int)($_GET['patient_id'] ?? 0);
if ($patient_id <= 0) { http_response_code(400); exit('Invalid patient.'); }

$stmt = $conn->prepare("SELECT amount, method, created_at FROM payments WHERE patient_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<table class='table-blue'><tr><th>Amount</th><th>Method</th><th>Date</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>".number_format((float)$row['amount'],2)."</td><td>".htmlspecialchars($row['method'] ?? '')."</td><td>".htmlspecialchars($row['created_at'] ?? '')."</td></tr>";
}
echo "</table>";
$stmt->close();