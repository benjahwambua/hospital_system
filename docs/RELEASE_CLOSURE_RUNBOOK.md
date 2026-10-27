# HMS Final Release Closure Runbook

**Purpose:** close the current HMS development cycle and move to a controlled release. This runbook is not evidence that database changes or clinical acceptance have already passed.

## Release rule

Freeze feature development. Reopen code only for a confirmed release-blocking defect. Do not restart module audits or reimplement merged work. The current `main` branch has a passing `php-lint` check, but lint does not prove database compatibility, permissions, clinical workflow correctness, or production readiness.

## Gate 1 — Freeze and record the release

- [ ] Record the exact `main` commit SHA being released.
- [ ] Confirm GitHub Actions `php-lint` passes on that SHA.
- [ ] Confirm there are no open pull requests intended for this release.
- [ ] Back up the application files and database; keep backups outside the web root.
- [ ] Record the target database name, database engine/version, PHP version, and maintenance window.
- [ ] Restore the backup into a separate test database and confirm the restore is usable before applying schema changes.

## Gate 2 — Database migration control

**Never import every SQL file blindly.** The repository contains schema migrations, conditional upgrades, data backfills, and integrity scripts. First compare the target schema with each script's preconditions and effects. Mark each script **Already applied**, **Apply in test**, **Not applicable**, or **Blocked**. Do not run destructive or one-time backfill scripts twice.

### Core prerequisites

Review the current target schema and apply only missing, applicable migrations in dependency order:

1. `database/access_control_migration.sql`
2. `database/visits_migration.sql` and `database/clinical_visit_links_migration.sql` where the visit schema/link columns are absent.
3. Clinical and inpatient foundations as required by the target schema: `clinical_care_migration.sql`, `clinical_ipd_migration.sql`, `inpatient_ward_bed_master_migration.sql`, `inpatient_daily_charges_migration.sql`, `inpatient_discharge_readiness_migration.sql`, and `inpatient_nursing_expansion_migration.sql`.
4. Finance/service prerequisites as required: `service_catalog_pricing_migration.sql`, `financial_core_phase2.sql`, `billing_accounting_integrity_migration.sql`, `cashier_shifts_migration.sql`, `procedure_coding_migration.sql`, and `diagnosis_coding_migration.sql`.
5. Review `explicit_permissions_migration.sql` after access-control tables exist. Verify individual module/action grants; do not grant broad access as a shortcut.

The list above is an inventory of potential prerequisites, **not an instruction to execute every file**. Inspect each SQL file and the actual database before applying it. If a script's preconditions or order cannot be established, stop that script and record the blocker rather than improvising on production.

### HR and staff

Apply only missing migrations, in this order, after the base schema and access-control tables are present:

1. `staff_leave_migration.sql`
2. `hr_staff_master_migration.sql`
3. `hr_staff_contracts_migration.sql`
4. `hr_staff_credentials_migration.sql`
5. `hr_staff_separations_migration.sql`
6. `hr_staff_rostering_migration.sql`

Confirm intended user permissions after module rows are created.

### Central Stores and supply chain

Follow the documented Central Stores order where applicable:

1. `central_stores_migration.sql`
2. `central_stores_lot_counts_migration.sql`
3. `central_stores_valuation_migration.sql`

Then inspect `supply_chain_hardening.sql` and `procurement_integrity_migration.sql` against the target schema and apply only if their preconditions are satisfied.

### Emergency / Casualty

After visits and access-control prerequisites, apply missing scripts in this order:

1. `emergency_casualty_migration.sql`
2. `emergency_observations_migration.sql`
3. `emergency_transfer_handover_migration.sql`

### Theatre / Surgery

After HR credentials, service/billing, admission, and Central Stores prerequisites are present, apply only missing scripts in this order:

1. `theatre_surgery_migration.sql` only if the base Theatre schema is absent.
2. `theatre_clinical_records_migration.sql`
3. `theatre_safety_checklist_migration.sql`
4. `theatre_billing_migration.sql`
5. `theatre_stock_usage_migration.sql`
6. `theatre_admission_linkage_migration.sql`

### Maternity, nursing, lab, and referrals

Review and apply only missing migrations after verifying dependencies:

