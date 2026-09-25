<?php
function ensure_walkin_column($conn): void {
    $check = $conn->query("SHOW COLUMNS FROM patients LIKE 'is_walkin'");
    if (!$check || $check->num_rows === 0) {
        if (!$conn->query("ALTER TABLE patients ADD COLUMN is_walkin TINYINT(1) NOT NULL DEFAULT 0")) {
            throw new Exception('Unable to prepare walk-in patient field: ' . $conn->error);
        }
    }
}

function hms_visits_available($conn): bool {
    $check = $conn->query("SHOW TABLES LIKE 'visits'");
    return $check && $check->num_rows > 0;
}

function get_or_create_current_visit($conn, int $patient_id, string $visitType = 'Outpatient', string $clinicCategory = 'General', int $doctorId = 0): int {
    if ($patient_id <= 0 || !hms_visits_available($conn)) {
        return 0;
    }

    $stmt = $conn->prepare("SELECT id FROM visits WHERE patient_id = ? AND visit_date = CURDATE() AND status IN ('Open','In Progress') ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) return (int)$row['id'];
    }

    $visitNumber = 'V-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
    $visitStmt = $conn->prepare(
        "INSERT INTO visits (visit_number, patient_id, visit_date, visit_time, visit_type, clinic_category, doctor_id, status, created_by, created_at)
         VALUES (?, ?, CURDATE(), CURTIME(), ?, ?, NULLIF(?,0), 'Open', NULLIF(?,0), NOW())"
    );
    if (!$visitStmt) {
        throw new Exception('Unable to create patient visit: ' . $conn->error);
    }
    $createdBy = (int)($_SESSION['user_id'] ?? 0);
    $visitStmt->bind_param('sissii', $visitNumber, $patient_id, $visitType, $clinicCategory, $doctorId, $createdBy);
    if (!$visitStmt->execute()) {
        $error = $visitStmt->error;
        $visitStmt->close();
        throw new Exception('Unable to create patient visit: ' . $error);
    }
    $id = (int)$visitStmt->insert_id;
    $visitStmt->close();
    return $id;
}

function invoice_column_exists($conn, $column) {
    $check = $conn->query("SHOW COLUMNS FROM invoices LIKE '" . $conn->real_escape_string($column) . "'");
    return $check && $check->num_rows > 0;
}

function invoice_item_column_exists($conn, $column) {
    $check = $conn->query("SHOW COLUMNS FROM invoice_items LIKE '" . $conn->real_escape_string($column) . "'");
    return $check && $check->num_rows > 0;
}

function get_invoice_number_column($conn) {
    if (invoice_column_exists($conn, 'invoice_number')) {
        return 'invoice_number';
    }
    if (invoice_column_exists($conn, 'invoice_no')) {
        return 'invoice_no';
    }
    return null;
}

function create_invoice($conn, $patient_id = null, $encounter_id = null, $walkin_id = null, $status = 'unpaid', $payment_mode = null, $paid_amount = 0.0, $invoice_number = null, $visit_id = null) {
    $columns = [];
    $placeholders = [];
    $types = '';
    $values = [];

    if ($patient_id !== null) {
        $columns[] = 'patient_id';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $patient_id;
    }
    if ($encounter_id !== null) {
        $columns[] = 'encounter_id';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $encounter_id;
    }
    if ($visit_id !== null && invoice_column_exists($conn, 'visit_id')) {
        $columns[] = 'visit_id';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $visit_id;
    }
    if ($walkin_id !== null && invoice_column_exists($conn, 'walkin_id')) {
        $columns[] = 'walkin_id';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $walkin_id;
    }

    $numberColumn = get_invoice_number_column($conn);
    if ($numberColumn !== null) {
        $columns[] = $numberColumn;
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $invoice_number ?? 'INV-' . date('YmdHis') . '-' . rand(100, 999);
    }

    $columns[] = 'total';
    $placeholders[] = '?';
    $types .= 'd';
    $values[] = 0.0;

    $columns[] = 'status';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = $status;

    if ($payment_mode !== null && invoice_column_exists($conn, 'payment_mode')) {
        $columns[] = 'payment_mode';
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $payment_mode;
    }

    if (invoice_column_exists($conn, 'paid_amount')) {
        $columns[] = 'paid_amount';
        $placeholders[] = '?';
        $types .= 'd';
        $values[] = $paid_amount;
    }

    if (invoice_column_exists($conn, 'created_at')) {
        $columns[] = 'created_at';
        $placeholders[] = 'NOW()';
    }

    $sql = sprintf(
        'INSERT INTO invoices (%s) VALUES (%s)',
        implode(', ', $columns),
        implode(', ', $placeholders)
    );

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare invoice insert: ' . $conn->error);
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$values);
    }

    $stmt->execute();
    $invoice_id = $stmt->insert_id;
    $stmt->close();

    return $invoice_id;
}

