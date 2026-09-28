-- Clinical IPD linkage and discharge documentation
-- Schema-aware migration. Safe to re-run against the existing HMS database.

SET @db = DATABASE();

-- Admissions: add visit linkage without assuming any neighboring column.
SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
  AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='visit_id'),
  'ALTER TABLE admissions ADD COLUMN visit_id INT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Discharge fields: do NOT use AFTER discharge_date because older HMS
-- databases do not necessarily contain discharge_date.
SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
  AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='discharge_diagnosis'),
  'ALTER TABLE admissions ADD COLUMN discharge_diagnosis TEXT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
  AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='discharge_notes'),
  'ALTER TABLE admissions ADD COLUMN discharge_notes TEXT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
  AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='follow_up'),
  'ALTER TABLE admissions ADD COLUMN follow_up TEXT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add admission visit index only after confirming the new column exists.
SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
  AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='visit_id')
  AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND INDEX_NAME='idx_admissions_visit'),
  'CREATE INDEX idx_admissions_visit ON admissions(visit_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Maternity linkage is optional because installations may not yet have
-- maternity_admissions.
SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions')
  AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions' AND COLUMN_NAME='admission_id'),
  'ALTER TABLE maternity_admissions ADD COLUMN admission_id INT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions')
  AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions' AND COLUMN_NAME='visit_id'),
  'ALTER TABLE maternity_admissions ADD COLUMN visit_id INT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions')
  AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions' AND COLUMN_NAME='admission_id')
  AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions' AND INDEX_NAME='idx_maternity_admission_id'),
  'CREATE INDEX idx_maternity_admission_id ON maternity_admissions(admission_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions')
  AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions' AND COLUMN_NAME='visit_id')
  AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions' AND INDEX_NAME='idx_maternity_visit_id'),
  'CREATE INDEX idx_maternity_visit_id ON maternity_admissions(visit_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- External referrals used by Patient Dashboard.
CREATE TABLE IF NOT EXISTS external_referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    referred_facility VARCHAR(200) NOT NULL,
    referred_doctor VARCHAR(150) DEFAULT NULL,
    specialty VARCHAR(150) DEFAULT NULL,
    reason VARCHAR(255) NOT NULL,
    urgency ENUM('Routine','Urgent','Emergency') NOT NULL DEFAULT 'Routine',
    notes TEXT DEFAULT NULL,
    status ENUM('Pending','Accepted','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_external_referrals_patient (patient_id),
    INDEX idx_external_referrals_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Clinical IPD migration completed or safely skipped where the current schema did not qualify.' AS result;
