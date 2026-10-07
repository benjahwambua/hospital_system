<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
echo "This placeholder SMS helper has been retired. Configure an approved SMS provider before enabling maternity messaging.";
exit;
