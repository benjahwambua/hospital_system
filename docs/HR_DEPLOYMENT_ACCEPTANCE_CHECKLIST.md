# HR and Staff Deployment & Acceptance Checklist

This checklist covers the HR employee directory, login-account linking, professional credentials, contract tracking, staff offboarding, leave requests/approvals and staff rostering. It is a deployment and acceptance aid; the presence of this document does not mean the database migrations or local UAT have passed.

## 1. Before deployment

- Back up the target database and verify that the backup can be read.
- Confirm the base HMS schema is installed, including `users` and `access_modules`.
- Run migrations against a test copy first. Do not blindly rerun SQL statements in migrations that are not explicitly idempotent.
- Check the exact migration contents against the current branch before applying them.

## 2. Migration order

Apply only migrations not already present in the target database, in this order:

1. `database/access_control_migration.sql` — module/action permission tables.
2. `database/staff_leave_migration.sql` — leave requests and the `staff_leave` module.
3. `database/hr_staff_master_migration.sql` — employee master and the `hr_staff` module.
4. `database/hr_staff_contracts_migration.sql` — employment contract register; requires the HR master.
5. `database/hr_staff_credentials_migration.sql` — professional credential register; requires the HR master.
6. `database/hr_staff_separations_migration.sql` — offboarding and clearance; requires the HR master.
7. `database/hr_staff_rostering_migration.sql` — shift register and the `staff_roster` module; requires the HR master.

If explicit per-user permissions have not been installed, review and apply the repository's appropriate explicit-permissions migration after checking its prerequisites. Do not grant broad access merely by assigning a role. Confirm every intended user's module/action permissions in Access Rights after the module rows exist.

## 3. Access and navigation checks

- HR and Staff appear only for users granted access to at least one relevant module.
- HR contains Staff Directory, Professional Credentials, Contract Register and Staff Offboarding.
- Staff contains Staff Rostering and Staff Leave according to the user's individual module rights.
- Staff Leave remains outside the super-user-only Administration section.
- Test direct URL access as well as sidebar visibility; hidden links are not a security control.
- Verify View, Create, Edit and Approve actions independently. Confirm a user without Approve cannot process leave decisions.
- Verify the super-user override remains intact.
- Navigate to several modules and back; HR/Staff expanded sections and sidebar scroll position should remain stable.

## 4. Employee directory and account linking

- View the employee register and all existing login accounts.
- Confirm an unlinked login account is visible in the system-account list.
- Create an employee profile from the relevant account-link action; confirm the selected account is linked.
- Verify one login account cannot be linked to two employee records.
- Create a staff record without a login account and confirm it does not create a user/login.
- Validate required first name, last name, department and job title; reject invalid email/date values and contract end dates before hire dates.
- Search by name/staff number/contact, filter by department/status, and edit an existing record.
- Confirm HTML output escapes user-provided text and changes are limited by permissions.

## 5. Leave request and approval

- Submit a valid request and confirm the request is recorded as Pending.
- Reject invalid leave types, impossible calendar dates, an end date before the start date, and notes longer than 500 characters.
- Confirm the calculated day count is inclusive of both dates.
- Submit an overlapping Pending or Approved request and confirm it is blocked.
- Attempt two concurrent overlapping submissions for the same user; confirm only one can be recorded.
- Confirm staff can see their own requests and an authorized approver can see the approval queue.
- Approve and reject separate pending requests; verify reviewer, timestamp, decision and notes are saved.
- Confirm an already processed request cannot be approved/rejected again and a user without Approve is denied server-side.
- Verify database/query failures produce a safe message rather than a PHP fatal error.

## 6. Contracts, credentials and offboarding

- Create and edit a contract and verify expired / renewal-due indicators.
- Add a credential and verify expired / expiring-soon indicators; test suspended/revoked states.
- Start an offboarding case and confirm completion is blocked until every required clearance item is recorded.
- Complete a valid clearance and confirm the employee status becomes Separated in the same transaction.
- Confirm offboarding records do not imply external accounts were automatically revoked; verify the real access-revocation process separately.

## 7. Staff rostering

- Schedule a shift for an active employee and confirm it appears in the roster.
- Reject invalid date/time values and an end time not later than the start.
- Attempt overlapping shifts for the same employee and confirm they are blocked.
- Confirm non-cancelled shifts are checked for overlap and cancelled shifts do not block new assignments.
- Verify allowed transitions: Scheduled → Confirmed/Cancelled and Confirmed → Completed/Cancelled only.
- Confirm unauthorized users cannot create, edit or transition shifts.
- Verify the screen clearly states that this roster is not an attendance clock or an automated clinical staffing-adequacy assessment.

## 8. Verification record

Record each check as **Pass**, **Fail**, or **Not run**, with the database/environment, tester and date. Do not mark HR production-ready until the target database migrations, PHP syntax/CI, role-specific direct-access tests, and local XAMPP/browser workflow checks have passed. A repository change or green syntax workflow alone is not clinical or production acceptance.
