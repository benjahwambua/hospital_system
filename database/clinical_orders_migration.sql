-- Clinical order and department workflow migration
-- Safe to run once; guards are included for columns/indexes where possible.

CREATE TABLE IF NOT EXISTS pharmacy_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    patient_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
    visit_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_pharmacy_queue_status (status),
    INDEX idx_pharmacy_queue_patient (patient_id),
    INDEX idx_pharmacy_queue_visit (visit_id),
    INDEX idx_pharmacy_queue_prescription (prescription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @db := DATABASE();

SET @sql := IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pharmacy_queue')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pharmacy_queue' AND COLUMN_NAME='visit_id'),
    'ALTER TABLE pharmacy_queue ADD COLUMN visit_id INT NULL AFTER quantity',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pharmacy_queue')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pharmacy_queue' AND COLUMN_NAME='completed_at'),
    'ALTER TABLE pharmacy_queue ADD COLUMN completed_at DATETIME NULL AFTER created_at',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='lab_requests')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='lab_requests' AND COLUMN_NAME='visit_id'),
    'ALTER TABLE lab_requests ADD COLUMN visit_id INT NULL AFTER patient_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='radiology_requests')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='radiology_requests' AND COLUMN_NAME='visit_id'),
    'ALTER TABLE radiology_requests ADD COLUMN visit_id INT NULL AFTER patient_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- The existing patient_services table is the canonical clinical service-order
-- ledger for Lab/Radiology. These indexes make visit-based department queues fast.
SET @sql := IF(
    NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='patient_services' AND INDEX_NAME='idx_patient_services_visit_category'),
    'CREATE INDEX idx_patient_services_visit_category ON patient_services (visit_id, category)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
