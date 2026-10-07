<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';

header('Content-Type: application/json');

$raw=file_get_contents('php://input');
$data=json_decode($raw,true);
$callback=$data['Body']['stkCallback'] ?? [];
$checkout=trim((string)($callback['CheckoutRequestID'] ?? ''));

if($checkout===''){ echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Accepted']); exit; }

$conn->begin_transaction();
try {
    $stmt=$conn->prepare("SELECT * FROM mpesa_transactions WHERE checkout_request_id=? LIMIT 1 FOR UPDATE");
    if(!$stmt) throw new Exception('Unable to load M-Pesa transaction.');
    $stmt->bind_param('s',$checkout); $stmt->execute(); $tx=$stmt->get_result()->fetch_assoc(); $stmt->close();

    if(!$tx || ($tx['status'] ?? '')==='completed'){
        $conn->commit();
        echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Accepted']); exit;
    }

    $resultCode=(string)($callback['ResultCode'] ?? '');
    $resultDesc=(string)($callback['ResultDesc'] ?? '');
    $receipt=null; $phone=(string)($tx['phone'] ?? ''); $callbackAmount=null;
    foreach(($callback['CallbackMetadata']['Item'] ?? []) as $item){
        $name=$item['Name'] ?? '';
        if($name==='MpesaReceiptNumber') $receipt=trim((string)($item['Value'] ?? ''));
        if($name==='Amount') $callbackAmount=(float)($item['Value'] ?? 0);
        if($name==='PhoneNumber') $phone=(string)($item['Value'] ?? $phone);
    }
    $status=($resultCode==='0')?'completed':'failed';
    if($status==='completed' && $callbackAmount===null){
        $status='failed';
        $resultDesc='M-Pesa callback did not include a payment amount.';
    } elseif($status==='completed' && abs($callbackAmount-(float)$tx['amount'])>0.00001){
        $status='failed';
        $resultDesc='M-Pesa callback amount does not match the initiated transaction amount.';
    }

    $stmt=$conn->prepare("UPDATE mpesa_transactions SET result_code=?, result_desc=?, mpesa_receipt=?, phone=?, status=?, raw_response=?, updated_at=NOW() WHERE id=?");
    if(!$stmt) throw new Exception('Unable to update M-Pesa transaction.');
    $stmt->bind_param('ssssssi',$resultCode,$resultDesc,$receipt,$phone,$status,$raw,$tx['id']);
    if(!$stmt->execute()) throw new Exception($stmt->error);
    $stmt->close();

    if($status==='completed' && $receipt!==''){
        $checkTx=$conn->prepare("SELECT id,invoice_id,status FROM mpesa_transactions WHERE mpesa_receipt=? AND id<>? LIMIT 1 FOR UPDATE");
        if(!$checkTx) throw new Exception('Unable to check M-Pesa receipt uniqueness.');
        $checkTx->bind_param('si',$receipt,$tx['id']); $checkTx->execute(); $duplicateTx=$checkTx->get_result()->fetch_assoc(); $checkTx->close();
        if($duplicateTx){
            $status='failed';
            $resultDesc='M-Pesa receipt is already linked to another transaction.';
            $dup=$conn->prepare("UPDATE mpesa_transactions SET status='failed', result_desc=?, updated_at=NOW() WHERE id=?");
            if(!$dup) throw new Exception('Unable to reject duplicate M-Pesa receipt.');
            $dup->bind_param('si',$resultDesc,$tx['id']); if(!$dup->execute()) throw new Exception('Unable to reject duplicate M-Pesa receipt.'); $dup->close();
        }
    }
    if($status==='completed' && $receipt!==''){
        $check=$conn->prepare("SELECT id FROM payments WHERE reference=? LIMIT 1");
        if(!$check) throw new Exception('Unable to check payment reference.');
        $check->bind_param('s',$receipt); $check->execute(); $existing=$check->get_result()->fetch_assoc(); $check->close();

        if(!$existing){
            $payment=record_payment($conn,(int)$tx['invoice_id'],(float)$tx['amount'],'M-Pesa',$receipt,(int)($tx['cashier_shift_id']??0));
            post_payment_journal($conn,(int)$tx['invoice_id'],$payment['amount'],'M-Pesa',$payment['payment_id']);
        }
    }
    $conn->commit();
}catch(Throwable $e){
    $conn->rollback();
    error_log('HMS M-Pesa callback error: '.$e->getMessage());
}
echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Accepted']);
