# HMS Capability Gap Audit & Product Roadmap

## Purpose

This is the source-based product gap audit for Hospitalis HMS. It compares the current repository and documented workflows with the capabilities expected from a serious small-to-mid-sized hospital information system. It is not a claim that every workflow is UAT-complete.

Status:
- **Strong** — coherent capability worth protecting.
- **Adequate** — capability exists but needs strengthening.
- **Partial** — important pieces exist but the end-to-end workflow is incomplete.
- **Missing** — no meaningful operational implementation found.
- **Planned** — deliberately deferred.

## Executive assessment

The current HMS has a solid core and should **not be restarted**.

Strong foundations include patient registration and identity, encounter-oriented workflows, clinical orders, laboratory, radiology, pharmacy, maternity, inpatient admission/ward/bed handling, procurement, billing/cashier/payments/reconciliation, M-Pesa foundations, per-user permissions, audit logging foundations, security controls and documentation.

The major gaps are **depth, integration and control**, not the number of screens.

Highest-priority gaps:
1. Operational SHA/insurance claims workflow
2. Central hospital stores/inventory
3. Stronger inpatient/ward/nursing workflow
4. Emergency/casualty
5. Theatre/operating room
6. Referral management
7. Structured diagnosis/procedure coding
8. Enterprise audit history
9. Notifications/work queues
10. Management analytics
11. Staff/credentials/rostering
12. Patient-facing portal and integrations

## Capability matrix

| Capability | Status | Assessment |
|---|---|---|
| Patient registration | Strong | Retain and harden |
| Patient identity / numbering | Strong | EMC / WLK conventions established |
| Patient dashboard | Strong | Correct architectural centre |
| Encounters / visits | Adequate | Continue standardisation |
| Appointments | Strong | Operational workflow exists |
| Reception | Strong | Good foundation |
| Clinical assessment | Adequate | Needs richer structured documentation |
| Orders | Strong | Lab/radiology/pharmacy ordering exists |
| Laboratory | Strong | Requests, results and inventory exist |
| Radiology | Strong | Requests and results exist |
| Pharmacy dispensing | Strong | Queue and dispensing workflow exists |
| Pharmacy inventory | Adequate | Stock movement exists; needs enterprise integration |
| Maternity | Strong | ANC, labour, PNC, deliveries and admissions exist |
| Inpatient admission | Adequate | Admission and active stay records exist; transfer is now transactional and auditable |
| Ward/bed management | Adequate | Occupancy and discharge, configurable ward/bed master, active-bed capacity, transfer history and manual duplicate-protected daily ward charges linked to canonical invoices exist; discharge readiness and end-to-end financial reconciliation require UAT |
| Nursing | Partial | Nursing station supports observations, notes, tasks, structured care plans with approval-controlled closure, structured shift handover with separate-user acknowledgement, and a prescription/dispense-linked MAR with scheduled-dose timestamps, duplicate-slot protection, and required allergy-review status/user/time accountability; real order scheduling, automated allergy/interaction checks, staff roster integration, overdue escalation and UAT remain |
| Emergency / casualty | First implementation | Emergency intake, linked visit, acuity triage, initial observations, active queue, clinician assignment and disposition workflow implemented; resuscitation protocols, repeat observations, alerts, transfers/bed integration and UAT remain |
| Theatre / surgery | First implementation | `theatre/index.php` supports case scheduling, a six-item pre-operative safety gate, lifecycle status control, intra-operative/anaesthesia notes and recovery outcome capture; full surgical safety checklist, detailed operative/anaesthesia records, implants/lot traceability, PACU observations, billing/stock integration and UAT remain |
| Referrals | Partial | Orders/referrals area exists; dedicated lifecycle is not established |
| Diagnosis coding / ICD-10 | Missing | Structured coding needed |
| Procedure coding | Missing | Structured procedure coding needed |
| Insurance/SHA data model | Partial | Strong schema foundation exists in insurance_migration.sql |
| Insurance/SHA operational UI | Partial | Coverage, preauthorizations, claims, remittances and a denial/appeal register exist in code; deeper eligibility/tariff rules, denial/appeal UAT and reconciliation controls remain |
| Claims | Partial | Schema exists; end-to-end workflow missing |
| Preauthorization | Partial | Schema exists; operational workflow missing |
| Remittance/reconciliation | Partial | Schema exists; payer remittance workflow missing |
| Central stores | Partial | Item/location master, multi-item requisitions, GRN receipts, independent lot-level physical counts/variance approval, FEFO requisition issues, lot-aware transfers and signed batch/expiry on-hand balance reporting exist. Return/adjustment controls now require a known lot or an explicit untracked-stock declaration, stock decreases validate the exact lot balance, and a dashboard queue flags negative lots, expired stock on hand and positive untracked balances. Reconcile those exceptions, complete valuation, wider integration and UAT remain |
| Stock requisitions | Partial | Department requisitions support up to four items per request with approval and issue; partial issue controls, counts and department-level integration remain |
| Batch/expiry control | Partial | Pharmacy/lab support exists; needs unified model |
| Procurement | Strong | PO, receiving, suppliers and payables exist |
| Supplier management | Strong | Keep and extend |
| Billing/invoicing | Strong | Core financial chain exists |
| Cashier | Strong | Shift and payment workflows exist |
| M-Pesa payments | Adequate | Integration foundation exists; reconciliation should be hardened |
| Financial reconciliation | Adequate | Exists; needs deeper bank/M-Pesa/insurer reconciliation |
| Refunds/reversals | Adequate | Controlled workflow and audit calls exist |
| Aged receivables | Strong | Important management control |
| Accounting ledger | Adequate | Exists; continue canonical-link cleanup |
| Financial approvals | Adequate | Permission model supports approval |
| Audit logging | Adequate | Central audit function exists |
| Audit history UI | Partial | Needs searchable actor/before-after history |
| Notifications | Missing | Event-driven alerts and work queues needed |
| Staff leave | Adequate | Correctly separate from Administration |
| HR / staff master | Partial | Employee directory, professional credentials, contract register and offboarding checklist implemented; protected document storage, payroll, rostering integration and UAT remain |
| Staff rostering | Missing | Needed for wards, nursing, theatre and shifts |
| Management dashboard | Adequate | Should evolve into a hospital command centre |
| Operational KPIs | Partial | Reporting exists; KPI depth needs expansion |
| Patient portal | Missing | Later-phase capability |
| SMS / email | Missing | Later-phase integration |
| Public/API integrations | Missing | Later-phase capability |
| Backup/restore | Adequate | Documented baseline exists |
| Production configuration | Adequate | Environment configuration foundation exists |
| CI/CD | Missing | Engineering maturity item, not a core functional gap |
| Docker | Missing | Useful later; not a functional HMS gap |
| Automated deployment | Missing | Important operational maturity item |
| Explicit per-user permissions | Partial | Engine exists; legacy fallback must be retired after migration |
| Role compatibility checks | Adequate | Should be systematically reduced |

