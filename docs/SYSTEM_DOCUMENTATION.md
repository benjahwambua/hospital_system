# HMS System Documentation

## 1. System overview

**Hospitalis Hospital Management System (HMS)** is a web-based PHP/MySQL hospital operations system running in an Apache/PHP environment such as XAMPP.

The system brings patient registration, clinical care, diagnostic services, pharmacy, maternity, procurement, billing, finance, reporting, staff leave and administration into one controlled application.

## 2. Primary objectives

- Maintain a single patient record and patient journey.
- Coordinate front-desk, clinical and departmental workflows.
- Track services, clinical activity and financial obligations.
- Support laboratory and radiology requests and results.
- Support pharmacy dispensing and stock operations.
- Support maternity/ANC workflows for registered maternity patients.
- Support procurement and supplier payables.
- Provide controlled financial and reporting functions.
- Enforce authenticated access and per-user permissions.
- Protect state-changing operations with CSRF and server-side authorization controls.
- Support operational backup, restore and deployment procedures.

## 3. Application structure

The current repository is organized primarily by functional area:

- `patients/` — patient-facing operational records and patient dashboard.
- `reception/` — front-desk dashboard.
- `clinical/` — clinical dashboard, orders/referrals, admissions and discharge/IPD functions.
- `lab/` — laboratory dashboard, requests, results and laboratory inventory.
- `radiology/` — radiology dashboard, requests and results.
- `pharmacy/` — pharmacy dashboard, dispensing, sales and stock functions.
- `maternity/` — ANC, labour, PNC, deliveries, admissions and maternity reporting.
- `procurement/` — suppliers, purchase orders, receiving and supplier payables.
- `finance/`, `cashier/`, `accounting/`, `billing/`, `expenses/` — finance and billing operations.
- `reports/` — system and patient reporting.
- `users/` — user accounts and access rights.
- `administration/` — administration dashboard.
- `leave/` — staff leave.
- `services/` — service catalogue and pricing.
- `settings/` — system configuration.
- `includes/` — session, authorization, sidebar, header/footer and shared application logic.
- `database/` — schema and migration scripts.
- `docs/` — technical and operational documentation.

## 4. Navigation and modules

### 4.1 Dashboard

The general dashboard is the entry point after authentication. Module navigation is permission-gated.

### 4.2 Front Desk / Reception

Current navigation includes:

- Reception Dashboard
- Register Patient

The reception registration workflow establishes the patient identity and routes the patient into the appropriate service/clinical workflow.

### 4.3 Clinical

Current navigation includes:

- Clinical Dashboard
- Patient List
- Appointments
- Orders & Referrals
- Ward / IPD

The Patient Dashboard is shared between Front Desk and Clinical workflows and is anchored to the patient's current open encounter where the visit model is available.

### 4.4 Laboratory

Current navigation includes:

- Laboratory Dashboard
- Lab Requests
- Lab Results
- Lab Inventory
- Test Materials (shown where the user has the relevant edit capability)

### 4.5 Radiology

Current navigation includes:

- Radiology Dashboard
- Radiology Requests
- Radiology Results

### 4.6 Pharmacy

Current navigation includes:

- Pharmacy Dashboard
- Dispensing Queue
- Sell Medicine
- View Stock

The wider pharmacy implementation also contains stock, sales, paid-sale, price and reporting functions.

### 4.7 Maternity

Current navigation includes:

- Maternity Dashboard
- ANC / Labour / PNC
- Antenatal (ANC)
- Postnatal (PNC)
- Deliveries
- Admissions
- Reports

Maternity is intended to operate on registered maternity patients rather than unrestricted walk-in records.

### 4.8 Finance & Billing Administration

This is the administration-oriented finance area and currently includes:

- Finance Dashboard
- Billing & Invoices
- M-Pesa Payments
- Ledger
- Financial Reconciliation
- Record Expense
- Expense History
- Sales Report

### 4.9 Procurement

Current navigation includes:

- Procurement Dashboard
- Suppliers
- Purchase Orders
- Receive Inventory
- Supplier Payables
- Supplier Statement

### 4.10 Finance

Current navigation includes:

- Central Cashier
- Cashier Shift
- Aged Receivables
- Payment History

### 4.11 Administration

Administration is reserved for super users. Current navigation includes:

