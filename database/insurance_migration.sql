-- Core payer / SHA / insurance data model starter for HMS
-- Designed for MySQL / MariaDB style environments.

CREATE TABLE IF NOT EXISTS payers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payer_code VARCHAR(50) NOT NULL UNIQUE,
    payer_name VARCHAR(150) NOT NULL,
    payer_type ENUM('SHA', 'Insurance', 'Corporate', 'Cash', 'Other') NOT NULL,
    contact_person VARCHAR(150) DEFAULT NULL,
    contact_phone VARCHAR(50) DEFAULT NULL,
    contact_email VARCHAR(150) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    claims_email VARCHAR(150) DEFAULT NULL,
    requires_preauth TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payer_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payer_id INT NOT NULL,
    plan_code VARCHAR(50) NOT NULL,
    plan_name VARCHAR(150) NOT NULL,
    coverage_scope ENUM('Outpatient', 'Inpatient', 'Both') NOT NULL DEFAULT 'Outpatient',
    consultation_cover_percent DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    pharmacy_cover_percent DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    lab_cover_percent DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    radiology_cover_percent DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    procedure_cover_percent DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    default_copay_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    annual_limit DECIMAL(12,2) DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payer_plans_payer FOREIGN KEY (payer_id) REFERENCES payers(id),
    UNIQUE KEY uq_payer_plan_code (payer_id, plan_code)
);

CREATE TABLE IF NOT EXISTS patient_coverages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    payer_id INT NOT NULL,
    plan_id INT DEFAULT NULL,
    member_number VARCHAR(100) NOT NULL,
    card_number VARCHAR(100) DEFAULT NULL,
    principal_member_name VARCHAR(150) DEFAULT NULL,
    employer_name VARCHAR(150) DEFAULT NULL,
    relationship_to_principal VARCHAR(50) DEFAULT NULL,
    eligibility_status ENUM('Pending', 'Verified', 'Inactive', 'Expired', 'Rejected') NOT NULL DEFAULT 'Pending',
    verification_reference VARCHAR(100) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_coverages_payer FOREIGN KEY (payer_id) REFERENCES payers(id),
    CONSTRAINT fk_patient_coverages_plan FOREIGN KEY (plan_id) REFERENCES payer_plans(id),
    UNIQUE KEY uq_patient_member (patient_id, payer_id, member_number)
);

CREATE TABLE IF NOT EXISTS payer_tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payer_id INT NOT NULL,
    plan_id INT DEFAULT NULL,
    item_type ENUM('Consultation', 'Service', 'Lab', 'Radiology', 'Procedure', 'Pharmacy', 'Admission', 'Other') NOT NULL,
    item_code VARCHAR(100) NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    base_price DECIMAL(12,2) NOT NULL,
    approved_price DECIMAL(12,2) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payer_tariffs_payer FOREIGN KEY (payer_id) REFERENCES payers(id),
    CONSTRAINT fk_payer_tariffs_plan FOREIGN KEY (plan_id) REFERENCES payer_plans(id),
    UNIQUE KEY uq_tariff_item (payer_id, plan_id, item_type, item_code)
);

CREATE TABLE IF NOT EXISTS preauthorizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    patient_coverage_id INT NOT NULL,
    payer_id INT NOT NULL,
    plan_id INT DEFAULT NULL,
    request_number VARCHAR(100) NOT NULL UNIQUE,
    authorization_number VARCHAR(100) DEFAULT NULL,
    requested_service_type ENUM('Consultation', 'Service', 'Lab', 'Radiology', 'Procedure', 'Pharmacy', 'Admission', 'Other') NOT NULL,
    requested_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    approved_amount DECIMAL(12,2) DEFAULT NULL,
    status ENUM('Draft', 'Submitted', 'Approved', 'Partially Approved', 'Rejected', 'Expired') NOT NULL DEFAULT 'Draft',
    valid_from DATETIME DEFAULT NULL,
    valid_to DATETIME DEFAULT NULL,
    clinical_notes TEXT DEFAULT NULL,
    payer_notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_preauth_coverage FOREIGN KEY (patient_coverage_id) REFERENCES patient_coverages(id),
    CONSTRAINT fk_preauth_payer FOREIGN KEY (payer_id) REFERENCES payers(id),
    CONSTRAINT fk_preauth_plan FOREIGN KEY (plan_id) REFERENCES payer_plans(id)
);

