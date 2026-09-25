<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
$id=(int)($_GET['id']??0);
if($id<=0){http_response_code(400);exit('Invalid invoice ID.');}
header('Location: /hospital_system/billing/view_invoice.php?id='.$id.'&print=1');
exit;