## What should be protected

### Patient-centric architecture

Keep the Patient Dashboard as the centre of the application:

**Patient → Encounter → Clinical → Orders → Departmental processing → Billing → Payment/Insurance → Medical history**

Do not replace this with disconnected departmental mini-systems.

### Consolidated workflows

The retirement of legacy wrappers is the correct direction. Future development should prefer one canonical workflow rather than another compatibility page.

### Permission architecture

The View/Create/Edit/Delete/Approve model is strong. Legacy role fallback is migration debt and should be removed after explicit user assignments are verified.

### Financial controls

Billing, cashier, payment, M-Pesa, refunds, reconciliation and aged receivables form a valuable foundation. Continue using transactions, approval controls, idempotency and audit records.

## Priority roadmap

### Phase 0 — Stabilise the foundation

- Complete obsolete-file retirement.
- Eliminate duplicate/legacy endpoints and canonical-link inconsistencies.
- Complete explicit user permission assignments.
- Reduce require_role dependence where module permissions are authoritative.
- Verify database migrations and critical workflows in UAT.
- Strengthen audit history visibility.
- Standardise shared UI and navigation.

### Phase 1 — Hospital enterprise core

1. **Insurance & SHA** — payers, plans, patient coverage, eligibility, tariffs, preauthorization, claims, denials, appeals, remittances and payer reconciliation.
2. **Central Stores** — reconcile legacy untracked stock and historical lot gaps; complete batch-level valuation from GRN receipt through departmental issue; verify active-record guards, concurrent stock locking, returns, adjustments, counts and FEFO issue controls in UAT.
3. **Inpatient/Ward expansion** — ward master, bed master, occupancy, transfers, admission lifecycle, daily charges and discharge.
4. **Nursing** — nursing notes, observations, care plans, handover, medication administration, intake/output and tasks.

### Phase 2 — Clinical expansion

5. Emergency/Casualty.
6. Theatre/Surgery (first workflow increment implemented; clinical validation and integrations remain).
7. Referral management.
8. Structured diagnosis and procedure coding.
9. Clinical documentation expansion.
10. Department work queues.

### Phase 3 — Control and intelligence

11. Enterprise audit history.
12. Notification engine.
13. Management command centre.
14. Operational KPI dashboards.
15. Advanced financial analytics.
16. Inventory analytics.
17. Claims analytics.

### Phase 4 — External connectivity

18. Patient portal.
19. SMS/email.
20. Payment integrations.
21. External healthcare integrations.
22. Public/internal API.
23. Mobile applications.

