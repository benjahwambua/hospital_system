-- Central cashier shift management
CREATE TABLE IF NOT EXISTS cashier_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cashier_id INT NOT NULL,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    opening_cash DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    closed_at DATETIME NULL,
    closing_cash DECIMAL(12,2) NULL,
    expected_cash DECIMAL(12,2) NULL,
    cash_variance DECIMAL(12,2) NULL,
    status ENUM('Open','Closed') NOT NULL DEFAULT 'Open',
    opening_notes TEXT NULL,
    closing_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cashier_shifts_cashier (cashier_id),
    INDEX idx_cashier_shifts_status (status),
    INDEX idx_cashier_shifts_opened (opened_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_shift_id := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'cashier_shift_id'
);
SET @sql := IF(@has_shift_id = 0,
    'ALTER TABLE payments ADD COLUMN cashier_shift_id INT NULL AFTER invoice_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_shift_index := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND INDEX_NAME = 'idx_payments_cashier_shift'
);
SET @sql := IF(@has_shift_index = 0,
    'CREATE INDEX idx_payments_cashier_shift ON payments(cashier_shift_id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
