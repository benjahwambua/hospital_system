# HMS Gap Closure Progress

Last updated: 2026-10-09

These percentages are **engineering completion estimates**, not UAT certification. A module only reaches 100% after its workflow is implemented, authorized, audited, reported, error-handled and verified in UAT.

| Gap / workstream | Current completion | Current state | Remaining critical work |
|---|---:|---|---|
| Legacy file retirement / canonical workflows | 90% | Major obsolete wrappers retired | Final reference scan and UAT |
| Explicit per-user permissions | 90% | View/Create/Edit/Delete/Approve engine active; legacy role gates largely retired | Verify every existing user assignment; remove remaining compatibility assumptions |
| Insurance & SHA | 87% | Coverage, verification, preauthorization, claims, submission, remittances/reconciliation and an auditable denial/appeal register are implemented in code | Deeper eligibility/tariff validation, automated claim/payment reconciliation controls, target database migration rollout and UAT |
| Inpatient / Ward | 85% | Admission, bed allocation, occupancy, transactional/audited transfers, configurable ward/bed register, transfer history, daily-charge posting ledger and discharge-readiness confirmations are implemented in code | Apply/reconcile required migrations in the target database, validate ward/bed mapping and daily-charge tariffs, confirm discharge controls in UAT, and complete operational reporting |
| Nursing | 85% | Active inpatient nursing station, observations, notes, assigned/overdue tasks, approval-controlled care plans with overdue-review flags, distinct-shift handover validation with separate-user acknowledgement audit display, and prescription/dispense-linked MAR with scheduled-dose timestamps, duplicate-slot guard, and required allergy-review status/time/user accountability are implemented in code | A real medication-order schedule and due-dose queue, automated allergy/interaction decision support, staff roster and shift coverage, escalation/notifications, MAR migrations applied/reconciled in target DB, and UAT |
| Central Stores | 70% | Item/location register, movement ledger, multi-item requisitions, procurement GRN receipts, independent physical-count approval, lot-level count snapshots and variances, FEFO requisition issues, lot-aware transfers and signed batch/expiry on-hand balances | Reconcile legacy untracked stock and historic lot gaps, valuation, wider department integration, automated regression tests and UAT |
| Emergency / Casualty | 0% | Not implemented as a dedicated lifecycle | Triage, emergency encounter, acuity, treatment, disposition and billing |
| Theatre / Surgery | 0% | Not implemented | Theatre scheduling, pre-op, intra-op, implants, anesthesia, recovery and billing |
| Referral management | 30% | Orders/referrals foundation exists | Referral lifecycle, receiving facility/provider, status, attachments and closure |
| Diagnosis coding / ICD-10 | 0% | Not implemented | Diagnosis master, coding UI and encounter/claim linkage |
| Procedure coding | 0% | Not implemented | Procedure master, coding UI, tariff/claim linkage |
| Clinical documentation | 55% | Encounters, vitals, orders and departmental records exist | Structured assessment, diagnosis, plans, progress notes and templates |
| Department work queues | 60% | Lab/radiology/pharmacy queues exist | Unified queue model, SLA/priority controls and cross-department visibility |
| Enterprise audit history | 35% | Central audit function and audit calls exist | Search/filter UI, actor/action/entity filters, before/after values and export |
| Notifications / work queues | 0% | No central event-driven notification engine | Alerts, assignments, reminders, escalation and in-app notification centre |
| Management command centre | 50% | Dashboards and reports exist | Executive KPIs, operational drill-down and exception monitoring |
| Operational KPI analytics | 45% | Core reports exist | Bed occupancy, LOS, revenue cycle, lab TAT, pharmacy, claims and staffing KPIs |
| HR / staff master | 20% | User/staff identity foundations exist | Employee master, credentials, departments, licences and employment records |
| Staff rostering | 0% | Not implemented | Shift templates, rosters, coverage, swaps and attendance |
| Patient portal | 0% | Not implemented | Patient access, appointments, results, statements and secure messaging |
| SMS / Email | 0% | Not implemented | Provider abstraction, templates, consent, queues and delivery logs |
| Payment integrations | 45% | M-Pesa foundation and payment workflows exist | Harden callbacks, reconciliation, idempotency and broader provider abstraction |
| External healthcare integrations | 0% | Not implemented | Standards/API integrations and interface monitoring |
| Public/Internal API | 0% | Not implemented as a governed API | Authentication, authorization, versioning, audit and documentation |
| CI/CD | 0% | No workflow pipeline | Automated lint/security/tests/build/deploy gates |
| Docker / containerisation | 0% | Traditional XAMPP deployment | Container baseline if operationally useful |
| Automated deployment | 10% | Deployment documentation exists | Repeatable environment provisioning and release process |
| Database migration strategy | 45% | Multiple migrations exist | Ordered migration runner/versioning and environment tracking |
| Automated testing | 15% | Manual/UAT-oriented checks dominate | Unit, integration, workflow and security regression suites |
| Monitoring / alerting | 10% | Logging/error_log foundations exist | Central application health, DB, queue and integration monitoring |
| Disaster recovery | 20% | Backup/restore documentation baseline exists | Tested backup automation, restore drills, RPO/RTO and off-site recovery |

