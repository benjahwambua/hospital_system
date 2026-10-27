# Insurance & SHA claim calculation deployment and UAT

## Release boundary

This runbook covers the claim tariff-adjustment schema and the current plan-benefit, tariff, copay, and claim-receivable calculations. It is not evidence that the migration has been applied or that the financial workflow has passed UAT.

**Migration order:** ensure the core HMS schema is present, apply `database/insurance_migration.sql` if the Insurance tables do not already exist, then apply `database/insurance_claim_tariff_adjustments_migration.sql`. Ensure `invoice_financial_allocations` exists from the base Insurance migration before deploying the claim/allocation integration. After deployment and a backup, review and run `database/insurance_legacy_allocations_backfill.sql` to link eligible legacy claims that do not yet have an allocation. Back up the target database first. The base migration uses `CREATE TABLE IF NOT EXISTS`; it does not add missing columns to existing tables.

## 1. Backup and identify target

- Confirm the intended environment and database name.
- Pause claim creation/submission and remittance allocation during the schema change.
- Take a full MySQL/MariaDB backup outside the web root and verify that it is non-empty.
- Prefer restoring the backup to a test database and rehearsing the migration before production.
- Record application commit, backup timestamp, operator and target database.

Example, executed from a secured terminal with the correct credentials:

```bash
mysqldump --single-transaction --routines --triggers -u <db_user> -p <database_name> > hms_pre_insurance_claim_tariff_adjustments.sql
```

## 2. Read-only preflight checks

Run these checks before changing schema:

```sql
SHOW TABLES LIKE 'claim_headers';
SHOW TABLES LIKE 'claim_items';
SHOW COLUMNS FROM claim_items;
SHOW COLUMNS FROM claim_headers;
SHOW INDEX FROM claim_items;
```

Confirm the existing `claim_items` table contains `gross_amount`, `covered_amount`, `copay_amount`, and `patient_responsibility`. Confirm the intended columns `allowed_amount` and `contractual_adjustment` do not already exist before applying the migration.

If either new column already exists, stop and inspect the database state. Do not blindly rerun the migration: the `ALTER TABLE` statement is intentionally not idempotent.

## 3. Apply and verify the migration

Apply `database/insurance_claim_tariff_adjustments_migration.sql` once in the target database. Stop on any SQL error and capture the client output.

Verify:

```sql
SHOW COLUMNS FROM claim_items LIKE 'allowed_amount';
SHOW COLUMNS FROM claim_items LIKE 'contractual_adjustment';

SELECT COUNT(*) AS historical_rows
FROM claim_items
WHERE allowed_amount IS NULL OR contractual_adjustment IS NULL;
```

Expected: both columns exist. Historical rows may remain NULL because prior allowed amounts and contractual adjustments cannot safely be reconstructed from invoice gross alone. Do not backfill them with guessed values.

## 4. Configure controlled test data

In a non-production database, prepare:
- An active payer and active plan with known benefit percentages and copay.
- Verified patient coverage whose start/end dates include the encounter date.
- One active plan-specific payer tariff with an exact item name/type match.
- One item with no matching tariff.
- Invoice lines with known quantity, unit price and total.
- A claim with multiple lines so copay allocation can be checked across the claim.

Record expected values before running the tests. Use amounts from your approved payer contract, not invented production policy.

## 5. Claim calculation UAT

For each case, record Pass/Fail, tester, timestamp, test data IDs and evidence.

- [ ] Exact plan-specific tariff match is preferred over a payer-wide tariff for the same item/type.
- [ ] A matching tariff uses the configured approved price (or the configured base price where approved price is zero) and quantity, capped at the invoice line gross.
- [ ] No tariff match falls back to the invoice line gross; the absence of a match is visible/reviewable and is not silently treated as a contractual reduction.
- [ ] Benefit percentages apply only to supported benefit categories and are clamped to 0–100%.
- [ ] Unsupported categories do not assume 100% payer coverage.
- [ ] The configured claim-level copay is applied once across lines, not once per line.
- [ ] For every line, `gross_amount`, `allowed_amount`, `contractual_adjustment`, `covered_amount`, `copay_amount`, and `patient_responsibility` are distinct and reconcile to the approved calculation rules.
- [ ] Contractual adjustment is not incorrectly added to patient responsibility.
- [ ] Claim-header `total_claim_amount` equals the sum of payer-covered line amounts after benefit percentage and copay, consistent with the open-claim receivable dashboard.
- [ ] Claim-header `total_patient_responsibility` equals the sum of line patient responsibilities.
- [ ] New claim creation creates exactly one linked `invoice_financial_allocations` row with expected payer amount and open balance.
- [ ] Claim adjudication updates the linked allocation's approved amount and outstanding balance.
- [ ] Remittance matching updates the linked allocation's settled amount, balance and settlement status in the same transaction.
- [ ] The legacy allocation backfill preview is reviewed; after execution, the post-check returns zero invoiced claims without allocations.
- [ ] Claim creation failure rolls back both header and item rows.
- [ ] Duplicate claim creation for the same invoice is rejected.
- [ ] Submission is only allowed from Draft and updates the expected submission date/status.
- [ ] Remittance allocations cannot exceed the remaining claim/claim-item balances and update paid/open balances correctly.
- [ ] Denial/appeal actions preserve the audit trail and do not permit denials exceeding the remaining claim/item balance.
- [ ] Users without the required Insurance permissions cannot view or perform the relevant actions.

## 6. Financial reconciliation

Compare the following for each test claim:
- Sum of invoice line gross amounts.
- Sum of allowed amounts.
- Sum of contractual adjustments.
- Sum of payer-covered amounts.
- Sum of patient responsibility.
- Claim-header amount and responsibility.
- Approved and paid amounts after simulated adjudication/remittance.
- Remaining payer receivable and patient balance.

Do not assume all these figures should be identical. They represent different stages and responsibilities. Have the facility's finance/insurance owner confirm the accounting treatment and contract rules before production sign-off.

## 7. Release and rollback

- Deploy application code only after the required columns exist.
- Smoke-test claim listing, claim creation, claim submission, remittance allocation and reporting.
- If migration or UAT fails, pause claim/remittance writes, capture errors and restore the verified pre-migration database together with the matching application release under the site's approved recovery procedure.
- Do not drop new columns while the new application code is active.
- Reconcile any transactions created after the backup before restoring or replaying them.

## 8. Sign-off record

Record environment/database, application commit, backup and restore rehearsal, migration operator/time, test claim IDs, each UAT result, reconciliation evidence, unresolved exceptions, and finance/insurance approval.

PHP lint is necessary but does not prove claim policy, database compatibility, remittance reconciliation, or production readiness.