function get_or_create_invoice($conn, $patient_id, $encounter_id = null, $visit_id = null) {
    // Prefer an outstanding invoice belonging to this specific visit.
    // Legacy invoices without visit_id remain available through the patient-wide fallback.
    if ($visit_id !== null && (int)$visit_id > 0 && invoice_column_exists($conn, 'visit_id')) {
        $q = $conn->prepare("
            SELECT id
            FROM invoices
            WHERE patient_id = ?
              AND visit_id = ?
              AND COALESCE(total, 0) > GREATEST(COALESCE(paid_amount, 0), COALESCE(amount_paid, 0))
            ORDER BY id DESC
            LIMIT 1
        ");
        if ($q) {
            $pid=(int)$patient_id; $vid=(int)$visit_id;
            $q->bind_param('ii',$pid,$vid);
            $q->execute();
            $res=$q->get_result()->fetch_assoc();
            $q->close();
            if ($res) return (int)$res['id'];
        }
        return create_invoice($conn, $patient_id, $encounter_id, null, 'unpaid', null, 0.0, null, (int)$visit_id);
    }

    $q = $conn->prepare("
        SELECT id
        FROM invoices
        WHERE patient_id = ?
          AND COALESCE(total, 0) > GREATEST(COALESCE(paid_amount, 0), COALESCE(amount_paid, 0))
        ORDER BY id DESC
        LIMIT 1
    ");
    if (!$q) {
        throw new Exception('Unable to find open invoice: ' . $conn->error);
    }
    $q->bind_param("i", $patient_id);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();
    $q->close();

    if ($res) return (int)$res['id'];
    return create_invoice($conn, $patient_id, $encounter_id);
}

function get_or_create_visit_invoice($conn, int $patient_id, int $visit_id = 0, ?int $encounter_id = null): int {
    if ($patient_id <= 0) throw new Exception('Invalid patient for invoice.');

    if ($visit_id <= 0) {
        $visit_id = get_or_create_current_visit($conn, $patient_id);
    }

    return $visit_id > 0
        ? get_or_create_invoice($conn, $patient_id, $encounter_id, $visit_id)
        : get_or_create_invoice($conn, $patient_id, $encounter_id);
}

function ensure_encounter_visit_column($conn): bool {
    $check = $conn->query("SHOW COLUMNS FROM encounters LIKE 'visit_id'");
    if ($check && $check->num_rows > 0) return true;
    return false;
}

function ensure_prescription_visit_column($conn): bool {
    $check = $conn->query("SHOW COLUMNS FROM prescriptions LIKE 'visit_id'");
    return $check && $check->num_rows > 0;
}

function invoice_is_walkin(array $invoice): bool {
    return !empty($invoice['walkin_id']);
}

function invoice_get_customer_info($conn, array $invoice): array {
    $patientName = 'Walk-in Customer';
    $patientId = null;
    $patientNumber = 'N/A';

    if (invoice_is_walkin($invoice)) {
        $stmt = $conn->prepare('SELECT full_name FROM walkin_customers WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $invoice['walkin_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $patientName = $row['full_name'];
            }
        }
    } elseif (!empty($invoice['patient_id'])) {
        $stmt = $conn->prepare('SELECT id, patient_number, full_name FROM patients WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $invoice['patient_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $patientName = $row['full_name'];
                $patientNumber = $row['patient_number'] ?? 'N/A';
                $patientId = (int)$row['id'];
            }
        }
    }

    return [
        'patient_name' => $patientName,
        'patient_id' => $patientId,
        'patient_number' => $patientNumber,
    ];
}

