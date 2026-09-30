<?php
/**
 * Cashier receipt compatibility route.
 * Accepts the payment ID used by the Cashier module and opens the
 * dedicated payment receipt, not the invoice document.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isSuper = !empty($_SESSION['is_super']) && (int)$_SESSION['is_super'] === 1;
if (!$isSuper && !in_array($role, ['admin', 'cashier'], true)) {
    http_response_code(403);
    die('Access denied. Only the cashier or administrator can print payment receipts.');
}

$paymentId = (int)($_GET['id'] ?? 0);
if ($paymentId <= 0) die('Invalid payment ID.');

$stmt = $conn->prepare('SELECT invoice_id FROM payments WHERE id=? LIMIT 1');
if (!$stmt) die('Unable to load payment.');
$stmt->bind_param('i',$paymentId);
$stmt->execute();
$row=$stmt->get_result()->fetch_assoc();
$stmt->close();

$invoiceId=(int)($row['invoice_id']??0);
if($invoiceId<=0) die('This payment is not linked to an invoice.');

header('Location: /hospital_system/billing/print_receipt.php?id='.$paymentId);
exit;
?>