<?php
include('../config/config.php');

$invoice_id = $_POST['invoice_id'];
$new_payment = $_POST['amount'];

$get_invoice = $conn->query("SELECT * FROM invoices WHERE id = $invoice_id");
$invoice = $get_invoice->fetch_assoc();

$new_amount_paid = $invoice['amount_paid'] + $new_payment;
$new_balance = $invoice['total_amount'] - $new_amount_paid;

if ($new_balance <= 0) {
    $payment_status = "Paid";
    $new_balance = 0;
} else {
    $payment_status = "Partial";
}

$update = $conn->prepare("UPDATE invoices 
SET amount_paid = ?, balance = ?, payment_status = ? 
WHERE id = ?");

$update->bind_param("ddsi", $new_amount_paid, $new_balance, $payment_status, $invoice_id);
$update->execute();

echo "Payment Updated Successfully";
?>