- Maternity: `maternity_routing_migration.sql`, `maternity_partograph_migration.sql`
- Nursing: `nursing_migration.sql`, `nursing_mar_allergy_review_migration.sql`, `nursing_mar_scheduled_dose_migration.sql`, `nursing_medication_administration_migration.sql`, `nursing_shift_handover_migration.sql`
- Laboratory: `lab_inventory_migration.sql`, `lab_resource_usage_migration.sql`
- Orders/referrals: `clinical_orders_migration.sql`, `referral_management_migration.sql`

Do not assume these scripts are interchangeable or all missing. Confirm their table and column prerequisites first.

### Insurance and other data-sensitive scripts

Inspect the following individually and run only when their stated preconditions are met:

- `insurance_migration.sql`
- `insurance_claim_tariff_adjustments_migration.sql`
- `insurance_denial_appeals_migration.sql`
- `insurance_financial_reconciliation_checks.sql`
- `insurance_legacy_allocations_backfill.sql`
- `operational_integrity_migration.sql`
- `patient_number_format_migration.sql)

In particular, treat backfills and reconciliation scripts as controlled data operations, not routine repeatable migrations. Take a fresh backup before each data-changing operation.

### Database sign-off

- [ ] All selected migrations completed without SQL errors on the restored test copy.
- [ ] Verify required tables, columns, indexes, foreign keys, and module permission rows.
- [ ] Confirm no unintended duplicate/orphan records from the migration.
- [ ] Confirm application pages load against the migrated test database.
- [ ] Record exactly which migrations were applied and their execution order.
- [ ] Repeat on production only after test-copy success and a fresh production backup.

## Gate 3 — One focused release smoke test

Run one pass on the migrated test environment. Record **Pass / Fail / Blocked**; do not turn a failure into another broad audit.

- [ ] Login, logout, session expiry, and access-denied behavior.
- [ ] Create/find a patient and register a visit/encounter.
- [ ] Save a clinical note and diagnosis with the expected permissions.
- [ ] Place and process a laboratory order/result.
- [ ] Place and process a radiology order/result.
- [ ] Complete a pharmacy/store issue and confirm stock movement.
- [ ] Complete a maternity record and newborn/delivery flow supported by the current schema.
- [ ] Run an Emergency/Casualty intake and triage workflow.
- [ ] Run Theatre safety gates, documentation, recovery/PACU, stock usage, and admission linkage.
- [ ] Run a cashier invoice/payment/refund workflow with a test patient and verify ledger/report totals.
- [ ] Verify insurance claim workflow where enabled.
- [ ] Verify HR directory, credentials, leave, and role-specific access.
- [ ] Verify Central Stores shows the same relevant stock records as the stock view.
- [ ] Generate key reports and confirm there are no fatal errors.
- [ ] Restore a backup into a separate database and confirm it is readable.

For each failure, record the exact page, steps, expected result, actual result, and severity. Fix only critical or high-severity release blockers before proceeding. Defer enhancements to a post-release list.

## Gate 4 — Production deployment

1. Announce the maintenance window and restrict application writes.
2. Take and verify fresh database and application backups outside the web root.
3. Deploy the exact tested `main` commit; keep the previous application release available for rollback.
4. Apply only the migrations approved and rehearsed on the test copy.
5. Run PHP lint and a short smoke test on the deployed environment.
6. Confirm login, permissions, patient registration, clinical orders, billing/payment, stock, and reports.
7. Reopen access only after critical workflows pass.
8. Monitor application/PHP/database logs and confirm backups are scheduled.

## Go / no-go decision

**GO** only when the target schema is migrated, critical smoke tests pass, access controls are verified, and backup/restore is demonstrated.

**NO-GO** if there is a migration error, data integrity issue, privilege escalation, failed critical patient-care workflow, incorrect financial posting, or no recoverable backup. Document non-blocking enhancements for later instead of extending the project indefinitely.

## Current status

- Repository PHP lint: passed on the latest checked `main` commit.
- Open pull requests: none at the last repository check.
- Database migrations on the target environment: not verified by this runbook.
- Runtime clinical/financial UAT and production deployment: not yet confirmed.
