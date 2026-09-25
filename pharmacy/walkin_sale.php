<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','pharmacist']);
header('Location: /hospital_system/pharmacy/dispensing_queue.php?notice=central_cashier');
exit;
