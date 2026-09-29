-- Service Catalog & Pricing Foundation
-- Run once against the existing HMS database.
-- This migration keeps services_master as the canonical service identity table
-- and moves pricing into an effective-dated service_prices table.

CREATE TABLE IF NOT EXISTS service_prices (
    id INT NOT NULL AUTO_INCREMENT,
    service_id INT NOT NULL,
    payer_id INT NULL,
    plan_id INT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    reason VARCHAR(255) NULL,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_by INT NULL,
    approved_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_service_prices_service (service_id),
    KEY idx_service_prices_payer_plan (payer_id, plan_id),
    KEY idx_service_prices_dates (effective_from, effective_to),
    CONSTRAINT fk_service_prices_service
        FOREIGN KEY (service_id) REFERENCES services_master(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Expand the service master so new service types do not require ALTER TABLE
-- every time the hospital introduces a new category.
ALTER TABLE services_master
    MODIFY category VARCHAR(50) NOT NULL,
    ADD COLUMN service_code VARCHAR(50) NULL AFTER id,
    ADD COLUMN department VARCHAR(100) NULL AFTER category,
    ADD COLUMN unit VARCHAR(50) NOT NULL DEFAULT 'Each' AFTER department,
    ADD COLUMN description TEXT NULL AFTER unit,
    ADD COLUMN cost_price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER price,
    ADD COLUMN requires_order TINYINT(1) NOT NULL DEFAULT 0 AFTER cost_price,
    ADD COLUMN requires_result TINYINT(1) NOT NULL DEFAULT 0 AFTER requires_order,
    ADD COLUMN billable TINYINT(1) NOT NULL DEFAULT 1 AFTER requires_result,
    ADD COLUMN created_by INT NULL AFTER billable,
    ADD COLUMN updated_by INT NULL AFTER created_by,
    ADD COLUMN updated_at DATETIME NULL AFTER created_at;

UPDATE services_master
SET service_code = CONCAT('SVC-', LPAD(id, 5, '0'))
WHERE service_code IS NULL OR service_code = '';

UPDATE services_master
SET category = 'procedure'
WHERE LOWER(category) IN ('procedures', 'procedure');

UPDATE services_master
UPDATE services_master
SET category = 'radiology'
WHERE LOWER(category) = 'radiology';

UPDATE services_master
SET department = CASE
    WHEN category = 'lab' THEN 'Laboratory'
    WHEN category = 'radiology' THEN 'Radiology'
    WHEN category = 'procedure' THEN 'Clinical'
    WHEN category = 'treatment' THEN 'Clinical'
    ELSE department
END
WHERE department IS NULL OR department = '';

ALTER TABLE services_master
    ADD UNIQUE KEY uq_services_master_code (service_code);

-- Seed the existing standard price as the first cash/standard price.
INSERT INTO service_prices
    (service_id, payer_id, plan_id, price, effective_from, active, reason)
SELECT
    sm.id, NULL, NULL, sm.price, DATE(sm.created_at), 1, 'Migrated from services_master.price'
FROM services_master sm
LEFT JOIN service_prices sp
    ON sp.service_id = sm.id
   AND sp.payer_id IS NULL
   AND sp.plan_id IS NULL
WHERE sp.id IS NULL;

-- Preserve the price actually charged on historical transactions and prepare
-- patient_services for future price-list/tariff references.
ALTER TABLE patient_services
    ADD COLUMN service_code_snapshot VARCHAR(50) NULL AFTER service_id,
    ADD COLUMN service_name_snapshot VARCHAR(255) NULL AFTER service_code_snapshot,
    ADD COLUMN quantity DECIMAL(12,3) NOT NULL DEFAULT 1.000 AFTER price,
    ADD COLUMN gross_amount DECIMAL(12,2) NULL AFTER quantity,
    ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER gross_amount,
    ADD COLUMN net_amount DECIMAL(12,2) NULL AFTER discount_amount,
    ADD COLUMN price_id INT NULL AFTER net_amount,
    ADD COLUMN payer_id INT NULL AFTER price_id,
    ADD COLUMN plan_id INT NULL AFTER payer_id;

UPDATE patient_services ps
INNER JOIN services_master sm ON sm.id = ps.service_id
SET
    ps.service_code_snapshot = sm.service_code,
    ps.service_name_snapshot = sm.service_name,
    ps.gross_amount = ps.price,
    ps.net_amount = ps.price
WHERE ps.service_code_snapshot IS NULL;

-- Keep service master price temporarily as the displayed/default cash price
-- for legacy screens while application code is migrated to service_prices.
