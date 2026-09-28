<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
echo "This legacy prescription endpoint has been retired. Use Clinical Care for prescriptions so billing and the Pharmacy dispensing queue remain synchronized.";
exit;
