-- Nursing MAR scheduled-dose accountability guard.
-- Apply after database/nursing_medication_administration_migration.sql.
-- A prescription can have multiple scheduled dose times, but only one MAR outcome
-- should exist for a given prescription/time slot. NULL scheduled_at values remain
-- allowed for legacy and intentionally unscheduled MAR events.
-- Before applying to a database with pre-existing scheduled rows, check:
-- SELECT prescription_id, scheduled_at, COUNT(*) AS records
-- FROM nursing_medication_administrations
-- WHERE scheduled_at IS NOT NULL
-- GROUP BY prescription_id, scheduled_at HAVING COUNT(*) > 1;

SET @mar_scheduled_index_exists = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'nursing_medication_administrations'
    AND index_name = 'uq_mar_prescription_scheduled_dose'
);
SET @mar_scheduled_index_sql = IF(
  @mar_scheduled_index_exists = 0,
  'ALTER TABLE nursing_medication_administrations ADD UNIQUE KEY uq_mar_prescription_scheduled_dose (prescription_id, scheduled_at)',
  'SELECT 1'
);
PREPARE mar_scheduled_index_stmt FROM @mar_scheduled_index_sql;
EXECUTE mar_scheduled_index_stmt;
DEALLOCATE PREPARE mar_scheduled_index_stmt;

SELECT 'Nursing MAR scheduled-dose uniqueness guard is present.' AS result;
