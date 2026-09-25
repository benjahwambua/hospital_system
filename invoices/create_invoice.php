<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','cashier','accountant']);
header('Location: /hospital_system/billing/view_bills.php?notice=modern_billing');
exit;
