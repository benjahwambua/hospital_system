<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn,'clinical','create');
require_role(['admin','doctor','nurse']);
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){http_response_code(419);exit('Invalid security token.');}
http_response_code(410);
exit('Legacy service endpoint retired. Use Patient Dashboard / Clinical Orders so services are linked to the current visit and Central Billing.');
