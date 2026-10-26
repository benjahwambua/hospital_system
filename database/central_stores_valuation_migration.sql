-- Central Stores inventory valuation foundation
-- Run after database/central_stores_migration.sql.
-- Backup the database first. Existing movements remain NULL-costed until
-- reconciled from reliable source documents; do not invent historical costs.
ALTER TABLE stores_movements
    ADD COLUMN unit_cost DECIMAL(12,4) NULL AFTER quantity,
    ADD COLUMN total_cost DECIMAL(14,2) NULL AFTER unit_cost,
    ADD INDEX idx_stores_movements_cost (item_id, batch_number, expiry_date, movement_type);
