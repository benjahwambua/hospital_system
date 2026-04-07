<?php
include('../config/config.php');

$patient_id = $_POST['patient_id'];
$total_amount = $_POST['total_amount'];
$amount_paid = $_POST['amount_paid'];

$balance = $total_amount - $amount_paid;

if ($balance <= 0) {
    $payment_status = "Paid";
    $balance = 0;
} elseif ($amount_paid > 0) {
    $payment_status = "Partial";
} else {
    $payment_status = "Unpaid";
}

$stmt = $conn->prepare("INSERT INTO invoices 
(patient_id, total_amount, amount_paid, balance, payment_status) 
VALUES (?, ?, ?, ?, ?)");

$stmt->bind_param("iddds", $patient_id, $total_amount, $amount_paid, $balance, $payment_status);

$stmt->execute();

echo "Invoice Saved Successfully";
?>
