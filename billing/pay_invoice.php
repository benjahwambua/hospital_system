<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../helpers/billing.php';
require_once __DIR__.'/../config/mpesa.php';
require_login();

$id=(int)($_POST['invoice_id'] ?? $_GET['id'] ?? 0);
$amount=(float)($_POST['amount'] ?? 0);
$mode=trim((string)($_POST['payment_mode'] ?? 'Cash'));
$mark_paid=isset($_POST['mark_paid']) && $_POST['mark_paid']=='1';

if(!$id) die("Invalid invoice ID");

$stmt=$conn->prepare("SELECT * FROM invoices WHERE id=? LIMIT 1");
$stmt->bind_param('i',$id);
$stmt->execute();
$invoice=$stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$invoice) die("Invoice not found");

$conn->begin_transaction();
try{
    $paidStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total_paid FROM payments WHERE invoice_id=?");
    $paidStmt->bind_param('i',$id);
    $paidStmt->execute();
    $paidRow = $paidStmt->get_result()->fetch_assoc();
    $paidStmt->close();
    $paid = (float)($paidRow['total_paid'] ?? 0);
    $remaining=max((float)($invoice['total'] ?? 0)-$paid,0);

    // M-Pesa is recorded manually by receipt code. STK Push is optional
    // and is handled separately from this payment-recording endpoint.
    if(strtolower($mode)==='mpesa' && $remaining>0){
        $phone = trim((string)($_POST['phone'] ?? ''));
        if($phone===''){
            $pstmt=$conn->prepare("SELECT phone FROM patients WHERE id=? LIMIT 1");
            if($pstmt){
                $patientId=(int)($invoice['patient_id'] ?? 0);
                $pstmt->bind_param('i',$patientId);
                $pstmt->execute();
                $prow=$pstmt->get_result()->fetch_assoc();
                $pstmt->close();
                $phone=trim((string)($prow['phone'] ?? ''));
            }
        }
        $receipt = strtoupper(trim((string)($_POST['mpesa_receipt'] ?? $_POST['reference'] ?? '')));
        // Receipt and phone are optional for manual M-Pesa record keeping.
        $paymentAmount = $amount>0 ? min($amount,$remaining) : $remaining;
        $payment=record_payment($conn,$id,$paymentAmount,'Mpesa',$receipt);
        record_manual_mpesa_transaction($conn,$id,(int)($invoice['patient_id'] ?? 0),$payment['amount'],$phone,$receipt,'Manually recorded M-Pesa payment');
        post_payment_journal($conn,$id,$payment['amount'],'Mpesa');
        $conn->commit();
        header("Location: /hospital_system/billing/view_invoice.php?id=".$id."&success=1&mpesa=recorded");
        exit;
    }

    if($mark_paid && $remaining>0){
        $payment=record_payment($conn,$id,$remaining,$mode,null);
        post_payment_journal($conn,$id,$payment['amount'],$mode);
    }elseif($amount>0 && $remaining>0){
        $payment=record_payment($conn,$id,min($amount,$remaining),$mode,null);
        post_payment_journal($conn,$id,$payment['amount'],$mode);
    }

    $conn->commit();

    if(isset($_POST['ajax'])){
        echo json_encode(['status'=>'success']);
    }else{
        header("Location: /hospital_system/billing/view_invoice.php?id=".$id."&success=1");
        exit;
    }
}catch(Throwable $e){
    $conn->rollback();
    if(isset($_POST['ajax'])) echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    else die("Error: ".$e->getMessage());
}
