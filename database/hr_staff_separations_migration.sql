-- HR staff separation and offboarding control. Apply after database/hr_staff_master_migration.sql.
CREATE TABLE IF NOT EXISTS hr_staff_separations (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 staff_id INT UNSIGNED NOT NULL,
 separation_type ENUM('Resignation','End of Contract','Termination','Retirement','Redundancy','Death','Other') NOT NULL DEFAULT 'Resignation',
 notice_date DATE NULL,
 last_working_date DATE NOT NULL,
 reason VARCHAR(500) NULL,
 status ENUM('In Progress','Completed','Cancelled') NOT NULL DEFAULT 'In Progress',
 handover_completed TINYINT(1) NOT NULL DEFAULT 0,
 assets_returned TINYINT(1) NOT NULL DEFAULT 0,
 access_revoked TINYINT(1) NOT NULL DEFAULT 0,
 finance_clearance TINYINT(1) NOT NULL DEFAULT 0,
 exit_interview TINYINT(1) NOT NULL DEFAULT 0,
 notes VARCHAR(1500) NULL,
 created_by INT NULL, updated_by INT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
 completed_at DATETIME NULL,
 KEY idx_hr_separation_staff (staff_id), KEY idx_hr_separation_status_date (status,last_working_date),
 CONSTRAINT fk_hr_separation_staff FOREIGN KEY (staff_id) REFERENCES hr_staff(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
