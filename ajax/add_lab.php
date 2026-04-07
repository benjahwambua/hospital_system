<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

header('Content-Type: application/json');

$patient_id = intval($_POST['patient_id'] ?? 0);
$lab_test = trim($_POST['lab_test'] ?? '');

if(!$patient_id || !$lab_test){
    echo json_encode(['status'=>0,'message'=>'Invalid input']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO lab_requests (patient_id, lab_test, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param("is", $patient_id, $lab_test);

if($stmt->execute()){
    echo json_encode(['status'=>1,'message'=>'Lab request added successfully']);
}else{
    echo json_encode(['status'=>0,'message'=>'Error adding lab request']);
}
$stmt->close();
