# HMS Gap Closure Progress

Last updated: 2026-10-09

These percentages are **engineering completion estimates**, not UAT certification. A module only reaches 100% after its workflow is implemented, authorized, audited, reported, error-handled and verified in UAT.

| Gap / workstream | Current completion | Current state | Remaining critical work |
|---|---:|---|---|
| Legacy file retirement / canonical workflows | 90% | Major obsolete wrappers retired | Final reference scan and UAT |
| Explicit per-user permissions | 90% | View/Create/Edit/Delete/Approve engine active; legacy role gates largely retired | Verify every existing user assignment; remove remaining compatibility assumptions |
| Insurance & SHA | 85% | Coverage, verification, preauthorization, claims, submission, remittances and reconciliation workspace implemented | Eligibility/tariff depth, denials/appeals, claim controls and UAT |
| Inpatient / Ward | 65% | Admission, bed allocation, occupancy and discharge exist | Ward/bed master, transfers, richer occupancy, daily charges and discharge controls |
| Nursing | 55% | Dedicated nursing station, observations, notes, care-plan/handover note types and inpatient linkage added in current work | Medication administration, tasking, formal care plans, shift workflow and UAT |
| Central Stores | 45% | Item register, locations, receipt entry, movement ledger, multi-item requisitions (up to four lines), requisition approval/issue, transfers, returns and authorized adjustments | Formal GRN/PO linkage, stock counts/variance controls, batch-level balances, valuation, automated regression tests and UAT |
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
2. Continue **Central Stores**: stock counts/variance approval, batch/expiry visibility and department integration.
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

The Nursing access-control migration was corrected on 2026-10-09; the live database migration and Nursing UAT are still outstanding. Central Stores requisitions now support multiple items, and procurement GRNs can post receipts directly to the Main Stores movement ledger. The live database migrations and end-to-end receiving/requisition UAT remain outstanding.\n\nThe main production-readiness risk is now **incomplete enterprise workflows**, especially stores, inpatient/nursing depth, emergency, theatre, coding and control/analytics—not the absence of more menu items.
