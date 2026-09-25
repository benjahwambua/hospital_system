<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../helpers/billing.php';
require_once __DIR__.'/../helpers/cashier.php';
require_once __DIR__.'/../config/mpesa.php';
require_login();

// Centralize all patient collections through the Cashier module.
$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isSuper = !empty($_SESSION['is_super']) && (int)$_SESSION['is_super'] === 1;
if (!$isSuper && !in_array($role, ['admin', 'cashier'], true)) {
    http_response_code(403);
    die('Access denied. Patient payments must be recorded by the Cashier.');
}

$id=(int)($_POST['invoice_id'] ?? $_GET['id'] ?? 0);
$amount=(float)($_POST['amount'] ?? 0);
$mode=trim((string)($_POST['payment_mode'] ?? 'Cash'));
$mark_paid=isset($_POST['mark_paid']) && $_POST['mark_paid']=='1';
$cashierId=(int)($_SESSION['user_id']??0);
$shift=get_open_cashier_shift($conn,$cashierId);

if(!$id) die("Invalid invoice ID");
if(!$shift) die("No open cashier shift. Please open your cashier shift before receiving patient payments.");

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
        $payment=record_payment($conn,$id,$paymentAmount,'Mpesa',$receipt,(int)$shift['id']);
        record_manual_mpesa_transaction($conn,$id,(int)($invoice['patient_id'] ?? 0),$payment['amount'],$phone,$receipt,'Manually recorded M-Pesa payment');
        post_payment_journal($conn,$id,$payment['amount'],'Mpesa',$payment['payment_id']);
        $conn->commit();
        if (($_POST['return_to'] ?? '') === 'cashier') {
            header("Location: /hospital_system/cashier/index.php?success=1");
        } else {
            header("Location: /hospital_system/billing/view_invoice.php?id=".$id."&success=1&mpesa=recorded");
        }
        exit;
    }

    if($mark_paid && $remaining>0){
        $payment=record_payment($conn,$id,$remaining,$mode,null,(int)$shift['id']);
        post_payment_journal($conn,$id,$payment['amount'],$mode,$payment['payment_id']);
    }elseif($amount>0 && $remaining>0){
        $payment=record_payment($conn,$id,min($amount,$remaining),$mode,null,(int)$shift['id']);
        post_payment_journal($conn,$id,$payment['amount'],$mode,$payment['payment_id']);
    }

    $conn->commit();

    if(isset($_POST['ajax'])){
        echo json_encode(['status'=>'success']);
    }else{
        if (($_POST['return_to'] ?? '') === 'cashier') {
            header("Location: /hospital_system/cashier/index.php?success=1");
        } else {
            header("Location: /hospital_system/billing/view_invoice.php?id=".$id."&success=1");
        }
        exit;
    }
}catch(Throwable $e){
    $conn->rollback();
    if(isset($_POST['ajax'])) echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    else die("Error: ".$e->getMessage());
}
