<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'radiology', 'create');
require_role(['admin','doctor','nurse']);
require_once __DIR__ . '/../helpers/billing.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); echo json_encode(['success'=>false,'message'=>'Invalid security token.']); exit; }

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
                $visitId=get_or_create_current_visit($conn,$patient_id,'Outpatient','Radiology');
                $invoiceId=get_or_create_visit_invoice($conn,$patient_id,$visitId);
                $itemId=add_invoice_item($conn,$invoiceId,"Radiology: ".$scan_name,1,$charge,'radiology',null);
                post_invoice_journal($conn,$invoiceId,$patient_id,$charge,'Radiology request',$itemId);
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
