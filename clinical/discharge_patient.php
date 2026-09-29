<?php
// Retired: inpatient discharge is now handled inside Ward / IPD Management.
// Keep this compatibility endpoint so older bookmarks/links continue to work.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$admissionId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$target = '/hospital_system/clinical/ward_management.php';
if ($admissionId > 0) {
    $target .= '?discharge_id=' . $admissionId . '#discharge';
}
header('Location: ' . $target);
exit;
?>