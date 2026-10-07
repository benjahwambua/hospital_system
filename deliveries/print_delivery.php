<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_module_access($conn, 'maternity', 'view');

$deliveryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$deliveryId || $deliveryId < 1) {
    http_response_code(400);
    exit('Invalid delivery ID.');
}

header('Location: /hospital_system/maternity/deliveries.php');
exit;
