-- Theatre / Surgery first workflow increment
-- Apply to a backed-up HMS test database after access-control and patient schema migrations.
CREATE TABLE IF NOT EXISTS theatre_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(32) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    visit_id INT NULL,
    procedure_name VARCHAR(180) NOT NULL,
    urgency ENUM('Emergency','Urgent','Elective') NOT NULL DEFAULT 'Elective',
    scheduled_at DATETIME NOT NULL,
    surgeon_id INT NULL,
    anesthetist_id INT NULL,
    status ENUM('Planned','Pre-op','In Theatre','Recovery','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
    clinical_notes TEXT NULL,
    consent_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    identity_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    site_marked TINYINT(1) NOT NULL DEFAULT 0,
    allergies_reviewed TINYINT(1) NOT NULL DEFAULT 0,
    fasting_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    anesthesia_reviewed TINYINT(1) NOT NULL DEFAULT 0,
    intraoperative_notes TEXT NULL,
    anesthesia_notes TEXT NULL,
    theatre_started_at DATETIME NULL,
    theatre_ended_at DATETIME NULL,
    recovery_notes TEXT NULL,
    outcome ENUM('Recovered','Transferred','Admitted','Other') NULL,
    cancellation_reason VARCHAR(1000) NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_theatre_schedule (scheduled_at),
    INDEX idx_theatre_status_schedule (status,scheduled_at),
    INDEX idx_theatre_patient (patient_id),
    INDEX idx_theatre_surgeon (surgeon_id),
    CONSTRAINT fk_theatre_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key,module_name,description,sort_order)
VALUES ('theatre','Theatre & Surgery','Surgical scheduling and peri-operative case tracking',35);
