<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['pharmacist','admin']);
header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
 'status'=>'error',
 'message'=>'Legacy pharmacy sales endpoint retired. Clinical prescriptions are dispensed from the Pharmacy Dispensing Queue and all patient payments are collected by Central Cashier.'
]);
exit;
