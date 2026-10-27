-- Theatre clinical records: PACU observations and implant/device traceability.
-- Apply once to a backed-up test database after database/theatre_surgery_migration.sql.
-- Additive only; existing theatre case records are preserved.

CREATE TABLE IF NOT EXISTS theatre_recovery_observations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    observed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT NULL,
    heart_rate SMALLINT UNSIGNED NULL,
    respiratory_rate SMALLINT UNSIGNED NULL,
    systolic_bp SMALLINT UNSIGNED NULL,
    diastolic_bp SMALLINT UNSIGNED NULL,
    oxygen_saturation DECIMAL(5,2) NULL,
    temperature_c DECIMAL(4,1) NULL,
    pain_score TINYINT UNSIGNED NULL,
    consciousness_level ENUM('Alert','Voice','Pain','Unresponsive','Other') NULL,
    oxygen_support VARCHAR(120) NULL,
    interventions VARCHAR(1000) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_theatre_recovery_case_time (case_id, observed_at),
    CONSTRAINT fk_theatre_recovery_case FOREIGN KEY (case_id)
        REFERENCES theatre_cases(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theatre_implant_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    item_name VARCHAR(180) NOT NULL,
    manufacturer VARCHAR(160) NULL,
    model VARCHAR(160) NULL,
    lot_number VARCHAR(120) NULL,
    serial_number VARCHAR(120) NULL,
    expiry_date DATE NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    implanted_site VARCHAR(180) NULL,
    notes VARCHAR(1000) NULL,
    recorded_by INT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_theatre_implant_case (case_id),
    INDEX idx_theatre_implant_lot (lot_number),
    INDEX idx_theatre_implant_serial (serial_number),
    CONSTRAINT fk_theatre_implant_case FOREIGN KEY (case_id)
        REFERENCES theatre_cases(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
