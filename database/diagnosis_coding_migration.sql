-- Structured diagnosis coding foundation
-- Apply after patients, visits and access-control migrations in a backed-up test database.
CREATE TABLE IF NOT EXISTS diagnosis_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_system ENUM('ICD-10','ICD-10-CM','SNOMED CT','Local') NOT NULL DEFAULT 'ICD-10',
    code VARCHAR(32) NOT NULL,
    description VARCHAR(500) NOT NULL,
    category VARCHAR(120) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_diagnosis_code_system (code_system,code),
    INDEX idx_diagnosis_description (description),
    INDEX idx_diagnosis_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_diagnoses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    visit_id INT NOT NULL,
    diagnosis_code_id INT NOT NULL,
    diagnosis_type ENUM('Primary','Secondary','Differential','Complication') NOT NULL DEFAULT 'Secondary',
    clinical_notes TEXT NULL,
    onset_date DATE NULL,
    status ENUM('Active','Resolved','Entered in Error') NOT NULL DEFAULT 'Active',
    coded_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_visit_diagnosis_code (visit_id,diagnosis_code_id),
    INDEX idx_visit_diagnoses_patient (patient_id),
    INDEX idx_visit_diagnoses_type_status (diagnosis_type,status),
    CONSTRAINT fk_visit_diagnosis_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_visit_diagnosis_visit FOREIGN KEY (visit_id) REFERENCES visits(id),
    CONSTRAINT fk_visit_diagnosis_code FOREIGN KEY (diagnosis_code_id) REFERENCES diagnosis_codes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key,module_name,description,sort_order)
VALUES ('diagnosis','Diagnosis Coding','Structured encounter diagnosis coding and code catalogue',37);
