-- Central Stores lot-level physical counts
-- Run once AFTER database/central_stores_migration.sql and BEFORE deploying the
-- matching stores/index.php change. Take a database backup first.
--
-- Existing count lines are preserved as legacy/untracked snapshots. Any old
-- count that included tracked lots should be rejected and recounted after this
-- migration because its historical expected quantity was item-level, not lot-level.

ALTER TABLE stores_stock_count_lines
    ADD COLUMN batch_number VARCHAR(100) NULL AFTER item_id,
    ADD COLUMN expiry_date DATE NULL AFTER batch_number,
    ADD COLUMN lot_key CHAR(64) NOT NULL DEFAULT '' AFTER expiry_date;

UPDATE stores_stock_count_lines
SET lot_key = SHA2(CONCAT('legacy-count-line:', id), 256)
WHERE lot_key = '';

ALTER TABLE stores_stock_count_lines
    DROP INDEX uq_stores_count_item,
    ADD UNIQUE KEY uq_stores_count_item_lot (count_id, item_id, lot_key),
    ADD INDEX idx_stores_count_lot (item_id, batch_number, expiry_date);
