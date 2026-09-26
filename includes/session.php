<?php
// Load shared HMS billing/visit helpers for every authenticated module.
require_once __DIR__ . '/../helpers/billing.php';
if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['lifetime'=>0,'path'=>'/hospital_system/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
function require_login(): void {
    if (empty($_SESSION['user_id'])) { header('Location: /hospital_system/auth/login.php'); exit; }

    // Central Odoo-style module guard. Page-level scripts that call require_login()
    // are denied before any page logic/POST action runs when their module is disabled.
    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        require_once __DIR__ . '/permissions.php';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $relative = ltrim((string)preg_replace('#^/hospital_system/?#', '', $path), '/');

        $module = null;
        $moduleMap = [
            'patients/reception_register.php' => ['front_desk','create'],
            'patients/edit_patient.php' => ['front_desk','edit'],
            'reception/' => ['front_desk','view'],
            'patients/' => ['clinical','view'],
            'clinical/' => ['clinical','view'],
            'lab/' => ['laboratory','view'],
            'radiology/' => ['radiology','view'],
            'pharmacy/' => ['pharmacy','view'],
            'maternity/' => ['maternity','view'],
            'cashier/' => ['finance','view'],
            'procurement/' => ['procurement','view'],
            'billing/' => ['finance_admin','view'],
            'accounting/' => ['finance_admin','view'],
            'expenses/' => ['finance_admin','view'],
            'users/' => ['administration','view'],
            'settings/' => ['administration','view'],
            'reports/' => ['administration','view']
        ];

        foreach ($moduleMap as $prefix => $rule) {
            if ($prefix === $relative || str_ends_with($prefix, '/') && str_starts_with($relative, $prefix)) {
                $module = $rule;
                break;
            }
        }

        if ($module) {
            require_module_access($conn, $module[0], $module[1]);
        }
    }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf_token(?string $token): bool {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return $sessionToken !== '' && is_string($token) && hash_equals($sessionToken, $token);
}