## Active priority sequence

1. Finish **Inpatient/Ward + Nursing**.
2. Continue **Central Stores**: complete lot-aware movement allocation, valuation, department integration and end-to-end verification.
3. Complete the remaining **Insurance/SHA controls**: denials, appeals, tariffs and stronger eligibility/claim validation.
4. Build **Emergency/Casualty**.
5. Build **Theatre/Surgery**.
6. Build **Referral management + structured diagnosis/procedure coding**.
7. Strengthen **audit history, notifications and command-centre analytics**.
8. Build **HR/staff master + rostering**.
9. Then move into **patient portal, messaging, integrations and API**.
10. Finish with **CI/CD, deployment, testing, monitoring and DR hardening**.

## Current overall assessment

The system has moved beyond a basic HMS prototype. The strongest areas are patient/reception, clinical departmental workflows, billing/cashier, procurement, permissions and the newly operational insurance revenue-cycle chain.

The Nursing access-control migration was corrected on 2026-10-09; the live database migration and Nursing UAT are still outstanding. Central Stores now supports multi-item requisitions, procurement GRN receipt posting, independent physical-count approval, lot-level count snapshots and variance postings, FEFO requisition issues, lot-aware transfers, signed batch/expiry on-hand balances, and stricter return/adjustment controls that require known lot identity or an explicit untracked-stock declaration. The stores dashboard now surfaces negative lot balances, expired on-hand stock, and positive untracked balances in a reconciliation queue. Apply `database/central_stores_lot_counts_migration.sql` before deploying the matching stores page. Live migrations, legacy lot reconciliation, and end-to-end stock reconciliation UAT remain outstanding.


## Central Stores release readiness note (2026-10-09)

A deployment/UAT runbook is available at `docs/CENTRAL_STORES_DEPLOYMENT_UAT.md`. It documents backup and restore rehearsal, schema/index preflight, one-time migration sequencing, post-migration checks, a permissions and stock-control UAT checklist, legacy-count treatment, and rollback safeguards. This is documentation only: it does not mean the migration has been applied or UAT has passed. Central Stores remains at 70% engineering completion pending database deployment, lot reconciliation, integration, and witnessed UAT.


## Nursing staff-linked task assignment (2026-10-09)\n\nNursing task creation now offers eligible nurse, doctor and admin user accounts as assignees and validates the selected user server-side. The task list shows the assignee and marks overdue open/in-progress tasks when their due time has passed. This uses the current `users` directory; a dedicated employee master, shift roster, notification/escalation and task claim/start lifecycle remain future work. No functional tests or UAT have been run.\n\n## Nursing shift handover expansion (2026-10-09)\n\n`database/nursing_shift_handover_migration.sql` adds an admission-linked handover register with outgoing/incoming shifts, care summary, outstanding follow-up, creator, pending/acknowledged status and acknowledgement audit fields. The Nursing Station now supports handover submission and a separate user with Nursing Approve permission acknowledging it; the original handover author cannot acknowledge their own record. The migration is not applied and no functional testing has been run. This is a structured handover record, not a replacement for local clinical escalation policy.\n\n## Inpatient daily charge expansion (2026-10-09)\n\n`database/inpatient_daily_charges_migration.sql` adds a daily-rate field to the ward master and an idempotency ledger keyed by admission/date. Ward configuration allows authorised users to set approved daily rates (default is zero; no tariff is assumed). `clinical/inpatient_charges.php` posts one manually triggered daily charge to the existing invoice/invoice-item workflow and stores the resulting invoice and line IDs. Finance Create permission is required; duplicate admission/date charges and future dates are blocked. The migration is not applied and no functional/database tests have been run. Automated recurring posting is deliberately deferred until scheduler/retry/monitoring support exists.\n\n## Inpatient / Ward configuration expansion (2026-10-09)\n\nThe new `database/inpatient_ward_bed_master_migration.sql` creates a configurable ward and bed register and idempotently bootstraps the five existing ward names with six beds each. Ward/IPD now reads active capacity from that register instead of hardcoded bed counts, and displays the latest 25 bed transfers. `clinical/ward_configuration.php` supports adding wards/beds and activating/deactivating beds; occupied beds cannot be deactivated. Apply the migration only after backing up the database and reconciling active admissions with ward/bed labels. This code has not been run against a live database or functionally tested.\n\n## Inpatient / Nursing expansion (2026-10-09)

