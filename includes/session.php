<?php
// Load shared HMS billing/visit helpers for every authenticated module.
require_once __DIR__ . '/../helpers/billing.php';
if (session_status() === PHP_SESSION_NONE) {
    // Use a dedicated HMS session cookie so legacy PHPSESSID cookies from older
    // localhost versions cannot cause the dashboard CSRF token to come from a
    // different session.
    ini_set('session.use_strict_mode', '1');
    session_name('HMSSESSID');
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
            'hr/' => ['hr_staff','view'],
            'emergency/' => ['emergency','view'],
            'settings/' => ['administration','view'],
            'reports/' => ['administration','view']
        ];

        // Patient Dashboard is a cross-module command centre. It is intentionally
        // handled by the page itself because both Front Desk and Clinical users may
        // legitimately open it; forcing it into one module here can cause access
        // handlers/custom 403 redirects to loop.
        $isPatientDashboard = ($relative === 'patients/patient_dashboard.php');

        foreach ($moduleMap as $prefix => $rule) {
            if (!$isPatientDashboard && ($prefix === $relative || (str_ends_with($prefix, '/') && str_starts_with($relative, $prefix)))) {
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
    // The CSRF token belongs to the authenticated PHP session. Do not derive or
    // overwrite it from a client cookie: doing so can invalidate an already
    // rendered form when the browser sends a stale/missing auxiliary cookie.
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || !preg_match('/^[a-f0-9]{64}$/', $_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function rotate_csrf_token(): string {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    $posted = (string)$token;
    $expected = (string)($_SESSION['csrf_token'] ?? '');
    if ($posted === '' || $expected === '') return false;
    if (!preg_match('/^[a-f0-9]{64}$/', $posted)) return false;
    return hash_equals($expected, $posted);
}
