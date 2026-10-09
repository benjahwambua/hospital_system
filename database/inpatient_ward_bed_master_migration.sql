-- Configurable inpatient ward/bed master and occupancy movement history.
-- Apply after clinical_ipd_migration.sql and inpatient_nursing_expansion_migration.sql.
-- Safe to re-run: existing ward names and bed numbers are preserved.

CREATE TABLE IF NOT EXISTS inpatient_wards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inpatient_wards_name (name),
  KEY idx_inpatient_wards_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inpatient_beds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ward_id INT NOT NULL,
  bed_number INT NOT NULL,
  label VARCHAR(80) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inpatient_beds_ward_number (ward_id, bed_number),
  KEY idx_inpatient_beds_active (ward_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO inpatient_wards (name, description, is_active)
VALUES
 ('General Ward (Male)', 'General inpatient care', 1),
 ('General Ward (Female)', 'General inpatient care', 1),
 ('Maternity Ward', 'Maternity inpatient care', 1),
 ('Pediatric Ward', 'Paediatric inpatient care', 1),
 ('ICU', 'Intensive care', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Bootstrap six beds for each standard ward without overwriting existing settings.
INSERT IGNORE INTO inpatient_beds (ward_id, bed_number, label, is_active)
SELECT w.id, n.bed_number, CONCAT('Bed ', n.bed_number), 1
FROM inpatient_wards w
CROSS JOIN (
  SELECT 1 AS bed_number UNION ALL SELECT 2 UNION ALL SELECT 3
  UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
) n
WHERE w.name IN ('General Ward (Male)','General Ward (Female)','Maternity Ward','Pediatric Ward','ICU');

SELECT 'Ward/bed master migration completed. Review active admissions against the seeded bed register before production use.' AS result;
