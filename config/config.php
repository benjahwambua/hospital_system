<?php
// config/config.php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // set if required
$db_name = 'hms_db';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("DB connection error: " . $conn->connect_error);

$ASSETS_PATH = '/hospital_system/assets';
$SITE_NAME = 'EMAQURE MEDICAL CENTRE';
$SITE_LOGO = $ASSETS_PATH . '/img/logo.png';

function audit($action, $details='') {
    global $conn;
    if (session_status() === PHP_SESSION_NONE) session_start();
    $uid = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?,?,?)");
    if ($stmt) { 
        $stmt->bind_param("iss", $uid, $action, $details); 
        @$stmt->execute(); 
        $stmt->close(); 
    }
}
