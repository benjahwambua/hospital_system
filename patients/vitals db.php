-- Adds the missing spo2 column to the vitals table if it does not already exist.
-- Compatible with MySQL/MariaDB environments that may not support
-- `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` directly.

SET @schema_name := DATABASE();
SET @table_name := 'vitals';
SET @column_name := 'spo2';

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = @table_name
      AND COLUMN_NAME = @column_name
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE vitals ADD COLUMN spo2 VARCHAR(20) NULL AFTER pulse',
    'SELECT ''spo2 column already exists on vitals'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
