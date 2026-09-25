<?php
if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['lifetime'=>0,'path'=>'/hospital_system/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
function require_login(): void {
    if (empty($_SESSION['user_id'])) { header('Location: /hospital_system/auth/login.php'); exit; }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf_token(?string $token): bool {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return $sessionToken !== '' && is_string($token) && hash_equals($sessionToken, $token);
}
