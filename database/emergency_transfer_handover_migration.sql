-- Emergency transfer handover details.
-- Apply after database/emergency_casualty_migration.sql.
-- Idempotent on MariaDB 10.4: the exported HMS schema may already contain
-- either or both columns, so only add a column when it is missing.

SET @hms_add_disposition_destination = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'emergency_cases'
          AND COLUMN_NAME = 'disposition_destination'
    ),
    'SELECT 1',
    'ALTER TABLE emergency_cases ADD COLUMN disposition_destination VARCHAR(255) NULL AFTER disposition_notes'
);
PREPARE hms_add_disposition_destination_stmt FROM @hms_add_disposition_destination;
EXECUTE hms_add_disposition_destination_stmt;
DEALLOCATE PREPARE hms_add_disposition_destination_stmt;

SET @hms_add_receiving_contact = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'emergency_cases'
          AND COLUMN_NAME = 'receiving_contact'
    ),
    'SELECT 1',
    'ALTER TABLE emergency_cases ADD COLUMN receiving_contact VARCHAR(255) NULL AFTER disposition_destination'
);
PREPARE hms_add_receiving_contact_stmt FROM @hms_add_receiving_contact;
EXECUTE hms_add_receiving_contact_stmt;
DEALLOCATE PREPARE hms_add_receiving_contact_stmt;
