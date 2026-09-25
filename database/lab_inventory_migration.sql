-- HMS pharmacy walk-in sales + laboratory inventory
-- Safe to run once.

CREATE TABLE IF NOT EXISTS lab_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'Laboratory Consumable',
    unit VARCHAR(50) NOT NULL DEFAULT 'Piece',
    quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
    reorder_level DECIMAL(12,2) NOT NULL DEFAULT 0,
    buying_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    supplier VARCHAR(150) DEFAULT NULL,
    batch_no VARCHAR(100) DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_lab_inventory_name (item_name),
    KEY idx_lab_inventory_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lab_inventory_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    movement_type ENUM('in','out','adjustment') NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    balance_after DECIMAL(12,2) NOT NULL,
    reference_no VARCHAR(100) DEFAULT NULL,
    note VARCHAR(255) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lab_movements_inventory (inventory_id),
    KEY idx_lab_movements_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
