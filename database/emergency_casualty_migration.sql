-- Emergency / Casualty first-release workflow. Apply after database/visits_migration.sql and access-control migrations.
CREATE TABLE IF NOT EXISTS emergency_cases (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 patient_id INT NOT NULL,
 visit_id INT NOT NULL,
 arrival_mode ENUM('Walk-in','Ambulance','Police','Referral','Other') NOT NULL DEFAULT 'Walk-in',
 chief_complaint VARCHAR(1000) NOT NULL,
 triage_level ENUM('Immediate','Very Urgent','Urgent','Standard','Non-Urgent') NOT NULL DEFAULT 'Urgent',
 triage_notes VARCHAR(1500) NULL,
 temperature_c DECIMAL(4,1) NULL,
 systolic_bp SMALLINT UNSIGNED NULL,
 diastolic_bp SMALLINT UNSIGNED NULL,
 pulse_bpm SMALLINT UNSIGNED NULL,
 respiratory_rate SMALLINT UNSIGNED NULL,
 oxygen_saturation DECIMAL(5,2) NULL,
 pain_score TINYINT UNSIGNED NULL,
 assigned_clinician_id INT NULL,
 status ENUM('Waiting','Under Assessment','Treatment','Admitted','Discharged','Transferred','Left Before Assessment','Cancelled') NOT NULL DEFAULT 'Waiting',
 disposition_notes VARCHAR(1500) NULL,
 triaged_by INT NULL,
 created_by INT NULL,
 updated_by INT NULL,
 arrived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 triaged_at DATETIME NULL,
 closed_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_emergency_visit (visit_id),
 KEY idx_emergency_queue (status,triage_level,arrived_at),
 KEY idx_emergency_patient (patient_id,arrived_at),
 CONSTRAINT fk_emergency_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE RESTRICT,
 CONSTRAINT fk_emergency_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT,
 CONSTRAINT fk_emergency_clinician FOREIGN KEY (assigned_clinician_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key,module_name,description,sort_order)
VALUES ('emergency','Emergency / Casualty','Emergency intake, triage, assessment queue and disposition',15);
