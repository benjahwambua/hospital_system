-- Operational integrity hardening for HMS
-- Run once against hms_db after the existing migrations.

SET @db=DATABASE();

-- Prevent duplicate pending pharmacy work for the same prescription.
SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pharmacy_queue')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pharmacy_queue' AND INDEX_NAME='ux_pharmacy_queue_prescription_status'),
 'CREATE UNIQUE INDEX ux_pharmacy_queue_prescription_status ON pharmacy_queue(prescription_id,status)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Prevent a cashier from having two simultaneously open shifts.
-- Closed rows contain NULL in the generated key and therefore remain unrestricted.
SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='open_cashier_key'),
 'ALTER TABLE cashier_shifts ADD COLUMN open_cashier_key INT GENERATED ALWAYS AS (CASE WHEN status=''Open'' THEN cashier_id ELSE NULL END) STORED',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts')
 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='open_cashier_key')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND INDEX_NAME='ux_cashier_one_open_shift'),
 'CREATE UNIQUE INDEX ux_cashier_one_open_shift ON cashier_shifts(open_cashier_key)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Useful reconciliation indexes.
SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='invoice_items')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='invoice_items' AND INDEX_NAME='idx_invoice_items_source'),
 'CREATE INDEX idx_invoice_items_source ON invoice_items(invoice_id,source,source_id)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='visits')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='visits' AND INDEX_NAME='idx_visits_patient_status_date'),
 'CREATE INDEX idx_visits_patient_status_date ON visits(patient_id,status,visit_date,id)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
