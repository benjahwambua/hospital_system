-- HMS release schema preflight (read-only; compatible with MariaDB 10.4).
-- Run this in phpMyAdmin against the intended TEST database first.
-- It makes no schema/data changes. "MISSING" means inspect the corresponding
-- migration and its prerequisites; it is not permission to run every migration.
SELECT DATABASE() AS inspected_database, VERSION() AS database_version;

SELECT expected.object_name,
       CASE WHEN t.TABLE_NAME IS NULL THEN 'MISSING' ELSE 'PRESENT' END AS status
FROM (
    SELECT 'theatre_cases' AS object_name
    UNION ALL SELECT 'admissions'
    UNION ALL SELECT 'hr_staff'
    UNION ALL SELECT 'hr_staff_credentials'
    UNION ALL SELECT 'hr_staff_contracts'
    UNION ALL SELECT 'hr_staff_roster'
    UNION ALL SELECT 'hr_staff_separations'
    UNION ALL SELECT 'emergency_cases'
    UNION ALL SELECT 'emergency_observations'
    UNION ALL SELECT 'theatre_recovery_observations'
    UNION ALL SELECT 'theatre_implant_usage'
    UNION ALL SELECT 'theatre_safety_checklist_phases'
    UNION ALL SELECT 'theatre_case_service_charges'
    UNION ALL SELECT 'theatre_case_stock_issues'
    UNION ALL SELECT 'theatre_case_admissions'
    UNION ALL SELECT 'maternity_partograph_observations'
    UNION ALL SELECT 'stores_locations'
    UNION ALL SELECT 'stores_items'
    UNION ALL SELECT 'stores_movements'
    UNION ALL SELECT 'invoices'
    UNION ALL SELECT 'invoice_items'
    UNION ALL SELECT 'patient_services'
    UNION ALL SELECT 'user_module_access'
    UNION ALL SELECT 'access_modules'
) AS expected
LEFT JOIN information_schema.TABLES AS t
       ON t.TABLE_SCHEMA = DATABASE()
      AND t.TABLE_NAME = expected.object_name
ORDER BY expected.object_name;

-- Column-level checks for a known duplicate-column hazard in the Emergency
-- handover migration and the Theatre case base schema.
SELECT expected.table_name,
       expected.column_name,
       CASE WHEN c.COLUMN_NAME IS NULL THEN 'MISSING' ELSE 'PRESENT' END AS status
FROM (
    SELECT 'emergency_cases' AS table_name, 'disposition_destination' AS column_name
    UNION ALL SELECT 'emergency_cases', 'receiving_contact'
    UNION ALL SELECT 'theatre_cases', 'visit_id'
    UNION ALL SELECT 'theatre_cases', 'status'
    UNION ALL SELECT 'theatre_cases', 'surgeon_id'
    UNION ALL SELECT 'theatre_cases', 'anesthetist_id'
    UNION ALL SELECT 'theatre_cases', 'consent_confirmed'
    UNION ALL SELECT 'theatre_cases', 'site_marked'
    UNION ALL SELECT 'theatre_cases', 'anesthesia_reviewed'
) AS expected
LEFT JOIN information_schema.COLUMNS AS c
       ON c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = expected.table_name
      AND c.COLUMN_NAME = expected.column_name
ORDER BY expected.table_name, expected.column_name;
