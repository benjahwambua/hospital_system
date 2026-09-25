<?php
require_once __DIR__ . '/session.php';
function current_user_id(): ?int { return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null; }
function current_user_role(): ?string { return isset($_SESSION['role']) ? (string)$_SESSION['role'] : null; }
function current_user_is_super(): bool { return !empty($_SESSION['is_super']); }
function require_role($roles = []): void {
    $roles = is_array($roles) ? $roles : [$roles];
    if (!current_user_is_super() && !in_array(current_user_role(), $roles, true)) { http_response_code(403); exit('Forbidden'); }
}
function require_super(): void {
    if (!current_user_is_super()) { http_response_code(403); exit('Forbidden (super only)'); }
}
