-- Inpatient daily ward charges.
-- Apply after database/inpatient_ward_bed_master_migration.sql.
-- Rates default to zero until finance configures them; no assumed tariff is charged.

SET @db = DATABASE();
SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='inpatient_wards')
  AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='inpatient_wards' AND COLUMN_NAME='daily_rate'),
  'ALTER TABLE inpatient_wards ADD COLUMN daily_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS inpatient_daily_charges (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  admission_id INT NOT NULL,
  patient_id INT NOT NULL,
  ward_id INT NOT NULL,
  ward_name VARCHAR(120) NOT NULL,
  bed_number INT NOT NULL,
  charge_date DATE NOT NULL,
  daily_rate DECIMAL(12,2) NOT NULL,
  invoice_id INT NOT NULL,
  invoice_item_id INT NOT NULL,
  created_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inpatient_charge_admission_day (admission_id, charge_date),
  KEY idx_inpatient_charges_patient_date (patient_id, charge_date),
  KEY idx_inpatient_charges_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Inpatient daily-charge ledger created. Configure each ward rate before posting charges.' AS result;
