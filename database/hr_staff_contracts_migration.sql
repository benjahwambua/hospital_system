-- HR employment contract register. Apply after database/hr_staff_master_migration.sql.
CREATE TABLE IF NOT EXISTS hr_staff_contracts (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 staff_id INT UNSIGNED NOT NULL,
 contract_reference VARCHAR(80) NULL,
 contract_type ENUM('Permanent','Fixed Term','Locum','Casual','Internship','Consultancy','Other') NOT NULL DEFAULT 'Fixed Term',
 start_date DATE NOT NULL,
 end_date DATE NULL,
 status ENUM('Draft','Active','Renewed','Expired','Terminated') NOT NULL DEFAULT 'Active',
 renewal_notes VARCHAR(1000) NULL,
 created_by INT NULL, updated_by INT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_hr_contract_reference (contract_reference),
 KEY idx_hr_contract_staff (staff_id), KEY idx_hr_contract_dates (status,end_date),
 CONSTRAINT fk_hr_contract_staff FOREIGN KEY (staff_id) REFERENCES hr_staff(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