function invoice_load_items($conn, array $invoice): array {
    $items = [];
    $invoiceId = (int)($invoice['id'] ?? 0);
    if ($invoiceId <= 0) {
        return $items;
    }

    $stmt = $conn->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC');
    if ($stmt) {
        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $items[] = [
                'description' => $row['description'] ?? 'Invoice Item',
                'quantity' => (float)($row['quantity'] ?? ($row['qty'] ?? 1)),
                'price' => (float)($row['price'] ?? ($row['unit_price'] ?? 0)),
                'amount' => (float)($row['total'] ?? ($row['amount'] ?? 0)),
                'source' => $row['source'] ?? 'invoice',
            ];
        }
        $stmt->close();
    }

    if (empty($items) && !invoice_is_walkin($invoice) && !empty($invoice['patient_id'])) {
        $patientId = (int)$invoice['patient_id'];

        $svcRes = $conn->query("SELECT sm.service_name AS description, 1 AS quantity, ps.price AS price, ps.price AS amount, 'service' AS source FROM patient_services ps JOIN services_master sm ON ps.service_id = sm.id WHERE ps.patient_id = {$patientId} AND ps.status != 'Cancelled' ORDER BY ps.created_at ASC");
        if ($svcRes) {
            while ($row = $svcRes->fetch_assoc()) {
                $items[] = [
                    'description' => $row['description'],
                    'quantity' => (float)$row['quantity'],
                    'price' => (float)$row['price'],
                    'amount' => (float)$row['amount'],
                    'source' => $row['source'],
                ];
            }
        }

        $rxRes = $conn->query("SELECT s.drug_name AS description, pr.quantity AS quantity, s.selling_price AS price, (pr.quantity * s.selling_price) AS amount, 'pharmacy' AS source FROM prescriptions pr JOIN pharmacy_stock s ON pr.medicine_id = s.id WHERE pr.patient_id = {$patientId} ORDER BY pr.created_at ASC");
        if ($rxRes) {
            while ($row = $rxRes->fetch_assoc()) {
                $items[] = [
                    'description' => $row['description'],
                    'quantity' => (float)$row['quantity'],
                    'price' => (float)$row['price'],
                    'amount' => (float)$row['amount'],
                    'source' => $row['source'],
                ];
            }
        }
    }

    return $items;
}

function invoice_add_consultation_if_missing(array &$items, bool $isWalkin, float $consultationFee = 200.0): void {
    // Consultation is a real charge created during registration, not a display-only
    // fallback. Never invent a KES 200 line while rendering an invoice.
    return;
}

function ensure_registered_consultation_charge($conn, int $patient_id, float $fee = 200.0): int {
    if ($patient_id <= 0) {
        throw new Exception('Invalid patient for consultation billing.');
    }

    ensure_walkin_column($conn);

    $patientStmt = $conn->prepare("SELECT is_walkin FROM patients WHERE id=? LIMIT 1");
    if (!$patientStmt) throw new Exception('Unable to load patient for consultation billing: '.$conn->error);
    $patientStmt->bind_param('i', $patient_id);
    $patientStmt->execute();
    $patientRow = $patientStmt->get_result()->fetch_assoc();
    $patientStmt->close();

    if (!$patientRow) throw new Exception('Patient not found.');
    if (!empty($patientRow['is_walkin'])) {
        return get_or_create_invoice($conn, $patient_id);
    }

    $visitId = get_or_create_current_visit($conn, $patient_id, 'Outpatient', 'General');
    $invoiceId = get_or_create_visit_invoice($conn, $patient_id, $visitId);

    $serviceStmt = $conn->prepare(
        "SELECT id, category FROM services_master
         WHERE service_name='Consultation' AND active=1 LIMIT 1"
    );
    $service = $serviceStmt ? (function() use ($serviceStmt) {
        $serviceStmt->execute();
        $row=$serviceStmt->get_result()->fetch_assoc();
        $serviceStmt->close();
        return $row;
    })() : null;

    if ($service) {
        $check = $conn->prepare(
            "SELECT id FROM patient_services
             WHERE patient_id=? AND service_id=?
               AND visit_id=? AND status<>'Cancelled' LIMIT 1"
        );
        if ($check) {
            $sid=(int)$service['id'];
            $check->bind_param('iii',$patient_id,$sid,$visitId);
            $check->execute();
            $exists=(bool)$check->get_result()->fetch_assoc();
            $check->close();

            if (!$exists) {
                $category=(string)($service['category'] ?? 'procedures');
                if (invoice_column_exists($conn,'visit_id')) {
                    $stmt=$conn->prepare(
                        "INSERT INTO patient_services
                         (patient_id,service_id,category,price,visit_id,created_at,status)
                         VALUES (?,?,?,?,?,NOW(),'Completed')"
                    );
                    if (!$stmt) throw new Exception('Unable to prepare consultation service: '.$conn->error);
                    $stmt->bind_param('iisdi',$patient_id,$sid,$category,$fee,$visitId);
                } else {
                    $stmt=$conn->prepare(
                        "INSERT INTO patient_services
                         (patient_id,service_id,category,price,created_at,status)
                         VALUES (?,?,?, ?,NOW(),'Completed')"
                    );
                    if (!$stmt) throw new Exception('Unable to prepare consultation service: '.$conn->error);
                    $stmt->bind_param('iisd',$patient_id,$sid,$category,$fee);
                }
                if (!$stmt->execute()) {
                    $err=$stmt->error; $stmt->close();
                    throw new Exception('Unable to create consultation service: '.$err);
                }
                $stmt->close();
            }
        }
    }

    $itemCheck=$conn->prepare(
        "SELECT id FROM invoice_items
         WHERE invoice_id=? AND LOWER(TRIM(description))='service: consultation' LIMIT 1"
    );
    $hasItem=false;
    if($itemCheck){
        $itemCheck->bind_param('i',$invoiceId);
        $itemCheck->execute();
        $hasItem=(bool)$itemCheck->get_result()->fetch_assoc();
        $itemCheck->close();
    }
    if(!$hasItem){
        $invoiceItemId=add_invoice_item($conn,$invoiceId,'Service: Consultation',1,$fee,'service',$service ? (int)$service['id'] : null);
        post_invoice_journal($conn,$invoiceId,$patient_id,$fee,'Consultation',$invoiceItemId);
    }

    return $invoiceId;
}

