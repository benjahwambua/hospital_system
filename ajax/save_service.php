<?php
require_once __DIR__ . '/../config/config.php';
$data = json_decode(file_get_contents('php://input'), true);

$patient_id = intval($data['patient_id'] ?? 0);
$service_name = $conn->real_escape_string($data['service_name'] ?? '');
$category = $conn->real_escape_string($data['category'] ?? '');
$price = floatval($data['price'] ?? 0);

if(!$patient_id || !$service_name || !$category || !$price){
    echo json_encode(['status'=>'error','message'=>'All fields required']);
    exit;
}

// Find service ID
$res = $conn->query("SELECT id FROM service_master WHERE name='$service_name' AND category='$category' LIMIT 1");
$service = $res->fetch_assoc();
$service_id = $service['id'] ?? 0;

if(!$service_id){
    echo json_encode(['status'=>'error','message'=>'Service not found']);
    exit;
}

// Insert patient service
$stmt = $conn->prepare("INSERT INTO patient_services (patient_id, service_id, category, price, created_at) VALUES (?,?,?,?,NOW())");
$stmt->bind_param("iiid", $patient_id, $service_id, $category, $price);
if($stmt->execute()){
    echo json_encode(['status'=>'success','message'=>'Service added successfully']);
} else {
    echo json_encode(['status'=>'error','message'=>'Database error']);
}
$stmt->close();
