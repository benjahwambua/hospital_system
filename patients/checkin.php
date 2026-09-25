<?php
// Legacy check-in route consolidated into Reception.
// Reception is the only entry point that creates the patient's clinical Visit.
require_once __DIR__ . '/../includes/session.php';
require_login();

$patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$target = '/hospital_system/patients/reception_register.php';
if ($patientId > 0) {
    $target .= '?patient_id=' . $patientId;
}
header('Location: ' . $target);
exit;
?>
