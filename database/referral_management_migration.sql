-- Dedicated referral lifecycle register
-- Apply after patients, visits and access-control migrations in a backed-up test database.
CREATE TABLE IF NOT EXISTS referral_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referral_number VARCHAR(32) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    visit_id INT NULL,
    direction ENUM('Incoming','Outgoing') NOT NULL DEFAULT 'Outgoing',
    urgency ENUM('Routine','Urgent','Emergency') NOT NULL DEFAULT 'Routine',
    destination_facility VARCHAR(180) NOT NULL,
    destination_provider VARCHAR(180) NULL,
    referral_reason TEXT NOT NULL,
    clinical_summary TEXT NULL,
    requested_service VARCHAR(180) NULL,
    referring_department VARCHAR(120) NULL,
    referring_provider VARCHAR(180) NULL,
    status ENUM('Pending','Accepted','Scheduled','Seen','Report Received','Closed','Cancelled') NOT NULL DEFAULT 'Pending',
    receiving_appointment_at DATETIME NULL,
    external_reference VARCHAR(180) NULL,
    follow_up_notes TEXT NULL,
    closure_reason VARCHAR(1000) NULL,
    closed_at DATETIME NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_referrals_patient (patient_id),
    INDEX idx_referrals_visit (visit_id),
    INDEX idx_referrals_status_urgency (status,urgency),
    INDEX idx_referrals_created (created_at),
    CONSTRAINT fk_referral_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key,module_name,description,sort_order)
VALUES ('referrals','Referral Management','Incoming and outgoing referral lifecycle and follow-up',36);
