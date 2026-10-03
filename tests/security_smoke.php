<?php
/**
 * Security smoke tests for the HMS application.
 * These checks validate the hardening measures that were added during the audit.
 */

$checks = [];

function addCheck(string $name, bool $passed, string $details = ''): void {
    global $checks;
    $checks[] = ['name' => $name, 'passed' => $passed, 'details' => $details];
}

$requiredFiles = [
    __DIR__ . '/../config/config.php',
    __DIR__ . '/../includes/session.php',
    __DIR__ . '/../auth/login.php',
    __DIR__ . '/../patients/patient_list.php',
    __DIR__ . '/../.env.example',
];

foreach ($requiredFiles as $file) {
    addCheck('file_exists:' . basename($file), file_exists($file), file_exists($file) ? 'found' : 'missing');
}

$configContents = @file_get_contents(__DIR__ . '/../config/config.php');
if ($configContents !== false) {
    addCheck('env_support', str_contains($configContents, 'hms_load_env_file') && str_contains($configContents, 'HMS_DB_NAME'), 'database config reads environment variables');
    addCheck('audit_function', str_contains($configContents, 'function audit('), 'audit helper present');
}

$sessionContents = @file_get_contents(__DIR__ . '/../includes/session.php');
if ($sessionContents !== false) {
    addCheck('session_timeout', str_contains($sessionContents, 'hms_session_timeout'), 'idle timeout implemented');
    addCheck('csrf_helpers', str_contains($sessionContents, 'csrf_token') && str_contains($sessionContents, 'verify_csrf_token'), 'CSRF helpers present');
}

$loginContents = @file_get_contents(__DIR__ . '/../auth/login.php');
if ($loginContents !== false) {
    addCheck('login_throttle', str_contains($loginContents, 'hms_login_is_locked') || str_contains($loginContents, 'hms_register_failed_login'), 'login throttling enabled');
    addCheck('login_csrf', str_contains($loginContents, 'verify_csrf_token'), 'login validates CSRF token');
}

$patientListContents = @file_get_contents(__DIR__ . '/../patients/patient_list.php');
if ($patientListContents !== false) {
    addCheck('patient_search_prepared', str_contains($patientListContents, 'bind_param($types') || str_contains($patientListContents, 'bind_param'), 'patient search uses prepared statements');
}

$failed = 0;
foreach ($checks as $check) {
    if (!$check['passed']) {
        $failed++;
    }
}

foreach ($checks as $check) {
    echo ($check['passed'] ? 'PASS' : 'FAIL') . ': ' . $check['name'] . ($check['details'] !== '' ? ' - ' . $check['details'] : '') . PHP_EOL;
}

echo PHP_EOL . 'Summary: ' . (count($checks) - $failed) . '/' . count($checks) . ' checks passed.' . PHP_EOL;
exit($failed > 0 ? 1 : 0);
