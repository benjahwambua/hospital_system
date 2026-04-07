<?php
require_once '../config/config.php';

$patient_id = intval($_POST['patient_id']);
$lab_id = intval($_POST['lab_id']);
$lab_price = floatval($_POST['lab_price']);

$lab = $conn->query("SELECT * FROM lab_tests_master WHERE id=$lab_id")->fetch_assoc();

if(!$lab){
    echo json_encode(['success'=>false,'message'=>'Invalid Lab']);
    exit;
}

/* Save Lab Request */
$stmt = $conn->prepare("INSERT INTO lab_requests (patient_id, lab_test, created_at) VALUES (?,?,NOW())");
$stmt->bind_param("is",$patient_id,$lab['test_name']);
$stmt->execute();
$stmt->close();

/* Save Billing */
$stmt = $conn->prepare("INSERT INTO billing (patient_id,item,amount,created_at) VALUES (?,?,?,NOW())");
$item = "Lab: ".$lab['test_name'];
$stmt->bind_param("isd",$patient_id,$item,$lab_price);
$stmt->execute();
$stmt->close();

echo json_encode(['success'=>true,'message'=>'Lab added & charged']);
