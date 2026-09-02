<?php
declare(strict_types=1);

header('Content-Type: application/json');
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/billing.php';

$transactionStarted = false;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $raw = file_get_contents('php://input');
    if (!$raw) {
        throw new Exception('No input received');
    }

    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }

    if (empty($data['items']) || !is_array($data['items'])) {
        throw new Exception('No medicines selected');
    }

    $customer_type = $data['customer_type'] ?? '';
    $patient_id    = !empty($data['patient_id']) ? (int)$data['patient_id'] : null;
    $payment_mode  = $data['payment_mode'] ?? 'Cash';
    $items         = $data['items'];

    if (!in_array($customer_type, ['registered', 'walkin'], true)) {
        throw new Exception('Invalid customer type');
    }

    if ($customer_type === 'registered' && !$patient_id) {
        throw new Exception('Patient selection required');
    }

    $conn->begin_transaction();
    $transactionStarted = true;

    $walkin_id = null;

    if ($customer_type === 'walkin') {
        $custName = trim((string)($data['customer_name'] ?? ''));
        $custPhone = trim((string)($data['customer_phone'] ?? ''));
        if ($custName === '') {
            $custName = 'Walk-in ' . date('Hi');
        }

        // Check if phone column exists in walkin_customers
        $hasPhone = false;
        $colCheck = $conn->query("SHOW COLUMNS FROM walkin_customers LIKE 'phone'");
        if ($colCheck && $colCheck->num_rows > 0) {
            $hasPhone = true;
        }

        if ($hasPhone) {
            $stmt = $conn->prepare(
                "INSERT INTO walkin_customers (full_name, phone, created_at) VALUES (?, ?, NOW())"
            );
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('ss', $custName, $custPhone);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO walkin_customers (full_name, created_at) VALUES (?, NOW())"
            );
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('s', $custName);
        }

        $stmt->execute();
        $walkin_id = $stmt->insert_id;
        $stmt->close();
    }

    $invoice_id = create_invoice(
        $conn,
        $patient_id,
        null,
        $walkin_id,
        'paid',
        $payment_mode,
        0.0,
        null
    );

    $grand_total = 0.0;

    foreach ($items as $item) {
        $med_id = (int)($item['med_id'] ?? 0);
        $qty    = (int)($item['quantity'] ?? 0);

        if ($med_id <= 0 || $qty <= 0) {
            throw new Exception('Invalid medicine or quantity selected');
        }

        $med_stmt = $conn->prepare(
            "SELECT drug_name, selling_price, quantity
             FROM pharmacy_stock
             WHERE id = ?
             FOR UPDATE"
        );
        if (!$med_stmt) {
            throw new Exception($conn->error);
        }

        $med_stmt->bind_param('i', $med_id);
        $med_stmt->execute();
        $result = $med_stmt->get_result();
        $med = $result->fetch_assoc();
        $med_stmt->close();

        if (!$med) {
            throw new Exception('Medicine not found');
        }

        if ((int)$med['quantity'] < $qty) {
            throw new Exception('Insufficient stock for ' . $med['drug_name']);
        }

        $unit_price = (float)$med['selling_price'];
        $total = $unit_price * $qty;
        $grand_total += $total;

        $update_stmt = $conn->prepare(
            "UPDATE pharmacy_stock
             SET quantity = quantity - ?
             WHERE id = ?"
        );
        if (!$update_stmt) {
            throw new Exception($conn->error);
        }

        $update_stmt->bind_param('ii', $qty, $med_id);
        $update_stmt->execute();
        $update_stmt->close();

        add_invoice_item(
            $conn,
            $invoice_id,
            $med['drug_name'],
            $qty,
            $unit_price,
            'pharmacy',
            $med_id
        );
    }

    update_invoice_total($conn, $invoice_id, $grand_total);
    record_invoice_payment($conn, $invoice_id, $grand_total, $payment_mode, 'paid');

    $note = "Invoice #{$invoice_id} ({$payment_mode})";

    $acc_stmt = $conn->prepare(
        "INSERT INTO accounting_entries (account, debit, credit, note, created_at)
         VALUES ('Pharmacy Sales', ?, 0, ?, NOW())"
    );
    if (!$acc_stmt) {
        throw new Exception($conn->error);
    }

    $acc_stmt->bind_param('ds', $grand_total, $note);
    $acc_stmt->execute();
    $acc_stmt->close();

    $conn->commit();
    $transactionStarted = false;

    ob_end_clean();

    echo json_encode([
        'status' => 'success',
        'invoice_id' => $invoice_id,
    ]);
    exit;
} catch (Throwable $e) {
    if ($transactionStarted) {
        $conn->rollback();
    }

    ob_end_clean();
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
    exit;
}
