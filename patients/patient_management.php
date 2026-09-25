<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','receptionist','doctor','nurse','pharmacist']);
header('Location: /hospital_system/reception/index.php?notice=modern_workflow');
exit;
