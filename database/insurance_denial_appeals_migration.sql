-- Insurance denial and appeal workflow metadata.
-- Apply after database/insurance_migration.sql.
-- Existing denial records remain valid; added appeal/resolution fields are nullable.
SET @db := DATABASE();

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='claim_denials')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='claim_denials' AND column_name='appeal_reference'),
  'ALTER TABLE claim_denials ADD COLUMN appeal_reference VARCHAR(100) NULL AFTER notes',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='claim_denials')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='claim_denials' AND column_name='appeal_submitted_at'),
  'ALTER TABLE claim_denials ADD COLUMN appeal_submitted_at DATETIME NULL AFTER appeal_reference',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='claim_denials')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='claim_denials' AND column_name='appeal_notes'),
  'ALTER TABLE claim_denials ADD COLUMN appeal_notes TEXT NULL AFTER appeal_submitted_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='claim_denials')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='claim_denials' AND column_name='resolution_outcome'),
  'ALTER TABLE claim_denials ADD COLUMN resolution_outcome ENUM(''Approved'',''Partially Approved'',''Upheld'',''Written Off'') NULL AFTER appeal_notes',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='claim_denials')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='claim_denials' AND column_name='resolution_notes'),
  'ALTER TABLE claim_denials ADD COLUMN resolution_notes TEXT NULL AFTER resolution_outcome',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='claim_denials')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='claim_denials' AND column_name='resolved_at'),
  'ALTER TABLE claim_denials ADD COLUMN resolved_at DATETIME NULL AFTER resolution_notes',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='claim_denials')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='claim_denials' AND column_name='created_by'),
  'ALTER TABLE claim_denials ADD COLUMN created_by INT NULL AFTER resolved_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='claim_denials')
  AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='claim_denials' AND column_name='updated_by'),
  'ALTER TABLE claim_denials ADD COLUMN updated_by INT NULL AFTER created_by',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Insurance denial and appeal workflow columns are present.' AS result;
