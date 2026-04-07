<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_role(['pharmacist', 'admin', 'accountant']);

$encounter_id = intval($_GET['encounter_id'] ?? 0);

if ($encounter_id <= 0) {
    $_SESSION['error'] = "Invalid Encounter ID.";
    header("Location: ../billing/list_encounters.php");
    exit();
}

// 1. Pre-fetch Data & Check if there's actually anything to bill
$check_query = "
    SELECT (SELECT COUNT(*) FROM patient_procedures WHERE encounter_id = ? AND invoice_id IS NULL) as proc_count,
           (SELECT COUNT(*) FROM prescriptions WHERE encounter_id = ? AND invoice_id IS NULL) as rx_count,
           e.patient_id 
    FROM encounters e WHERE e.id = ?";

$stmt = $conn->prepare($check_query);
$stmt->bind_param("iii", $encounter_id, $encounter_id, $encounter_id);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();

if (!$counts || ($counts['proc_count'] == 0 && $counts['rx_count'] == 0)) {
    $_SESSION['error'] = "No pending items found for this encounter or it is already invoiced.";
    header("Location: view_patient_history.php?id=" . ($counts['patient_id'] ?? ''));
    exit();
}

$patient_id = $counts['patient_id'];
$total_invoice_amount = 0;

// ==============================================================================
// 2. START TRANSACTION
// ==============================================================================
$conn->begin_transaction();

try {
    // Create Main Invoice
    $stmt = $conn->prepare("INSERT INTO invoices (patient_id, encounter_id, status, total, created_at) VALUES (?, ?, 'Unpaid', 0, NOW())");
    $stmt->bind_param("ii", $patient_id, $encounter_id);
    $stmt->execute();
    $invoice_id = $conn->insert_id;

    // Prepare the insertion for items (we reuse this for both procedures and drugs)
    $item_stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, price, total) VALUES (?, ?, ?, ?, ?)");

    /* --- SECTION A: PROCEDURES --- */
    $pp_query = "SELECT pp.id, pr.name, pr.price 
                 FROM patient_procedures pp 
                 JOIN procedures pr ON pr.id = pp.procedure_id 
                 WHERE pp.encounter_id = ? AND pp.invoice_id IS NULL";
    
    $pp_stmt = $conn->prepare($pp_query);
    $pp_stmt->bind_param("i", $encounter_id);
    $pp_stmt->execute();
    $pp_list = $pp_stmt->get_result();

    while ($p = $pp_list->fetch_assoc()) {
        $qty = 1;
        $line_total = $p['price'] * $qty;
        
        $item_stmt->bind_param("isidd", $invoice_id, $p['name'], $qty, $p['price'], $line_total);
        $item_stmt->execute();

        // Update procedure record
        $conn->query("UPDATE patient_procedures SET invoice_id = $invoice_id WHERE id = {$p['id']}");
        $total_invoice_amount += $line_total;
    }

    /* --- SECTION B: DRUGS --- */
    $rx_query = "SELECT r.id, r.drug_name, r.quantity, d.selling_price 
                 FROM prescriptions r
                 JOIN drugs d ON r.drug_id = d.id 
                 WHERE r.encounter_id = ? AND r.invoice_id IS NULL";
    
    $rx_stmt = $conn->prepare($rx_query);
    $rx_stmt->bind_param("i", $encounter_id);
    $rx_stmt->execute();
    $rx_list = $rx_stmt->get_result();

    while ($r = $rx_list->fetch_assoc()) {
        $qty = ($r['quantity'] > 0) ? $r['quantity'] : 1;
        $unit_price = $r['selling_price'];
        $line_total = $qty * $unit_price;

        $item_stmt->bind_param("isidd", $invoice_id, $r['drug_name'], $qty, $unit_price, $line_total);
        $item_stmt->execute();

        // Update prescription record
        $conn->query("UPDATE prescriptions SET invoice_id = $invoice_id WHERE id = {$r['id']}");
        $total_invoice_amount += $line_total;
    }

    // 3. Finalize Total
    $update_total = $conn->prepare("UPDATE invoices SET total = ? WHERE id = ?");
    $update_total->bind_param("di", $total_invoice_amount, $invoice_id);
    $update_total->execute();

    // IF WE REACHED HERE WITHOUT ERROR, SAVE EVERYTHING
    $conn->commit();

    header("Location: view_invoice.php?id=$invoice_id&success=generated");
    exit();

} catch (Exception $e) {
    // SOMETHING WENT WRONG, UNDO ALL CHANGES
    $conn->rollback();
    $_SESSION['error'] = "Billing Error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}