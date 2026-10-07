<?php
// Legacy medication editor retired.
// Pharmacy stock changes must use the controlled stock-management workflow.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_module_access($conn, 'pharmacy', 'view');

header('Location: /hospital_system/pharmacy/manage_stock.php?legacy_editor=retired');
exit;
