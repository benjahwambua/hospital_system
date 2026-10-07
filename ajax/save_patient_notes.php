<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
echo "This legacy patient-notes endpoint has been retired.";
exit;
