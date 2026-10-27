-- Theatre consumables usage linked to Central Stores movements.
-- Apply once after database/theatre_surgery_migration.sql and Central Stores migrations.
CREATE TABLE IF NOT EXISTS theatre_case_stock_issues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    item_id INT NOT NULL,
    location_id INT NOT NULL,
    movement_id BIGINT NOT NULL,
    batch_number VARCHAR(100) NOT NULL DEFAULT '',
    expiry_date DATE NULL,
    quantity DECIMAL(12,3) NOT NULL,
    recorded_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_theatre_case_stock_lot (case_id, item_id, location_id, batch_number),
    UNIQUE KEY uq_theatre_stock_movement (movement_id),
    KEY idx_theatre_stock_item_location (item_id, location_id),
    CONSTRAINT fk_theatre_stock_case FOREIGN KEY (case_id) REFERENCES theatre_cases(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
