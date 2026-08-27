<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

header('Content-Type: application/json');

$patient_id = intval($_POST['patient_id'] ?? 0);
$medicine_id = intval($_POST['medicine_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

if(!$patient_id || !$medicine_id || $quantity < 1){
    echo json_encode(['status'=>0,'message'=>'Invalid input']);
    exit;
}

// Check stock
$stock = $conn->query("SELECT drug_name, selling_price, quantity FROM pharmacy_stock WHERE id=$medicine_id")->fetch_assoc();
if(!$stock || $stock['quantity'] < $quantity){
    echo json_encode(['status'=>0,'message'=>'Insufficient stock']);
    exit;
}

// Insert prescription
$stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, medicine_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iii", $patient_id, $medicine_id, $quantity);

if($stmt->execute()){
    $prescription_id = $stmt->insert_id;
    $stmt->close();

    // Immediately create/update patient invoice for the prescribed medication
    $invoice_id = get_or_create_invoice($conn, $patient_id);
    add_invoice_item(
        $conn,
        $invoice_id,
        'Medication: ' . $stock['drug_name'],
        $quantity,
        (float)$stock['selling_price'],
        'pharmacy',
        $medicine_id
    );

    $update_prescription = $conn->prepare("UPDATE prescriptions SET invoice_id = ? WHERE id = ?");
    if ($update_prescription) {
        $update_prescription->bind_param('ii', $invoice_id, $prescription_id);
        $update_prescription->execute();
        $update_prescription->close();
    }

    // Reduce stock
    $conn->query("UPDATE pharmacy_stock SET quantity = quantity - $quantity WHERE id = $medicine_id");
    echo json_encode(['status'=>1,'message'=>'Prescription added successfully']);
}else{
    echo json_encode(['status'=>0,'message'=>'Error adding prescription']);
    $stmt->close();
}
