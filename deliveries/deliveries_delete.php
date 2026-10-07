<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'maternity', 'view');
http_response_code(410);
exit('The legacy delivery record route has been retired. Use the integrated Maternity Delivery Register.');
