<?php
/**
 * Cashier receipt compatibility route.
 *
 * The hospital system uses billing/view_invoice.php as the authoritative
 * printable document because it contains the invoice items and the exact
 * amount paid/balance for that invoice.
 *
 * This route accepts the historical payment ID used by the Cashier module,
 * resolves its invoice, then opens the invoice print view.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isSuper = !empty($_SESSION['is_super']) && (int)$_SESSION['is_super'] === 1;
if (!$isSuper && !in_array($role, ['admin', 'cashier'], true)) {
    http_response_code(403);
    die('Access denied. Only the cashier or administrator can print payment documents.');
}

$paymentId = (int)($_GET['id'] ?? 0);
if ($paymentId <= 0) {
    die('Invalid payment ID.');
}

$stmt = $conn->prepare('SELECT invoice_id FROM payments WHERE id = ? LIMIT 1');
if (!$stmt) {
    die('Unable to load payment.');
}

$stmt->bind_param('i', $paymentId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$invoiceId = (int)($row['invoice_id'] ?? 0);
if ($invoiceId <= 0) {
    die('This payment is not linked to an invoice.');
}

header('Location: /hospital_system/billing/view_invoice.php?id=' . $invoiceId . '&print=1');
exit;
?>