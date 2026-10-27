-- Link billable procedure service lines to theatre cases and standard HMS invoices.
-- Apply once after database/theatre_surgery_migration.sql and billing/service catalogue migrations.
CREATE TABLE IF NOT EXISTS theatre_case_service_charges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    service_id INT NOT NULL,
    patient_service_id INT NOT NULL,
    invoice_id INT NOT NULL,
    invoice_item_id INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    recorded_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_theatre_case_service (case_id, service_id),
    KEY idx_theatre_charge_invoice (invoice_id),
    KEY idx_theatre_charge_patient_service (patient_service_id),
    CONSTRAINT fk_theatre_charge_case FOREIGN KEY (case_id) REFERENCES theatre_cases(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
