# HMS Administrator Manual

## 1. Purpose

This manual is for authorized HMS super users and technical administrators.

Administration access is deliberately separate from ordinary operational module access.

## 2. Administration area

The Administration section currently contains:

- Administration Dashboard
- Manage Users
- Access Rights
- Service Catalogue
- Price History
- General Settings
- System Reports

Administration is reserved for super users.

## 3. User management

Open:

**Administration → Manage Users**

Use this area to create and manage HMS user accounts.

Administrator responsibilities include:

- create accounts only for authorized staff
- assign the correct role/context
- maintain account status
- prevent shared accounts
- disable accounts that should no longer have access
- ensure users receive only the access they need

## 4. Per-user access rights

Open:

**Administration → Access Rights**

HMS supports explicit rights for each user.

The action model is:

| Right | Purpose |
|---|---|
| View | User can access/read the module |
| Create | User can create records |
| Edit | User can modify records |
| Delete | User can delete eligible records |
| Approve | User can approve eligible transactions/workflows |

### Assigning rights

1. Select the user.
2. Review available modules.
3. Enable the modules the user actually needs.
4. Assign the required actions.
5. Ensure View is enabled for any module where another action is granted.
6. Save the access rights.
7. Log in/test with an appropriate test account.
8. Verify both allowed and denied actions.

### Principle of least privilege

Do not give a user every module simply because they have a senior job title. Assign access according to actual duties.

## 5. Super user

A super user has unrestricted HMS access.

Super-user credentials must be protected carefully. They should be used for system administration, configuration and controlled troubleshooting, not routine clinical or cashier work when a normal account is sufficient.

## 6. Service Catalogue

Open:

**Administration → Service Catalogue**

Use this area to manage services offered by the facility.

Before changing a service:

- confirm the service name
- confirm category
- confirm operational use
- confirm price implications
- consider existing patient records and reports
- ensure the change does not break a departmental workflow

## 7. Price History

Use Price History to review changes to service pricing.

Financially significant price changes should be authorized and traceable.

## 8. General Settings

Use General Settings for approved system-level configuration.

Do not change production settings experimentally.

Record significant configuration changes and, where applicable, perform a backup before changes that can affect system operation.

## 9. System Reports

System Reports provides administration-level operational summaries.

Department users may have their own dashboards/reports without requiring Administration access.

## 10. Staff Leave

Staff Leave is **not** part of Administration.

It is a separate Staff module controlled by its own permission.

This separation is intentional:

- Administration = system administration
- Staff Leave = operational staff function

## 11. Security administration

Administrators must ensure:

- every user has an individual account
- inactive users are disabled
- passwords are not shared
- least privilege is applied
- access rights match job duties
- unauthorized access produces a denial
- critical financial and administrative actions remain controlled
- backups are maintained
- production credentials are protected

## 12. Backup and recovery

Follow [BACKUP_RESTORE.md](BACKUP_RESTORE.md).

At minimum:

- perform daily database backups in production
- back up before migrations and major releases
- retain an application release copy
- keep at least one backup outside the production server
- periodically test restoration

An untested backup is not a verified recovery plan.

## 13. Deployment

Follow [DEPLOYMENT.md](DEPLOYMENT.md).

Production deployment should include:

1. restrict access
2. back up database and application
3. deploy the tested commit
4. apply required migrations
5. run CI/syntax checks
6. perform smoke tests
7. verify login and critical workflows
8. reopen access

## 14. Troubleshooting

### User cannot see a module

Check:

1. user account status
2. module assignment
3. View permission
4. whether the module is active
5. whether the user needs to log out/in to refresh session state

### User can see a module but cannot perform an action

Check the specific action:

- Create
- Edit
- Delete
- Approve

Remember that module visibility and action rights are separate.

### A user receives 403

Do not bypass the authorization check. Determine which permission is missing and correct the account assignment if the access is legitimate.

## 15. Change control

All source-code changes should use the controlled GitHub workflow.

Do not directly patch production code without recording the change.

The expected lifecycle is:

**Planned → Applied → Committed → CI Verified → Reviewed → Merged → Post-merge Verified → UAT Complete**

Documentation should be updated for meaningful functional, permission, security or workflow changes.
