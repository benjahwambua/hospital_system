-- Read-only Insurance & SHA financial reconciliation checks.
-- Run after database/insurance_migration.sql and (where needed) the legacy allocation backfill.
-- These queries do not modify records. Review every returned row with Finance/Insurance before release.

-- 1. Claims with invoices but no linked payer allocation (expected: zero rows after backfill).
SELECT ch.id AS claim_id, ch.claim_number, ch.invoice_id, ch.claim_status
FROM claim_headers ch
LEFT JOIN invoice_financial_allocations a ON a.claim_id = ch.id
WHERE ch.invoice_id IS NOT NULL AND a.id IS NULL
ORDER BY ch.id;

-- 2. Claims linked to more than one invoice allocation (investigate before settlement).
SELECT claim_id, COUNT(*) AS allocation_count, GROUP_CONCAT(id ORDER BY id) AS allocation_ids
FROM invoice_financial_allocations
WHERE claim_id IS NOT NULL
GROUP BY claim_id
HAVING COUNT(*) > 1;

-- 3. Claim-header and invoice-allocation amounts that disagree.
-- Status differences can be legitimate for legacy records; amount differences require review.
SELECT ch.id AS claim_id, ch.claim_number, ch.claim_status,
       ch.total_claim_amount, ch.total_approved_amount, ch.total_paid_amount,
       a.id AS allocation_id, a.expected_amount, a.approved_amount,
       a.settled_amount, a.balance_amount, a.allocation_status
FROM claim_headers ch
JOIN invoice_financial_allocations a ON a.claim_id = ch.id
WHERE ABS(COALESCE(a.expected_amount,0)-COALESCE(ch.total_claim_amount,0)) > 0.01
   OR ABS(COALESCE(a.approved_amount,0)-COALESCE(ch.total_approved_amount,0)) > 0.01
   OR ABS(COALESCE(a.settled_amount,0)-COALESCE(ch.total_paid_amount,0)) > 0.01
   OR ABS(COALESCE(a.balance_amount,0)-GREATEST(0,COALESCE(ch.total_approved_amount,0)-COALESCE(ch.total_paid_amount,0))) > 0.01
ORDER BY ch.id;

-- 4. Claims whose recorded payments exceed their approved amount.
SELECT id AS claim_id, claim_number, claim_status, total_approved_amount, total_paid_amount
FROM claim_headers
WHERE COALESCE(total_paid_amount,0) > COALESCE(total_approved_amount,0) + 0.01
ORDER BY id;

-- 5. Remittances allocated beyond the received amount.
SELECT r.id AS remittance_id, r.reference_number, r.amount_received,
       COALESCE(SUM(ri.paid_amount),0) AS allocated_amount,
       r.amount_received-COALESCE(SUM(ri.paid_amount),0) AS remaining_amount
FROM remittances r
LEFT JOIN remittance_items ri ON ri.remittance_id = r.id
GROUP BY r.id, r.reference_number, r.amount_received
HAVING COALESCE(SUM(ri.paid_amount),0) > r.amount_received + 0.01
ORDER BY r.id;

-- 6. Remittance items whose payer does not match the claim payer.
SELECT ri.id AS remittance_item_id, r.reference_number,
       r.payer_id AS remittance_payer_id, ch.id AS claim_id,
       ch.claim_number, ch.payer_id AS claim_payer_id, ri.paid_amount
FROM remittance_items ri
JOIN remittances r ON r.id = ri.remittance_id
JOIN claim_headers ch ON ch.id = ri.claim_id
WHERE r.payer_id <> ch.payer_id
ORDER BY ri.id;

-- 7. Remittance items that refer to claims not currently in an approved/paid state.
-- Review rather than auto-correcting historical statuses.
SELECT ri.id AS remittance_item_id, r.reference_number,
       ch.id AS claim_id, ch.claim_number, ch.claim_status, ri.paid_amount
FROM remittance_items ri
JOIN remittances r ON r.id = ri.remittance_id
JOIN claim_headers ch ON ch.id = ri.claim_id
WHERE ch.claim_status NOT IN ('Approved','Partially Approved','Paid')
ORDER BY ri.id;

-- 8. Denial records whose cumulative amount exceeds the claim's approved payer exposure.
-- Before adjudication, total_claim_amount is used as the provisional ceiling.
SELECT ch.id AS claim_id, ch.claim_number, ch.claim_status,
       ch.total_claim_amount, ch.total_approved_amount,
       SUM(d.denied_amount) AS recorded_denied_amount,
       CASE WHEN ch.total_approved_amount > 0
            THEN ch.total_approved_amount ELSE ch.total_claim_amount END AS payer_exposure_ceiling
FROM claim_headers ch
JOIN claim_denials d ON d.claim_id = ch.id
GROUP BY ch.id, ch.claim_number, ch.claim_status,
         ch.total_claim_amount, ch.total_approved_amount
HAVING SUM(d.denied_amount) >
       CASE WHEN ch.total_approved_amount > 0
            THEN ch.total_approved_amount ELSE ch.total_claim_amount END + 0.01
ORDER BY ch.id;

-- 9. Resolved/written-off appeals requiring explicit Finance review.
-- Appeal outcomes do not automatically change claim or invoice allocation balances.
SELECT d.id AS denial_id, ch.id AS claim_id, ch.claim_number, ch.claim_status,
       d.denial_reason, d.denied_amount, d.appeal_status, d.resolution_outcome,
       d.resolution_notes, a.id AS allocation_id, a.approved_amount AS allocation_approved,
       a.settled_amount AS allocation_settled, a.balance_amount AS allocation_balance,
       a.allocation_status
FROM claim_denials d
JOIN claim_headers ch ON ch.id = d.claim_id
LEFT JOIN invoice_financial_allocations a ON a.claim_id = ch.id
WHERE d.appeal_status IN ('Resolved','Written Off')
   OR d.resolution_outcome IS NOT NULL
ORDER BY d.updated_at DESC, d.id DESC;
