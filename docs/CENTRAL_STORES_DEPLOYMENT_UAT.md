# Central Stores deployment and UAT runbook

## Purpose and release boundary

This runbook covers the Central Stores lot-level physical-count schema and the matching application workflow. It is a controlled deployment procedure, not evidence that the migration or UAT has already been completed.

**Do not deploy the new lot-level stock-count page before applying** `database/central_stores_lot_counts_migration.sql`. The migration expects the base Central Stores schema from `database/central_stores_migration.sql` to exist first.

## 1. Before changing production

- Schedule a maintenance window and tell stores, procurement and finance users not to post receipts, issues, transfers, returns, adjustments or stock counts during the change.
- Confirm the target database name and that the release is being applied to the intended environment.
- Take a full database backup using the site's approved MySQL/MariaDB backup procedure. Store the backup outside the web root and verify that the dump file is non-empty.
- If possible, restore the backup into a separate test database and rehearse the migration there first.
- Record the current application commit/release and the backup timestamp.
- Confirm the base migration has been applied and the `stores_stock_count_lines` table exists.

Example backup command (run in a secured terminal; substitute the correct database and credentials; do not put passwords in shell history):

```bash
mysqldump --single-transaction --routines --triggers -u <db_user> -p <database_name> > hms_pre_stores_lot_counts.sql
```

Check the backup file and, where practical, perform a restore rehearsal before proceeding.

## 2. Preflight schema checks

Run these read-only checks in the target database before the migration:

```sql
SHOW TABLES LIKE 'stores_stock_count_lines';
SHOW COLUMNS FROM stores_stock_count_lines;
SHOW INDEX FROM stores_stock_count_lines;
```

Verify that the table has the expected existing columns, including `id`, `count_id` and `item_id`, and that the old unique index is named `uq_stores_count_item`. If the table or index differs, stop and investigate rather than editing the migration blindly.

Check existing duplicate item lines within each count before changing the unique key:

```sql
SELECT count_id, item_id, COUNT(*) AS line_count
FROM stores_stock_count_lines
GROUP BY count_id, item_id
HAVING COUNT(*) > 1;
```

If this query returns rows, review them before migration. Do not delete stock-count records just to make a migration succeed.

## 3. Apply the migration

1. Ensure the maintenance window is active and stock posting is paused.
2. Open `database/central_stores_lot_counts_migration.sql` and verify it is the reviewed version from the release being deployed.
3. Apply it once, after the base Central Stores migration.
4. Capture the SQL client output and stop on any error. Do not blindly rerun a partially applied migration: the `ALTER TABLE ... ADD COLUMN` statements are not idempotent.
5. Verify the new columns and indexes:

```sql
SHOW COLUMNS FROM stores_stock_count_lines;
SHOW INDEX FROM stores_stock_count_lines;
```

Expected new columns: `batch_number`, `expiry_date`, `lot_key`. Expected unique index: `uq_stores_count_item_lot` on `count_id`, `item_id`, `lot_key`. The previous `uq_stores_count_item` index should no longer exist.

Verify that all rows have a populated lot key:

```sql
SELECT COUNT(*) AS blank_lot_keys
FROM stores_stock_count_lines
WHERE lot_key IS NULL OR lot_key = '';
```

The expected result is zero.

## 4. Deploy and smoke-test the application

Deploy the application code from the same reviewed release. Confirm that the Central Stores page loads and that the database connection reports no missing-column or missing-index errors.

Use a non-production database or a dedicated test item/location for write tests. Do not create test movements against real stock balances.

## 5. UAT checklist

Record Pass/Fail, tester, timestamp and evidence for each test. Use test data in a non-production environment.

- [ ] A user without Central Stores View permission cannot access the page or its data.
- [ ] The consolidated inventory catalogue lists active Central Stores items, Pharmacy medicines, and active Laboratory inventory items when their source tables exist.
- [ ] Each catalogue row identifies its source ledger; the view does not add Pharmacy/Laboratory quantities into the Central Stores movement balance.
- [ ] Central Stores catalogue quantities match the existing main-store register for the same item and location; department/ward balances are not accidentally included in the main-store quantity.
- [ ] Pharmacy and Laboratory catalogue rows show the source-ledger quantity, unit, batch and expiry values accurately.
- [ ] Low-stock, out-of-stock, expired and soon-to-expire flags are checked against controlled test data and the source module's threshold rules.
- [ ] Search and source filters work together, and the no-match message appears when the filters return no records.
- [ ] A user with View only can inspect records but cannot create, edit, approve or post movements.
- [ ] A permitted user can create a physical count and capture separate lines for the same item when the batch/expiry lot differs.
- [ ] Count snapshot values are tied to the correct item, location and lot.
- [ ] Submitting a count does not itself post an unapproved variance.
- [ ] An authorized approver can approve/reject the variance using the intended approval workflow; the action is audited.
- [ ] Approved variance postings affect only the intended lot and location.
- [ ] FEFO issuing selects eligible stock by expiry and does not consume expired stock.
- [ ] A transfer out and transfer in preserve item, lot and expiry identity.
- [ ] A return or stock decrease against an unknown lot is rejected unless the explicit untracked/legacy-stock path is selected where allowed.
- [ ] A stock decrease exceeding the exact lot balance is rejected even when the item's aggregate balance across other lots would be sufficient.
- [ ] The reconciliation queue flags negative lot balances, expired stock with positive on-hand, and positive untracked stock.
- [ ] Existing legacy count records remain visible and can be reviewed.
- [ ] The key workflows complete without PHP errors, SQL errors or duplicate postings.
- [ ] Relevant audit entries and reports reflect the test actions accurately.

A syntax-lint pass is not a substitute for this UAT.

## 5A. Consolidated inventory catalogue boundary

The Central Stores page may present a read-only catalogue of existing Central Stores, Pharmacy and Laboratory stock. The catalogue is an operational visibility layer, not a merged inventory ledger. Never use a catalogue row alone as proof that a quantity is physically held in the main store. Reconcile the source ledger, location, item identity, unit and lot before any future cross-department transfer or opening-balance migration. Do not create duplicate master items or opening movements merely to make source items appear in the catalogue.

## 6. Legacy count handling

The migration preserves old count lines by assigning a unique legacy `lot_key`; it cannot infer the real historical batch or expiry for an item-level count. Therefore:

- Treat historical item-level counts as legacy snapshots, not proof of lot-level accuracy.
- If an old count included tracked lots but did not distinguish them, reject/reopen it and recount by lot after the migration.
- Reconcile positive untracked balances and other exceptions through an approved physical review and controlled movement process. Do not rewrite historical ledger entries simply to clear the queue.

## 7. Rollback and recovery

There is no safe generic reverse SQL script for this migration. The migration replaces an index and adds columns that the deployed application expects. If deployment or UAT fails:

1. Stop stock posting and keep the system in maintenance mode.
2. Capture the error, SQL output and application release/commit.
3. Do not drop the new columns or recreate the old index while the new application code is active.
4. Prefer restoring the verified pre-migration database backup together with the matching pre-migration application release, following the site's approved recovery procedure.
5. If any real stock movements were posted after the backup, reconcile them before restoring or replay them through an approved, audited recovery procedure. Never silently discard post-backup transactions.
6. Re-run smoke tests before reopening the workflow.

## 8. Release sign-off

Record:

- Environment and database
- Application commit/release
- Migration execution time and operator
- Backup location and restore-rehearsal result
- UAT checklist results and evidence
- Outstanding reconciliation exceptions
- Approval to resume stock posting

Do not mark Central Stores production-ready until migration, UAT, permissions, stock reconciliation and recovery checks have been evidenced.
