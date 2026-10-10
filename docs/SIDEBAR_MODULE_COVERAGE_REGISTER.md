# Sidebar and Module Coverage Register

Last reviewed: 2026-10-10

## Purpose

Use this register to distinguish existing pages that need navigation from modules that do not yet exist. A sidebar link is not evidence that a workflow is complete. Full workflow and role-based checks remain part of the planned end-to-end test phase.

## Navigation coverage checks

- Repository tree contains 147 PHP files at the time of review.
- 87 explicit sidebar destinations were checked against the repository tree.
- No explicit sidebar destination was missing at the time of review.
- Existing workflow entry points added during this review:
  - Patient Dashboard: `patients/patient_dashboard.php`
  - Laboratory Inventory Movement History: `lab/inventory/stock_movements.php`
  - Add Laboratory Inventory Item: `lab/inventory/add_item.php` (Laboratory edit permission)
  - Pharmacy Medicine Catalogue: `pharmacy/add_medication.php` (Pharmacy edit permission)

## Existing pages intentionally not promoted to top-level navigation

These are supporting actions, print views, callbacks, or context-dependent pages. They should be linked from the relevant record/workflow instead of exposed as standalone module navigation:

- Patient edit/delete and line-item removal actions.
- Invoice payment, receipt, invoice print, and invoice detail pages.
- M-Pesa callback and application configuration files.
- Purchase-order create/edit/delete/process/detail actions where the Procurement Purchase Orders workflow should be the entry point.
- Maternity edit/view/print, bill, admission, delivery registration, and SMS actions where the Maternity workflow should launch them.
- Leave request/action pages, which should be reachable from the Staff Leave workflow.
- HR staff directory entry point: `hr/index.php` (employee master; migration and explicit permission assignment required).
- HR professional credentials: `hr/credentials.php` (expiry monitoring; apply `database/hr_staff_credentials_migration.sql`).
- HR contract register: `hr/contracts.php` (contract metadata and renewal monitoring; apply `database/hr_staff_contracts_migration.sql`).
- Emergency/Casualty: `emergency/index.php` (triage-linked visit and disposition queue; apply `database/emergency_casualty_migration.sql`).
- Theatre & Surgery: `theatre/index.php` (case scheduling, pre-op safety gate, intra-operative/anaesthesia notes and recovery tracking; apply `database/theatre_surgery_migration.sql`).
- HR offboarding: `hr/offboarding.php` (separation cases and mandatory five-part clearance checklist; apply `database/hr_staff_separations_migration.sql`).
- Clinical discharge action, which should be available from the admission/ward workflow with its required patient/admission context.

These routes still need direct-access permission and CSRF review; hiding an action from the sidebar is not an access-control measure.

## Capability gaps to build

The following capabilities were identified in the project planning register as missing or incomplete. They must be implemented as real modules before their links are presented as working features:

| Capability | Status at review | Navigation expectation |
|---|---|---|
| Emergency / Casualty | First implementation | `emergency/index.php` provides intake, triage observations, acuity queue and case disposition; apply `database/emergency_casualty_migration.sql` and assign emergency permissions |
| Theatre / Surgery | First implementation | `theatre/index.php` provides scheduling, pre-op gate and guarded case lifecycle; apply `database/theatre_surgery_migration.sql`, assign Theatre view/create/edit permissions and complete clinical validation |
| Referral management | Partial | Keep current clinical referral workflow visible; expand when dedicated module is built |
| Diagnosis / ICD-10 coding | Not implemented | Add dedicated clinical coding entry when built |
| Procedure coding | Not implemented | Add dedicated entry when built |
| Staff master / HR | In progress | `hr/index.php` provides a permission-gated employee register; run `database/hr_staff_master_migration.sql`, then assign `hr_staff` permissions to authorized users. Credentials, documents, payroll and rostering are not implemented yet |
| Staff rostering | Not implemented | Add roster page when workflow exists |
| Notifications and escalation | Not implemented | Add notification centre when functional |
| Patient portal | Not implemented | Keep out of staff navigation until implemented and access model is defined |
| SMS / Email communications | Not implemented as a governed module | Add communications centre when built |
| External healthcare integrations / governed API | Not implemented | Admin-only integration settings and API status when built |

## Next review order

1. Confirm sidebar permission keys align with the permission registry and direct page guards.
2. Review Central Stores receipt, requisition, issue, transfer, return, stock-count, and adjustment paths for transactional integrity.
3. Review Laboratory and Pharmacy inventory workflows, including movement history and stock corrections.
4. Review the next module as a complete workflow, not just as a set of screens.
5. Perform end-to-end tests after the planned module build-out, using controlled data and recording evidence.

## Verification limits

The repository tree and PHP Syntax Audit are static checks. They do not establish that the local XAMPP database has every migration applied, that all routes load successfully, or that transactions and permissions behave correctly at runtime.
