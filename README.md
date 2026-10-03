# Security and operational hardening

This application now includes several safeguards introduced during the audit process:

- environment-backed database configuration via `.env`
- login throttling and lockout protections
- CSRF validation enhancements
- idle session timeout enforcement
- safer patient search handling using prepared statements
- audit logging hooks for sensitive actions

The smoke test script in `tests/security_smoke.php` validates these invariants before deployment.

## Running the smoke test

```bash
php tests/security_smoke.php
```

This is a lightweight validation layer and does not replace a full staging test or database-backed QA run.
