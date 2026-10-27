-- Backfill invoice financial allocations for legacy insurance claims.
-- Apply only after database/insurance_migration.sql has created invoice_financial_allocations.
-- Take a database backup first. This is idempotent for claims already linked by claim_id.
-- Review the preview SELECT before running the INSERT in production.

SELECT ch.id AS claim_id, ch.claim_number, ch.invoice_id, ch.patient_id,
       ch.payer_id, ch.patient_coverage_id, ch.total_claim_amount,
       ch.total_approved_amount, ch.total_paid_amount, ch.claim_status
FROM claim_headers ch
WHERE ch.invoice_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM invoice_financial_allocations a WHERE a.claim_id = ch.id
  )
ORDER BY ch.id;

INSERT INTO invoice_financial_allocations
    (invoice_id, patient_id, allocation_type, payer_id, patient_coverage_id,
     claim_id, expected_amount, approved_amount, settled_amount, balance_amount,
     allocation_status, notes)
SELECT ch.invoice_id, ch.patient_id, 'Insurance', ch.payer_id, ch.patient_coverage_id,
       ch.id, ch.total_claim_amount, ch.total_approved_amount, ch.total_paid_amount,
       GREATEST(0, (CASE WHEN ch.total_approved_amount > 0
                         THEN ch.total_approved_amount
                         ELSE ch.total_claim_amount END) - ch.total_paid_amount),
       CASE
         WHEN ch.claim_status = 'Rejected' THEN 'Denied'
         WHEN ch.claim_status IN ('Paid', 'Closed') THEN 'Settled'
         WHEN ch.total_paid_amount > 0 THEN 'Partially Settled'
         ELSE 'Open'
       END,
       CONCAT('Backfilled from legacy insurance claim ', ch.claim_number)
FROM claim_headers ch
WHERE ch.invoice_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM invoice_financial_allocations a WHERE a.claim_id = ch.id
  );

-- Post-check: should return zero rows for claims that have an invoice but no allocation.
SELECT ch.id, ch.claim_number
FROM claim_headers ch
LEFT JOIN invoice_financial_allocations a ON a.claim_id = ch.id
WHERE ch.invoice_id IS NOT NULL AND a.id IS NULL;
