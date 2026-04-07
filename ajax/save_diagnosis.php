<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$response = ['success'=>false,'message'=>'Invalid request'];

if($_SERVER['REQUEST_METHOD']=='POST'){
    $patient_id = intval($_POST['patient_id']);
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $procedures = trim($_POST['procedures'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $procedure_charge = floatval($_POST['procedure_charge'] ?? 0);

    if($patient_id && ($diagnosis || $procedures || $notes)){
        $stmt = $conn->prepare("INSERT INTO diagnosis(patient_id, diagnosis, procedures, notes, created_at) VALUES(?,?,?,?,NOW())");
        $stmt->bind_param("isss",$patient_id, $diagnosis, $procedures, $notes);
        if($stmt->execute()){
            $stmt->close();

            // Add billing if procedure charge exists
            if($procedure_charge>0){
                $stmt = $conn->prepare("INSERT INTO billing(patient_id,item,amount,paid,created_at) VALUES(?,?,?,0,NOW())");
                $item = "Procedure: $procedures";
                $stmt->bind_param("isd",$patient_id,$item,$procedure_charge);
                $stmt->execute();
                $stmt->close();
            }

            $response['success']=true;
            $response['message']="Diagnosis saved successfully.";
        }else{
            $response['message']="Failed to save diagnosis.";
        }
    }else{
        $response['message']="Please enter diagnosis or procedure.";
    }
}

echo json_encode($response);
?>