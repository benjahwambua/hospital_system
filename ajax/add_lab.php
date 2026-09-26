<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_module_access($conn, 'laboratory', 'create');
require_role(['admin','doctor','nurse']);

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    echo json_encode(['status'=>0,'message'=>'Invalid security token.']);
    exit;
}

$patientId=(int)($_POST['patient_id']??0);
$labTest=trim((string)($_POST['lab_test']??''));
if($patientId<=0 || $labTest===''){
    echo json_encode(['status'=>0,'message'=>'Invalid laboratory request.']);
    exit;
}

$stmt=$conn->prepare("SELECT id,service_name,price FROM services_master WHERE active=1 AND category='lab' AND service_name=? LIMIT 1");
$stmt->bind_param('s',$labTest);
$stmt->execute();
$service=$stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$service){
    echo json_encode(['status'=>0,'message'=>'Laboratory service not found.']);
    exit;
}

$visitId=(int)($_POST['visit_id']??0);
if($visitId<=0) $visitId=get_or_create_current_visit($conn,$patientId,'Outpatient','Laboratory');

$conn->begin_transaction();
try{
    $price=(float)$service['price'];
    $cat='lab';
    $ins=$conn->prepare("INSERT INTO patient_services (patient_id,service_id,category,price,visit_id,created_at,status) VALUES (?,?,?,?,?,NOW(),'Pending')");
    if(!$ins) throw new Exception($conn->error);
    $ins->bind_param('iisdi',$patientId,$service['id'],$cat,$price,$visitId);
    if(!$ins->execute()) throw new Exception($ins->error);
    $ins->close();

    $invoiceId=get_or_create_visit_invoice($conn,$patientId,$visitId);
    $itemId=add_invoice_item($conn,$invoiceId,'Lab: '.$service['service_name'],1,$price,'lab',(int)$service['id']);
    post_invoice_journal($conn,$invoiceId,$patientId,$price,'Laboratory order',$itemId);
    $conn->commit();

    echo json_encode(['status'=>1,'message'=>'Laboratory order added to the canonical Lab worklist and central invoice.','visit_id'=>$visitId,'invoice_id'=>$invoiceId]);
}catch(Throwable $e){
    $conn->rollback();
    error_log('HMS legacy lab endpoint error: '.$e->getMessage());
    echo json_encode(['status'=>0,'message'=>'Unable to create laboratory order.']);
}
