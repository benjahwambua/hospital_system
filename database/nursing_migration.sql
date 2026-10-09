-- Nursing workflow foundation
-- Run after the core HMS schema and access-control migrations.

CREATE TABLE IF NOT EXISTS nursing_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    patient_id INT NOT NULL,
    note_type ENUM('Assessment','Progress Note','Shift Handover','Care Plan','Other') NOT NULL DEFAULT 'Progress Note',
    shift ENUM('Day','Evening','Night') NULL,
    note_text TEXT NOT NULL,
    recorded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nursing_notes_admission (admission_id),
    INDEX idx_nursing_notes_patient (patient_id),
    INDEX idx_nursing_notes_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nursing_observations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    patient_id INT NOT NULL,
    observation_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    temperature DECIMAL(5,2) NULL,
    systolic_bp SMALLINT NULL,
    diastolic_bp SMALLINT NULL,
    pulse SMALLINT NULL,
    respiration SMALLINT NULL,
    spo2 DECIMAL(5,2) NULL,
    pain_score TINYINT NULL,
    consciousness VARCHAR(50) NULL,
    intake_ml INT NULL,
    output_ml INT NULL,
    observation_notes TEXT NULL,
    recorded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nursing_obs_admission (admission_id),
    INDEX idx_nursing_obs_patient (patient_id),
    INDEX idx_nursing_obs_time (observation_time)
) ENGINE=InnoDB;

-- Register Nursing in the canonical access-control module table.
INSERT INTO access_modules (module_key, module_name, description, sort_order, active)
SELECT 'nursing', 'Nursing', 'Inpatient nursing observations, notes, care plans and shift handover', 96, 1
WHERE NOT EXISTS (SELECT 1 FROM access_modules WHERE module_key = 'nursing');

-- Grant a conservative initial set of permissions to existing clinical staff.
-- Administrators can adjust each user's permissions from Access Rights.
INSERT INTO user_module_access
    (user_id, module_id, can_view, can_create, can_edit, can_delete, can_approve)
SELECT u.id, am.id, 1, 1, 1, 0, 1
FROM users u
JOIN access_modules am ON am.module_key = 'nursing' AND am.active = 1
WHERE LOWER(COALESCE(u.role, '')) IN ('admin', 'doctor', 'nurse')
  AND COALESCE(u.is_super, 0) = 0
  AND NOT EXISTS (
      SELECT 1
      FROM user_module_access uma
      WHERE uma.user_id = u.id AND uma.module_id = am.id
  );