function remove_walkin_consultation_charge($conn, int $patient_id): void {
    if ($patient_id <= 0) return;

    ensure_walkin_column($conn);
    $patientStmt = $conn->prepare("SELECT is_walkin FROM patients WHERE id = ? LIMIT 1");
    if (!$patientStmt) return;
    $patientStmt->bind_param('i', $patient_id);
    $patientStmt->execute();
    $patientRow = $patientStmt->get_result()->fetch_assoc();
    $patientStmt->close();

    if (empty($patientRow['is_walkin'])) return;

    // Remove the mistakenly-created KES 200 consultation charge only when
    // the invoice has no payments. Never alter an invoice that already has
    // financial activity.
    $invoiceRes = $conn->query("SELECT i.id
        FROM invoices i
        WHERE i.patient_id = " . (int)$patient_id . "
        ORDER BY i.id DESC");
    if (!$invoiceRes) return;

    while ($invoice = $invoiceRes->fetch_assoc()) {
        $invoiceId = (int)$invoice['id'];
        $paymentRes = $conn->query("SELECT COALESCE(SUM(amount),0) AS paid FROM payments WHERE invoice_id = " . $invoiceId);
        $paid = $paymentRes ? (float)($paymentRes->fetch_assoc()['paid'] ?? 0) : 0.0;
        if ($paid > 0) continue;

        $conn->query("DELETE FROM invoice_items
            WHERE invoice_id = " . $invoiceId . "
              AND LOWER(TRIM(description)) IN ('service: consultation', 'consultation')");

        // Remove the matching consultation service record as well.
        $conn->query("DELETE ps FROM patient_services ps
            INNER JOIN services_master sm ON sm.id = ps.service_id
            WHERE ps.patient_id = " . (int)$patient_id . "
              AND ps.status <> 'Cancelled'
              AND LOWER(TRIM(sm.service_name)) = 'consultation'");

        $totalRes = $conn->query("SELECT COALESCE(SUM(total),0) AS total FROM invoice_items WHERE invoice_id = " . $invoiceId);
        $newTotal = $totalRes ? (float)($totalRes->fetch_assoc()['total'] ?? 0) : 0.0;
        update_invoice_total($conn, $invoiceId, $newTotal);

        $conn->query("UPDATE invoices
            SET paid_amount = 0, amount_paid = 0, balance = " . $newTotal . ",
                payment_status = " . ($newTotal > 0 ? "'unpaid'" : "'paid'") . ",
                status = " . ($newTotal > 0 ? "'unpaid'" : "'paid'") . "
            WHERE id = " . $invoiceId);
    }
}

function update_invoice_total($conn, $invoice_id, $total) {
    $stmt = $conn->prepare("UPDATE invoices SET total = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare invoice total update: ' . $conn->error);
    }
    $stmt->bind_param('di', $total, $invoice_id);
    $stmt->execute();
    $stmt->close();
}

function record_invoice_payment($conn, $invoice_id, $amount, $payment_mode = null, $status = 'paid') {
    $sql = "UPDATE invoices SET paid_amount = COALESCE(paid_amount, 0) + ?, status = ?";
    $params = ['ds', $amount, $status];

    if ($payment_mode !== null && invoice_column_exists($conn, 'payment_mode')) {
        $sql .= ", payment_mode = ?";
        $params[0] .= 's';
        $params[] = $payment_mode;
    }

    $sql .= " WHERE id = ?";
    $params[0] .= 'i';
    $params[] = $invoice_id;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare invoice payment update: ' . $conn->error);
    }

    $stmt->bind_param(...$params);
    $stmt->execute();
    $stmt->close();
}

function add_invoice_item($conn, $invoice_id, $description, $qty, $unit_price, $item_type = null, $med_id = null) {
    $total = $qty * $unit_price;
    // HMS has used both (qty, unit_price) and (quantity, price)
    // across older invoice code. Detect the live schema before inserting.
    $qtyColumn = invoice_item_column_exists($conn, 'qty') ? 'qty' : 'quantity';
    $priceColumn = invoice_item_column_exists($conn, 'unit_price') ? 'unit_price' : 'price';

    $columns = ['invoice_id', 'description', $qtyColumn, $priceColumn, 'total'];
    $placeholders = ['?', '?', '?', '?', '?'];
    $types = 'isidd';
    $values = [$invoice_id, $description, $qty, $unit_price, $total];

    if ($item_type !== null && invoice_item_column_exists($conn, 'item_type')) {
        $columns[] = 'item_type';
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $item_type;
    }
    if ($med_id !== null && invoice_item_column_exists($conn, 'med_id')) {
        $columns[] = 'med_id';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $med_id;
    }

    $sql = sprintf(
        'INSERT INTO invoice_items (%s) VALUES (%s)',
        implode(', ', $columns),
        implode(', ', $placeholders)
    );

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare invoice item insert: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Unable to save invoice item: ' . $error);
    }
    $itemId = (int)$stmt->insert_id;
    $stmt->close();

    if ($total !== 0) {
        $updateStmt = $conn->prepare("UPDATE invoices SET total = COALESCE(total, 0) + ? WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception('Unable to prepare invoice total update: ' . $conn->error);
        }
        $updateStmt->bind_param('di', $total, $invoice_id);
        if (!$updateStmt->execute()) {
            $error = $updateStmt->error;
            $updateStmt->close();
            throw new Exception('Unable to update invoice total: ' . $error);
        }
        $updateStmt->close();
    }

    return $itemId;
}

function get_prescription_total($conn, $patient_id) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(p.quantity * s.selling_price), 0) AS total FROM prescriptions p LEFT JOIN pharmacy_stock s ON s.id = p.medicine_id WHERE p.patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float)($result['total'] ?? 0);
}

