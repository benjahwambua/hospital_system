-- HMS Visit / Encounter layer
-- Run once in hms_db. Existing patient, invoice and payment data is preserved.

CREATE TABLE IF NOT EXISTS visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_number VARCHAR(30) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_time TIME NOT NULL,
    visit_type VARCHAR(50) NOT NULL DEFAULT 'Outpatient',
    clinic_category VARCHAR(100) DEFAULT 'General',
    doctor_id INT DEFAULT NULL,
    status ENUM('Open','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Open',
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_visits_patient (patient_id),
    INDEX idx_visits_date (visit_date),
    INDEX idx_visits_status (status),
    INDEX idx_visits_doctor (doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link existing operational records to a visit without changing their existing workflow.
ALTER TABLE appointments ADD COLUMN visit_id INT NULL AFTER patient_id;
ALTER TABLE vitals ADD COLUMN visit_id INT NULL AFTER patient_id;
ALTER TABLE patient_services ADD COLUMN visit_id INT NULL AFTER patient_id;
ALTER TABLE invoices ADD COLUMN visit_id INT NULL AFTER patient_id;

CREATE INDEX idx_appointments_visit ON appointments(visit_id);
CREATE INDEX idx_vitals_visit ON vitals(visit_id);
CREATE INDEX idx_patient_services_visit ON patient_services(visit_id);
CREATE INDEX idx_invoices_visit ON invoices(visit_id);
