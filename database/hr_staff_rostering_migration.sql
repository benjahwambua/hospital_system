-- Staff rostering and shift coverage.
-- Apply after database/hr_staff_master_migration.sql.
-- This migration registers the module; user-level permissions must be assigned
-- explicitly through Access Rights. Super users retain their existing override.
CREATE TABLE IF NOT EXISTS hr_staff_roster (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_id INT UNSIGNED NOT NULL,
    shift_type ENUM('Day','Evening','Night','On-call','Weekend','Other') NOT NULL DEFAULT 'Day',
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    status ENUM('Scheduled','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
    notes VARCHAR(1000) NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_hr_roster_staff_time (staff_id, start_at, end_at),
    KEY idx_hr_roster_time_status (start_at, status),
    CONSTRAINT fk_hr_roster_staff FOREIGN KEY (staff_id) REFERENCES hr_staff(id) ON DELETE RESTRICT,
    CONSTRAINT chk_hr_roster_time_order CHECK (end_at > start_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key, module_name, description, sort_order)
VALUES ('staff_roster', 'Staff Rostering', 'Staff shift scheduling, coverage and attendance status', 95);
