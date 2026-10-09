-- Structured inpatient nursing shift handover and acknowledgement.
-- Apply after database/nursing_migration.sql and database/inpatient_nursing_expansion_migration.sql.

CREATE TABLE IF NOT EXISTS nursing_shift_handovers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    patient_id INT NOT NULL,
    outgoing_shift ENUM('Day','Evening','Night') NOT NULL,
    incoming_shift ENUM('Day','Evening','Night') NOT NULL,
    handover_summary TEXT NOT NULL,
    outstanding_tasks TEXT NULL,
    handed_over_by INT NOT NULL,
    handed_over_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending','Acknowledged') NOT NULL DEFAULT 'Pending',
    acknowledged_by INT NULL,
    acknowledged_at DATETIME NULL,
    acknowledgement_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nursing_handover_admission_time (admission_id, handed_over_at),
    INDEX idx_nursing_handover_status_time (status, handed_over_at),
    INDEX idx_nursing_handover_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Structured nursing shift handover table created or already present.' AS result;
