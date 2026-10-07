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
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments'),
 'ALTER TABLE payments MODIFY patient_id INT NULL',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='mpesa_transactions'),
 'ALTER TABLE mpesa_transactions MODIFY patient_id INT NULL',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='mpesa_transactions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='mpesa_transactions' AND COLUMN_NAME='cashier_shift_id'),
 'ALTER TABLE mpesa_transactions ADD COLUMN cashier_shift_id INT NULL AFTER patient_id',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

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

-- Database-level idempotency for payment/refund references.
-- Existing duplicate references are deliberately detected first; if duplicates
-- already exist, application-level checks remain active and the migration does
-- not fail or destroy historical records.
SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments')
 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='reference')
 AND NOT EXISTS(SELECT 1 FROM payments WHERE reference IS NOT NULL AND TRIM(reference)<>'' GROUP BY reference HAVING COUNT(*)>1)
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND INDEX_NAME='uq_payments_reference'),
 'CREATE UNIQUE INDEX uq_payments_reference ON payments(reference)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payment_refunds')
 AND NOT EXISTS(SELECT 1 FROM payment_refunds WHERE reference IS NOT NULL AND TRIM(reference)<>'' GROUP BY reference HAVING COUNT(*)>1)
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payment_refunds' AND INDEX_NAME='uq_refund_reference'),
 'CREATE UNIQUE INDEX uq_refund_reference ON payment_refunds(reference)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='mpesa_transactions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='mpesa_transactions' AND INDEX_NAME='uq_mpesa_checkout'),
 'CREATE UNIQUE INDEX uq_mpesa_checkout ON mpesa_transactions(checkout_request_id)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql=IF(
 EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='mpesa_transactions')
 AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND INDEX_NAME='uq_mpesa_receipt')
 AND NOT EXISTS(SELECT 1 FROM mpesa_transactions WHERE mpesa_receipt IS NOT NULL AND TRIM(mpesa_receipt)<>'' GROUP BY mpesa_receipt HAVING COUNT(*)>1),
 'CREATE UNIQUE INDEX uq_mpesa_receipt ON mpesa_transactions(mpesa_receipt)',
 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
