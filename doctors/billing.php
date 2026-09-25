<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_role('doctor');

$encounterId = (int)($_GET['encounter_id'] ?? 0);
if ($encounterId <= 0) {
    http_response_code(400);
    exit('Invalid encounter.');
}

$stmt = $conn->prepare("SELECT patient_id, visit_id FROM encounters WHERE id=? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    exit('Unable to load encounter.');
}
$stmt->bind_param('i', $encounterId);
$stmt->execute();
$encounter = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$encounter) {
    http_response_code(404);
    exit('Encounter not found.');
}

$patientId = (int)($encounter['patient_id'] ?? 0);
$visitId = (int)($encounter['visit_id'] ?? 0);

if ($patientId <= 0) {
    http_response_code(400);
    exit('Encounter has no valid patient.');
}

if ($visitId > 0) {
    header('Location: /hospital_system/clinical/care.php?patient_id='.$patientId.'&visit_id='.$visitId);
} else {
    header('Location: /hospital_system/clinical/care.php?patient_id='.$patientId);
}
exit;
