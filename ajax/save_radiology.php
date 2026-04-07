<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$response = ['success'=>false,'message'=>'Invalid request'];

if($_SERVER['REQUEST_METHOD']=='POST'){
    $patient_id = intval($_POST['patient_id']);
    $scan_type = trim($_POST['scan_type'] ?? '');
    $custom_scan = trim($_POST['custom_scan'] ?? '');
    $charge = floatval($_POST['scan_charge'] ?? 0);

    $scan_name = $custom_scan ?: $scan_type;

    if($patient_id && $scan_name){
        $stmt = $conn->prepare("INSERT INTO radiology_requests(patient_id,scan_type,created_at) VALUES(?,?,NOW())");
        $stmt->bind_param("is",$patient_id,$scan_name);
        if($stmt->execute()){
            $stmt->close();

            if($charge>0){
                $stmt = $conn->prepare("INSERT INTO billing(patient_id,item,amount,paid,created_at) VALUES(?,?,?,0,NOW())");
                $item = "Radiology: $scan_name";
                $stmt->bind_param("isd",$patient_id,$item,$charge);
                $stmt->execute();
                $stmt->close();
            }

            $response['success']=true;
            $response['message']="Radiology request added successfully.";
        }else{
            $response['message']="Failed to add radiology request.";
        }
    }else{
        $response['message']="Please select or enter a scan type.";
    }
}

echo json_encode($response);
?>
