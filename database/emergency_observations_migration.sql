-- Repeat observations for Emergency/Casualty cases.
-- Apply after database/emergency_casualty_migration.sql.
CREATE TABLE IF NOT EXISTS emergency_observations (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 case_id INT UNSIGNED NOT NULL,
 temperature_c DECIMAL(4,1) NULL,
 systolic_bp SMALLINT UNSIGNED NULL,
 diastolic_bp SMALLINT UNSIGNED NULL,
 pulse_bpm SMALLINT UNSIGNED NULL,
 respiratory_rate SMALLINT UNSIGNED NULL,
 oxygen_saturation DECIMAL(5,2) NULL,
 pain_score TINYINT UNSIGNED NULL,
 notes VARCHAR(1500) NULL,
 recorded_by INT NULL,
 recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_emergency_observations_case_time (case_id, recorded_at),
 CONSTRAINT fk_emergency_observations_case FOREIGN KEY (case_id) REFERENCES emergency_cases(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
