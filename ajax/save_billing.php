<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_role(['admin','cashier','accountant']);

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    echo json_encode(['success'=>false,'message'=>'Invalid security token.']); exit;
}
$patient_id=(int)($_POST['patient_id']??0);
$item=trim((string)($_POST['item']??''));
$amount=(float)($_POST['amount']??0);
if($patient_id<=0 || $item==='' || $amount<0){ echo json_encode(['success'=>false,'message'=>'Invalid billing details.']); exit; }

$conn->begin_transaction();
try {
    $visitId=get_or_create_current_visit($conn,$patient_id);
    $invoiceId=get_or_create_visit_invoice($conn,$patient_id,$visitId);
    $itemId=add_invoice_item($conn,$invoiceId,$item,1,$amount,'service',null);
    post_invoice_journal($conn,$invoiceId,$patient_id,$amount,'Billing charge',$itemId);
    $conn->commit();
    echo json_encode(['success'=>true,'message'=>'Charge added to the central invoice. Payment must be received by Cashier.','invoice_id'=>$invoiceId]);
} catch(Throwable $e) {
    $conn->rollback();
    error_log('HMS legacy billing bridge error: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Unable to save billing charge.']);
}
?>