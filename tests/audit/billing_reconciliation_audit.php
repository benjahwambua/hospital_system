<?php
/**
 * Billing Reconciliation Audit
 * Checks all invoices for financial integrity issues.
 * Run from command line: php tests/audit/billing_reconciliation_audit.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../billing/invoice_validator.php';
require_once __DIR__ . '/../../billing/payment_reconciliation.php';

echo "\n" . str_repeat("=", 70) . "\n";
echo "BILLING RECONCILIATION AUDIT\n";
echo str_repeat("=", 70) . "\n\n";

$auditTime = date('Y-m-d H:i:s');
echo "Audit Time: $auditTime\n\n";

$totalInvoices = 0;
$passedInvoices = 0;
$failedInvoices = 0;
$warnings = [];
$failures = [];

try {
    $reconciler = new PaymentReconciliation($conn);
    
    // Get all active invoices
    $result = $conn->query(
        "SELECT id, invoice_number, patient_id, total, status FROM invoices 
         WHERE LOWER(COALESCE(status, '')) NOT IN ('cancelled', 'canceled', 'void')
         ORDER BY id ASC"
    );

    if (!$result) {
        throw new Exception("Unable to fetch invoices: " . $conn->error);
    }

    echo "Reconciling invoices...\n\n";
    echo str_repeat("-", 70) . "\n";

    while ($row = $result->fetch_assoc()) {
        $invoiceId = (int)$row['id'];
        $invoiceNumber = $row['invoice_number'] ?? 'INV-' . $invoiceId;
        $totalInvoices++;

        try {
            $reconciliation = $reconciler->reconcile_invoice($invoiceId);

            if ($reconciliation['is_reconciled']) {
                echo "✅ PASS: Invoice #$invoiceId ($invoiceNumber)\n";
                echo "   Total: KES " . number_format($reconciliation['total'], 2) . 
                     " | Paid: " . number_format($reconciliation['paid'], 2) . 
                     " | Status: " . $reconciliation['status'] . "\n";
                $passedInvoices++;
            } else {
                echo "❌ FAIL: Invoice #$invoiceId ($invoiceNumber)\n";
                foreach ($reconciliation['discrepancies'] as $disc) {
                    echo "   - $disc\n";
                    $failures[] = "Invoice #$invoiceId: $disc";
                }
                $failedInvoices++;
            }
        } catch (Throwable $e) {
            echo "⚠️  ERROR: Invoice #$invoiceId - " . $e->getMessage() . "\n";
            $failures[] = "Invoice #$invoiceId: " . $e->getMessage();
            $failedInvoices++;
        }
    }

    echo "\n" . str_repeat("-", 70) . "\n\n";

    // Summary
    echo "AUDIT SUMMARY\n";
    echo str_repeat("=", 70) . "\n";
    echo "Total Invoices Audited: $totalInvoices\n";
    echo "✅ Passed: $passedInvoices\n";
    echo "❌ Failed: $failedInvoices\n";
    echo "Pass Rate: " . ($totalInvoices > 0 ? round(100 * $passedInvoices / $totalInvoices, 1) : 0) . "%\n\n";

    if (!empty($failures)) {
        echo "FAILURES & ERRORS:\n";
        echo str_repeat("-", 70) . "\n";
        foreach ($failures as $i => $failure) {
            echo ($i + 1) . ". $failure\n";
        }
        echo "\n";
    }

    // Overall status
    if ($failedInvoices === 0) {
        echo "\n🎉 ALL INVOICES RECONCILED SUCCESSFULLY\n";
    } else {
        echo "\n⚠️  CRITICAL: $failedInvoices invoice(s) have reconciliation issues.\n";
        echo "Please review and correct before further transactions.\n";
    }

    echo "\n" . str_repeat("=", 70) . "\n\n";

} catch (Throwable $e) {
    echo "\n❌ AUDIT FAILED: " . $e->getMessage() . "\n\n";
    exit(1);
}

exit($failedInvoices > 0 ? 1 : 0);