The feature branch adds `database/inpatient_nursing_expansion_migration.sql` for bed-transfer history, assigned nursing tasks and structured care plans. Ward/IPD now supports a permission-gated, CSRF-protected transfer action that checks the destination bed, updates the active admission in a transaction, records the old/new ward and bed, and writes an audit event. The Nursing Station adds task creation, priority/due-time capture, task completion notes and structured care-plan capture linked to an active admission.

These are implementation estimates only. Apply the new migration after `database/clinical_ipd_migration.sql` and `database/nursing_migration.sql` in a backed-up test database before enabling the features. No database execution or clinical UAT has been performed. Medication administration remains deliberately unimplemented until it can be linked safely to an actual prescription/medication order and its dispense/administration records.


## Inpatient discharge readiness expansion (2026-10-09)

Ward/IPD discharge now requires explicit confirmation that inpatient financial status has been reviewed and medication reconciliation/discharge instructions have been reviewed, in addition to the existing required diagnosis and clinical notes. The migration `database/inpatient_discharge_readiness_migration.sql` stores both confirmation flags plus the confirming user and timestamp on the admission. These are accountable user confirmations only; they do not automatically reconcile invoices, prescriptions, or pharmacy dispensing. Apply after `database/clinical_ipd_migration.sql` in a backed-up environment. No functional/UAT testing has been performed.

## Nursing medication administration record (2026-10-09)

PR #53 adds `nursing/medication_administration.php`, a nursing-station link, and `database/nursing_medication_administration_migration.sql`. It records a nursing outcome against a completed pharmacy dispense and existing prescription, including outcome, dose, route, notes, user and time. It does not schedule doses, validate allergies/interactions, create prescriptions, or deduct stock. The first PHP Syntax Audit found a parse error in the history table; the rendering block was corrected, the subsequent full PHP Syntax Audit passed, and PR #53 was merged. The migration has **not** been applied to any database because no live/local database connection is available through this session. No functional tests or UAT have been run.

## Nursing handover and care-plan review controls (2026-10-09)

The Nursing Station now rejects shift handovers where the outgoing and incoming shifts are identical, and clearly flags active care plans whose configured review time has passed. These are workflow guardrails, not automated clinical escalation; review notifications, staff roster/coverage and UAT remain outstanding. No functional tests have been run.

## Inpatient progress reconciliation (2026-10-09)

The progress table now reflects the merged ward/bed master, inpatient daily-charge ledger and discharge-readiness confirmation work rather than listing daily charges and stronger discharge controls as unbuilt. These capabilities are implemented in code, but their database migrations still require controlled application to the target environment and subsequent UAT. Daily charge rates must be reviewed and configured by the hospital; the implementation does not assume a tariff. Engineering estimate for Inpatient/Ward: 85%, not production certification.

## Nursing handover acknowledgement visibility (2026-10-09)

The Nursing Station now displays the recorded acknowledgement timestamp and acknowledging user ID for acknowledged shift handovers, alongside any acknowledgement notes. This improves traceability in the screen but does not replace the broader searchable enterprise audit history. No functional tests or UAT have been run.

## Nursing progress rate reconciliation (2026-10-09)

