-- Structured procedure coding foundation
-- Apply after patients, visits and access-control migrations in a backed-up test database.
CREATE TABLE IF NOT EXISTS procedure_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_system ENUM('ICD-10-PCS','CPT','Local') NOT NULL DEFAULT 'Local',
    code VARCHAR(32) NOT NULL,
    description VARCHAR(500) NOT NULL,
    category VARCHAR(120) NULL,
    standard_fee DECIMAL(12,2) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_procedure_code_system (code_system,code),
    INDEX idx_procedure_description (description),
    INDEX idx_procedure_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_procedures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    visit_id INT NOT NULL,
    procedure_code_id INT NOT NULL,
    procedure_type ENUM('Planned','Performed','Cancelled','Entered in Error') NOT NULL DEFAULT 'Planned',
    laterality ENUM('Not Applicable','Left','Right','Bilateral') NOT NULL DEFAULT 'Not Applicable',
    performed_at DATETIME NULL,
    performing_provider VARCHAR(180) NULL,
    clinical_notes TEXT NULL,
    status_reason TEXT NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_visit_procedures_patient (patient_id),
    INDEX idx_visit_procedures_visit (visit_id),
    INDEX idx_visit_procedures_code (procedure_code_id),
    INDEX idx_visit_procedures_type (procedure_type),
    CONSTRAINT fk_visit_procedure_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_visit_procedure_visit FOREIGN KEY (visit_id) REFERENCES visits(id),
    CONSTRAINT fk_visit_procedure_code FOREIGN KEY (procedure_code_id) REFERENCES procedure_codes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key,module_name,description,sort_order)
VALUES ('procedure_coding','Procedure Coding','Structured visit-linked procedure coding and catalogue',38);
