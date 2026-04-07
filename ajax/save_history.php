<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

header('Content-Type: application/json');

$patient_id = intval($_POST['patient_id'] ?? 0);

if(!$patient_id){
    echo json_encode(['status'=>0,'message'=>'Invalid patient ID']);
    exit;
}

$presenting_complaints = trim($_POST['presenting_complaints'] ?? '');
$history_presenting = trim($_POST['history_presenting'] ?? '');
$allergies = trim($_POST['allergies'] ?? '');
$past_medical = trim($_POST['past_medical'] ?? '');
$surgical_history = trim($_POST['surgical_history'] ?? '');
$family_history = trim($_POST['family_history'] ?? '');
$social_history = trim($_POST['social_history'] ?? '');
$medications = trim($_POST['medications'] ?? '');

// Check if history exists for patient
$exists = $conn->query("SELECT id FROM patient_history WHERE patient_id=$patient_id")->fetch_assoc();

if($exists){
    // Update existing
    $stmt = $conn->prepare("UPDATE patient_history SET presenting_complaints=?, history_presenting_complaints=?, allergies=?, past_medical_history=?, surgical_history=?, family_history=?, social_history=?, medications=?, created_at=NOW() WHERE patient_id=?");
    $stmt->bind_param("ssssssssi",$presenting_complaints,$history_presenting,$allergies,$past_medical,$surgical_history,$family_history,$social_history,$medications,$patient_id);
}else{
    // Insert new
    $stmt = $conn->prepare("INSERT INTO patient_history (patient_id, presenting_complaints, history_presenting_complaints, allergies, past_medical_history, surgical_history, family_history, social_history, medications, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issssssss",$patient_id,$presenting_complaints,$history_presenting,$allergies,$past_medical,$surgical_history,$family_history,$social_history,$medications);
}

if($stmt->execute()){
    echo json_encode(['status'=>1,'message'=>'Patient history saved successfully']);
}else{
    echo json_encode(['status'=>0,'message'=>'Error saving patient history']);
}
$stmt->close();
