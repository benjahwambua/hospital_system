<?php
function get_or_create_invoice($conn, $patient_id, $encounter_id = null) {

    $q = $conn->prepare("
        SELECT id FROM invoices 
        WHERE patient_id = ? AND status = 'unpaid'
        ORDER BY id DESC LIMIT 1
    ");
    $q->bind_param("i", $patient_id);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();

    if ($res) return $res['id'];

    $inv_no = 'INV-' . date('YmdHis') . '-' . rand(100,999);
    $stmt = $conn->prepare("
        INSERT INTO invoices (patient_id, invoice_number, total, status, encounter_id)
        VALUES (?, ?, 0, 'unpaid', ?)
    ");
    $stmt->bind_param("isi", $patient_id, $inv_no, $encounter_id);
    $stmt->execute();

    return $conn->insert_id;
}
