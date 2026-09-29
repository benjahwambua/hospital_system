-- HMS maternity routing backfill
-- Ensures every existing ANC/PNC/Maternity patient has a maternity record.
-- Safe to run repeatedly because existing patient_id records are skipped.

INSERT INTO maternity (patient_id, anc_number, created_at)
SELECT p.id,
       CONCAT('ANC-', YEAR(CURDATE()), '-', LPAD(p.id, 4, '0')),
       NOW()
FROM patients p
LEFT JOIN maternity m ON m.patient_id = p.id
WHERE m.id IS NULL
  AND p.clinic_category IN ('ANC','PNC','Maternity');

-- Verification:
-- SELECT p.id,p.patient_number,p.full_name,p.clinic_category,m.id AS maternity_id,m.anc_number
-- FROM patients p
-- LEFT JOIN maternity m ON m.patient_id=p.id
-- WHERE p.clinic_category IN ('ANC','PNC','Maternity')
-- ORDER BY p.id DESC;
