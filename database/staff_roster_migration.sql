-- Staff roster and shift assignment foundation
-- Apply after users and access-control migrations in a backed-up test database.
CREATE TABLE IF NOT EXISTS staff_roster_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department ENUM('Nursing','Theatre','Emergency','Clinical','Reception','Laboratory','Radiology','Pharmacy','Maternity','Finance','Administration','Other') NOT NULL DEFAULT 'Clinical',
    shift_label VARCHAR(100) NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('Planned','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
    assignment_notes TEXT NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_roster_staff_time (user_id,starts_at,ends_at),
    INDEX idx_roster_department_time (department,starts_at),
    INDEX idx_roster_status_time (status,starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key,module_name,description,sort_order)
VALUES ('staff_roster','Staff Rostering','Staff shift scheduling and coverage register',39);
