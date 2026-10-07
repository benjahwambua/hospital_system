# HMS Security Baseline

## Authentication and authorization
- Require authenticated sessions for protected pages.
- Enforce module/action permissions server-side; sidebar visibility is not a security boundary.
- Keep administrative approval actions restricted to authorized roles.
- Rotate administrative credentials and do not share accounts.

## Input and database safety
- Use prepared statements for user-controlled values.
- Validate identifiers, dates, quantities, monetary amounts, and status transitions server-side.
- Use transactions and row locks for financial operations where concurrency could corrupt balances.
- Never trust posted names, prices, stock identifiers, invoice totals, or permission values when authoritative values can be loaded from the database.

## State-changing requests
- Use POST for mutations.
- Require and verify CSRF tokens.
- Make callbacks/webhooks idempotent and validate identifiers before applying financial effects.

## Financial integrity
- Reject overpayments and over-refunds.
- Preserve original payments and record refunds as separate transactions.
- Prevent duplicate M-Pesa receipt/reference records.
- Audit critical payment, refund, approval, stock, and administrative actions.

## Production controls
- Use environment-specific configuration and secrets outside source control.
- Use HTTPS, secure cookies, security headers, least-privilege database credentials, and restricted filesystem permissions.
- Do not expose SQL errors, stack traces, credentials, tokens, or internal paths to end users.
- Keep backups encrypted and access-controlled.

## Release checklist
- PHP syntax/CI checks pass.
- Database migrations are reviewed and backed up.
- Critical workflows pass smoke tests.
- Unauthorized access tests pass.
- Backup restoration has been tested.
- Audit logging is operational.
