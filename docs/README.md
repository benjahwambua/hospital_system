# HMS Documentation

Hospitalis Hospital Management System (HMS) documentation index.

## Documentation status

This documentation is maintained against the HMS source code and should describe **implemented and verified behavior**, not planned functionality. Where a workflow or feature has not been verified in the running environment, it must be marked as pending verification.

## Documents

| Document | Audience | Purpose |
|---|---|---|
| [System Documentation](SYSTEM_DOCUMENTATION.md) | Management, implementers, technical administrators | System scope, architecture, modules, workflows, permissions, security and operational controls |
| [User Manual](USER_MANUAL.md) | Reception, clinical, laboratory, radiology, pharmacy, maternity, finance, procurement and staff users | Day-to-day instructions for using HMS |
| [Administrator Manual](ADMINISTRATOR_MANUAL.md) | Super users / system administrators | User management, access rights, configuration, security and maintenance |
| [Workflow & SOP Manual](WORKFLOWS_AND_SOPS.md) | Department heads and operational staff | End-to-end hospital workflows and standard operating procedures |
| [Installation & Deployment](DEPLOYMENT.md) | Technical administrators | Deployment and go-live procedures |
| [Backup & Restore](BACKUP_RESTORE.md) | Technical administrators | Backup, recovery and restoration procedures |
| [Security Baseline](SECURITY.md) | Technical administrators | Security requirements and release controls |
| [Production Configuration](PRODUCTION_CONFIG.md) | Technical administrators | Production environment configuration |

## Documentation principles

1. Document the current system, not assumptions.
2. Distinguish implemented, verified, pending and planned functionality.
3. Permissions documented here must agree with the application permission engine.
4. User instructions should use the labels shown in the HMS interface.
5. Database or configuration procedures belong in technical documentation, not the ordinary user manual.
6. Update documentation when a module, workflow, permission, report or navigation item changes.

## Current top-level navigation

The current application sidebar exposes these functional areas according to the signed-in user's permissions:

- Dashboard
- Front Desk / Reception
- Clinical
- Laboratory
- Radiology
- Pharmacy
- Maternity
- Finance & Billing Administration
- Procurement
- Finance
- Administration
- Staff / Staff Leave

Administration is a super-user area. Staff Leave is a separate Staff function and is not part of Administration.

## Permission model

HMS supports per-user module/action access using the Access Rights facility. The action model is:

- View
- Create
- Edit
- Delete
- Approve

A super user has unrestricted access. Normal users should receive only the rights assigned to their account. Any remaining legacy/default behavior must be treated as a compatibility mechanism until explicit access assignments are established and verified.

## Documentation lifecycle

Use the following status model when maintaining this documentation:

**Planned → Applied → Committed → CI Verified → Reviewed → Merged → Post-merge Verified → UAT Complete**

Documentation should never describe a change as complete merely because code has been drafted.
