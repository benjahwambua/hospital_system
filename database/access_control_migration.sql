-- HMS Access Control
-- Run once against hms_db. Super Users always retain full access.

CREATE TABLE IF NOT EXISTS access_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_key VARCHAR(80) NOT NULL UNIQUE,
    module_name VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 100,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_module_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    can_view TINYINT(1) NOT NULL DEFAULT 1,
    can_create TINYINT(1) NOT NULL DEFAULT 0,
    can_edit TINYINT(1) NOT NULL DEFAULT 0,
    can_delete TINYINT(1) NOT NULL DEFAULT 0,
    can_approve TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_module (user_id, module_id),
    INDEX idx_uma_user (user_id),
    INDEX idx_uma_module (module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key,module_name,description,sort_order) VALUES
('front_desk','Front Desk','Reception and patient registration',10),
('clinical','Clinical','Patient care, appointments, orders and admissions',20),
('laboratory','Laboratory','Lab requests, results and inventory',30),
('radiology','Radiology','Radiology requests and results',40),
('pharmacy','Pharmacy','Dispensing and pharmacy stock',50),
('maternity','Maternity','Maternity care and deliveries',60),
('finance','Finance','Cashier, collections and receivables',70),
('procurement','Procurement','Suppliers, purchase orders and receiving',80),
('finance_admin','Finance & Billing Administration','Billing, accounting and reconciliation',90),
('administration','Administration','Users, settings and system administration',100);

-- Existing users retain their current role-based access until
-- the Super User assigns explicit module permissions.
