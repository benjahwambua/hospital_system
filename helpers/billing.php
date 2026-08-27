<?php
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

function create_invoice($conn, $patient_id = null, $encounter_id = null, $walkin_id = null, $status = 'unpaid', $payment_mode = null, $paid_amount = 0.0, $invoice_number = null) {
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

function get_or_create_invoice($conn, $patient_id, $encounter_id = null) {
    $q = $conn->prepare("SELECT id FROM invoices WHERE patient_id = ? AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
    $q->bind_param("i", $patient_id);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();
    $q->close();

    if ($res) {
        return $res['id'];
    }

    return create_invoice($conn, $patient_id, $encounter_id);
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
    if ($isWalkin) {
        return;
    }

    $hasConsultation = false;
    foreach ($items as $item) {
        if (stripos((string)($item['description'] ?? ''), 'consultation') !== false) {
            $hasConsultation = true;
            break;
        }
    }

    if (!$hasConsultation) {
        $items[] = [
            'description' => 'Consultation Fee',
            'quantity' => 1.0,
            'price' => $consultationFee,
            'amount' => $consultationFee,
            'source' => 'service',
        ];
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
    $columns = ['invoice_id', 'description', 'qty', 'unit_price', 'total'];
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
    $stmt->execute();
    $stmt->close();

    if ($total !== 0) {
        $conn->query("UPDATE invoices SET total = total + $total WHERE id = $invoice_id");
    }
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

    if ($reference_id !== null) {
        $columns[] = 'reference_id';
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $reference_id;
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
    $stmt->execute();
    $stmt->close();
}

function post_invoice_journal($conn, $invoice_id, $patient_id, $total, $note = null) {
    if ($total <= 0) return;

    $invoice_note = $note ?? ('Invoice #' . $invoice_id);
    $invNum = get_invoice_number($conn, $invoice_id);
    if ($invNum) {
        $invoice_note = $invNum;
    }

    // Debit: Accounts Receivable
    post_journal_entry($conn, 'Accounts Receivable', $total, 0, 'Sale: ' . $invoice_note, $invoice_id);
    // Credit: Sales Revenue
    post_journal_entry($conn, 'Sales Revenue', 0, $total, 'Sale: ' . $invoice_note, $invoice_id);
}

function post_payment_journal($conn, $invoice_id, $amount, $payment_method = 'Cash') {
    if ($amount <= 0) return;

    $paymentAccount = 'Cash';
    if (stripos($payment_method, 'mpesa') !== false) {
        $paymentAccount = 'M-Pesa';
    } elseif (stripos($payment_method, 'bank') !== false || stripos($payment_method, 'transfer') !== false) {
        $paymentAccount = 'Bank';
    }

    $invNum = get_invoice_number($conn, $invoice_id);
    $note = $invNum ? ('Payment received: Invoice ' . $invNum) : ('Payment received: Invoice #' . $invoice_id);

    // Debit: Payment Account (Cash/Bank/M-Pesa)
    post_journal_entry($conn, $paymentAccount, $amount, 0, $note, $invoice_id);
    // Credit: Accounts Receivable
    post_journal_entry($conn, 'Accounts Receivable', 0, $amount, $note, $invoice_id);
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