CREATE TABLE IF NOT EXISTS patient_financial_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL UNIQUE,
    account_class ENUM('Cash', 'SHA', 'Insurance', 'Corporate', 'Mixed') NOT NULL DEFAULT 'Cash',
    current_coverage_id INT DEFAULT NULL,
    current_payer_id INT DEFAULT NULL,
    current_plan_id INT DEFAULT NULL,
    running_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_copay_due DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_claims_outstanding DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    last_verified_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_financial_account_coverage FOREIGN KEY (current_coverage_id) REFERENCES patient_coverages(id),
    CONSTRAINT fk_financial_account_payer FOREIGN KEY (current_payer_id) REFERENCES payers(id),
    CONSTRAINT fk_financial_account_plan FOREIGN KEY (current_plan_id) REFERENCES payer_plans(id)
);

CREATE TABLE IF NOT EXISTS claim_headers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_number VARCHAR(100) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    patient_coverage_id INT NOT NULL,
    payer_id INT NOT NULL,
    plan_id INT DEFAULT NULL,
    invoice_id INT DEFAULT NULL,
    preauthorization_id INT DEFAULT NULL,
    encounter_date DATE NOT NULL,
    submission_date DATE DEFAULT NULL,
    claim_status ENUM('Draft', 'Submitted', 'Under Review', 'Approved', 'Partially Approved', 'Rejected', 'Paid', 'Closed') NOT NULL DEFAULT 'Draft',
    total_claim_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_approved_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_patient_responsibility DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    diagnosis_summary TEXT DEFAULT NULL,
    submission_notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_claim_header_coverage FOREIGN KEY (patient_coverage_id) REFERENCES patient_coverages(id),
    CONSTRAINT fk_claim_header_payer FOREIGN KEY (payer_id) REFERENCES payers(id),
    CONSTRAINT fk_claim_header_plan FOREIGN KEY (plan_id) REFERENCES payer_plans(id),
    CONSTRAINT fk_claim_header_preauth FOREIGN KEY (preauthorization_id) REFERENCES preauthorizations(id)
);

CREATE TABLE IF NOT EXISTS claim_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_id INT NOT NULL,
    item_type ENUM('Consultation', 'Service', 'Lab', 'Radiology', 'Procedure', 'Pharmacy', 'Admission', 'Other') NOT NULL,
    source_table VARCHAR(100) DEFAULT NULL,
    source_id INT DEFAULT NULL,
    item_code VARCHAR(100) DEFAULT NULL,
    item_name VARCHAR(200) NOT NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    covered_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    copay_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    patient_responsibility DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('Draft', 'Submitted', 'Approved', 'Partially Approved', 'Rejected', 'Paid') NOT NULL DEFAULT 'Draft',
    rejection_reason VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_claim_items_header FOREIGN KEY (claim_id) REFERENCES claim_headers(id)
);

CREATE TABLE IF NOT EXISTS invoice_financial_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    patient_id INT DEFAULT NULL,
    allocation_type ENUM('Patient', 'SHA', 'Insurance', 'Corporate', 'Writeoff') NOT NULL,
    payer_id INT DEFAULT NULL,
    patient_coverage_id INT DEFAULT NULL,
    claim_id INT DEFAULT NULL,
    expected_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    approved_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    settled_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    balance_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    allocation_status ENUM('Open', 'Partially Settled', 'Settled', 'Denied', 'Written Off') NOT NULL DEFAULT 'Open',
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoice_allocations_payer FOREIGN KEY (payer_id) REFERENCES payers(id),
    CONSTRAINT fk_invoice_allocations_coverage FOREIGN KEY (patient_coverage_id) REFERENCES patient_coverages(id),
    CONSTRAINT fk_invoice_allocations_claim FOREIGN KEY (claim_id) REFERENCES claim_headers(id)
);

