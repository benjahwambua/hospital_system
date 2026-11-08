# ICU / High Dependency Unit (HDU) — Module Specification

## Status
Scope and acceptance specification only. This document does not represent an implemented or clinically validated ICU workflow.

## Product decision
ICU/HDU is an extension of the existing inpatient episode, not a second patient or admission system. It must reuse the canonical patient, visit, admission, ward/bed, nursing, clinical, medication, billing and audit records wherever those foundations support the required workflow.

The module must be optional at facility level. A hospital that does not operate ICU/HDU must be able to leave it disabled without breaking general inpatient care.

## Intended users
- ICU/HDU nurses
- Attending clinicians and critical-care clinicians
- Ward/inpatient coordinators
- Authorised supervisors
- Finance users only for approved charge review/posting

## Core workflow
1. An authorised clinician requests ICU/HDU placement for an existing active inpatient admission, recording indication, urgency and clinical handover.
2. An authorised bed coordinator assigns an active ICU/HDU bed. The assignment must be transactional and must prevent two active patients occupying the same bed.
3. The patient appears on the ICU/HDU census with patient number, admission, bed, responsible clinician, admission time and current status.
4. Authorised staff record time-stamped observations, clinical reviews, care notes, intake/output and escalation events against the same admission.
5. Clinicians review and acknowledge escalations; the system records who acknowledged and when.
6. Transfer to another ICU/HDU bed or step-down ward uses the existing inpatient transfer lifecycle and preserves a complete movement history.
7. ICU/HDU discharge/step-down requires a documented clinical decision and handover. The underlying hospital admission remains active unless the patient is actually discharged from hospital.
8. Approved billable services may be linked to the canonical billing ledger. No duplicate invoice or parallel charge ledger is permitted.

## Functional scope — first release
### A. Unit and bed configuration
- Mark configured wards/beds as ICU or HDU through the existing ward/bed master, subject to a schema review.
- Support active/inactive beds and clear bed availability.
- Do not seed arbitrary bed counts or silently rewrite existing ward/bed records.

### B. Admission and census
- Link every ICU/HDU stay to an existing active admissions record and patient.
- Record request/acceptance, admission-to-unit timestamp, indication, source location, accepting clinician and handover.
- Prevent duplicate active ICU/HDU stays for the same inpatient admission unless a documented transfer/re-entry rule is approved.
- Show active census, occupied/available beds, length of stay and pending handovers.

### C. Critical-care chart
- Record time-stamped vital observations using validated units and ranges.
- Record fluid intake and output as individual entries with time, category, amount, unit and author; provide shift/day totals and balance.
- Record structured clinical review and care notes with author and timestamp.
- Preserve corrections as auditable amendments; do not silently overwrite signed clinical entries.
- Reuse existing observation/MAR records where their schema and semantics are suitable. Do not create duplicate medication orders or medication administration records.

### D. Escalation and handover
- Support configurable, clinician-approved escalation rules.
- Record trigger, severity, responsible role/user, acknowledgement, action taken and resolution.
- An alert must never silently disappear when unacknowledged.
- Provide shift handover with outstanding risks, tasks and pending review.

### E. Transfer and step-down
- Use canonical inpatient bed-transfer history.
- Record reason, destination, clinical handover, authorising clinician and timestamps.
- Release the source bed only after the destination assignment succeeds.
- Distinguish transfer out of ICU/HDU from hospital discharge.

### F. Audit and permissions
- Use the existing explicit module/action permission engine; do not introduce a parallel role system.
- Define separate view, create, edit, approve and delete capabilities as supported by the existing ACL model.
- Restrict clinical sign-off, transfer approval, configuration and charge posting to appropriately authorised users.
- Enforce permissions server-side, CSRF protection on state-changing requests, prepared statements and transactional writes.
- Audit admission, bed assignment, observation, fluid entry, amendment, escalation, acknowledgement, handover, transfer and charge-link actions.

## Data and integration rules
- Patient and inpatient admission IDs are canonical foreign references.
- Do not duplicate patient demographics, hospital admission, prescriptions, dispensing, MAR, invoices or payment records.
- New tables must have documented keys, indexes, constraints and migration ordering.
- Use transactions for bed allocation, transfer and any operation spanning multiple records.
- Avoid depending on optional columns without an explicit migration or a safe schema compatibility strategy.
- Any billing integration must call the existing canonical billing helper and retain invoice/line references.

## Acceptance criteria
- [ ] Facility can enable/disable ICU/HDU without affecting other inpatient workflows.
- [ ] ICU/HDU beds are configured through the canonical ward/bed master.
- [ ] Only an active inpatient admission can be accepted into the unit.
- [ ] Concurrent admission/transfer attempts cannot allocate one bed twice.
- [ ] Census agrees with active unit stays and canonical bed occupancy.
- [ ] Observations and fluid entries are timestamped, attributable and admission-linked.
- [ ] Corrections retain an audit trail and do not erase signed clinical history.
- [ ] Fluid totals reconcile to the underlying entries for the selected period.
- [ ] Escalations remain visible until acknowledged/resolved and retain the full response history.
- [ ] ICU/HDU transfer preserves inpatient continuity and movement history.
- [ ] Step-down does not accidentally discharge the hospital admission.
- [ ] Permissions are tested for each action using distinct authorised and unauthorised users.
- [ ] Migration is tested on a restored copy of the target database before rollout.
- [ ] Browser/UAT tests cover normal flow, validation failures, concurrent bed allocation and rollback.
- [ ] Clinical representatives approve observation fields, escalation rules and documentation before live use.

## Deliberately deferred
- Ventilator/device integrations
- Automated clinical scoring or treatment recommendations
- Advanced waveform capture
- Automated medication prescribing
- Automatic charge generation
- External ICU device interoperability

These require separate clinical, technical and governance approval.

## Build sequence
1. Confirm current inpatient, ward/bed, nursing observation, MAR, permission and audit schemas.
2. Add the smallest compatible migration and module ACL registration.
3. Implement unit configuration and ICU/HDU census.
4. Implement admission/bed assignment and transfer lifecycle.
5. Implement critical-care observations and intake/output.
6. Implement handover and acknowledgement-based escalations.
7. Add canonical billing links only after finance approval.
8. Add controlled test fixtures, automated checks and a documented XAMPP UAT script.

## Safety boundary
This module is a clinical record and workflow aid. It must not diagnose, prescribe, calculate treatment, or replace clinician judgement. Thresholds and clinical forms require approval by qualified hospital clinical leadership before operational use.