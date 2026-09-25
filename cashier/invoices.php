<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','cashier']);
header('Location: /hospital_system/cashier/index.php?notice=central_cashier');
exit;
