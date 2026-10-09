-- Inpatient discharge readiness confirmations.
-- Re-runnable schema-aware migration. Apply after database/clinical_ipd_migration.sql.
SET @db = DATABASE();

SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='discharge_financial_reviewed'),
 'ALTER TABLE admissions ADD COLUMN discharge_financial_reviewed TINYINT(1) NOT NULL DEFAULT 0',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='discharge_medication_reconciled'),
 'ALTER TABLE admissions ADD COLUMN discharge_medication_reconciled TINYINT(1) NOT NULL DEFAULT 0',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='discharge_checklist_by'),
 'ALTER TABLE admissions ADD COLUMN discharge_checklist_by INT NULL',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='discharge_checklist_at'),
 'ALTER TABLE admissions ADD COLUMN discharge_checklist_at DATETIME NULL',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Inpatient discharge readiness migration completed or safely skipped.' AS result;
