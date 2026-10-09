-- Nursing MAR allergy-review accountability.
-- Apply after database/nursing_medication_administration_migration.sql.
-- Safe to re-run: each column is added only when missing. Existing MAR rows remain
-- NULL to distinguish historical records from an explicitly reviewed allergy status.
SET @db := DATABASE();

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='nursing_medication_administrations')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='nursing_medication_administrations' AND column_name='allergy_review_status'),
  'ALTER TABLE nursing_medication_administrations ADD COLUMN allergy_review_status VARCHAR(40) NULL AFTER administration_notes',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='nursing_medication_administrations')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='nursing_medication_administrations' AND column_name='allergy_reviewed_at'),
  'ALTER TABLE nursing_medication_administrations ADD COLUMN allergy_reviewed_at DATETIME NULL AFTER allergy_review_status',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='nursing_medication_administrations')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='nursing_medication_administrations' AND column_name='allergy_reviewed_by'),
  'ALTER TABLE nursing_medication_administrations ADD COLUMN allergy_reviewed_by INT NULL AFTER allergy_reviewed_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Nursing MAR allergy-review accountability columns are present.' AS result;
