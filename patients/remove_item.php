<?php
// remove_item.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$id = intval($_GET['id']);
$type = $_GET['type']; // 'service' or 'prescription'
$patient_id = intval($_GET['patient_id']);

if ($type == 'service') {
    $conn->query("DELETE FROM patient_services WHERE id = $id");
} elseif ($type == 'prescription') {
    $conn->query("DELETE FROM prescriptions WHERE id = $id");
}

header("Location: patient_dashboard.php?id=$patient_id&tab=billing&success=Item+Removed");
exit;