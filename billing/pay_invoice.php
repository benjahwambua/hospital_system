<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../helpers/billing.php';
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
    $paid=(float)($invoice['paid_amount'] ?? 0);
    $remaining=max((float)($invoice['total'] ?? 0)-$paid,0);

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
