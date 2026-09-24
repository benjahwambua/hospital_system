-- HMS billing/M-Pesa migration. Run once in hms_db.
ALTER TABLE prescriptions ADD COLUMN unit_price DECIMAL(10,2) NULL AFTER quantity;

CREATE TABLE IF NOT EXISTS mpesa_transactions (
 id INT AUTO_INCREMENT PRIMARY KEY,
 invoice_id INT NOT NULL,
 patient_id INT NOT NULL,
 amount DECIMAL(10,2) NOT NULL,
 phone VARCHAR(20) NOT NULL,
 merchant_request_id VARCHAR(100) DEFAULT NULL,
 checkout_request_id VARCHAR(100) DEFAULT NULL,
 mpesa_receipt VARCHAR(100) DEFAULT NULL,
 result_code VARCHAR(20) DEFAULT NULL,
 result_desc VARCHAR(255) DEFAULT NULL,
 status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
 raw_response LONGTEXT DEFAULT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_mpesa_checkout (checkout_request_id),
 UNIQUE KEY uq_mpesa_receipt (mpesa_receipt),
 KEY idx_mpesa_invoice (invoice_id),
 KEY idx_mpesa_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE payments ADD COLUMN invoice_id INT NULL AFTER reference;
ALTER TABLE payments ADD KEY idx_payments_invoice (invoice_id);
ALTER TABLE billing ADD KEY idx_billing_invoice (invoice_id);

UPDATE invoices SET status='unpaid' WHERE status IS NULL OR LOWER(status) NOT IN ('unpaid','paid');
UPDATE invoices SET payment_status=CASE WHEN COALESCE(paid_amount,0)>=COALESCE(total,0) AND COALESCE(total,0)>0 THEN 'Paid' ELSE 'Unpaid' END;
