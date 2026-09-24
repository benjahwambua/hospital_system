-- HMS Clinical Visit links
-- Safe to run once or repeatedly. Adds Visit linkage to clinical encounters
-- and pharmacy prescriptions without removing existing data.

SET @db = DATABASE();

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA=@db AND TABLE_NAME='encounters' AND COLUMN_NAME='visit_id') = 0,
    'ALTER TABLE encounters ADD COLUMN visit_id INT NULL AFTER patient_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA=@db AND TABLE_NAME='prescriptions' AND COLUMN_NAME='visit_id') = 0,
    'ALTER TABLE prescriptions ADD COLUMN visit_id INT NULL AFTER patient_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA=@db AND TABLE_NAME='encounters' AND INDEX_NAME='idx_encounters_visit') = 0,
    'CREATE INDEX idx_encounters_visit ON encounters(visit_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA=@db AND TABLE_NAME='prescriptions' AND INDEX_NAME='idx_prescriptions_visit') = 0,
    'CREATE INDEX idx_prescriptions_visit ON prescriptions(visit_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
