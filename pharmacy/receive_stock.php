<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
header('Location: /hospital_system/procurement/receive_inventory.php');
exit;
