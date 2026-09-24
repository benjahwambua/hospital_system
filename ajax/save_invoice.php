<?php
require_once('../config/config.php');
require_once('../includes/session.php');
require_once('../helpers/billing.php');

require_login();

$patient_id = (int)($_POST['patient_id'] ?? 0);
$total_amount = round((float)($_POST['total_amount'] ?? 0), 2);
$amount_paid = round((float)($_POST['amount_paid'] ?? 0), 2);

if ($patient_id <= 0 || $total_amount <= 0) {
    echo "Invalid invoice details";
    exit;
}

$inv_no = generate_invoice_number($conn);

$invoice_id = create_invoice(
    $conn,
    $patient_id,
    null,
    null,
    'unpaid',
    null,
    0.0,
    $inv_no
);

add_invoice_item($conn, $invoice_id, 'Invoice Total', 1, $total_amount, 'billing');
update_invoice_total($conn, $invoice_id, $total_amount);
post_invoice_journal($conn, $invoice_id, $patient_id, $total_amount, 'Invoice ' . $inv_no);

if ($amount_paid > 0) {
    $payment = record_payment($conn, $invoice_id, min($amount_paid, $total_amount), 'Cash', null);
    post_payment_journal($conn, $invoice_id, $payment['amount'], 'Cash');
}

echo "Invoice Saved Successfully";
?>