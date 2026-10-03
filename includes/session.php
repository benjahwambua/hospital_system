<?php
// Load shared HMS billing/visit helpers for every authenticated module.
require_once __DIR__ . '/../helpers/billing.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_name('HMSSESSID');
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['lifetime'=>0,'path'=>'/hospital_system/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

function hms_session_timeout(): void {
    $now = time();
    $last = isset($_SESSION['last_activity']) ? (int)$_SESSION['last_activity'] : $now;
    $timeoutSeconds = 1800;

    if (($now - $last) > $timeoutSeconds) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        session_start();
        $_SESSION['session_expired'] = true;
        header('Location: /hospital_system/auth/login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = $now;
}

function require_login(): void {
    if (empty($_SESSION['user_id'])) { header('Location: /hospital_system/auth/login.php'); exit; }

    hms_session_timeout();

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
