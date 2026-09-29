-- Restore registered patient numbers to the EMC#### format, e.g. EMC0011.
-- The numeric patient ID remains the source of truth, so this is deterministic and unique.
UPDATE patients
SET patient_number = CONCAT('EMC', LPAD(id, 4, '0'))
WHERE COALESCE(is_walkin, 0) = 0
  AND id > 0;
