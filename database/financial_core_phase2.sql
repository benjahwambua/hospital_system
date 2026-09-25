-- Phase 2 Financial Core hardening
-- Safe to run once in hms_db. Adds refund/reversal support and reconciliation metadata.

CREATE TABLE IF NOT EXISTS payment_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    invoice_id INT NOT NULL,
    patient_id INT NULL,
    amount DECIMAL(12,2) NOT NULL,
    refund_method VARCHAR(50) NOT NULL DEFAULT 'Original',
    reference VARCHAR(100) NULL,
    reason VARCHAR(500) NOT NULL,
    status ENUM('Approved','Voided') NOT NULL DEFAULT 'Approved',
    refunded_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_refunds_payment (payment_id),
    INDEX idx_refunds_invoice (invoice_id),
    INDEX idx_refunds_created (created_at),
    INDEX idx_refunds_reference (reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @db=DATABASE();

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_reference'),
 'CREATE INDEX idx_payments_reference ON payments(reference)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='mpesa_transactions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='mpesa_transactions' AND INDEX_NAME='idx_mpesa_checkout'),
 'CREATE INDEX idx_mpesa_checkout ON mpesa_transactions(checkout_request_id)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='mpesa_transactions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='mpesa_transactions' AND INDEX_NAME='idx_mpesa_receipt'),
 'CREATE INDEX idx_mpesa_receipt ON mpesa_transactions(mpesa_receipt)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='expected_mpesa'),
 'ALTER TABLE cashier_shifts ADD COLUMN expected_mpesa DECIMAL(12,2) NULL AFTER expected_cash',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='expected_other'),
 'ALTER TABLE cashier_shifts ADD COLUMN expected_other DECIMAL(12,2) NULL AFTER expected_mpesa',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='total_collected'),
 'ALTER TABLE cashier_shifts ADD COLUMN total_collected DECIMAL(12,2) NULL AFTER expected_other',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='reconciled_by'),
 'ALTER TABLE cashier_shifts ADD COLUMN reconciled_by INT NULL AFTER closing_notes',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cashier_shifts' AND COLUMN_NAME='reconciled_at'),
 'ALTER TABLE cashier_shifts ADD COLUMN reconciled_at DATETIME NULL AFTER reconciled_by',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
