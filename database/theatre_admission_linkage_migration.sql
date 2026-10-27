-- Auditable linkage from completed theatre cases to the active inpatient admission.
-- Apply after database/theatre_surgery_migration.sql and the admissions schema migration.
CREATE TABLE IF NOT EXISTS theatre_case_admissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    admission_id INT NOT NULL,
    recorded_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_theatre_case_admission_case (case_id),
    KEY idx_theatre_case_admission_admission (admission_id),
    CONSTRAINT fk_theatre_case_admission_case
        FOREIGN KEY (case_id) REFERENCES theatre_cases(id) ON DELETE RESTRICT,
    CONSTRAINT fk_theatre_case_admission_admission
        FOREIGN KEY (admission_id) REFERENCES admissions(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
