<?php
require_once '../config/config.php';

$patient_id = intval($_POST['patient_id']);
$amount = floatval($_POST['amount']);
$method = $_POST['method'];

if($amount <= 0){
    echo json_encode(['success'=>false,'message'=>'Invalid amount']);
    exit;
}

/* Save payment */
$stmt = $conn->prepare("
    INSERT INTO payments (patient_id, amount, method, created_at)
    VALUES (?,?,?,NOW())
");
$stmt->bind_param("ids",$patient_id,$amount,$method);
$stmt->execute();
$stmt->close();

/* Apply payment to billing */
$conn->query("
    UPDATE billing 
    SET paid_amount = paid_amount + $amount
    WHERE patient_id = $patient_id
");

echo json_encode(['success'=>true,'message'=>'Payment recorded successfully']);