- Administration Dashboard
- Manage Users
- Access Rights
- Service Catalogue
- Price History
- General Settings
- System Reports

Administration is not the location for Staff Leave.

### 4.12 Staff

Current Staff navigation includes:

- Staff Leave

Staff Leave is permission-gated independently from Administration.

## 5. Identity and patient records

The HMS patient model uses generated patient numbers. The established conventions include:

- Registered patients: `EMC####` style identifiers.
- Walk-in patients: `WLK####` style identifiers.

The exact next number is generated by the application and must not be manually fabricated by users.

The Patient Dashboard consolidates information relevant to the current patient and, where supported, the active encounter.

## 6. Encounter model

Where the visit model is available, HMS uses an open/current encounter to anchor clinical, service, prescription and billing information.

An appointment can be associated with an encounter. Closed/completed/cancelled encounters should not be treated as the current open encounter.

## 7. Service and billing relationship

Services are configured in the Service Catalogue and may have pricing history. Patient services contribute to the operational and financial journey.

Clinical users should not be treated as the authority for pharmacy selling prices; authoritative pricing is loaded from pharmacy stock where the relevant workflow requires it.

## 8. Diagnostic workflows

### Laboratory

Clinical/service workflows can create laboratory requests. Laboratory users process requests and record results.

### Radiology

Clinical/service workflows can create radiology requests. Radiology users process requests and record results.

## 9. Pharmacy

Pharmacy supports dispensing and medicine sales together with stock visibility and management. Pharmacy operations are connected to clinical prescribing and patient billing where applicable.

## 10. Maternity

Maternity supports ANC, labour, PNC, deliveries, admissions and maternity statistics. Registration/routing rules are used to ensure the maternity list represents eligible registered maternity patients.

## 11. Procurement

Procurement supports supplier management, purchase orders, inventory receiving, supplier payables and supplier statements.

Financially significant procurement operations must be performed by authorized users and validated server-side.

## 12. Finance and payments

Finance and billing cover:

- invoices and billing
- cashier operations
- payment history
- M-Pesa payments
- ledger
- reconciliation
- expenses
- sales reporting
- aged receivables

Financial controls include transaction validation, prevention of duplicate payment references, overpayment/over-refund controls where implemented, and preservation of payment/refund records.

## 13. Reports

System reporting currently includes operational summaries and links to patient medical, clinical, laboratory, radiology, pharmacy, maternity and procurement activity.

Patient medical reporting is designed for a consolidated patient record/report and includes clinical and financial information relevant to the patient.

## 14. User access control

HMS supports explicit user-module permissions through `access_modules` and `user_module_access`.

For normal users, permissions are represented by:

| Action | Meaning |
|---|---|
| View | Open/read the module |
| Create | Add a new record |
| Edit | Modify an existing record |
| Delete | Remove an eligible record |
| Approve | Perform an approval action |

Non-view actions require module visibility.

The Access Rights screen is restricted to super users.

## 15. Security architecture

The application security baseline requires:

- authenticated sessions
- server-side module/action authorization
- prepared statements for user-controlled database values
- server-side validation
- POST for state-changing requests
- CSRF protection
- transactional handling for sensitive financial operations
- idempotent payment callbacks/webhooks
- controlled production secrets
- secure session/cookie settings
- HTTPS in production
- restricted database/filesystem privileges
- controlled backups
- auditability of critical actions

The sidebar is only a navigation convenience; hiding a link is not considered authorization.

## 16. Deployment environment

The application is designed for Apache/PHP/MySQL environments, including XAMPP for local development.

Production deployments must use environment-based database credentials and must not use MySQL root credentials.

See [DEPLOYMENT.md](DEPLOYMENT.md), [PRODUCTION_CONFIG.md](PRODUCTION_CONFIG.md), [BACKUP_RESTORE.md](BACKUP_RESTORE.md), and [SECURITY.md](SECURITY.md).

## 17. Change management

All HMS changes should follow the repository's controlled development workflow:

1. Plan
2. Apply changes
3. Commit
4. Verify CI
5. Review
6. Merge
7. Verify after merge
8. Perform UAT where applicable

Documentation must be updated as part of relevant feature completion.

## 18. Verification status

This document is a documentation foundation based on the current source tree and navigation. It does not by itself certify every workflow as UAT-complete. Screens, permissions, database migrations and operational workflows must be tested in the deployed HMS environment before being marked verified.
