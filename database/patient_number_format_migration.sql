-- Restore registered patient numbers to the EMC#### format, e.g. EMC0011.
-- The numeric patient ID remains the source of truth, so this is deterministic and unique.
UPDATE patients
SET patient_number = CONCAT('EMC', LPAD(id, 4, '0'))
WHERE COALESCE(is_walkin, 0) = 0
  AND id > 0;

-- Normalize existing walk-in records to the WLK#### format, e.g. WLK0011.
-- The numeric patient ID remains the source of truth.
UPDATE patients
SET patient_number = CONCAT('WLK', LPAD(id, 4, '0'))
WHERE COALESCE(is_walkin, 0) = 1
  AND id > 0;
