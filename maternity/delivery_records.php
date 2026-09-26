<?php
require_once __DIR__ . '/../config/config.php';require_once __DIR__ . '/../includes/session.php';require_once __DIR__ . '/../includes/auth.php';require_login();require_module_access($conn,'maternity','view');header('Location: /hospital_system/maternity/deliveries.php');exit;
