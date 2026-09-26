<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'clinical', 'edit');
require_role(['admin','doctor']);
require_once __DIR__ . '/../helpers/billing.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); echo json_encode(['success'=>false,'message'=>'Invalid security token.']); exit; }

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

            if($procedure_charge>0){
                $visitId=get_or_create_current_visit($conn,$patient_id,'Outpatient','Clinical');
                $invoiceId=get_or_create_visit_invoice($conn,$patient_id,$visitId);
                $description=$procedures!=='' ? "Procedure: ".$procedures : "Clinical procedure";
                $itemId=add_invoice_item($conn,$invoiceId,$description,1,$procedure_charge,'procedure',null);
                post_invoice_journal($conn,$invoiceId,$patient_id,$procedure_charge,'Clinical procedure',$itemId);
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