<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

header('Content-Type: application/json');

$patient_id = intval($_POST['patient_id'] ?? 0);
$medicine_id = intval($_POST['medicine_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

if(!$patient_id || !$medicine_id || $quantity < 1){
    echo json_encode(['status'=>0,'message'=>'Invalid input']);
    exit;
}

// Check stock
$stock = $conn->query("SELECT quantity FROM pharmacy_stock WHERE id=$medicine_id")->fetch_assoc();
if(!$stock || $stock['quantity'] < $quantity){
    echo json_encode(['status'=>0,'message'=>'Insufficient stock']);
    exit;
}

// Insert prescription
$stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, medicine_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iii", $patient_id, $medicine_id, $quantity);

if($stmt->execute()){
    // Reduce stock
    $conn->query("UPDATE pharmacy_stock SET quantity = quantity - $quantity WHERE id = $medicine_id");
    echo json_encode(['status'=>1,'message'=>'Prescription added successfully']);
}else{
    echo json_encode(['status'=>0,'message'=>'Error adding prescription']);
}
$stmt->close();
