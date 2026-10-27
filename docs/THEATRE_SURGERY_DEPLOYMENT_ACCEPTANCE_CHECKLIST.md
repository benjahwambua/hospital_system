# Theatre & Surgery Deployment and Acceptance Checklist

This checklist applies to the current Theatre & Surgery workflow in `theatre/index.php`. It is not a declaration of clinical approval or production readiness.

## Scope implemented in code

- Schedule a case for an existing patient with procedure, urgency, date/time and optional surgeon/anaesthesia assignments.
- Require individual Theatre View/Create/Edit permissions and CSRF validation.
- Retain a six-item pre-operative gate: consent, identity, site/side marking, allergy review, fasting status and anaesthesia review.
- Guard status transitions through Planned, Pre-op, In Theatre, Recovery, and Completed, with cancellation restricted to the early workflow.
- Require two distinct assigned active user accounts as surgeon and anaesthesia provider before entering theatre, with each assigned user linked to an Active HR staff record and an Active Professional Licence credential with a registration number and no expired expiry date. Regulator authenticity and scope of practice still require clinical-governance verification.
- Require intra-operative, anaesthesia and recovery/handover documentation plus a valid outcome before Recovery/Completed.
- Require an active inpatient admission before completing a case with the Admitted outcome.
- Preserve already-recorded clinical notes when a later status update submits blank note fields.
- Display unavailable metrics/lookup errors rather than silently presenting database failures as zero cases.

## Migration and permissions

1. Back up the target database and verify the backup.
2. Confirm the base HMS schema, patient table and access-control migration are installed.
3. Review `database/theatre_surgery_migration.sql` against the target schema; apply it once in a backed-up test environment.
4. Verify the `theatre_cases` table and `theatre` row in `access_modules`.
5. Explicitly assign Theatre View/Create/Edit rights to the intended users through Access Rights. The current page enforces View, Create and Edit; it does not implement a separate approval action, so do not treat Approve permission as an implemented Theatre workflow. Do not infer permissions from a job title or role.
6. Confirm the Theatre link is visible only to users with module access, then test direct URL access for users without access.

## Functional acceptance tests

Mark each test Pass, Fail or Not run and record the tester, date and environment.

- Create a case with valid details; confirm a unique case number and Planned status.
- Reject missing patient, procedure, schedule, invalid urgency, invalid date/time and overlength notes.
- Confirm patient and assigned team lookups fail safely if their queries fail.
- Confirm a user without Create cannot schedule a case and a user without Edit cannot update a case.
- Try entering In Theatre with each safety confirmation missing; every attempt must be blocked.
- Confirm In Theatre requires two distinct assigned accounts to exist and be active, and confirm each assigned clinician has an Active HR staff record plus an Active, unexpired Professional Licence with a registration number. Clinical governance must still verify the regulator/source and scope of practice; database fields alone do not authenticate a licence.
- Before theatre starts, check each safety item, save, reopen, explicitly uncheck one item, and save again; confirm the revocation is persisted and the case cannot transition into In Theatre until all six items are re-confirmed.
- After a case enters In Theatre or Recovery, attempt to change a pre-operative checklist flag; confirm the application rejects the edit so the historical pre-operative record is preserved.
- Verify only these lifecycle transitions are accepted: Planned → Pre-op/Cancelled; Pre-op → Planned/In Theatre/Cancelled; In Theatre → Recovery; Recovery → Completed. Same-state saves may update documentation but must not bypass the gates.
- Confirm cancellation requires a reason and cannot be used after the case has entered theatre.
- Confirm Recovery and Completed require intra-operative notes, anaesthesia notes, recovery/handover notes and a valid outcome.
- Save operative and anaesthesia notes during one update, then submit a later status/documentation update with blank note fields; confirm saved notes are retained and cannot be silently erased.
- Select Admitted as the outcome; completion must fail without an active inpatient admission and succeed only after that admission exists.
- Confirm Completed and Cancelled cases cannot be edited through the normal workflow.
- Force a database lookup/metrics failure in a test environment and verify the page shows a clear unavailable message, not a false zero/empty state.
- Verify case status, notes, checklist flags, timestamps and audit events in the database after the workflow.

## Clinical gaps that must remain explicit

The current six-item gate is a first safety checkpoint, not a substitute for an approved WHO Surgical Safety Checklist or local theatre policy. The application currently validates active user accounts, not professional qualifications or licence status; a named clinician and clinical-governance process must verify those before assigning staff to a live case. Before production clinical use, the hospital must validate and complete the appropriate Sign In, Time Out and Sign Out checklist, operative report, anaesthetic chart, implant/device and lot traceability, blood product checks, antibiotic/time-out documentation where applicable, PACU/recovery observations, post-operative orders, consent documentation, clinical team sign-off, and the interfaces to admissions, billing, pharmacy/stores and reporting.

