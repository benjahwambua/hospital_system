<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    echo json_encode(['success'=>false,'message'=>'Invalid security token.']);
    exit;
}

http_response_code(410);
echo json_encode([
    'success'=>false,
    'message'=>'Legacy clinical order endpoint retired. Use Clinical Orders so the visit, clinical request and Central Billing remain linked.'
]);
exit;
