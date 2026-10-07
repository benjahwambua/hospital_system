# HMS Deployment Guide

## Scope
Deployment guidance for Apache/PHP/MySQL, including XAMPP, with minimum go-live controls.

## Database
1. Create the HMS database and import the baseline schema.
2. Run required migrations in dependency order, including access control, clinical/IPD, financial core, and staff leave migrations where applicable.
3. Confirm every migration completes successfully.
4. Take a fresh database backup before production data is entered.

## Application configuration
- Keep database credentials outside source control for production.
- Use a dedicated database user with least privilege; do not use MySQL root in production.
- Configure secure PHP session/cookie settings and HTTPS.
- Do not expose .git, SQL dumps, backups, logs, or configuration secrets through the web root.

## Apache/PHP
- Use a supported PHP 8.x release with mysqli and required extensions enabled.
- Disable directory listing.
- Restrict writable filesystem locations.

## Go-live validation
- Verify administrator login and role/permission assignments.
- Test registration, encounters, clinical notes, laboratory, radiology, pharmacy, maternity, procurement, billing, payment, refund, reporting, and staff leave workflows.
- Verify CSRF protection on state-changing forms.
- Verify unauthorized users receive HTTP 403.
- Verify database backup and restore using a test copy.
- Review the audit log for critical financial and administrative actions.

## Deployment sequence
1. Restrict application access.
2. Back up the database and current application files.
3. Deploy the tested commit.
4. Apply database migrations.
5. Run PHP syntax/CI checks.
6. Run smoke tests.
7. Confirm login and critical workflows.
8. Reopen access.

## Rollback
For a critical failure, restrict access, restore the previous application release, and restore the database backup when a schema/data rollback is required. Do not attempt an untested destructive SQL rollback against live clinical data.
