# HMS Workflow & SOP Manual

## 1. Purpose

This document defines the operational chains that HMS is designed to support. Department procedures should be aligned with facility policy and applicable clinical/legal requirements.

## 2. Core outpatient journey

**Registration → Patient/Encounter → Clinical Assessment → Services/Orders → Diagnostic or Pharmacy Processing → Billing → Payment → Completion**

Each stage must be performed by an authorized user.

## 3. Reception SOP

### Objective
Create or locate the correct patient record and route the patient to the correct service.

### Procedure
1. Search for an existing patient where appropriate.
2. If no suitable record exists, register the patient.
3. Verify demographic information.
4. Select the correct clinical department/service.
5. For ANC/maternity patients, use the maternity/ANC routing option where applicable.
6. Save and confirm the generated patient number.
7. Direct the patient to the next operational stage.

### Control
Do not create duplicate patient records merely because a patient cannot immediately be found.

## 4. Clinical SOP

1. Confirm patient identity.
2. Open the appropriate patient/encounter.
3. Record assessment and vital information.
4. Add required services.
5. Create laboratory or radiology requests where clinically required.
6. Prescribe medication where appropriate.
7. Admit or discharge when authorized and clinically appropriate.
8. Ensure records are saved accurately.

## 5. Laboratory SOP

**Request → Verification → Test Processing → Result Entry → Result Availability**

- Verify patient and request.
- Perform the test according to laboratory procedure.
- Enter the verified result.
- Review before submission.
- Make the result available to authorized clinical users.

## 6. Radiology SOP

**Request → Examination → Findings → Result/Report → Clinical Review**

Verify the patient/request before entering findings.

## 7. Pharmacy SOP

**Prescription/Order → Stock Verification → Dispensing/Sale → Billing/Payment → Stock Update**

Verify medicine, quantity and patient before dispensing.

## 8. Maternity SOP

**Maternity Registration/Routing → ANC/Labour/PNC → Follow-up → Delivery/Admission where applicable → Postnatal Care → Reporting**

Only eligible registered maternity patients should appear in the maternity operational workflow.

## 9. Procurement SOP

**Supplier → Purchase Order → Authorization/Processing → Receive Inventory → Supplier Payable → Statement/Reconciliation**

Receiving staff must verify quantity and condition before completing receipt.

## 10. Finance SOP

**Bill → Verify Charges → Receive Payment → Record Payment → Receipt → Reconcile**

For M-Pesa:

**Payment Initiation → Callback/Payment Record → Idempotency/Validation → Patient Account Update → Reconciliation**

Never manually duplicate a payment transaction because a callback or confirmation appears delayed.

## 11. Cashier shift SOP

1. Open/confirm the assigned shift.
2. Process authorized transactions.
3. Verify payment amount and method.
4. Confirm receipts.
5. Monitor outstanding balances.
6. Review shift totals.
7. Close/reconcile the shift according to facility procedure.

## 12. Staff Leave SOP

**Staff Member → Leave Request → Authorized Review → Approval/Decision → Leave Record**

Staff Leave is separate from Administration and is permission-gated.

## 13. Incident handling

If a user discovers an incorrect patient, clinical, billing, payment, stock or procurement record:

1. Stop and verify the source information.
2. Do not create a second record to compensate.
3. Do not bypass permission checks.
4. Escalate to the responsible supervisor/administrator.
5. Correct the record only through an authorized workflow.
6. Preserve auditability where applicable.

## 14. Downtime

If HMS becomes unavailable:

1. Notify the responsible technical administrator.
2. Follow the facility's approved downtime procedure.
3. Record essential transactions using the approved downtime process.
4. Do not improvise database changes.
5. Reconcile downtime records into HMS after recovery according to facility procedure.

## 15. End-of-day controls

Department heads should confirm, as applicable:

- patient registrations are complete
- clinical records are saved
- pending lab/radiology requests are reviewed
- pharmacy transactions and stock are reconciled
- billing and payments are reconciled
- cashier shifts are closed
- procurement receipts are accounted for
- staff leave actions are processed
- critical reports are reviewed
- backup status is confirmed by the technical administrator
