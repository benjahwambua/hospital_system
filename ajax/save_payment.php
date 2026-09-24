<?php
require_once '../config/config.php';
require_once '../includes/session.php';
require_once '../helpers/billing.php';

require_login();
header('Content-Type: application/json');

$patient_id = (int)($_POST['patient_id'] ?? 0);
$amount = round((float)($_POST['amount'] ?? 0), 2);
$method = trim((string)($_POST['method'] ?? 'Cash'));

if ($patient_id <= 0 || $amount <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid payment details']);
    exit;
}

try {
    $invoice_id = get_or_create_invoice($conn, $patient_id);
    $payment = record_payment($conn, $invoice_id, $amount, $method, null);
    post_payment_journal($conn, $invoice_id, $payment['amount'], $method);

    echo json_encode([
        'success'=>true,
        'invoice_id'=>$invoice_id,
        'amount'=>$payment['amount'],
        'balance'=>$payment['balance'],
        'status'=>$payment['status']
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>