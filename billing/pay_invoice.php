<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../helpers/billing.php';
require_login();

$id = (int)($_POST['invoice_id'] ?? $_GET['id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$mode = $_POST['payment_mode'] ?? 'Cash';
$mark_paid = isset($_POST['mark_paid']) && $_POST['mark_paid'] == '1';

if (!$id) {
    die("Invalid invoice ID");
}

// Get invoice details
$invoice = $conn->query("SELECT * FROM invoices WHERE id = $id")->fetch_assoc();
if (!$invoice) {
    die("Invoice not found");
}

$conn->begin_transaction();

try {
    if ($mark_paid) {
        // Calculate remaining balance
        $paid_amount = floatval($invoice['paid_amount'] ?? 0);
        $remaining = $invoice['total'] - $paid_amount;
        
        if ($remaining > 0) {
            // Record the payment
            record_payment($conn, $id, $remaining, $mode, null);
            
            // Update invoice status
            $conn->query("UPDATE invoices SET status='paid', payment_mode='$mode', paid_at=NOW() WHERE id=$id");
            
            // Post payment journal entry
            post_payment_journal($conn, $id, $remaining, $mode);
        }
    } elseif ($amount > 0) {
        // Record partial payment
        record_payment($conn, $id, $amount, $mode, null);
        
        // Check if fully paid
        $total_paid = $conn->query("SELECT SUM(amount) as total FROM payments WHERE invoice_id = $id")->fetch_assoc()['total'];
        $status = ($total_paid >= $invoice['total']) ? 'paid' : 'partial';
        
        $conn->query("UPDATE invoices SET status='$status', payment_mode='$mode' WHERE id=$id");
        
        // Post payment journal entry
        post_payment_journal($conn, $id, $amount, $mode);
    }
    
    $conn->commit();
    
    if (isset($_POST['ajax'])) {
        echo json_encode(['status'=>'success']);
    } else {
        header("Location: /hospital_system/billing/view_bills.php?success=1");
        exit;
    }
    
} catch (Exception $e) {
    $conn->rollback();
    if (isset($_POST['ajax'])) {
        echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
    } else {
        die("Error: " . $e->getMessage());
    }
}