function get_service_total($conn, $patient_id) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(price), 0) AS total FROM patient_services WHERE patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float)($result['total'] ?? 0);
}

function record_manual_mpesa_transaction($conn, int $invoice_id, int $patient_id, float $amount, string $phone, string $receipt, ?string $resultDesc = null): void {
    $amount = round($amount, 2);
    $phone = trim($phone);
    $receipt = strtoupper(trim($receipt));
    if ($invoice_id <= 0 || $patient_id <= 0 || $amount <= 0) {
        throw new Exception('Invalid M-Pesa transaction details.');
    }
    // Receipt and phone are optional for manual record keeping. Check for
    // duplicates only when a receipt has actually been supplied.
    $receiptValue = $receipt !== '' ? $receipt : null;

    if ($receiptValue !== null) {
        $check = $conn->prepare("SELECT id FROM mpesa_transactions WHERE mpesa_receipt = ? LIMIT 1");
        if ($check) {
            $check->bind_param('s', $receiptValue);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();
            if ($exists) {
                throw new Exception('This M-Pesa receipt has already been recorded.');
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO mpesa_transactions
        (invoice_id, patient_id, amount, phone, mpesa_receipt, result_code, result_desc, status, raw_response, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, '0', ?, 'completed', NULL, NOW(), NOW())");
    if (!$stmt) {
        throw new Exception('Unable to save M-Pesa transaction. Run mpesa_migration.sql first.');
    }
    $stmt->bind_param('iidsss', $invoice_id, $patient_id, $amount, $phone, $receiptValue, $resultDesc);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Unable to save M-Pesa transaction: ' . $error);
    }
    $stmt->close();
}

function record_payment($conn, $invoice_id, $amount, $payment_method = 'Cash', $reference = null, $cashier_shift_id = null) {
    $invoice_id=(int)$invoice_id; $amount=(float)$amount;
    if($invoice_id<=0||$amount<=0) throw new Exception('Invalid invoice payment.');
    $stmt=$conn->prepare("SELECT id,patient_id,total,paid_amount,amount_paid FROM invoices WHERE id=? LIMIT 1 FOR UPDATE");
    if(!$stmt) throw new Exception('Unable to load invoice: '.$conn->error);
    $stmt->bind_param('i',$invoice_id); $stmt->execute(); $invoice=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if(!$invoice) throw new Exception('Invoice not found.');
    $total=(float)($invoice['total']??0);
    $itemStmt=$conn->prepare("SELECT COALESCE(SUM(total),0) items_total FROM invoice_items WHERE invoice_id=?");
    if($itemStmt){$itemStmt->bind_param('i',$invoice_id);$itemStmt->execute();$itemTotal=(float)(($itemStmt->get_result()->fetch_assoc()['items_total'])??0);$itemStmt->close();if($itemTotal>0)$total=$itemTotal;}
    $paidStmt=$conn->prepare("SELECT COALESCE(SUM(p.amount),0) - COALESCE((SELECT SUM(r.amount) FROM payment_refunds r WHERE r.invoice_id=p.invoice_id AND r.status='Approved'),0) AS total_paid FROM payments p WHERE p.invoice_id=?");
    if($paidStmt){$paidStmt->bind_param('i',$invoice_id);$paid=(float)(($paidStmt->get_result()->fetch_assoc()['total_paid'])??0);$paidStmt->close();}else{$paid=max((float)($invoice['paid_amount']??0),(float)($invoice['amount_paid']??0));}
    $balance=max($total-$paid,0); if($balance<=0) throw new Exception('Invoice is already fully paid.'); $amount=min($amount,$balance);
    $patientId=(int)($invoice['patient_id'] ?? 0); $method=trim((string)$payment_method); $referenceValue=$reference!==null?trim((string)$reference):null; $shiftId=(int)($cashier_shift_id??0);
    if($referenceValue!==null && $referenceValue!==''){
        $dup=$conn->prepare("SELECT id FROM payments WHERE reference=? LIMIT 1");
        if($dup){$dup->bind_param('s',$referenceValue);$dup->execute();$existing=$dup->get_result()->fetch_assoc();$dup->close();if($existing) throw new Exception('This payment reference has already been recorded.');}
    }
    if($shiftId>0 && invoice_column_exists($conn,'cashier_shift_id')){
        $stmt=$conn->prepare("INSERT INTO payments (patient_id,amount,method,reference,invoice_id,cashier_shift_id,created_at) VALUES (?,?,?,?,?,?,NOW())");
        if(!$stmt) throw new Exception('Unable to record payment with cashier shift: '.$conn->error);
        $stmt->bind_param('idssii',$patientId,$amount,$method,$referenceValue,$invoice_id,$shiftId);
    }else{
        $stmt=$conn->prepare("INSERT INTO payments (patient_id,amount,method,reference,invoice_id,created_at) VALUES (?,?,?,?,?,NOW())");
        if(!$stmt) throw new Exception('Unable to record payment: '.$conn->error);
        $stmt->bind_param('idssi',$patientId,$amount,$method,$referenceValue,$invoice_id);
    }
    if(!$stmt->execute()){ $err=$stmt->error;$stmt->close();throw new Exception('Unable to save payment: '.$err); }
    $paymentId=(int)$stmt->insert_id;
    $stmt->close();
    $newPaid=$paid+$amount;$newBalance=max($total-$newPaid,0);$newStatus=$newBalance<=0.00001?'paid':($newPaid>0?'partial':'unpaid');
    $update=$conn->prepare("UPDATE invoices SET total=?,paid_amount=?,amount_paid=?,balance=?,payment_status=?,status=?,payment_mode=?,paid_at=CASE WHEN ?='paid' THEN NOW() ELSE paid_at END WHERE id=?");
    if(!$update) throw new Exception('Unable to update invoice: '.$conn->error);
    $update->bind_param('ddddssssi',$total,$newPaid,$newPaid,$newBalance,$newStatus,$newStatus,$method,$newStatus,$invoice_id);
    if(!$update->execute()){ $err=$update->error;$update->close();throw new Exception('Unable to update invoice payment status: '.$err); } $update->close();
    // The modern payments table is authoritative. Legacy billing is read-only compatibility data and is no longer written here.
    return ['amount'=>$amount,'paid_amount'=>$newPaid,'balance'=>$newBalance,'status'=>$newStatus,'payment_id'=>$paymentId];
}

function refresh_invoice_payment_state($conn, int $invoice_id): array {
    $stmt=$conn->prepare("SELECT total FROM invoices WHERE id=? FOR UPDATE");
    if(!$stmt) throw new Exception('Unable to load invoice for payment reconciliation: '.$conn->error);
    $stmt->bind_param('i',$invoice_id); $stmt->execute(); $invoice=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if(!$invoice) throw new Exception('Invoice not found.');

    $paidStmt=$conn->prepare("SELECT
        COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id=?),0)
        - COALESCE((SELECT SUM(amount) FROM payment_refunds WHERE invoice_id=? AND status='Approved'),0) AS paid");
    if(!$paidStmt) throw new Exception('Unable to calculate invoice payments: '.$conn->error);
    $paidStmt->bind_param('ii',$invoice_id,$invoice_id); $paidStmt->execute();
    $paid=(float)($paidStmt->get_result()->fetch_assoc()['paid']??0); $paidStmt->close();

    $total=(float)($invoice['total']??0);
    $paid=max(0,min($paid,$total));
    $balance=max($total-$paid,0);
    $status=$balance<=0.00001?'paid':($paid>0?'partial':'unpaid');

    $upd=$conn->prepare("UPDATE invoices SET paid_amount=?, amount_paid=?, balance=?, payment_status=?, status=?, paid_at=CASE WHEN ?='paid' THEN COALESCE(paid_at,NOW()) ELSE NULL END WHERE id=?");
    if(!$upd) throw new Exception('Unable to update invoice reconciliation: '.$conn->error);
    $upd->bind_param('dddsssi',$paid,$paid,$balance,$status,$status,$status,$invoice_id);
    if(!$upd->execute()){ $e=$upd->error; $upd->close(); throw new Exception('Unable to update invoice reconciliation: '.$e); }
    $upd->close();
    return ['total'=>$total,'paid'=>$paid,'balance'=>$balance,'status'=>$status];
}

function refund_payment($conn, int $paymentId, float $amount, string $reason, string $refundMethod='Original', ?string $reference=null, int $userId=0): array {
    if($paymentId<=0 || $amount<=0) throw new Exception('Invalid refund amount.');
    $reason=trim($reason);
    if($reason==='') throw new Exception('Refund reason is required.');

    $stmt=$conn->prepare("SELECT id,invoice_id,patient_id,amount,method,reference FROM payments WHERE id=? LIMIT 1 FOR UPDATE");
    if(!$stmt) throw new Exception('Unable to load payment for refund: '.$conn->error);
    $stmt->bind_param('i',$paymentId); $stmt->execute(); $payment=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if(!$payment) throw new Exception('Payment not found.');

    $rs=$conn->prepare("SELECT COALESCE(SUM(amount),0) refunded FROM payment_refunds WHERE payment_id=? AND status='Approved'");
    if(!$rs) throw new Exception('Unable to check previous refunds: '.$conn->error);
    $rs->bind_param('i',$paymentId); $rs->execute(); $already=(float)($rs->get_result()->fetch_assoc()['refunded']??0); $rs->close();
    $refundable=max((float)$payment['amount']-$already,0);
    if($refundable<=0) throw new Exception('This payment has already been fully refunded.');
    if($amount>$refundable+0.00001) throw new Exception('Refund exceeds the remaining refundable payment amount.');
    if($userId<=0) $userId=(int)($_SESSION['user_id']??0);

    $method=$refundMethod==='Original'?(string)$payment['method']:$refundMethod;
    $reference=trim((string)($reference??''));
    if($reference==='') $reference=null;

    $ins=$conn->prepare("INSERT INTO payment_refunds (payment_id,invoice_id,patient_id,amount,refund_method,reference,reason,status,refunded_by,created_at) VALUES (?,?,?,?,?,?,?,'Approved',?,NOW())");
    if(!$ins) throw new Exception('Unable to create refund. Run database/financial_core_phase2.sql first.');
    $status='Approved';
    $ins->bind_param('iiidsssi',$paymentId,$payment['invoice_id'],$payment['patient_id'],$amount,$method,$reference,$reason,$userId);
    if(!$ins->execute()){ $e=$ins->error; $ins->close(); throw new Exception('Unable to save refund: '.$e); }
    $refundId=(int)$ins->insert_id; $ins->close();

    $account='Cash';
    if(stripos($method,'mpesa')!==false) $account='M-Pesa';
    elseif(stripos($method,'bank')!==false || stripos($method,'transfer')!==false) $account='Bank';
    $note='Payment refund #'.$refundId.' for Invoice #'.$payment['invoice_id'];
    post_journal_entry($conn,'Accounts Receivable',$amount,0,$note,(int)$payment['invoice_id'],'REF-'.$refundId.'-AR');
    post_journal_entry($conn,$account,0,$amount,$note,(int)$payment['invoice_id'],'REF-'.$refundId.'-'.strtoupper(str_replace(' ','',$account)));

    $state=refresh_invoice_payment_state($conn,(int)$payment['invoice_id']);
    return ['refund_id'=>$refundId,'payment_id'=>$paymentId,'invoice_id'=>(int)$payment['invoice_id'],'amount'=>$amount,'balance'=>$state['balance'],'status'=>$state['status']];
}

function get_invoice_number($conn, $invoice_id) {
    $col = get_invoice_number_column($conn);
    if ($col === null) {
        return null;
    }

    $stmt = $conn->prepare("SELECT {$col} AS invoice_number FROM invoices WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare invoice number lookup: ' . $conn->error);
    }

    $stmt->bind_param('i', $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result['invoice_number'] ?? null;
}

function generate_invoice_number($conn) {
    $prefix = 'INV';
    // Fetch prefix from settings
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'billing_invoice_prefix'");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $prefix = $result->fetch_assoc()['setting_value'];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT MAX(id) as max_id FROM invoices");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;
    return $prefix . '-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
}

function accounting_reference_column_exists($conn): bool {
    static $exists = null;
    if ($exists !== null) return $exists;
    $check = $conn->query("SHOW COLUMNS FROM accounting_entries LIKE 'reference_id'");
    $exists = (bool)($check && $check->num_rows > 0);
    return $exists;
}

function post_journal_entry($conn, $account, $debit, $credit, $note, $invoice_id = null, $reference_id = null) {
    $columns = ['account', 'debit', 'credit', 'note', 'created_at'];
    $placeholders = ['?', '?', '?', '?', 'NOW()'];
    $types = 'sdds';
    $values = [$account, $debit, $credit, $note];

    if ($invoice_id !== null) {
        $columns[] = 'invoice_id';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $invoice_id;
    }

    if ($reference_id !== null && accounting_reference_column_exists($conn)) {
        $columns[] = 'reference_id';
        $placeholders[] = '?';
        $types .= 's';
        $values[] = (string)$reference_id;
    }

    $sql = sprintf(
        'INSERT INTO accounting_entries (%s) VALUES (%s)',
        implode(', ', $columns),
        implode(', ', $placeholders)
    );

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to post journal entry: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Failed to post journal entry: ' . $error);
    }
    $stmt->close();
}

