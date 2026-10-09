-- Inpatient and nursing workflow expansion
-- Run after database/clinical_ipd_migration.sql and database/nursing_migration.sql.
-- Adds auditable bed transfers and assigned nursing task/care-plan records.

CREATE TABLE IF NOT EXISTS inpatient_bed_transfers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    patient_id INT NOT NULL,
    from_ward VARCHAR(120) NOT NULL,
    from_bed INT NOT NULL,
    to_ward VARCHAR(120) NOT NULL,
    to_bed INT NOT NULL,
    transfer_reason VARCHAR(500) NOT NULL,
    transferred_by INT NOT NULL,
    transferred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ipd_transfer_admission (admission_id, transferred_at),
    INDEX idx_ipd_transfer_patient (patient_id, transferred_at),
    INDEX idx_ipd_transfer_destination (to_ward, to_bed, transferred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS nursing_tasks (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    patient_id INT NOT NULL,
    task_title VARCHAR(180) NOT NULL,
    task_details TEXT NULL,
    priority ENUM('Routine','High','Urgent') NOT NULL DEFAULT 'Routine',
    due_at DATETIME NULL,
    assigned_to INT NULL,
    status ENUM('Open','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Open',
    created_by INT NOT NULL,
    completed_by INT NULL,
    completed_at DATETIME NULL,
    completion_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nursing_tasks_admission (admission_id, status),
    INDEX idx_nursing_tasks_assignee (assigned_to, status),
    INDEX idx_nursing_tasks_due (status, due_at),
    INDEX idx_nursing_tasks_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS nursing_care_plans (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    patient_id INT NOT NULL,
    problem_or_need VARCHAR(180) NOT NULL,
    goal VARCHAR(500) NOT NULL,
    interventions TEXT NOT NULL,
    review_due_at DATETIME NULL,
    status ENUM('Active','Achieved','Discontinued') NOT NULL DEFAULT 'Active',
    created_by INT NOT NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    review_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nursing_plan_admission (admission_id, status),
    INDEX idx_nursing_plan_review (status, review_due_at),
    INDEX idx_nursing_plan_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO access_modules (module_key, module_name, description, sort_order, active)
SELECT 'nursing', 'Nursing', 'Inpatient nursing observations, notes, tasks and care plans', 96, 1
WHERE NOT EXISTS (SELECT 1 FROM access_modules WHERE module_key = 'nursing');

SELECT 'Inpatient/nursing workflow expansion tables created or already present.' AS result;
