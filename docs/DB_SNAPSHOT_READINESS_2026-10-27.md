# HMS Database Snapshot Readiness — 27 October 2026

**Source reviewed:** user-provided `hms_db.sql` export generated 27 October 2026, MariaDB 10.4.32 / PHP 8.0.30. This is a static snapshot, not a live connection to the deployment database. Confirm the target database still matches it before executing anything.

## Findings that change the release plan

### Present in the snapshot

- Base `theatre_cases` table.
- `admissions` table.
- `hr_staff`, `hr_staff_credentials`, `hr_staff_contracts`, `hr_staff_roster`, and `hr_staff_separations`.
- `emergency_cases` and `emergency_observations`.
- `patient_services`, `invoices`, and `invoice_items`.
- Existing `emergency_cases.disposition_destination` and `emergency_cases.receiving_contact` columns.

### Not found in the snapshot

The following tables were searched for by their exact `CREATE TABLE` names and were not found:

- `theatre_recovery_observations`
- `theatre_implant_usage`
- `theatre_safety_checklist_phases`
- `theatre_case_service_charges`
- `theatre_case_stock_issues`
- `theatre_case_admissions`
- `maternity_partograph_observations`

These absences mean the related features must not be assumed database-ready on a database matching this snapshot. They do **not** prove the current live target is identical to this dump.

## Migration actions for a matching test copy

1. Take a fresh backup and restore the export into an isolated test database.
2. Confirm the schema matches this snapshot; check each migration's prerequisites and whether it has already run.
3. For Theatre, with base `theatre_cases`, `admissions`, HR credentials, billing tables, and Central Stores prerequisites verified, apply missing migrations in this order:
   - `database/theatre_clinical_records_migration.sql`
   - `database/theatre_safety_checklist_migration.sql`
   - `database/theatre_billing_migration.sql`
   - `database/theatre_stock_usage_migration.sql`
   - `database/theatre_admission_linkage_migration.sql`
4. For Maternity, apply `database/maternity_partograph_migration.sql` only if `maternity_partograph_observations` remains absent.
5. **Do not run `database/emergency_transfer_handover_migration.sql` against a database matching this snapshot without modifying the migration first.** That file uses a non-idempotent `ALTER TABLE ... ADD COLUMN` for `disposition_destination` and `receiving_contact`, but both columns already exist in the export. Running it as-is would fail on duplicate columns. No change is needed for those two columns in a matching schema.
6. The theatre stock migration contains an index upgrade step. Inspect current indexes and confirm the script can run safely on the test copy before applying it.
7. Run migrations one at a time, record success/failure and verify the expected tables/indexes after each step. Do not flatten them into one import or run all repository SQL files indiscriminately.

## Release status

- [x] Static schema snapshot reviewed for the named release-critical tables/columns above.
- [ ] Current target schema compared with this snapshot.
- [ ] Selected migrations rehearsed successfully on a restored test database.
- [ ] PHP application workflows verified against the migrated test database.
- [ ] Backup restore demonstrated and production release approved.

**Decision:** this snapshot is not yet a production go-ahead. The immediate next task is a controlled test-database migration rehearsal—not another broad code audit. No migration has been executed by this review.
