-- HMS Laboratory service material / reagent usage
CREATE TABLE IF NOT EXISTS lab_service_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity_per_test DECIMAL(12,4) NOT NULL DEFAULT 1,
    unit VARCHAR(50) DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_lab_service_material (service_id, inventory_id),
    KEY idx_lsm_service (service_id),
    KEY idx_lsm_inventory (inventory_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lab_resource_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_service_id INT NOT NULL,
    service_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity DECIMAL(12,4) NOT NULL,
    unit VARCHAR(50) DEFAULT NULL,
    reference_no VARCHAR(100) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_lab_usage_service_inventory (patient_service_id, inventory_id),
    KEY idx_lab_usage_patient_service (patient_service_id),
    KEY idx_lab_usage_inventory (inventory_id),
    KEY idx_lab_usage_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
