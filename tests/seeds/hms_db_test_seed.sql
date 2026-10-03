-- Test data seed for HMS
-- Populate with sample data for manual and automated testing

-- Users (test accounts)
INSERT INTO users (username, password_hash, full_name, role, is_super, active, created_at) VALUES
('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMye0ItqMh8.cHPTQYjDzZzLY6sE3H1IGRO', 'Administrator', 'admin', 1, 1, NOW()),
('cashier1', '$2y$10$N9qo8uLOickgx2ZMRZoMye0ItqMh8.cHPTQYjDzZzLY6sE3H1IGRO', 'John Cashier', 'cashier', 0, 1, NOW()),
('receptionist1', '$2y$10$N9qo8uLOickgx2ZMRZoMye0ItqMh8.cHPTQYjDzZzLY6sE3H1IGRO', 'Mary Reception', 'receptionist', 0, 1, NOW()),
('doctor1', '$2y$10$N9qo8uLOickgx2ZMRZoMye0ItqMh8.cHPTQYjDzZzLY6sE3H1IGRO', 'Dr. Sample', 'doctor', 0, 1, NOW()),
('lab1', '$2y$10$N9qo8uLOickgx2ZMRZoMye0ItqMh8.cHPTQYjDzZzLY6sE3H1IGRO', 'Lab Tech', 'lab', 0, 1, NOW());

-- Patients (test cases)
INSERT INTO patients (patient_number, full_name, gender, date_of_birth, phone, address, age, is_walkin, created_at) VALUES
('PAT-00001', 'John Doe', 'Male', '1990-01-15', '0712345678', '123 Main St', 34, 0, NOW()),
('PAT-00002', 'Jane Smith', 'Female', '1985-05-20', '0700000001', '456 Oak Ave', 39, 0, NOW()),
('PAT-00003', 'Mary Johnson', 'Female', '1992-08-10', '0700000002', '789 Elm St', 32, 0, NOW()),
('WLK-0001', 'Walk-in Test', 'Male', NULL, '0700000003', NULL, NULL, 1, NOW()),
('PAT-00004', 'Robert Brown', 'Male', '1988-03-25', '0700000004', '321 Pine Rd', 36, 0, NOW());

-- Services Master (billable services)
INSERT INTO services_master (service_name, category, billable, price, active, created_at) VALUES
('Consultation', 'general', 1, 500.00, 1, NOW()),
('Blood Test', 'lab', 1, 800.00, 1, NOW()),
('X-Ray', 'radiology', 1, 1500.00, 1, NOW()),
('Injection', 'clinical', 1, 200.00, 1, NOW()),
('Maternity ANC', 'anc', 1, 600.00, 1, NOW()),
('Paracetamol 500mg', 'pharmacy', 1, 50.00, 1, NOW());

-- Invoices (test billing)
INSERT INTO invoices (invoice_number, patient_id, total, status, payment_status, created_at) VALUES
('INV-TEST-001', 1, 1300.00, 'unpaid', 'unpaid', NOW()),
('INV-TEST-002', 2, 2000.00, 'unpaid', 'unpaid', NOW()),
('INV-TEST-003', 3, 800.00, 'unpaid', 'unpaid', NOW()),
('INV-TEST-004', 4, 500.00, 'unpaid', 'unpaid', NOW()),
('INV-TEST-005', 5, 1500.00, 'paid', 'paid', NOW() - INTERVAL 7 DAY);

-- Invoice Items
INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total, item_type, created_at) VALUES
(1, 'Service: Consultation', 1, 500.00, 500.00, 'service', NOW()),
(1, 'Lab: Blood Test', 1, 800.00, 800.00, 'lab', NOW()),
(2, 'Service: Consultation', 1, 500.00, 500.00, 'service', NOW()),
(2, 'Radiology: X-Ray', 1, 1500.00, 1500.00, 'radiology', NOW()),
(3, 'Lab: Blood Test', 1, 800.00, 800.00, 'lab', NOW()),
(4, 'Service: Consultation', 1, 500.00, 500.00, 'service', NOW()),
(5, 'Service: Consultation', 1, 500.00, 500.00, 'service', NOW()),
(5, 'Radiology: X-Ray', 1, 1000.00, 1000.00, 'radiology', NOW());

-- Payments
INSERT INTO payments (invoice_id, amount, method, reference, payment_status, created_at) VALUES
(5, 1500.00, 'Cash', 'CASH-00001', 'completed', NOW() - INTERVAL 7 DAY);

-- Access Modules
INSERT INTO access_modules (module_name, description, active) VALUES
('patients', 'Patient management', 1),
('clinical', 'Clinical operations', 1),
('pharmacy', 'Pharmacy management', 1),
('lab', 'Laboratory operations', 1),
('radiology', 'Radiology operations', 1),
('billing', 'Billing and invoicing', 1),
('finance', 'Finance and cashier', 1),
('administration', 'System administration', 1),
('accounting', 'Accounting and reconciliation', 1);

-- User Role Module Access
INSERT INTO user_module_access (user_id, module_id, can_view, can_create, can_edit, can_delete, can_approve) VALUES
(1, 1, 1, 1, 1, 1, 1), -- Admin access to patients
(1, 2, 1, 1, 1, 1, 1), -- Admin access to clinical
(1, 9, 1, 1, 1, 1, 1), -- Admin access to accounting
(2, 9, 1, 1, 1, 0, 1), -- Cashier access to finance
(3, 1, 1, 1, 0, 0, 0), -- Receptionist access to patients
(4, 2, 1, 1, 1, 0, 0), -- Doctor access to clinical
(5, 4, 1, 1, 1, 0, 0); -- Lab tech access to lab
