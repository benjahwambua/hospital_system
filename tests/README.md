# Hospital Management System - Test Environment Setup & Audit Suite

## Quick Start

This guide will set up a fully functional test environment for the HMS on your local machine using XAMPP.

### Prerequisites
- **XAMPP** (Apache + MySQL + PHP) - [Download](https://www.apachefriends.org/)
- **Git** (for cloning the repo)
- **Composer** (optional, for PHPUnit)
- **Postman** or **cURL** (for API testing)

### Step 1: Install & Start XAMPP

```bash
# Download and install XAMPP for your OS
# On Mac/Linux: brew install xampp
# On Windows: Download installer from official site

# Start Apache and MySQL
# You'll see green start buttons in the XAMPP Control Panel
```

### Step 2: Clone the Repository

```bash
cd /path/to/xampp/htdocs
git clone https://github.com/benjahwambua/hospital_system.git
cd hospital_system
git checkout security-audit-fixes
```

### Step 3: Set Up the Database

```bash
# Open phpMyAdmin in your browser
# http://localhost/phpmyadmin

# Create a new database
# Name: hms_db_test
# Collation: utf8mb4_general_ci

# Import the seeded test data
# File > Import > Choose tests/seeds/hms_db_test_seed.sql

# Verify the database is loaded
# You should see tables like: users, patients, invoices, payments, etc.
```

Alternatively, from command line:

```bash
mysql -u root -p < hms_db.sql
mysql -u root -p < tests/seeds/hms_db_test_seed.sql
```

### Step 4: Configure Application

**Edit `config/config.php`:**

```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Adjust if your MySQL has a password
$db_name = 'hms_db_test'; // Use test database
```

### Step 5: Verify Installation

Open your browser:
- **Home**: http://localhost/hospital_system
- **Login**: http://localhost/hospital_system/auth/login.php

**Test login credentials** (from seed data):
- Username: `admin`
- Password: `admin123`

---

## Running Tests

### Manual Testing (Browser-based)

See `tests/MANUAL_TEST_PLAN.md` for detailed step-by-step testing procedures.

```bash
cd tests
open MANUAL_TEST_PLAN.md  # macOS
cat MANUAL_TEST_PLAN.md   # Linux
type MANUAL_TEST_PLAN.md  # Windows
```

### Automated Testing with PHP Unit Tests

```bash
# Install PHPUnit (if not already installed)
composer require phpunit/phpunit --dev

# Run all tests
php vendor/bin/phpunit tests/unit/

# Run specific test suite
php vendor/bin/phpunit tests/unit/BillingTest.php

# Run with detailed output
php vendor/bin/phpunit tests/unit/ --verbose
```

### API Testing with Postman

Import the collection:
- File: `tests/postman/HMS_API_Tests.postman_collection.json`
- Environment: `tests/postman/HMS_Test_Environment.postman_environment.json`

Run the collection in Postman Runner to execute all API tests.

### Command-Line Testing

```bash
# Run the functional test suite
php tests/functional/run_tests.php

# Run the billing audit
php tests/audit/billing_reconciliation_audit.php

# Run the security audit
php tests/audit/security_audit.php
```

---

## Test Coverage

### 1. **Authentication & Authorization**
- Login validation (valid/invalid credentials)
- CSRF token validation
- Session management
- Role-based access control
- Module access restrictions

### 2. **Patient Management**
- Full patient registration
- Walk-in patient registration
- Duplicate patient prevention
- Patient data validation
- Gender-based service restrictions (maternity)

### 3. **Billing & Invoicing**
- Invoice creation and item addition
- Invoice total reconciliation
- Payment recording (Cash, M-Pesa, Other)
- Partial and full payment handling
- Overpayment prevention
- Refund processing
- Payment history audit

### 4. **Clinical Orders**
- Service order creation
- Duplicate order prevention
- Lab order validation
- Radiology order validation
- Pharmacy order validation
- Order status transitions

### 5. **Financial Integrity**
- Invoice total vs. item total reconciliation
- Payment amount validation
- Refund deduction from balance
- Aged receivables reporting
- Daily collection reconciliation

### 6. **M-Pesa Integration** (if configured)
- M-Pesa callback handling
- Receipt number duplicate prevention
- Amount validation
- Transaction state management

### 7. **Data Security**
- SQL Injection prevention
- XSS prevention
- CSRF protection
- Password hashing verification
- Sensitive data logging restrictions

---

## Interpreting Test Results

### Unit Tests
```
OK (42 tests, 0 assertions)
```
✅ All tests passed.

```
FAILURES! Failures: 2, Errors: 1, Skipped: 0, Incomplete: 0
```
❌ Review failed tests. Fix issues before deployment.

### Audit Output
```
✅ PASSED: Invoice total matches item total
⚠️  WARNING: Payment exceeds invoice total by 50 KES
❌ FAILED: Duplicate payment detected
```

---

## Continuous Integration (Optional)

Set up automated testing on GitHub:

```yaml
# .github/workflows/test.yml
name: Run Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: hms_db_test
    steps:
      - uses: actions/checkout@v2
      - name: Run PHPUnit
        run: php vendor/bin/phpunit tests/unit/
```

---

## Troubleshooting

### "Cannot connect to database"
- Check MySQL is running in XAMPP Control Panel
- Verify credentials in `config/config.php`
- Ensure `hms_db_test` database exists

### "Table does not exist"
- Reimport the SQL schema
- Verify all migrations have run
- Check for typos in table names

### "Access denied" errors
- Verify user roles in `users` table
- Check module permissions in `access_modules` table
- Ensure user is assigned correct role

### "Payment validation failed"
- Check invoice total reconciliation first
- Verify no orphaned payments exist
- Review payment history in invoice details

---

## Next Steps

1. **Run all manual tests** (see MANUAL_TEST_PLAN.md)
2. **Execute unit tests** to validate core logic
3. **Review audit reports** for financial discrepancies
4. **Document any failures** in GitHub Issues
5. **Plan remediation** for high-risk findings

---

## Support

For issues or questions:
- Check the test logs in `tests/logs/`
- Review application error log in `error_log`
- Open an issue on GitHub with test output
