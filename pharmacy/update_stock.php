<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
$id=(int)($_GET['id']??0);
header('Location: /hospital_system/pharmacy/manage_stock.php'.($id>0?'?id='.$id:''));
exit;
