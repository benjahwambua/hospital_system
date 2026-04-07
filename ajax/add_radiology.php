<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

header('Content-Type: application/json');

$patient_id = intval($_POST['patient_id'] ?? 0);
$scan_type = trim($_POST['scan_type'] ?? '');

if(!$patient_id || !$scan_type){
    echo json_encode(['status'=>0,'message'=>'Invalid input']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO radiology_requests (patient_id, scan_type, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param("is", $patient_id, $scan_type);

if($stmt->execute()){
    echo json_encode(['status'=>1,'message'=>'Radiology request added successfully']);
}else{
    echo json_encode(['status'=>0,'message'=>'Error adding radiology request']);
}
$stmt->close();
