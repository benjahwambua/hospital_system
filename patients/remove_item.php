<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'clinical', 'delete');
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    exit('Invalid security token.');
}

$id = (int)($_POST['id'] ?? 0);
$type = (string)($_POST['type'] ?? '');
$patient_id = (int)($_POST['patient_id'] ?? 0);

if ($id > 0 && $patient_id > 0 && $type === 'service') {
    $stmt = $conn->prepare("DELETE FROM patient_services WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $id, $patient_id);
    $stmt->execute();
    $stmt->close();
} elseif ($id > 0 && $patient_id > 0 && $type === 'prescription') {
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $id, $patient_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: patient_dashboard.php?id={$patient_id}&tab=billing&success=Item+Removed");
exit;
