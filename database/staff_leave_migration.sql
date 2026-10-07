-- Staff leave management
CREATE TABLE IF NOT EXISTS staff_leave_requests (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 leave_type VARCHAR(50) NOT NULL,
 start_date DATE NOT NULL,
 end_date DATE NOT NULL,
 days DECIMAL(6,2) NOT NULL DEFAULT 0,
 reason VARCHAR(500) DEFAULT NULL,
 status ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
 reviewed_by INT NULL,
 reviewed_at DATETIME NULL,
 review_notes VARCHAR(500) DEFAULT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 INDEX idx_slr_user(user_id), INDEX idx_slr_status(status), INDEX idx_slr_dates(start_date,end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key,module_name,description,sort_order)
VALUES ('staff_leave','Staff Leave','Employee leave requests, approvals and history',95);
