<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','doctor','nurse']);
header('Content-Type: application/json');
http_response_code(410);
echo json_encode(['status'=>0,'message'=>'Legacy prescription endpoint retired. Use Clinical Orders so stock is deducted only at Pharmacy dispensing.']);
