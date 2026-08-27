<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_role(['pharmacist', 'admin', 'accountant']); // Expanded roles for flexibility

// 1. Validate Encounter ID
$encounter_id = intval($_GET['encounter_id'] ?? 0);
if ($encounter_id <= 0) {
    die("Error: Invalid Encounter ID.");
}

// 2. Fetch Patient ID from the Encounter to ensure accuracy
$enc_stmt = $conn->prepare("SELECT patient_id FROM encounters WHERE id = ?");
$enc_stmt->bind_param("i", $encounter_id);
$enc_stmt->execute();
$enc_res = $enc_stmt->get_result()->fetch_assoc();

if (!$enc_res) {
    die("Error: Encounter not found.");
}
$patient_id = $enc_res['patient_id'];

// 3. Create the Main Invoice Record
// Note: Adjusted to include patient_id for better cross-referencing in view_bills.php
$stmt = $conn->prepare("INSERT INTO invoices (patient_id, encounter_id, status, created_at) VALUES (?, ?, 'Unpaid', NOW())");
$stmt->bind_param("ii", $patient_id, $encounter_id);
$stmt->execute();
$invoice_id = $conn->insert_id;

$total_invoice_amount = 0;

/* --- SECTION A: PROCEDURES --- */
// Fetch procedures linked to this encounter that haven't been invoiced yet
$pp_query = "SELECT pp.id, pr.name, pr.price 
             FROM patient_procedures pp 
             JOIN procedures pr ON pr.id = pp.procedure_id 
             WHERE pp.encounter_id = ? AND pp.invoice_id IS NULL";

$stmt = $conn->prepare($pp_query);
$stmt->bind_param("i", $encounter_id);
$stmt->execute();
$pp_list = $stmt->get_result();

while ($p = $pp_list->fetch_assoc()) {
    $desc = $p['name'];
    $price = $p['price'];
    $ref_id = $p['id'];

    // Insert into items
    $item_stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, price, total) VALUES (?, ?, 1, ?, ?)");
    $item_stmt->bind_param("isdd", $invoice_id, $desc, $price, $price);
    $item_stmt->execute();

    // Mark as invoiced
    $conn->query("UPDATE patient_procedures SET invoice_id = $invoice_id WHERE id = $ref_id");
    $total_invoice_amount += $price;
}

/* --- SECTION B: DRUGS / PRESCRIPTIONS --- */
// Fetch prescriptions linked to this encounter with their drug prices
$rx_query = "SELECT r.id, r.drug_name, r.quantity, d.selling_price 
             FROM prescriptions r
             JOIN drugs d ON r.drug_id = d.id 
             WHERE r.encounter_id = ? AND r.invoice_id IS NULL";

$stmt = $conn->prepare($rx_query);
$stmt->bind_param("i", $encounter_id);
$stmt->execute();
$rx_list = $stmt->get_result();

while ($r = $rx_list->fetch_assoc()) {
    $qty = $r['quantity'] > 0 ? $r['quantity'] : 1;
    $unit_price = $r['selling_price'];
    $line_total = $qty * $unit_price;
    $ref_id = $r['id'];

    // Insert into items
    $item_stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
    $item_stmt->bind_param("isidd", $invoice_id, $r['drug_name'], $qty, $unit_price, $line_total);
    $item_stmt->execute();

    // Mark as invoiced
    $conn->query("UPDATE prescriptions SET invoice_id = $invoice_id WHERE id = $ref_id");
    $total_invoice_amount += $line_total;
}

// 4. Update the final total in the main invoice table
$update_total = $conn->prepare("UPDATE invoices SET total = ? WHERE id = ?");
$update_total->bind_param("di", $total_invoice_amount, $invoice_id);
$update_total->execute();

// 5. Redirect to the professional view we created earlier
header("Location: view_invoice.php?id=$invoice_id");
exit();