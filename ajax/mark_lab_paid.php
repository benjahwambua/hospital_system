<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','cashier']);
header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
 'status'=>'error',
 'message'=>'Legacy payment-status endpoint retired. Patient payments are recorded through Central Cashier.'
]);
