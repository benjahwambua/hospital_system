-- ICU/HDU foundation migration
-- Adds a dedicated fluid-balance ledger and registers ICU/HDU in the existing access-control system.
-- Does not create duplicate patients, admissions, wards, beds, observations, medication or billing records.

CREATE TABLE IF NOT EXISTS icu_fluid_entries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admission_id INT NOT NULL,
    patient_id INT NOT NULL,
    entry_type ENUM('Intake','Output') NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount_ml DECIMAL(10,2) NOT NULL,
    recorded_at DATETIME NOT NULL,
    notes VARCHAR(500) DEFAULT NULL,
    recorded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_icu_fluid_admission_time (admission_id, recorded_at),
    KEY idx_icu_fluid_patient_time (patient_id, recorded_at),
    KEY idx_icu_fluid_type_time (entry_type, recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO access_modules (module_key, module_name, description, sort_order, active)
SELECT 'icu', 'ICU / High Dependency Unit', 'Critical-care census, fluid balance and critical-care records', 25, 1
WHERE NOT EXISTS (SELECT 1 FROM access_modules WHERE module_key = 'icu');

-- Access is intentionally not granted automatically to all users.
-- A Super User should assign ICU view/create/edit/approve rights to the appropriate staff.
