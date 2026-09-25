<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','doctor','nurse']);
$patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$target = '/hospital_system/clinical/triage.php';
if ($patientId > 0) $target .= '?patient_id=' . $patientId;
header('Location: ' . $target);
exit;