Do not mark this module production-ready based only on syntax checks or a successful code merge. Apply the migration in a test database and complete witnessed theatre/anaesthesia UAT first.


## Theatre team credential validation

- [ ] When scheduling a case with an assigned surgeon or anaesthesia provider, verify that the user is linked to an Active HR staff record and has an Active `Professional Licence` credential with a registration number and no expired expiry date.
- [ ] Confirm an expired, suspended, revoked, unlinked or missing licence blocks the assignment with a clear message.
- [ ] Confirm a missing HR staff/credential schema fails closed for assigned clinicians; unassigned cases can still be scheduled for later credential verification.
- [ ] Confirm the HR staff master and credential migrations are applied before operational rollout; PHP lint alone does not prove this workflow against the live database.


## PACU and implant/device records

1. Review `database/theatre_clinical_records_migration.sql` against the target schema and apply it only after a verified backup in a test environment.
2. Confirm the Theatre page links to `theatre/clinical_records.php` and users without Theatre View cannot access the records page directly.
3. Confirm PACU observations can be recorded only while the case is in Recovery and require Theatre Edit permission.
4. Validate accepted ranges and rejection of invalid heart rate, respiratory rate, blood pressure, oxygen saturation, temperature and pain-score values.
5. Confirm observation history is retained and displayed in timestamp order without overwriting earlier observations.
6. Confirm implant/device records require an item name and at least a lot/batch or serial number; verify manufacturer, model, expiry, quantity, site and notes persist.
7. Confirm device records are available for In Theatre, Recovery and Completed cases, and cannot be added to Planned or Pre-op cases.
8. Verify foreign keys prevent orphan clinical records and the audit log records successful creates.
9. Mark each test Pass, Fail or Not run with tester, date and environment. PHP lint and merge status do not establish live database/browser acceptance.

The new PACU observation form is a supplemental structured record and is not a substitute for a clinically approved observation frequency, escalation thresholds, discharge criteria or a locally approved PACU chart. The hospital's clinical leadership must approve the fields and workflow before live clinical use.


## Phased surgical safety checklist

- Apply `database/theatre_safety_checklist_migration.sql` after the Theatre/Surgery base migration.
- Open **Phased Safety Checklist** from the theatre case list and complete Sign In, Time Out and Sign Out at the appropriate clinical workflow points.
- Each item must be recorded as Confirmed or Not applicable; completed phases are locked and audited.
- The application blocks transition to **In Theatre** until Sign In and Time Out are complete, and blocks transition to **Recovery** until Sign Out is complete. The legacy six-item pre-operative safety gate remains in force.
- Validate wording and phase timing with the hospital's surgical, anaesthesia and nursing leads before production; this implementation is not a substitute for local policy or clinical judgement.
- Apply the migration and test on a backed-up non-production database first. Browser UAT remains required.


## Theatre procedure billing integration

- Apply `database/theatre_billing_migration.sql` after the base Theatre/Surgery migration and the billing/service catalogue migrations.
- After the case reaches Recovery or Completed, open **Procedure Billing**, select an active billable service in the Procedure category, and confirm the correct payer/plan tariff.
- The charge is linked to the case, patient service, invoice and invoice item. The database unique key and transaction-level case lock block duplicate case/service charges.
- Confirm the charge appears exactly once in the patient's invoice and accounting journal. Do not use this screen to charge unapproved services or invent tariffs.
- Test with a backed-up non-production database; browser UAT and finance approval remain required.


## Theatre consumables and Central Stores integration

- Apply `database/theatre_stock_usage_migration.sql` after the base Theatre/Surgery and Central Stores migrations.
- From a case in Pre-op, In Theatre, Recovery or Completed, open **Theatre Stock Usage** and select an active item, issuing location, quantity and batch/expiry where tracked.
- The workflow checks the exact lot balance, blocks expired stock and over-issues, writes an **Issue** movement into `stores_movements`, and links it to the theatre case.
- The stock issue requires both Theatre edit and Central Stores edit permissions. Validate item balances and batch records with stores staff in a backed-up test database; browser UAT remains required.


## PACU completion gate

- A theatre case cannot move from Recovery to Completed until at least one structured PACU observation has been recorded during Recovery.
- Validate the observation chart and completion gate with theatre and anaesthesia staff. A recorded observation does not itself establish clinical stability or replace clinician judgement.


- Confirm multiple stock issues for the same item and lot can be recorded as separate Central Stores movements against one theatre case, and that each movement remains traceable.
- Confirm no additional stock issue is permitted after a case reaches Completed or Cancelled.
