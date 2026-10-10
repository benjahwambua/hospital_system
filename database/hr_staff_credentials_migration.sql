-- HR professional credentials and expiry monitoring.
-- Apply after database/hr_staff_master_migration.sql.
CREATE TABLE IF NOT EXISTS hr_staff_credentials (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_id INT UNSIGNED NOT NULL,
    credential_type VARCHAR(100) NOT NULL,
    credential_name VARCHAR(160) NOT NULL,
    issuing_authority VARCHAR(160) NULL,
    registration_number VARCHAR(100) NULL,
    issue_date DATE NULL,
    expiry_date DATE NULL,
    status ENUM('Active','Suspended','Revoked') NOT NULL DEFAULT 'Active',
    notes VARCHAR(500) NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_hr_credential_staff (staff_id),
    KEY idx_hr_credential_expiry (expiry_date,status),
    CONSTRAINT fk_hr_credential_staff FOREIGN KEY (staff_id) REFERENCES hr_staff(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
