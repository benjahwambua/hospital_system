<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['pharmacist','admin','cashier']);
header('Location: /hospital_system/pharmacy/walkin_sale.php');
exit;
