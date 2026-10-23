-- Emergency transfer handover details.
-- Apply once after database/emergency_casualty_migration.sql.
ALTER TABLE emergency_cases
    ADD COLUMN disposition_destination VARCHAR(255) NULL AFTER disposition_notes,
    ADD COLUMN receiving_contact VARCHAR(255) NULL AFTER disposition_destination;
