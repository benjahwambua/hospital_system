# Theatre & Surgery Deployment and Acceptance Checklist

This checklist applies to the current Theatre & Surgery workflow in `theatre/index.php`. It is not a declaration of clinical approval or production readiness.

## Scope implemented in code

- Schedule a case for an existing patient with procedure, urgency, date/time and optional surgeon/anaesthesia assignments.
- Require individual Theatre View/Create/Edit permissions and CSRF validation.
- Retain a six-item pre-operative gate: consent, identity, site/side marking, allergy review, fasting status and anaesthesia review.
- Guard status transitions through Planned, Pre-op, In Theatre, Recovery, and Completed, with cancellation restricted to the early workflow.
- Require assigned active surgeon and anaesthesia accounts before entering theatre.
- Require intra-operative, anaesthesia and recovery/handover documentation plus a valid outcome before Recovery/Completed.
- Require an active inpatient admission before completing a case with the Admitted outcome.
- Preserve already-recorded clinical notes when a later status update submits blank note fields.
- Display unavailable metrics/lookup errors rather than silently presenting database failures as zero cases.

## Migration and permissions

1. Back up the target database and verify the backup.
2. Confirm the base HMS schema, patient table and access-control migration are installed.
3. Review `database/theatre_surgery_migration.sql` against the target schema; apply it once in a backed-up test environment.
4. Verify the `theatre_cases` table and `theatre` row in `access_modules`.
5. Explicitly assign Theatre View/Create/Edit/Approve rights to the intended users through Access Rights. The current page uses View, Create and Edit actions; do not infer permissions from a job title or role.
6. Confirm the Theatre link is visible only to users with module access, then test direct URL access for users without access.

## Functional acceptance tests

Mark each test Pass, Fail or Not run and record the tester, date and environment.

- Create a case with valid details; confirm a unique case number and Planned status.
- Reject missing patient, procedure, schedule, invalid urgency, invalid date/time and overlength notes.
- Confirm patient and assigned team lookups fail safely if their queries fail.
- Confirm a user without Create cannot schedule a case and a user without Edit cannot update a case.
- Try entering In Theatre with each safety confirmation missing; every attempt must be blocked.
- Confirm In Theatre requires both assigned accounts to exist and be active.
- Verify only these lifecycle transitions are accepted: Planned → Pre-op/Cancelled; Pre-op → Planned/In Theatre/Cancelled; In Theatre → Recovery; Recovery → Completed. Same-state saves may update documentation but must not bypass the gates.
- Confirm cancellation requires a reason and cannot be used after the case has entered theatre.
- Confirm Recovery and Completed require intra-operative notes, anaesthesia notes, recovery/handover notes and a valid outcome.
- Save operative and anaesthesia notes during one update, then submit a later status/documentation update with blank note fields; confirm saved notes are retained and cannot be silently erased.
- Select Admitted as the outcome; completion must fail without an active inpatient admission and succeed only after that admission exists.
- Confirm Completed and Cancelled cases cannot be edited through the normal workflow.
- Force a database lookup/metrics failure in a test environment and verify the page shows a clear unavailable message, not a false zero/empty state.
- Verify case status, notes, checklist flags, timestamps and audit events in the database after the workflow.

## Clinical gaps that must remain explicit

The current six-item gate is a first safety checkpoint, not a substitute for an approved WHO Surgical Safety Checklist or local theatre policy. Before production clinical use, the hospital must validate and complete the appropriate Sign In, Time Out and Sign Out checklist, operative report, anaesthetic chart, implant/device and lot traceability, blood product checks, antibiotic/time-out documentation where applicable, PACU/recovery observations, post-operative orders, consent documentation, clinical team sign-off, and the interfaces to admissions, billing, pharmacy/stores and reporting.

Do not mark this module production-ready based only on syntax checks or a successful code merge. Apply the migration in a test database and complete witnessed theatre/anaesthesia UAT first.
