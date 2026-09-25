<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','receptionist']);
header('Location: /hospital_system/patients/reception_register.php?notice=use_reception');
exit;
