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
    KEY idx_theatre_case_stock_lot (case_id, item_id, location_id, batch_number),
    UNIQUE KEY uq_theatre_stock_movement (movement_id),
    KEY idx_theatre_stock_item_location (item_id, location_id),
    CONSTRAINT fk_theatre_stock_case FOREIGN KEY (case_id) REFERENCES theatre_cases(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Upgrade older installations that created a unique case/item/lot key.
-- Repeated issues are valid: every stock movement has its own unique movement_id.
SET @theatre_drop_lot_unique = IF(
    EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'theatre_case_stock_issues'
          AND index_name = 'uq_theatre_case_stock_lot'
    ),
    'ALTER TABLE theatre_case_stock_issues DROP INDEX uq_theatre_case_stock_lot',
    'SELECT 1'
);
PREPARE theatre_drop_lot_unique_stmt FROM @theatre_drop_lot_unique;
EXECUTE theatre_drop_lot_unique_stmt;
DEALLOCATE PREPARE theatre_drop_lot_unique_stmt;
