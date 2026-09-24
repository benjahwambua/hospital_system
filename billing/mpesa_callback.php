<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/billing.php';

header('Content-Type: application/json');

$raw=file_get_contents('php://input');
$data=json_decode($raw,true);
$callback=$data['Body']['stkCallback'] ?? [];
$checkout=(string)($callback['CheckoutRequestID'] ?? '');

if($checkout===''){ echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Accepted']); exit; }

$stmt=$conn->prepare("SELECT * FROM mpesa_transactions WHERE checkout_request_id=? LIMIT 1");
$stmt->bind_param('s',$checkout);
$stmt->execute();
$tx=$stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$tx || ($tx['status'] ?? '')==='completed'){
    echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Accepted']); exit;
}

$resultCode=(string)($callback['ResultCode'] ?? '');
$resultDesc=(string)($callback['ResultDesc'] ?? '');
$receipt=null;
$phone=$tx['phone'];

foreach(($callback['CallbackMetadata']['Item'] ?? []) as $item){
    $name=$item['Name'] ?? '';
    if($name==='MpesaReceiptNumber') $receipt=(string)($item['Value'] ?? '');
    if($name==='PhoneNumber') $phone=(string)($item['Value'] ?? $phone);
}

$conn->begin_transaction();
try{
    $status=($resultCode==='0')?'completed':'failed';
    $stmt=$conn->prepare("UPDATE mpesa_transactions SET result_code=?, result_desc=?, mpesa_receipt=?, phone=?, status=?, raw_response=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param('ssssssi',$resultCode,$resultDesc,$receipt,$phone,$status,$raw,$tx['id']);
    $stmt->execute();
    $stmt->close();

    if($status==='completed' && $receipt){
        $check=$conn->prepare("SELECT id FROM payments WHERE reference=? LIMIT 1");
        $check->bind_param('s',$receipt);
        $check->execute();
        $existing=$check->get_result()->fetch_assoc();
        $check->close();

        if(!$existing){
            $payment=record_payment($conn,(int)$tx['invoice_id'],(float)$tx['amount'],'M-Pesa',$receipt);
            post_payment_journal($conn,(int)$tx['invoice_id'],$payment['amount'],'M-Pesa');
        }
    }
    $conn->commit();
}catch(Throwable $e){
    $conn->rollback();
}
echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Accepted']);