function post_invoice_journal($conn, $invoice_id, $patient_id, $total, $note = null, $invoice_item_id = null) {
    if ($total <= 0) return;

    $invoice_note = $note ?? ('Invoice #' . $invoice_id);
    $invNum = get_invoice_number($conn, $invoice_id);
    if ($invNum) {
        $invoice_note = $invNum;
    }

    // Each invoice item gets its own balanced AR/revenue pair. This prevents
    // a multi-item invoice from losing later revenue lines while remaining
    // idempotent when the same item is posted again.
    $reference = $invoice_item_id !== null ? 'INVITEM-' . (int)$invoice_item_id : null;
    if ($reference !== null && accounting_reference_column_exists($conn)) {
        $dup = $conn->prepare("SELECT COUNT(*) AS c FROM accounting_entries WHERE reference_id=?");
        if ($dup) {
            $dup->bind_param('s', $reference);
            $dup->execute();
            $already = (int)($dup->get_result()->fetch_assoc()['c'] ?? 0);
            $dup->close();
            if ($already > 0) return;
        }
    }

    $saleNote = 'Sale: ' . $invoice_note;
    post_journal_entry($conn, 'Accounts Receivable', $total, 0, $saleNote, $invoice_id, $reference);
    post_journal_entry($conn, 'Sales Revenue', 0, $total, $saleNote, $invoice_id, $reference);
}

