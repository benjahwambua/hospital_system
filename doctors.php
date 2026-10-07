<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
require_module_access($conn, 'clinical', 'view');

header('Location: /hospital_system/clinical/index.php');
exit;