CREATE TABLE IF NOT EXISTS invoice_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    patient_id INT DEFAULT NULL,
    payer_source ENUM('Patient', 'SHA', 'Insurance', 'Corporate', 'Other') NOT NULL DEFAULT 'Patient',
    payer_id INT DEFAULT NULL,
    patient_coverage_id INT DEFAULT NULL,
    payment_method ENUM('Cash', 'Mpesa', 'Card', 'Bank', 'Cheque', 'Transfer', 'Credit', 'Other') NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    reference_number VARCHAR(100) DEFAULT NULL,
    external_reference VARCHAR(100) DEFAULT NULL,
    payment_status ENUM('Pending', 'Completed', 'Reversed', 'Failed') NOT NULL DEFAULT 'Completed',
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoice_payments_payer FOREIGN KEY (payer_id) REFERENCES payers(id),
    CONSTRAINT fk_invoice_payments_coverage FOREIGN KEY (patient_coverage_id) REFERENCES patient_coverages(id)
);

CREATE TABLE IF NOT EXISTS invoice_payment_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_payment_id INT NOT NULL,
    invoice_id INT NOT NULL,
    invoice_financial_allocation_id INT DEFAULT NULL,
    claim_id INT DEFAULT NULL,
    remittance_item_id INT DEFAULT NULL,
    applied_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    allocation_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_allocations_payment FOREIGN KEY (invoice_payment_id) REFERENCES invoice_payments(id),
    CONSTRAINT fk_payment_allocations_invoice_split FOREIGN KEY (invoice_financial_allocation_id) REFERENCES invoice_financial_allocations(id),
    CONSTRAINT fk_payment_allocations_claim FOREIGN KEY (claim_id) REFERENCES claim_headers(id)
);

CREATE TABLE IF NOT EXISTS remittances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payer_id INT NOT NULL,
    reference_number VARCHAR(100) NOT NULL UNIQUE,
    payment_date DATE NOT NULL,
    amount_received DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('Bank', 'Cheque', 'Transfer', 'Cash', 'Other') NOT NULL DEFAULT 'Bank',
    bank_reference VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_remittances_payer FOREIGN KEY (payer_id) REFERENCES payers(id)
);

CREATE TABLE IF NOT EXISTS remittance_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remittance_id INT NOT NULL,
    claim_id INT NOT NULL,
    claim_item_id INT DEFAULT NULL,
    approved_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    adjustment_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    patient_responsibility_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    remittance_status ENUM('Matched', 'Partial', 'Denied', 'Unmatched') NOT NULL DEFAULT 'Matched',
    remarks TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_remittance_items_header FOREIGN KEY (remittance_id) REFERENCES remittances(id),
    CONSTRAINT fk_remittance_items_claim FOREIGN KEY (claim_id) REFERENCES claim_headers(id),
    CONSTRAINT fk_remittance_items_claim_item FOREIGN KEY (claim_item_id) REFERENCES claim_items(id)
);

CREATE TABLE IF NOT EXISTS claim_denials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_id INT NOT NULL,
    claim_item_id INT DEFAULT NULL,
    denial_code VARCHAR(50) DEFAULT NULL,
    denial_reason VARCHAR(255) NOT NULL,
    denial_category ENUM('Eligibility', 'Authorization', 'Benefit Limit', 'Documentation', 'Coding', 'Duplicate', 'Pricing', 'Other') NOT NULL DEFAULT 'Other',
    denied_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    appeal_status ENUM('Not Appealed', 'Appealed', 'Resolved', 'Written Off') NOT NULL DEFAULT 'Not Appealed',
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_claim_denials_header FOREIGN KEY (claim_id) REFERENCES claim_headers(id),
    CONSTRAINT fk_claim_denials_item FOREIGN KEY (claim_item_id) REFERENCES claim_items(id)
);
