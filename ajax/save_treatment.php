<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'clinical', 'create');
require_role(['admin','doctor','nurse']);
require_once __DIR__ . '/../helpers/billing.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); echo json_encode(['success'=>false,'message'=>'Invalid security token.']); exit; }

$response = ['success'=>false,'message'=>'Invalid request'];

if($_SERVER['REQUEST_METHOD']=='POST'){
    $patient_id = intval($_POST['patient_id']);
    $treatment = trim($_POST['treatment'] ?? '');
    $charge = floatval($_POST['treatment_charge'] ?? 0);

    if($patient_id && $treatment){
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO treatments(patient_id,treatment_desc,created_at) VALUES(?,?,NOW())");
            if(!$stmt) throw new Exception($conn->error);
            $stmt->bind_param("is",$patient_id,$treatment);
            if(!$stmt->execute()) throw new Exception($stmt->error);
            $stmt->close();

            if($charge>0){
                $visitId=get_or_create_current_visit($conn,$patient_id,'Outpatient','Clinical');
                $invoiceId=get_or_create_visit_invoice($conn,$patient_id,$visitId);
                $itemId=add_invoice_item($conn,$invoiceId,"Treatment: ".$treatment,1,$charge,'treatment',null);
                post_invoice_journal($conn,$invoiceId,$patient_id,$charge,'Clinical treatment',$itemId);
            }
            $conn->commit();
            $response['success']=true;
            $response['message']=$charge>0?"Treatment saved and added to the central invoice.":"Treatment saved successfully.";
        } catch(Throwable $e) {
            $conn->rollback();
            error_log('HMS treatment save error: '.$e->getMessage());
            $response['message']="Unable to save treatment.";
        }
    }else{
        $response['message']="Enter treatment details.";
    }
}

echo json_encode($response);
?>
