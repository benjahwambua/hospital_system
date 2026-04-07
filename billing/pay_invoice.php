<?php
require_once __DIR__.'/../config/config.php';

$id = (int)$_POST['invoice_id'];
$mode = $_POST['payment_mode'];

$stmt = $conn->prepare("
UPDATE invoices 
SET status='paid', payment_mode=?, paid_at=NOW()
WHERE id=?
");
$stmt->bind_param("si",$mode,$id);
$stmt->execute();

echo json_encode(['status'=>'success']);
