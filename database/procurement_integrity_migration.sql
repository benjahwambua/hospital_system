-- Procurement -> GRN -> Inventory -> Supplier Payables integrity migration
SET @db=DATABASE();

SET @sql=IF(NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='purchase_order_items' AND COLUMN_NAME='inventory_type'),
'ALTER TABLE purchase_order_items ADD COLUMN inventory_type VARCHAR(30) NOT NULL DEFAULT ''pharmacy'' AFTER item_name','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql=IF(NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='purchase_order_items' AND COLUMN_NAME='inventory_item_id'),
'ALTER TABLE purchase_order_items ADD COLUMN inventory_item_id INT NULL AFTER inventory_type','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql=IF(NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='inventory_receipts' AND COLUMN_NAME='inventory_type'),
'ALTER TABLE inventory_receipts ADD COLUMN inventory_type VARCHAR(30) NOT NULL DEFAULT ''pharmacy'' AFTER po_item_id','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS supplier_payables (
 id INT AUTO_INCREMENT PRIMARY KEY,
 supplier_id INT NOT NULL,
 po_id INT NULL,
 receipt_id INT NULL,
 supplier_invoice_no VARCHAR(120) NOT NULL,
 amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 balance DECIMAL(12,2) NOT NULL DEFAULT 0,
 status ENUM('Unpaid','Partially Paid','Paid','Cancelled') NOT NULL DEFAULT 'Unpaid',
 due_date DATE NULL,
 created_by INT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 INDEX idx_sp_supplier(supplier_id), INDEX idx_sp_po(po_id), INDEX idx_sp_receipt(receipt_id),
 INDEX idx_sp_status(status), INDEX idx_sp_invoice(supplier_invoice_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_payments (
 id INT AUTO_INCREMENT PRIMARY KEY,
 payable_id INT NOT NULL,
 supplier_id INT NOT NULL,
 amount DECIMAL(12,2) NOT NULL,
 payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash',
 reference VARCHAR(120) NULL,
 notes VARCHAR(255) NULL,
 paid_by INT NULL,
 paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_spp_payable(payable_id), INDEX idx_spp_supplier(supplier_id), INDEX idx_spp_paid_at(paid_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql=IF(NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='supplier_payables' AND INDEX_NAME='idx_sp_receipt_unique'),
'CREATE UNIQUE INDEX idx_sp_receipt_unique ON supplier_payables(receipt_id)','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
