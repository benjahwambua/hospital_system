-- Billing and accounting integrity migration
-- Safe to run more than once.

SET @db = DATABASE();

SET @sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='accounting_entries')
  AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='accounting_entries' AND COLUMN_NAME='reference_id'),
  'ALTER TABLE accounting_entries ADD COLUMN reference_id VARCHAR(100) NULL AFTER note',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='accounting_entries')
  AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='accounting_entries' AND INDEX_NAME='idx_accounting_reference_id'),
  'CREATE INDEX idx_accounting_reference_id ON accounting_entries(reference_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments')
  AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_invoice'),
  'CREATE INDEX idx_payments_invoice ON payments(invoice_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='invoice_items')
  AND NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='invoice_items' AND INDEX_NAME='idx_invoice_items_invoice'),
  'CREATE INDEX idx_invoice_items_invoice ON invoice_items(invoice_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
