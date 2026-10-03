<?php
/**
 * Payment reconciliation and financial integrity audit.
 * Ensures invoice totals, payments, and refunds are balanced.
 */

class PaymentReconciliation {
    private mysqli $conn;
    private array $discrepancies = [];

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Full audit of invoice payment state.
     * Returns: [total, paid, refunded, balance, status, discrepancies]
     */
    public function reconcile_invoice(int $invoiceId): array {
        $this->discrepancies = [];

        // Get invoice header total
        $invStmt = $this->conn->prepare("SELECT total, status FROM invoices WHERE id = ? LIMIT 1 FOR UPDATE");
        if (!$invStmt) {
            throw new Exception('Unable to reconcile invoice: database error.');
        }
        $invStmt->bind_param('i', $invoiceId);
        $invStmt->execute();
        $invoice = $invStmt->get_result()->fetch_assoc();
        $invStmt->close();

        if (!$invoice) {
            throw new Exception('Invoice #' . $invoiceId . ' not found.');
        }

        $invoiceHeaderTotal = (float)($invoice['total'] ?? 0);

        // Get sum of all invoice items
        $itemStmt = $this->conn->prepare("SELECT COALESCE(SUM(total), 0) as item_total FROM invoice_items WHERE invoice_id = ?");
        if (!$itemStmt) {
            throw new Exception('Unable to fetch invoice items.');
        }
        $itemStmt->bind_param('i', $invoiceId);
        $itemStmt->execute();
        $itemTotal = (float)($itemStmt->get_result()->fetch_assoc()['item_total'] ?? 0);
        $itemStmt->close();

        // Use authoritative item total if it differs from header
        if (abs($invoiceHeaderTotal - $itemTotal) > 0.01) {
            $this->discrepancies[] = 'Invoice total mismatch: Header=' . $invoiceHeaderTotal . ', Items=' . $itemTotal;
        }
        $authorativeTotal = $itemTotal > 0 ? $itemTotal : $invoiceHeaderTotal;

        // Calculate total paid (payments minus approved refunds)
        $paidStmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(p.amount), 0) - COALESCE((SELECT SUM(r.amount) FROM payment_refunds r WHERE r.invoice_id = ? AND r.status = 'Approved'), 0) as net_paid FROM payments p WHERE p.invoice_id = ?"
        );
        if (!$paidStmt) {
            throw new Exception('Unable to calculate payments.');
        }
        $paidStmt->bind_param('ii', $invoiceId, $invoiceId);
        $paidStmt->execute();
        $netPaid = (float)($paidStmt->get_result()->fetch_assoc()['net_paid'] ?? 0);
        $paidStmt->close();

        // Validate that no payment exceeds invoice total
        $overpayStmt = $this->conn->prepare(
            "SELECT id, amount FROM payments WHERE invoice_id = ? AND amount > ?"
        );
        if ($overpayStmt) {
            $overpayStmt->bind_param('id', $invoiceId, $authorativeTotal);
            $overpayStmt->execute();
            $res = $overpayStmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $this->discrepancies[] = 'Payment #' . $row['id'] . ' of KES ' . $row['amount'] . ' exceeds invoice total of KES ' . $authorativeTotal;
            }
            $overpayStmt->close();
        }

        $netPaid = max(0, min($netPaid, $authorativeTotal));
        $balance = max($authorativeTotal - $netPaid, 0);
        $status = $balance <= 0.00001 ? 'Paid' : ($netPaid > 0 ? 'Partial' : 'Unpaid');

        // Update invoice status
        $updateStmt = $this->conn->prepare(
            "UPDATE invoices SET total = ?, status = ?, paid_amount = ? WHERE id = ?"
        );
        if ($updateStmt) {
            $updateStmt->bind_param('dsdi', $authorativeTotal, $status, $netPaid, $invoiceId);
            $updateStmt->execute();
            $updateStmt->close();
        }

        return [
            'invoice_id' => $invoiceId,
            'total' => $authorativeTotal,
            'paid' => $netPaid,
            'balance' => $balance,
            'status' => $status,
            'discrepancies' => $this->discrepancies,
            'is_reconciled' => empty($this->discrepancies)
        ];
    }

    /**
     * Batch reconciliation for all outstanding invoices.
     * Returns summary of reconciliation results.
     */
    public function reconcile_all_outstanding(): array {
        $summary = [
            'total_invoices' => 0,
            'reconciled' => 0,
            'with_discrepancies' => 0,
            'total_discrepancies' => [],
            'errors' => []
        ];

        $stmt = $this->conn->prepare(
            "SELECT id FROM invoices WHERE LOWER(COALESCE(status, '')) NOT IN ('cancelled', 'canceled', 'void') ORDER BY id ASC"
        );
        if (!$stmt) {
            $summary['errors'][] = 'Unable to fetch invoices for reconciliation.';
            return $summary;
        }

        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $summary['total_invoices']++;
            try {
                $result = $this->reconcile_invoice((int)$row['id']);
                if ($result['is_reconciled']) {
                    $summary['reconciled']++;
                } else {
                    $summary['with_discrepancies']++;
                    foreach ($result['discrepancies'] as $disc) {
                        $summary['total_discrepancies'][] = 'Invoice #' . $result['invoice_id'] . ': ' . $disc;
                    }
                }
            } catch (Throwable $e) {
                $summary['errors'][] = 'Invoice #' . $row['id'] . ': ' . $e->getMessage();
            }
        }
        $stmt->close();

        return $summary;
    }

    public function get_discrepancies(): array { return $this->discrepancies; }
}
