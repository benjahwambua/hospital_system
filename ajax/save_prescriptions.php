<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$patient_id = intval($data['patient_id']);
$medicine_id = intval($data['medicine_id']);
$quantity = intval($data['quantity']);
$price = floatval($data['price']);

if(!$patient_id || !$medicine_id || !$quantity){
    echo json_encode(['status'=>'error','message'=>'Invalid data']);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO prescriptions (patient_id, medicine_id, quantity)
    VALUES (?, ?, ?)
");

$stmt->bind_param("iii", $patient_id, $medicine_id, $quantity);

if($stmt->execute()){
    echo json_encode(['status'=>'success','message'=>'Medicine dispensed']);
} else {
    echo json_encode(['status'=>'error','message'=>'Database error']);
}
