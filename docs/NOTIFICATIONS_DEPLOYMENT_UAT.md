# Notifications & Work Alerts — Deployment and UAT Runbook

Last reviewed: 2026-10-11

## Scope and release state

This runbook covers the persistent in-app notification inbox, topbar unread indicator, roster assignment alerts, and Nursing task/handover alerts. The code is on `feature/notifications-work-queues`; this is not evidence that the migration has been applied or that the workflows have passed UAT.

## Deployment preflight

1. Back up the target `hms_db` and confirm that the backup can be located for rollback.
2. Confirm the active code version includes `includes/notifications.php`, `notifications/index.php`, the topbar/sidebar integration, and the Nursing integration.
3. Confirm `database/access_control_migration.sql` has been applied and the expected `access_modules` schema exists.
4. Review the access matrix before granting the `notifications` module. The migration registers the module but deliberately does not grant access to existing users.
5. Apply `database/notifications_migration.sql` once to the target database.
6. Verify that `hms_notifications` exists and has the expected user, read-state, dedupe, and expiry indexes.
7. Assign Notifications View access only to the intended roles/users. Do not infer access from sidebar visibility.

## Functional UAT checklist

Use non-production test accounts and test inpatient records. Do not place patient identifiers or clinical details in notification titles/messages.

- [ ] A user without Notifications View permission cannot access `notifications/index.php` directly.
- [ ] An authorized user can open their inbox and filter all/unread items.
- [ ] Mark-read and mark-all-read affect only the signed-in user's notification rows.
- [ ] The unread badge reflects the signed-in user's unread, non-expired items only.
- [ ] An external or protocol-relative target URL is rejected by the URL helper; internal `/hospital_system/` paths remain usable.
- [ ] Assign a Nursing task to an eligible nurse/doctor/admin account; the assignee receives one generic notification.
- [ ] Complete the task as an authorized assignee; the task creator receives one generic completion notification when they are a different user.
- [ ] Submit a shift handover and acknowledge it as a different user; the handover author receives one generic acknowledgement notification.
- [ ] Retry or reload the relevant workflow and confirm dedupe keys prevent duplicate notification rows for the same event.
- [ ] With the notifications table absent in a disposable test database, confirm notification helpers fail best-effort and do not block the underlying Nursing/rostering transaction.
- [ ] Confirm existing Nursing permissions and task/handover status guards still apply; notifications must not grant clinical access.
- [ ] Nursing observation entry rejects malformed timestamps, out-of-range measurements, negative intake/output and systolic BP below diastolic BP; verify valid values and blank optional measurements still save.
- [ ] Review PHP error logs and the audit trail after the test sequence.

## Rollback and safeguards

- Do not drop `hms_notifications` as a first-line rollback; it may contain useful audit-adjacent user history. Prefer rolling back the application release while leaving the table intact.
- If the migration must be reversed, first take a fresh backup and confirm retention requirements. A destructive drop should be an explicitly approved database operation.
- Notification delivery is best-effort. A notification failure must not undo a successful clinical task or handover transition.
- Do not deploy as production-ready until migration, permission review, direct-access checks, and witnessed UAT are complete.

## Known gaps

- No scheduled escalation/expiry worker is implemented by this increment.
- Email/SMS delivery, delivery logs, and consent/preferences are not implemented.
- There is no CI workflow run recorded for this branch at the latest review.
- Live database migration and XAMPP end-to-end UAT remain outstanding.
