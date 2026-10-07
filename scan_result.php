<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
require_module_access($conn, 'radiology', 'view');

http_response_code(410);
exit('This legacy scan-result form has been retired. Use the Radiology results workflow.');
