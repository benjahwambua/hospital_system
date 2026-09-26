-- Clinical IPD linkage and discharge documentation
SET @db=DATABASE();

SET @sql=IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='visit_id'),
 'ALTER TABLE admissions ADD COLUMN visit_id INT NULL AFTER patient_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='discharge_diagnosis'),
 'ALTER TABLE admissions ADD COLUMN discharge_diagnosis TEXT NULL AFTER discharge_date','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='discharge_notes'),
 'ALTER TABLE admissions ADD COLUMN discharge_notes TEXT NULL AFTER discharge_diagnosis','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND COLUMN_NAME='follow_up'),
 'ALTER TABLE admissions ADD COLUMN follow_up TEXT NULL AFTER discharge_notes','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admissions' AND INDEX_NAME='idx_admissions_visit'),
 'SELECT 1','CREATE INDEX idx_admissions_visit ON admissions(visit_id)');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Maternity admission integration: link maternal admission to the canonical
-- clinical inpatient admission and patient visit. Existing rows remain intact
-- and can be reconciled separately; no historical linkage is fabricated.
SET @sql=IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions' AND COLUMN_NAME='admission_id'),
 'ALTER TABLE maternity_admissions ADD COLUMN admission_id INT NULL AFTER patient_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions' AND COLUMN_NAME='visit_id'),
 'ALTER TABLE maternity_admissions ADD COLUMN visit_id INT NULL AFTER admission_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions' AND INDEX_NAME='idx_maternity_admission_id'),
 'SELECT 1','CREATE INDEX idx_maternity_admission_id ON maternity_admissions(admission_id)');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maternity_admissions' AND INDEX_NAME='idx_maternity_visit_id'),
 'SELECT 1','CREATE INDEX idx_maternity_visit_id ON maternity_admissions(visit_id)');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- External clinical referrals used by the Patient Dashboard.
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
