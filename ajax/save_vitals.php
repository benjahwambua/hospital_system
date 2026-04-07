<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $patient_id = intval($_POST['patient_id']);
    $bp = $_POST['bp'] ?? '';
    $temperature = floatval($_POST['temperature'] ?? 0);
    $pulse = intval($_POST['pulse'] ?? 0);
    $respiration = intval($_POST['respiration'] ?? 0);
    $weight = floatval($_POST['weight'] ?? 0);
    $complaint = $_POST['complaint'] ?? '';

    $stmt = $conn->prepare("INSERT INTO vitals (patient_id, bp, temperature, pulse, respiration, weight, complaint, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isdiids", $patient_id, $bp, $temperature, $pulse, $respiration, $weight, $complaint);
    if($stmt->execute()){
        echo json_encode(['status'=>'success','message'=>'Vitals & complaint saved']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Failed to save vitals']);
    }
    $stmt->close();
}
?>
