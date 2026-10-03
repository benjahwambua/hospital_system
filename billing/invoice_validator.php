<?php
/**
 * Invoice validation and integrity checking for production deployment.
 * Prevents financial data corruption and reconciliation failures.
 */

class InvoiceValidator {
    private mysqli $conn;
    private array $errors = [];
    private array $warnings = [];

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Comprehensive invoice validation before payment processing.
     */
    public function validate_invoice_before_payment(int $invoiceId): bool {
        $this->errors = [];
        $this->warnings = [];

        // 1. Invoice must exist and not be cancelled
        $stmt = $this->conn->prepare(
            "SELECT id, patient_id, total, status FROM invoices WHERE id = ? LIMIT 1"
        );
        if (!$stmt) {
            $this->errors[] = 'Database error: Unable to validate invoice.';
            return false;
        }

        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $invoice = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$invoice) {
            $this->errors[] = 'Invoice #' . $invoiceId . ' not found.';
            return false;
        }

        if (in_array(strtolower($invoice['status'] ?? ''), ['cancelled', 'canceled', 'void'], true)) {
            $this->errors[] = 'Invoice #' . $invoiceId . ' is cancelled and cannot receive payments.';
            return false;
        }

        // 2. Invoice must have items with valid totals
        $itemStmt = $this->conn->prepare(
            "SELECT COUNT(*) as cnt, SUM(total) as item_total FROM invoice_items WHERE invoice_id = ?"
        );
        if (!$itemStmt) {
            $this->errors[] = 'Database error: Unable to check invoice items.';
            return false;
        }

        $itemStmt->bind_param('i', $invoiceId);
        $itemStmt->execute();
        $itemData = $itemStmt->get_result()->fetch_assoc();
        $itemStmt->close();

        $itemCount = (int)($itemData['cnt'] ?? 0);
        $itemTotal = (float)($itemData['item_total'] ?? 0);

        if ($itemCount === 0) {
            $this->errors[] = 'Invoice #' . $invoiceId . ' has no billable items.';
            return false;
        }

        // 3. Reconcile invoice total with item totals
        $invoiceTotal = (float)($invoice['total'] ?? 0);
        if (abs($invoiceTotal - $itemTotal) > 0.01) {
            $this->errors[] = 'Invoice #' . $invoiceId . ' total mismatch: Header says ' . $invoiceTotal . 
                             ' but items total ' . $itemTotal . '. Run reconciliation before payment.';
            return false;
        }

        // 4. Check for orphaned payments (payments without invoice)
        $orphanStmt = $this->conn->prepare(
            "SELECT COUNT(*) as cnt FROM payments WHERE invoice_id = ? AND amount > 0 AND (amount < ? OR amount > ?)"
        );
        if ($orphanStmt) {
            $overdraft = $invoiceTotal * 0.01;
            $orphanStmt->bind_param('idd', $invoiceId, $invoiceTotal, $overdraft);
            $orphanStmt->execute();
            $orphanCount = (int)($orphanStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
            $orphanStmt->close();
            if ($orphanCount > 0) {
                $this->warnings[] = 'Invoice #' . $invoiceId . ' has unusual payment patterns. Verify before processing.';
            }
        }

        // 5. Verify patient exists if linked
        if ((int)$invoice['patient_id'] > 0) {
            $patStmt = $this->conn->prepare("SELECT id FROM patients WHERE id = ? LIMIT 1");
            if ($patStmt) {
                $patStmt->bind_param('i', $invoice['patient_id']);
                $patStmt->execute();
                if ($patStmt->get_result()->num_rows === 0) {
                    $this->warnings[] = 'Invoice patient record no longer exists. Review before payment.';
                }
                $patStmt->close();
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate payment amount before recording.
     */
    public function validate_payment_amount(int $invoiceId, float $paymentAmount): bool {
        $this->errors = [];

        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(total), 0) as item_total FROM invoice_items WHERE invoice_id = ?"
        );
        if (!$stmt) {
            $this->errors[] = 'Unable to validate payment amount.';
            return false;
        }

        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $invoiceTotal = (float)($stmt->get_result()->fetch_assoc()['item_total'] ?? 0);
        $stmt->close();

        $paidStmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(amount), 0) - COALESCE((SELECT SUM(amount) FROM payment_refunds WHERE invoice_id = ? AND status = 'Approved'), 0) as paid FROM payments WHERE invoice_id = ?"
        );
        if (!$paidStmt) {
            $this->errors[] = 'Unable to check payment history.';
            return false;
        }

        $paidStmt->bind_param('ii', $invoiceId, $invoiceId);
        $paidStmt->execute();
        $alreadyPaid = (float)($paidStmt->get_result()->fetch_assoc()['paid'] ?? 0);
        $paidStmt->close();

        $outstanding = max($invoiceTotal - $alreadyPaid, 0);

        if ($paymentAmount <= 0) {
            $this->errors[] = 'Payment amount must be greater than zero.';
            return false;
        }

        if ($paymentAmount > $outstanding + 0.01) {
            $this->errors[] = 'Payment amount KES ' . number_format($paymentAmount, 2) . 
                             ' exceeds outstanding balance of KES ' . number_format($outstanding, 2) . '.';
            return false;
        }

        return true;
    }

    /**
     * Validate that a service order is medically and financially appropriate.
     */
    public function validate_service_order(int $patientId, int $serviceId, ?int $visitId = null): bool {
        $this->errors = [];
        $this->warnings = [];

        // Service must exist and be billable
        $svcStmt = $this->conn->prepare(
            "SELECT id, service_name, category, billable FROM services_master WHERE id = ? AND active = 1 LIMIT 1"
        );
        if (!$svcStmt) {
            $this->errors[] = 'Unable to validate service.';
            return false;
        }

        $svcStmt->bind_param('i', $serviceId);
        $svcStmt->execute();
        $service = $svcStmt->get_result()->fetch_assoc();
        $svcStmt->close();

        if (!$service) {
            $this->errors[] = 'Service #' . $serviceId . ' not found or inactive.';
            return false;
        }

        // Prevent duplicate lab orders within same visit
        if ($service['category'] === 'lab' && $visitId && $visitId > 0) {
            $dupStmt = $this->conn->prepare(
                "SELECT COUNT(*) as cnt FROM patient_services WHERE patient_id = ? AND service_id = ? AND visit_id = ? AND status NOT IN ('Cancelled')"
            );
            if ($dupStmt) {
                $dupStmt->bind_param('iii', $patientId, $serviceId, $visitId);
                $dupStmt->execute();
                $dupCount = (int)($dupStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
                $dupStmt->close();
                if ($dupCount > 0) {
                    $this->warnings[] = 'This lab test has already been ordered in this visit.';
                }
            }
        }

        // Check patient eligibility for maternity services
        if (in_array(strtolower($service['category']), ['anc', 'pnc', 'maternity'], true)) {
            $patStmt = $this->conn->prepare("SELECT gender FROM patients WHERE id = ? LIMIT 1");
            if ($patStmt) {
                $patStmt->bind_param('i', $patientId);
                $patStmt->execute();
                $patData = $patStmt->get_result()->fetch_assoc();
                $patStmt->close();
                if ($patData && strtolower($patData['gender'] ?? '') !== 'female') {
                    $this->errors[] = 'Maternity services can only be ordered for female patients.';
                    return false;
                }
            }
        }

        return empty($this->errors);
    }

    public function get_errors(): array { return $this->errors; }
    public function get_warnings(): array { return $this->warnings; }
    public function has_errors(): bool { return !empty($this->errors); }
}
