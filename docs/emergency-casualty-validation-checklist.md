# Emergency / Casualty deployment and acceptance checklist

Use this checklist to verify Emergency/Casualty against the target HMS database before treating the module as production-ready. Run SQL against a backup or test database first. These checks do not replace clinical sign-off.

## 1. Confirm prerequisites

Confirm the target database is the intended HMS database and that the existing `patients`, `users`, `appointments`, `vitals`, `patient_services`, and `invoices` tables are present.

Apply the existing schema/access-control prerequisites only if they have not already been applied. The visits migration is a one-time migration and contains `ALTER TABLE` statements; do not rerun it blindly on a database where those columns/indexes already exist.

Expected dependency order for a fresh setup:

1. Existing HMS base schema (including patients and users).
2. `database/access_control_migration.sql`.
3. `database/visits_migration.sql`.
4. `database/emergency_casualty_migration.sql`.
5. `database/emergency_observations_migration.sql`.
6. `database/emergency_transfer_handover_migration.sql`.

The transfer-handover migration uses `ALTER TABLE ... ADD COLUMN` and is intended to be applied once. Back up the database before applying schema changes.

## 2. Read-only schema verification

Run these checks after migrations. A missing row or column means the database is not ready for the workflow tests.

```sql
SHOW TABLES LIKE 'visits';
SHOW TABLES LIKE 'emergency_cases';
SHOW TABLES LIKE 'emergency_observations';

SHOW COLUMNS FROM visits;
SHOW COLUMNS FROM emergency_cases;
SHOW COLUMNS FROM emergency_observations;

SELECT module_key, module_name, active
FROM access_modules
WHERE module_key = 'emergency';

SELECT COUNT(*) AS emergency_cases_without_matching_visit
FROM emergency_cases e
LEFT JOIN visits v
  ON v.id = e.visit_id
 AND v.patient_id = e.patient_id
 AND v.visit_type = 'Emergency'
 AND v.clinic_category = 'Emergency/Casualty'
WHERE v.id IS NULL;

SELECT COUNT(*) AS emergency_cases_missing_transfer_destination
FROM emergency_cases
WHERE status = 'Transferred'
  AND (disposition_destination IS NULL OR TRIM(disposition_destination) = '');
```

Both count queries should return zero for a clean dataset. Review any existing transferred records with missing destinations as data-quality exceptions rather than silently rewriting clinical records.

## 3. Manual workflow acceptance tests

Use test patients and test data only. Record the result and any error message for each test.

- [ ] Register an emergency case with valid intake details; confirm both the case and linked Emergency/Casualty visit are created.
- [ ] Reject intake with no patient, no chief complaint, invalid triage level/arrival mode, out-of-range vital signs, decimal values for whole-number fields, or only one blood-pressure value.
- [ ] Move a case from Waiting to Under Assessment, then Treatment; confirm the linked visit remains In Progress.
- [ ] Confirm invalid state jumps are rejected, including Waiting directly to Treatment and any update to a closed case.
- [ ] Discharge a case with disposition notes; confirm the case has a closure timestamp and the linked visit is Completed.
- [ ] Confirm discharge without disposition notes is rejected.
- [ ] Transfer a case with destination and handover notes; confirm the destination/contact are saved, the case is closed, and the linked visit is Completed.
- [ ] Confirm transfer without a destination is rejected.
- [ ] Record repeat observations on an open case; confirm the row appears in newest-first history with the correct recorder.
- [ ] Confirm repeat observations are rejected for Admitted, Discharged, Transferred, Left Before Assessment, and Cancelled cases.
- [ ] Admit an open Emergency/Casualty case to an active bed; confirm the admission is created, the emergency case becomes Admitted with a closure timestamp, and the original Emergency/Casualty visit becomes Completed.
- [ ] Confirm a failed admission rolls back its admission/visit changes and leaves the emergency case open.
- [ ] Confirm users without Emergency create/edit rights cannot perform those actions, while authorized users can view the queue.
- [ ] Confirm a database/query failure is shown as unavailable rather than incorrectly presented as an empty queue.

## 4. Evidence required before sign-off

- [ ] PHP syntax checks pass for `emergency/index.php`, `emergency/observations.php`, and `clinical/admit_patient.php`.
- [ ] The schema checks above pass on the target database.
- [ ] The manual workflow tests are recorded with outcomes.
- [ ] A clinician/operations owner reviews the disposition and transfer workflow.

Do not mark the module production-ready based on source review alone. This repository checklist does not mean migrations or runtime tests have already been executed.
