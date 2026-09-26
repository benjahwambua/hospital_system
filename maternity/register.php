<?php
// Compatibility entry point.
// Maternity patients are registered through the central Reception workflow so
// the patient, visit and maternity record are created together.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'maternity', 'create');
header('Location: /hospital_system/patients/reception_register.php?clinic_category=Maternity');
exit;
