<?php
require_once __DIR__ . '/../includes/session.php';
require_login();
require_module_access($conn, 'clinical', 'view');
header('Location: ../patients/appointments.php');
exit;