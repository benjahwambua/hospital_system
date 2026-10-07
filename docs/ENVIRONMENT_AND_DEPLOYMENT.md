# HMS Environment & Deployment Guide

## 1. Current environment model

HMS is a traditional PHP/MySQL web application. The supported local development model is Apache + PHP + MySQL, with XAMPP used as the current local development environment.

Docker is **not currently provided as part of the HMS repository**. This is intentional for the current development setup; Docker is a future optional reproducibility enhancement, not a prerequisite for running HMS locally.

## 2. Configuration separation

The repository provides `config/.env.example` as a production configuration template. It documents the database variables and optional application base URL expected by deployment tooling.

Production values must be supplied outside source control. Real database passwords, API credentials and other secrets must never be committed to Git.

Current documented variables include:

| Variable | Purpose |
|---|---|
| `HMS_DB_HOST` | Production database host |
| `HMS_DB_USER` | Dedicated application database user |
| `HMS_DB_PASS` | Application database password |
| `HMS_DB_NAME` | HMS database name |
| `HMS_BASE_URL` | Optional deployed application base URL |

For production, use a dedicated database account with only the privileges required by HMS. Do not use MySQL `root`.

## 3. Local XAMPP development

The current local model is:

```text
Windows
  |
  +-- XAMPP
       +-- Apache
       +-- PHP 8.x
       +-- MySQL/MariaDB
       |
       +-- C:\xampp\htdocs\hospital_system
```

Local development should use non-production credentials and a development/test database.

## 4. CI

HMS has GitHub Actions continuous integration through:

```text
.github/workflows/php-lint.yml
```

The `PHP Syntax Audit` workflow runs PHP syntax validation across the repository. Pull requests targeting `main` must pass the configured required checks.

CI is a validation gate; it is not a production deployment mechanism.

## 5. Deployment

There is currently **no automated continuous deployment (CD) pipeline**. Production deployment remains a controlled manual operation following `docs/DEPLOYMENT.md`.

The current release path is:

```text
Local development
      ↓
Feature/fix branch
      ↓
Pull request
      ↓
GitHub Actions CI
      ↓
main
      ↓
Controlled deployment
      ↓
Production smoke tests / UAT
```

Do not interpret a successful CI run as proof that the deployed application is operational.

## 6. Database migrations

Database changes must be reviewed before deployment. Production database backup should be taken before migrations or other release operations.

Migrations are currently controlled operationally rather than executed by an automated deployment pipeline.

## 7. Future deployment maturity

The recommended future progression is:

1. Add a dedicated staging environment.
2. Add automated database/schema validation in CI.
3. Add automated application smoke and permission tests.
4. Add controlled deployment to staging.
5. Run documented UAT against staging.
6. Add production deployment with an explicit approval gate.
7. Add automated post-deployment health checks and rollback support.

For HMS, controlled deployment and reliable testing are higher priority than introducing Docker solely for checklist completeness.

## 8. Docker roadmap

Docker may be introduced later to provide a reproducible development environment for contributors and CI. If introduced, it should initially coexist with the established XAMPP workflow rather than unexpectedly replacing it.

Any Docker implementation must document:

- PHP version and extensions
- Apache/web-server configuration
- database version
- persistent database storage
- application configuration/secrets
- migration process
- local mail/payment integration strategy
- backup and restore behavior

## 9. Backup and recovery

Production backup automation is not the same as source-code CI. Backups should be scheduled independently, retained according to operational policy, stored securely, and periodically restored in a non-production environment.

See `docs/BACKUP_RESTORE.md` for the current procedure.

## 10. Operational status

| Capability | Current status |
|---|---|
| XAMPP local development | Implemented |
| Traditional PHP/MySQL deployment | Implemented/documented |
| Docker | Not implemented; optional future enhancement |
| GitHub Actions CI | Implemented |
| Automated CD | Not implemented |
| `.env.example` configuration template | Implemented |
| Production secret store | Not yet automated |
| Automated migration execution | Not implemented |
| Automated smoke tests | Future |
| Automated security/permission tests | Future |
| Automated production backup | Future operational improvement |
| Scheduled restore testing | Required operational practice |
| Production monitoring/health checks | Future |

