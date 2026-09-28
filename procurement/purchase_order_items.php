<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'procurement', 'create');
$poId=(int)($_GET['id']??0);
header('Location: /hospital_system/procurement/purchase_orders.php'.($poId>0?'?view_id='.$poId:''));
exit;
