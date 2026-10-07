# HMS Backup and Restore

## Minimum backup policy
- Database: daily full backup, plus backups before migrations and major releases.
- Application: retain the deployed release archive and configuration separately from the database backup.
- Keep at least one backup copy outside the production server.
- Periodically test restoration; an untested backup is not a verified recovery plan.

## MySQL backup
Example secure server command:

mysqldump --single-transaction --routines --triggers -u HMS_DB_USER -p hms_db > hms_db_YYYY-MM-DD.sql

For XAMPP/Windows, phpMyAdmin can be used for export when shell access is unavailable. Store SQL backups outside the public web directory.

## Restore
1. Stop or restrict HMS writes.
2. Create a safety backup of the current database if possible.
3. Create/select the target database.
4. Import the verified SQL backup.
5. Apply only migrations newer than the backup, in documented order.
6. Run smoke tests before reopening the system.

## Recovery test
Periodically restore a backup into a separate test database and verify authentication plus patient, clinical, pharmacy, laboratory, billing, and reporting data.

Never test restore procedures against the live production database.
