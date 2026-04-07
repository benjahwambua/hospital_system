<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$response = ['success'=>false,'message'=>'Invalid request'];

if($_SERVER['REQUEST_METHOD']=='POST'){
    $patient_id = intval($_POST['patient_id']);
    $treatment = trim($_POST['treatment'] ?? '');
    $charge = floatval($_POST['treatment_charge'] ?? 0);

    if($patient_id && $treatment){
        $stmt = $conn->prepare("INSERT INTO treatments(patient_id,treatment_desc,created_at) VALUES(?,?,NOW())");
        $stmt->bind_param("is",$patient_id,$treatment);
        if($stmt->execute()){
            $stmt->close();

            if($charge>0){
                $stmt = $conn->prepare("INSERT INTO billing(patient_id,item,amount,paid,created_at) VALUES(?,?,?,0,NOW())");
                $item = "Treatment: $treatment";
                $stmt->bind_param("isd",$patient_id,$item,$charge);
                $stmt->execute();
                $stmt->close();
            }

            $response['success']=true;
            $response['message']="Treatment saved & charged successfully.";
        }else{
            $response['message']="Failed to save treatment.";
        }
    }else{
        $response['message']="Enter treatment details.";
    }
}

echo json_encode($response);
?>
