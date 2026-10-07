# HMS Production Configuration

The application reads database settings from environment variables:

- HMS_DB_HOST
- HMS_DB_USER
- HMS_DB_PASS
- HMS_DB_NAME

See `config/.env.example` for the variable names.

For production:
1. Create a dedicated database user; do not use MySQL `root`.
2. Store the password in the server/container secret store.
3. Set the four HMS_DB_* variables before starting Apache/PHP.
4. Verify database connectivity before enabling public access.
5. Never commit real credentials to Git.

The PHP application retains localhost/root/hms_db fallbacks for existing XAMPP installations, but production deployments must provide the environment variables.
