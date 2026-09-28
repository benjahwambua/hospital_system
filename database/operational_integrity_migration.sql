-- HMS operational integrity migration
-- Schema-aware and safe to re-run.
-- Run against the hms_db database after taking a backup.

SET @db = DATABASE();

-- 1. Prevent duplicate pending/completed queue rows only when the existing
-- table has the expected columns and there are no conflicting duplicates.
SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pharmacy_queue'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pharmacy_queue' AND COLUMN_NAME='prescription_id'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pharmacy_queue' AND COLUMN_NAME='status'
  )
  AND NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pharmacy_queue'
      AND INDEX_NAME='ux_pharmacy_queue_prescription_status'
  )
  AND NOT EXISTS (
    SELECT 1
    FROM pharmacy_queue
    GROUP BY prescription_id,status
    HAVING COUNT(*) > 1
  ),
  'CREATE UNIQUE INDEX ux_pharmacy_queue_prescription_status ON pharmacy_queue(prescription_id,status)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Prevent more than one OPEN cashier shift per cashier.
-- Generated key is NULL for closed/non-open shifts, so those rows do not
-- conflict under a unique index.
SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='cashier_id'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='status'
  )
  AND NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='open_cashier_key'
  ),
  'ALTER TABLE cashier_shifts ADD COLUMN open_cashier_key INT GENERATED ALWAYS AS (CASE WHEN status=''Open'' THEN cashier_id ELSE NULL END) STORED',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='open_cashier_key'
  )
  AND NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts'
      AND INDEX_NAME='ux_cashier_one_open_shift'
  )
  AND NOT EXISTS (
    SELECT 1
    FROM cashier_shifts
    WHERE open_cashier_key IS NOT NULL
    GROUP BY open_cashier_key
    HAVING COUNT(*) > 1
  ),
  'CREATE UNIQUE INDEX ux_cashier_one_open_shift ON cashier_shifts(open_cashier_key)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. invoice_items uses source + item_type + med_id in this HMS schema.
-- There is deliberately NO source_id reference.
SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='invoice_items'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='invoice_items' AND COLUMN_NAME='invoice_id'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='invoice_items' AND COLUMN_NAME='source'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='invoice_items' AND COLUMN_NAME='item_type'
  )
  AND NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='invoice_items'
      AND INDEX_NAME='idx_invoice_items_source'
  ),
  'CREATE INDEX idx_invoice_items_source ON invoice_items(invoice_id,source,item_type)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Visit reconciliation index. Every referenced column is checked first.
SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='visits'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='visits' AND COLUMN_NAME='patient_id'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='visits' AND COLUMN_NAME='status'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='visits' AND COLUMN_NAME='visit_date'
  )
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='visits' AND COLUMN_NAME='id'
  )
  AND NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='visits'
      AND INDEX_NAME='idx_visits_patient_status_date'
  ),
  'CREATE INDEX idx_visits_patient_status_date ON visits(patient_id,status,visit_date,id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Operational integrity migration completed or safely skipped where the current schema/data did not qualify.' AS result;
