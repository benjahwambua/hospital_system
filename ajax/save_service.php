<?php
require_once '../config/config.php';
require_once '../includes/session.php';
require_once '../helpers/billing.php';

require_login();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$patient_id = (int)($data['patient_id'] ?? 0);
$service_name = trim((string)($data['service_name'] ?? ''));
$category = trim((string)($data['category'] ?? ''));
$price = round((float)($data['price'] ?? 0), 2);

if (!$patient_id || !$service_name || !$category || $price < 0) {
    echo json_encode(['status'=>'error','message'=>'Invalid service details']);
    exit;
}

$stmt = $conn->prepare("SELECT id, service_name, category, price FROM services_master WHERE service_name = ? AND category = ? AND active = 1 LIMIT 1");
$stmt->bind_param("ss", $service_name, $category);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$service) {
    echo json_encode(['status'=>'error','message'=>'Service not found']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO patient_services (patient_id, service_id, category, price, created_at, status) VALUES (?, ?, ?, ?, NOW(), 'Completed')");
$stmt->bind_param("iisd", $patient_id, $service['id'], $service['category'], $price);

if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    echo json_encode(['status'=>'error','message'=>$error]);
    exit;
}
$stmt->close();

$invoice_id = get_or_create_invoice($conn, $patient_id);
add_invoice_item($conn, $invoice_id, 'Service: ' . $service['service_name'], 1, $price, 'service', (int)$service['id']);

echo json_encode([
    'status'=>'success',
    'message'=>'Service added successfully',
    'invoice_id'=>$invoice_id
]);
?>