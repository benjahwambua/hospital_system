-- Central Stores foundation
-- Run after core schema and database/access_control_migration.sql.
-- Stock balances are derived from the immutable movement ledger.

CREATE TABLE IF NOT EXISTS stores_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(60) NOT NULL UNIQUE,
    item_name VARCHAR(180) NOT NULL,
    category VARCHAR(100) NULL,
    unit VARCHAR(40) NOT NULL DEFAULT 'Each',
    reorder_level DECIMAL(12,3) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stores_items_name (item_name),
    INDEX idx_stores_items_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stores_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_code VARCHAR(60) NOT NULL UNIQUE,
    location_name VARCHAR(150) NOT NULL,
    location_type ENUM('Main Store','Department','Pharmacy','Laboratory','Ward','Other') NOT NULL DEFAULT 'Main Store',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stores_movements (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    location_id INT NOT NULL,
    movement_type ENUM('Opening','Receipt','Issue','Transfer In','Transfer Out','Return','Adjustment In','Adjustment Out') NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    reference_type VARCHAR(40) NULL,
    reference_id BIGINT NULL,
    batch_number VARCHAR(100) NULL,
    expiry_date DATE NULL,
    notes VARCHAR(500) NULL,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stores_mov_item_location (item_id, location_id),
    INDEX idx_stores_mov_created (created_at),
    INDEX idx_stores_mov_ref (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stores_requisitions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    requisition_number VARCHAR(40) NOT NULL UNIQUE,
    requesting_department VARCHAR(120) NOT NULL,
    destination_location_id INT NOT NULL,
    requested_by INT NOT NULL,
    approved_by INT NULL,
    issued_by INT NULL,
    status ENUM('Draft','Submitted','Approved','Partially Issued','Issued','Rejected','Cancelled') NOT NULL DEFAULT 'Draft',
    request_notes TEXT NULL,
    approval_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    approved_at DATETIME NULL,
    issued_at DATETIME NULL,
    INDEX idx_stores_req_status (status),
    INDEX idx_stores_req_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stores_requisition_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    requisition_id BIGINT NOT NULL,
    item_id INT NOT NULL,
    quantity_requested DECIMAL(12,3) NOT NULL,
    quantity_issued DECIMAL(12,3) NOT NULL DEFAULT 0,
    notes VARCHAR(300) NULL,
    INDEX idx_stores_req_items_req (requisition_id),
    INDEX idx_stores_req_items_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO access_modules (module_key,module_name,description,sort_order,active)
SELECT 'central_stores','Central Stores','Hospital-wide stock, requisitions, issues and stock ledger',85,1
WHERE NOT EXISTS (SELECT 1 FROM access_modules WHERE module_key='central_stores');

INSERT INTO user_module_access (user_id,module_id,can_view,can_create,can_edit,can_delete,can_approve)
SELECT u.id,am.id,1,1,1,0,1
FROM users u JOIN access_modules am ON am.module_key='central_stores'
WHERE COALESCE(u.is_super,0)=0
  AND LOWER(COALESCE(u.role,'')) IN ('admin','procurement','storekeeper','stores')
  AND NOT EXISTS (SELECT 1 FROM user_module_access x WHERE x.user_id=u.id AND x.module_id=am.id);

INSERT INTO stores_locations (location_code,location_name,location_type)
SELECT 'MAIN','Main Stores','Main Store'
WHERE NOT EXISTS (SELECT 1 FROM stores_locations WHERE location_code='MAIN');


-- Physical stock counts and approval-controlled variance posting.
CREATE TABLE IF NOT EXISTS stores_stock_counts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    count_number VARCHAR(50) NOT NULL UNIQUE,
    location_id INT NOT NULL,
    status ENUM('Counting','Submitted','Approved','Rejected') NOT NULL DEFAULT 'Counting',
    notes VARCHAR(500) NULL,
    created_by INT NOT NULL,
    submitted_by INT NULL,
    approved_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    approved_at DATETIME NULL,
    INDEX idx_stores_counts_status (status),
    INDEX idx_stores_counts_location (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stores_stock_count_lines (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    count_id BIGINT NOT NULL,
    item_id INT NOT NULL,
    expected_quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
    counted_quantity DECIMAL(12,3) NULL,
    variance_quantity DECIMAL(12,3) NULL,
    INDEX idx_stores_count_lines_count (count_id),
    INDEX idx_stores_count_lines_item (item_id),
    UNIQUE KEY uq_stores_count_item (count_id,item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
