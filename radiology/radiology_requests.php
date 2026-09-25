<?php
// Legacy radiology request entry point consolidated into Clinical Orders.
// Doctors place Lab/Radiology/Pharmacy orders from the same visit.
require_once __DIR__ . '/../includes/session.php';
require_login();

$patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$visitId = (int)($_GET['visit_id'] ?? $_POST['visit_id'] ?? 0);
$target = '/hospital_system/clinical/orders.php';
$params = [];
if ($patientId > 0) $params[] = 'patient_id=' . $patientId;
if ($visitId > 0) $params[] = 'visit_id=' . $visitId;
header('Location: ' . $target . ($params ? '?' . implode('&', $params) : ''));
exit;
?>
