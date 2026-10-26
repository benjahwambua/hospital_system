-- Insurance claim tariff adjustments
-- Apply after database/insurance_migration.sql. Back up the database first.
-- Existing claim rows remain NULL because their historical allowed amounts and
-- contractual adjustments cannot safely be inferred from invoice gross alone.
ALTER TABLE claim_items
    ADD COLUMN allowed_amount DECIMAL(12,2) NULL AFTER gross_amount,
    ADD COLUMN contractual_adjustment DECIMAL(12,2) NULL AFTER allowed_amount;
