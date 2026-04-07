<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id = intval($_POST['patient_id'] ?? 0);
$service_id = intval($_POST['service_id'] ?? 0);
$category = $_POST['category'] ?? '';
$price = floatval($_POST['price'] ?? 0);

if(!$patient_id || !$service_id || !$category || !$price){
    die("Missing required fields.");
}

// Insert service for patient
$stmt = $conn->prepare("INSERT INTO patient_services (patient_id, service_id, category, price, created_at) VALUES (?,?,?,?,NOW())");
$stmt->bind_param("iisd",$patient_id,$service_id,$category,$price);
$stmt->execute();
$stmt->close();

// Redirect back to dashboard
header("Location: patient_dashboard.php?id=$patient_id");
exit;
?>