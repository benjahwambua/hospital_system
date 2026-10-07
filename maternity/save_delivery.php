<?php
// Retired legacy endpoint. Delivery and newborn recording are now handled by the consolidated Maternity Delivery Register.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
http_response_code(410);
exit('This legacy maternity endpoint has been retired. Please use the Maternity Delivery Register.');
