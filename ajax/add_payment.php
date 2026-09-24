<?php
require_once('../config/config.php');
require_once('../includes/session.php');
require_once('../helpers/billing.php');

require_login();

$invoice_id = (int)($_POST['invoice_id'] ?? 0);
$new_payment = round((float)($_POST['amount'] ?? 0), 2);
$method = trim((string)($_POST['method'] ?? 'Cash'));

if ($invoice_id <= 0 || $new_payment <= 0) {
    echo "Invalid payment details";
    exit;
}

$conn->begin_transaction();
try {
    $payment = record_payment($conn, $invoice_id, $new_payment, $method, null);
    post_payment_journal($conn, $invoice_id, $payment['amount'], $method);
    $conn->commit();

    echo "Payment Updated Successfully";
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(400);
    echo $e->getMessage();
}
?>