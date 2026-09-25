<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_role(['admin','doctor','nurse']);

$patient_id=(int)($_POST['patient_id']??0);
$lab_id=(int)($_POST['lab_id']??0);
$lab_price=(float)($_POST['lab_price']??0);
if($patient_id<=0 || $lab_id<=0 || $lab_price<0){ echo json_encode(['success'=>false,'message'=>'Invalid laboratory request.']); exit; }

$stmt=$conn->prepare("SELECT id,test_name FROM lab_tests_master WHERE id=? LIMIT 1");
$stmt->bind_param('i',$lab_id); $stmt->execute(); $lab=$stmt->get_result()->fetch_assoc(); $stmt->close();
if(!$lab){ echo json_encode(['success'=>false,'message'=>'Invalid Lab']); exit; }

$conn->begin_transaction();
try{
    $visitId=get_or_create_current_visit($conn,$patient_id,'Outpatient','Laboratory');
    $stmt=$conn->prepare("INSERT INTO lab_requests (patient_id,lab_test,created_at) VALUES (?,?,NOW())");
    if(!$stmt) throw new Exception('Unable to create laboratory request.');
    $stmt->bind_param('is',$patient_id,$lab['test_name']);
    if(!$stmt->execute()) throw new Exception('Unable to save laboratory request.');
    $stmt->close();

    $invoiceId=get_or_create_visit_invoice($conn,$patient_id,$visitId);
    $itemId=add_invoice_item($conn,$invoiceId,'Lab: '.$lab['test_name'],1,$lab_price,'lab',$lab_id);
    post_invoice_journal($conn,$invoiceId,$patient_id,$lab_price,'Laboratory',$itemId);
    $conn->commit();
    echo json_encode(['success'=>true,'message'=>'Lab added and charged to the central invoice.','invoice_id'=>$invoiceId]);
}catch(Throwable $e){
    $conn->rollback();
    error_log('HMS lab billing error: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Unable to add laboratory charge.']);
}
