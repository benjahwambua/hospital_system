<?php
// config/config.php
// Centralize session initialization before any audit/auth-dependent work.
require_once __DIR__ . '/../includes/session.php';

function hms_env(string $key, $default = null) {
    $value = getenv($key);
    if ($value === false || $value === null || $value === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }

    if ($value === null || $value === '') {
        return $default;
    }

    return $value;
}

$db_host = hms_env('HMS_DB_HOST', 'localhost');
$db_user = hms_env('HMS_DB_USER', 'root');
$db_pass = hms_env('HMS_DB_PASS', '');
$db_name = hms_env('HMS_DB_NAME', 'hms_db');

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    error_log("HMS database connection failed: " . $conn->connect_error);
    http_response_code(503);
    exit("The hospital system is temporarily unavailable. Please contact the administrator.");
}

$ASSETS_PATH = '/hospital_system/assets';
$SITE_NAME = 'EMAQURE MEDICAL CENTRE';
$SITE_LOGO = $ASSETS_PATH . '/img/logo.png';

function audit($action, $details = '') {
    global $conn;

    if (!isset($conn) || !($conn instanceof mysqli)) {
        return;
    }

    $uid = $_SESSION['user_id'] ?? null;
    $action = trim((string)$action);
    $details = is_scalar($details) ? (string)$details : json_encode($details, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES);

    if ($action === '') {
        return;
    }

    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('iss', $uid, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}
