-- HR Staff Master: employee records independent from login accounts.
-- Apply after database/access_control_migration.sql. Back up the database first.
CREATE TABLE IF NOT EXISTS hr_staff (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_no VARCHAR(30) NULL,
    linked_user_id INT NULL,
    first_name VARCHAR(80) NOT NULL,
    middle_name VARCHAR(80) NULL,
    last_name VARCHAR(80) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    department VARCHAR(100) NOT NULL,
    job_title VARCHAR(120) NOT NULL,
    employment_type ENUM('Permanent','Contract','Locum','Casual','Intern','Volunteer') NOT NULL DEFAULT 'Permanent',
    hire_date DATE NULL,
    contract_end_date DATE NULL,
    status ENUM('Active','On Leave','Separated') NOT NULL DEFAULT 'Active',
    notes VARCHAR(1000) NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hr_staff_no (staff_no),
    UNIQUE KEY uq_hr_staff_linked_user (linked_user_id),
    KEY idx_hr_staff_name (last_name, first_name),
    KEY idx_hr_staff_department_status (department, status),
    KEY idx_hr_staff_status (status),
    CONSTRAINT fk_hr_staff_user FOREIGN KEY (linked_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key, module_name, description, sort_order)
VALUES ('hr_staff', 'HR Staff Master', 'Employee directory, employment details and staff status', 94);
