<?php
include('../config/config.php');
require_once('../helpers/billing.php');

$patient_id = $_POST['patient_id'];
$total_amount = $_POST['total_amount'];
$amount_paid = $_POST['amount_paid'];

$status = 'unpaid';
if ($amount_paid >= $total_amount) {
    $status = 'paid';
} elseif ($amount_paid > 0) {
    $status = 'partial';
}

$inv_no = generate_invoice_number($conn);

$invoice_id = create_invoice($conn, $patient_id, null, null, $status, null, $total_amount, $inv_no);

// Post journal entries for invoice creation
post_invoice_journal($conn, $invoice_id, $patient_id, $total_amount, 'Invoice ' . $inv_no);

if ($amount_paid > 0) {
    record_payment($conn, $invoice_id, $amount_paid, 'cash', null);
}

echo "Invoice Saved Successfully";
?>