### Phase 5 — Engineering maturity

24. CI/CD.
25. Automated deployment.
26. Containerisation/Docker where useful.
27. Automated database migration strategy.
28. Automated functional/security tests.
29. Monitoring and alerting.
30. Disaster recovery testing.

## What we should not build

- Duplicate dashboards
- Duplicate billing workflows
- Duplicate patient registration
- Duplicate prescription systems
- Legacy compatibility pages without active callers
- Modules created only to increase menu count
- AI features before underlying data is reliable
- Mobile applications before web workflows are stable
- Docker merely for appearance
- Complex integrations before the core operational chain is complete

## Product quality gate

A module is not complete merely because its CRUD screens work.

Every major workflow should satisfy:

**Identity → Authorization → Transaction → Audit → Financial/stock effect where applicable → Reporting → Error handling → UAT**

Example: dispensing should verify prescription/order, patient, stock, batch/expiry, dispensing, billing, payment/credit, stock movement, audit and reporting.

The same standard applies to claims, procurement, admissions, theatre and future departments.

## Brutal conclusion

Hospitalis is already a credible HMS foundation, but it is not yet a complete enterprise hospital information system.

The gap is primarily **depth, integration and control**, not the number of screens.

The next objective is therefore not another cosmetic dashboard. It is closing the operational gaps above while continuing the cleanup of legacy code.

This document is the master reference for future HMS development.

## Inpatient / Nursing implementation note (2026-10-09)

The current feature branch adds an auditable inpatient bed-transfer workflow and Nursing Station task/care-plan records. Database migration: `database/inpatient_nursing_expansion_migration.sql`. Apply it after the existing IPD and Nursing migrations in a backed-up test environment. These changes have not been validated against a live database; do not treat the percentage estimates as UAT completion. Medication administration should be built only after confirming prescription/order and dispense schemas so it cannot become an unsafe parallel medication record.
\n\n## Ward configuration implementation note (2026-10-09)\n\n`database/inpatient_ward_bed_master_migration.sql` adds an idempotent ward/bed master and seeds the existing five ward labels with six beds each without overwriting existing bed records. `clinical/ward_management.php` uses active master records to render capacity and exposes the latest 25 bed transfers. `clinical/ward_configuration.php` provides permission-gated ward/bed creation and safe bed activation/deactivation, refusing to deactivate an occupied bed. The migration is not applied and the new workflow has not undergone functional/UAT testing. Before production, back up the database, apply migrations in documented order, and reconcile any active admission whose ward/bed does not match the seeded master.\n\n\n## Inpatient daily charges implementation note (2026-10-09)\n\n`database/inpatient_daily_charges_migration.sql` adds a ward daily rate (zero by default) and `inpatient_daily_charges`, uniquely keyed by admission and charge date. `clinical/inpatient_charges.php` posts a manually selected date's charge through `helpers/billing.php` into canonical invoices and invoice items, then records the invoice/line references in the ledger. Posting requires Finance Create permission, only active stays are eligible, future dates and duplicates are blocked, and the ward rate must be configured first. No migration, invoice reconciliation or functional/UAT test has been performed. Automated daily posting is deferred until a supported scheduler and retry/monitoring design is in place.\n

## Nursing shift handover implementation note (2026-10-09)

`database/nursing_shift_handover_migration.sql` creates an admission-linked handover register with outgoing/incoming shifts, care summary, outstanding follow-up, creator, pending/acknowledged status and acknowledgement audit fields. The Nursing Station supports handover submission and acknowledgement by a different user with Nursing Approve permission. Both actions are CSRF-protected and audited. The migration is not applied and functional/UAT checks remain deferred until the agreed module scope is built.


## Nursing task assignment expansion (2026-10-09)

Task assignment now selects from eligible nurse, doctor and admin accounts and validates the assignee on submission. Open/in-progress tasks with past due times display an overdue flag. The current users table is used until a dedicated staff master and roster are implemented. No functional/UAT checks have been performed.


## Theatre / Surgery implementation note (2026-10-10)

`database/theatre_surgery_migration.sql` adds a patient-linked theatre case register. `theatre/index.php` provides planned-case scheduling, optional surgeon/anaesthesia assignment, a six-part pre-op gate, guarded lifecycle transitions (Planned → Pre-op → In Theatre → Recovery → Completed), intra-operative/anaesthesia notes, recovery notes and outcome, cancellation reason, case counts, CSRF checks, permission checks and audit-hook calls when the shared audit function is available. The migration is not applied to the target database and the workflow has not had live XAMPP/UAT validation. This is not a replacement for the approved surgical safety checklist, anaesthesia chart, operative report, implant traceability or PACU monitoring.
