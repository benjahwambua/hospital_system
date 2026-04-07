<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id = intval($_POST['patient_id'] ?? 0);
$presenting_complaint = $_POST['presenting_complaint'] ?? '';
$hpc = $_POST['hpc'] ?? '';
$clinical_history = $_POST['clinical_history'] ?? '';
$findings = $_POST['findings'] ?? '';

if(!$patient_id){
    die("Patient ID missing.");
}

// Update patient record
$stmt = $conn->prepare("UPDATE patients SET presenting_complaint=?, hpc=?, clinical_history=?, findings=? WHERE id=?");
$stmt->bind_param("ssssi",$presenting_complaint,$hpc,$clinical_history,$findings,$patient_id);
$stmt->execute();
$stmt->close();

// Redirect back
header("Location: patient_dashboard.php?id=$patient_id");
exit;
?>