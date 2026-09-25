<?php
// Central logout endpoint.
require_once __DIR__ . '/../includes/session.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
}

session_destroy();
header('Location: /hospital_system/auth/login.php?logout=success');
exit;
