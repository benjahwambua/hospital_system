<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_module_access($conn, 'finance', 'view');

http_response_code(410);
exit('This legacy invoice print route has been retired. Use billing/view_invoice.php.');