function post_payment_journal($conn, $invoice_id, $amount, $payment_method = 'Cash', $payment_id = null) {
    if ($amount <= 0) return;

    $paymentAccount = 'Cash';
    if (stripos($payment_method, 'mpesa') !== false) {
        $paymentAccount = 'M-Pesa';
    } elseif (stripos($payment_method, 'bank') !== false || stripos($payment_method, 'transfer') !== false) {
        $paymentAccount = 'Bank';
    }

    $invNum = get_invoice_number($conn, $invoice_id);
    $note = $invNum ? ('Payment received: Invoice ' . $invNum) : ('Payment received: Invoice #' . $invoice_id);

    // Payments are separate accounting events. Use the payment primary key as
    // the idempotency reference so two legitimate equal payments are not merged.
    $reference = $payment_id !== null ? 'PAY-' . (int)$payment_id : null;
    if ($reference !== null && accounting_reference_column_exists($conn)) {
        $dup = $conn->prepare("SELECT COUNT(*) AS c FROM accounting_entries WHERE reference_id=?");
        if ($dup) {
            $dup->bind_param('s', $reference);
            $dup->execute();
            $already = (int)($dup->get_result()->fetch_assoc()['c'] ?? 0);
            $dup->close();
            if ($already > 0) return;
        }
    }

    post_journal_entry($conn, $paymentAccount, $amount, 0, $note, $invoice_id, $reference);
    post_journal_entry($conn, 'Accounts Receivable', 0, $amount, $note, $invoice_id, $reference);
}

function post_expense_journal($conn, $expense_id, $category, $amount, $payment_method = 'Cash', $note = null) {
    if ($amount <= 0) return;

    $paymentAccount = 'Cash';
    if (stripos($payment_method, 'mpesa') !== false) {
        $paymentAccount = 'M-Pesa';
    } elseif (stripos($payment_method, 'bank') !== false || stripos($payment_method, 'transfer') !== false) {
        $paymentAccount = 'Bank';
    }

    $expenseNote = $note ?? ('Expense: ' . $category);

    // Debit: Expense Category
    post_journal_entry($conn, $category, $amount, 0, $expenseNote, null, 'EXP-' . $expense_id);
    // Credit: Payment Account
    post_journal_entry($conn, $paymentAccount, 0, $amount, $expenseNote, null, 'EXP-' . $expense_id);
}