<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'procurement', 'view');
require_module_access($conn, 'procurement', 'create');
header('Location: /hospital_system/procurement/create_po.php');
exit;
