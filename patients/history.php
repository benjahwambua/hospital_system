<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$patient_id = (int)($_GET['id'] ?? 0);
if ($patient_id <= 0) { http_response_code(400); exit('Invalid patient.'); }

$patientStmt = $conn->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
$patientStmt->bind_param('i', $patient_id);
$patientStmt->execute();
$patient = $patientStmt->get_result()->fetch_assoc();
$patientStmt->close();

$historyStmt = $conn->prepare("SELECT * FROM patient_history WHERE patient_id = ? ORDER BY id DESC");
$historyStmt->bind_param('i', $patient_id);
$historyStmt->execute();
$history = $historyStmt->get_result();
?>

<h2>Patient History: <?= $patient['fullname'] ?></h2>

<table border="1" width="100%">
<tr>
    <th>Type</th>
    <th>Details</th>
    <th>Date</th>
</tr>
<?php while($h = $history->fetch_assoc()): ?>
<tr>
    <td><?= $h['type'] ?></td>
    <td><?= $h['details'] ?></td>
    <td><?= $h['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</table>
