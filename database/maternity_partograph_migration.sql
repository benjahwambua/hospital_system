-- HMS Maternity Partograph: labour observations
-- Run once against the existing hms_db before using maternity/partograph.php.
-- This table stores observations separately from ANC/PNC visits and delivery records.
CREATE TABLE IF NOT EXISTS maternity_partograph_observations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    maternity_id INT NOT NULL,
    labour_start DATETIME NOT NULL,
    observed_at DATETIME NOT NULL,
    fetal_heart_rate SMALLINT UNSIGNED NULL,
    liquor VARCHAR(40) NULL,
    moulding VARCHAR(40) NULL,
    cervical_dilatation DECIMAL(4,1) NULL,
    head_descent VARCHAR(40) NULL,
    contractions_per_10min TINYINT UNSIGNED NULL,
    contraction_duration VARCHAR(40) NULL,
    pulse SMALLINT UNSIGNED NULL,
    systolic_bp SMALLINT UNSIGNED NULL,
    diastolic_bp SMALLINT UNSIGNED NULL,
    temperature DECIMAL(4,1) NULL,
    urine_volume_ml SMALLINT UNSIGNED NULL,
    urine_protein VARCHAR(40) NULL,
    urine_ketones VARCHAR(40) NULL,
    oxytocin_rate VARCHAR(80) NULL,
    medications VARCHAR(255) NULL,
    notes TEXT NULL,
    recorded_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_partograph_maternity_time (maternity_id, observed_at),
    KEY idx_partograph_labour_start (maternity_id, labour_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