Nursing is now estimated at 84% engineering completion after adding an optional scheduled-dose timestamp to the MAR and a duplicate prescription/time-slot guard in the application plus a database uniqueness migration. This records the scheduled time for an administration outcome; it does not create a medication order, infer frequency from prescription text, or provide a due-dose queue. A proper order schedule, allergy/interaction safety controls, staffing/roster integration, notifications, target database migration rollout and UAT remain outstanding. This is not a production-readiness certification.

## Shared dashboard-aligned styling (2026-10-09)

Added shared `hms-module-*` workspace styles to `assets/css/style.css`, based on the Hospital Command Centre dashboard's blue-to-teal hero, rounded panels, responsive spacing and restrained table styling. Applied the visual standard to the Insurance & SHA landing page, patient coverage, claims, preauthorizations and remittances, plus ward/IPD management, ward/bed configuration, inpatient daily charges, the Nursing Station and nursing MAR. This is a presentation consistency change only; it does not change workflow permissions or data semantics. PHP Syntax Audit and visual review in a running browser are still required; no functional tests or UAT were run.

## Nursing MAR scheduled-dose accountability (2026-10-09)

The MAR now accepts an optional scheduled dose time, displays it separately from the actual administered/recorded time, and blocks a second outcome for the same prescription and scheduled slot when a slot is supplied. A new idempotent migration adds a unique key on `(prescription_id, scheduled_at)`; existing unscheduled records remain allowed. The scheduled-time field is an accountability marker only: it does not create or alter a prescription or infer a dose schedule from `frequency`. Apply `database/nursing_mar_scheduled_dose_migration.sql` after the base MAR migration, first checking for duplicate non-null prescription/time pairs. No database migration was applied and no functional tests/UAT were run. The MAR continues to use the shared Hospital Command Centre dashboard styling.

## Nursing MAR allergy-review accountability (2026-10-09)

The MAR now surfaces the patient's structured `patient_allergies` entries and legacy `patients.allergies` text before the administration form. A missing allergy entry is explicitly not treated as proof of no known allergies. Each new MAR outcome requires a recorded choice of “Allergy information reviewed” or “Unable to verify allergy status”; the selection, reviewing user and timestamp are stored and shown in the history, and the audit event includes the review status. Migration: `database/nursing_mar_allergy_review_migration.sql`, applied after the base MAR migration (and after the scheduled-dose migration where both are being installed). This is a human-review accountability control, not automatic allergy matching, interaction checking or a medicine-safety decision engine. Historical MAR records remain NULL/unreviewed rather than being falsely marked reviewed. No functional tests, database migration or UAT have been run.

## Insurance denials and appeals workflow (2026-10-09)

Added `insurance/denials_appeals.php`, linked from the Insurance & SHA sidebar and dashboard, using the shared Hospital Command Centre visual standard. The page records claim-level or claim-item-level denials with category, code, reason, amount and notes; validates that an item belongs to the selected claim and caps the denied amount at the item/claim gross amount; supports approval-permission-controlled appeal submission and resolution; and audits record, submit and resolve events. Resolution records do not automatically change claim totals, invoice balances or remittance allocations. Migration `database/insurance_denial_appeals_migration.sql` adds appeal reference/timestamps/notes, resolution outcome/notes/time and actor fields idempotently. It must be applied after `database/insurance_migration.sql`. No live migration, functional testing or UAT has been run. Insurance & SHA estimate updated to 87% implementation completion, not production certification.

## Shared module shell consistency audit (2026-10-09)

Audited the primary module landing pages and found two shared-shell exceptions: Central Stores rendered a standalone document and only included the sidebar; Insurance & SHA included the shared header and sidebar but omitted the shared footer. Central Stores now uses `includes/header.php`, `includes/sidebar.php` and `includes/footer.php`; its stock workflow panels remain in place and the extra left margin was removed to prevent double indentation. Insurance & SHA now closes through the shared footer. The common topbar/sidebar alignment was corrected so the sidebar begins below the fixed topbar, the sidebar brand matches “Emaqure Medical Centre”, and the fixed footer begins at the sidebar edge on desktop and spans the viewport on small screens. This is a shell/layout consistency fix, not proof of browser-level visual QA. Printable reports, receipts, login and other standalone documents intentionally retain their own document layouts. PHP Syntax Audit is required; no live-browser review or functional UAT has been performed.
