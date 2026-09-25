<?php
// Legacy root appointment route consolidated into the active appointment register.
require_once __DIR__ . '/includes/session.php';
require_login();

$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: /hospital_system/patients/appointments.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
