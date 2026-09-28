<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn,'laboratory','create');
require_role(['admin','doctor','nurse']);
header('Content-Type: application/json');
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){http_response_code(419);echo json_encode(['status'=>0,'message'=>'Invalid security token.']);exit;}
http_response_code(410);
echo json_encode(['status'=>0,'message'=>'Legacy laboratory endpoint retired. Use Clinical Orders so the request, visit and Central Billing remain linked.']);
