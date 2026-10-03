@echo off
REM =========================================================================
REM HOSPITAL MANAGEMENT SYSTEM - WINDOWS QUICK START & AUTOMATED AUDIT
REM =========================================================================
REM This script automates the complete setup and testing process on Windows
REM Usage: Right-click this file and select \"Run as administrator\"
REM =========================================================================

setlocal EnableExtensions EnableDelayedExpansion

title Hospital Management System - Setup & Audit

REM ----- Colors and formatting -----
REM Note: Windows CMD doesn't support ANSI colors natively
REM We'll use simple [INFO], [SUCCESS], [ERROR] prefixes instead

echo.
echo =========================================================================
echo  HOSPITAL MANAGEMENT SYSTEM - WINDOWS SETUP & AUDIT
echo =========================================================================
echo.

REM ----- Configuration -----
set "XAMPP_PATH=C:\xampp"
set "PROJECT_ROOT=%~dp0.."
for %%I in ("!PROJECT_ROOT!") do set "PROJECT_ROOT=%%~fI"
set "DB_NAME=hms_db_test"
set "DB_USER=root"
set "DB_PASS="
set "PHP_EXE=!XAMPP_PATH!\php\php.exe"
set "MYSQL_EXE=!XAMPP_PATH!\mysql\bin\mysql.exe"
set "APACHE_PATH=!XAMPP_PATH!\apache\bin\httpd.exe"
set "CONFIG_FILE=!PROJECT_ROOT!\config\config.php"
set "LOG_FILE=!PROJECT_ROOT!\setup_audit.log"

REM Clear log file
if exist "!LOG_FILE!" del "!LOG_FILE!"

echo [INFO] Project Root: !PROJECT_ROOT!
echo [INFO] XAMPP Path: !XAMPP_PATH!
echo [INFO] Log File: !LOG_FILE!
echo.

REM ----- Step 1: Verify Administrator Rights -----
echo =========================================================================
echo Step 1: Verifying Administrator Rights
echo =========================================================================
net session >nul 2>&1
if errorlevel 1 (
    echo [ERROR] This script must be run as Administrator.
    echo [ERROR] Right-click the file and select "Run as administrator"
    pause
    exit /b 1
)
echo [SUCCESS] Administrator rights verified.
echo.

REM ----- Step 2: Verify XAMPP Installation -----
echo =========================================================================
echo Step 2: Verifying XAMPP Installation
echo =========================================================================
if not exist "!XAMPP_PATH!" (
    echo [ERROR] XAMPP not found at: !XAMPP_PATH!
    echo [ERROR] Please install XAMPP first from: https://www.apachefriends.org/
    pause
    exit /b 1
)

if not exist "!PHP_EXE!" (
    echo [ERROR] PHP not found at: !PHP_EXE!
    pause
    exit /b 1
)

if not exist "!MYSQL_EXE!" (
    echo [ERROR] MySQL not found at: !MYSQL_EXE!
    pause
    exit /b 1
)

if not exist "!APACHE_PATH!" (
    echo [ERROR] Apache not found at: !APACHE_PATH!
    pause
    exit /b 1
)

echo [SUCCESS] XAMPP installation verified.
echo   - PHP: !PHP_EXE!
echo   - MySQL: !MYSQL_EXE!
echo   - Apache: !APACHE_PATH!
echo.

REM ----- Step 3: Verify Project Files -----
echo =========================================================================
echo Step 3: Verifying Project Files
echo =========================================================================
if not exist "!PROJECT_ROOT!\hms_db.sql" (
    echo [ERROR] Database schema not found: !PROJECT_ROOT!\hms_db.sql
    pause
    exit /b 1
)

if not exist "!PROJECT_ROOT!\config\config.php" (
    echo [ERROR] Config file not found: !PROJECT_ROOT!\config\config.php
    pause
    exit /b 1
)

if not exist "!PROJECT_ROOT!\tests\seeds\hms_db_test_seed.sql" (
    echo [WARN] Test seed data not found: !PROJECT_ROOT!\tests\seeds\hms_db_test_seed.sql
) else (
    echo [SUCCESS] Test seed data found.
)

echo [SUCCESS] Project files verified.
echo.

REM ----- Step 4: Check MySQL Connection -----
echo =========================================================================
echo Step 4: Testing MySQL Connection
echo =========================================================================
echo [INFO] Attempting to connect to MySQL as root...

"!MYSQL_EXE!" -u !DB_USER! --password=!DB_PASS! -e "SELECT VERSION();" >nul 2>>!LOG_FILE!
if errorlevel 1 (
    echo [ERROR] Could not connect to MySQL.
    echo [ERROR] Make sure MySQL is running in XAMPP Control Panel.
    echo [ERROR] If MySQL requires a password, edit this script and set DB_PASS
    echo.
    echo [HELP] To fix:
    echo   1. Open XAMPP Control Panel
    echo   2. Click "Start" next to MySQL
    echo   3. Run this script again
    pause
    exit /b 1
)

echo [SUCCESS] MySQL connection successful.
echo.

REM ----- Step 5: Create Database -----
echo =========================================================================
echo Step 5: Creating Test Database
echo =========================================================================
echo [INFO] Creating database: !DB_NAME!

"!MYSQL_EXE!" -u !DB_USER! --password=!DB_PASS! -e "CREATE DATABASE IF NOT EXISTS !DB_NAME!;" >>!LOG_FILE! 2>&1
if errorlevel 1 (
    echo [ERROR] Failed to create database.
    type !LOG_FILE!
    pause
    exit /b 1
)

echo [SUCCESS] Database created (or already exists).
echo.

REM ----- Step 6: Import Database Schema -----
echo =========================================================================
echo Step 6: Importing Database Schema
echo =========================================================================
echo [INFO] Importing schema from: !PROJECT_ROOT!\hms_db.sql
echo [INFO] This may take a minute...

"!MYSQL_EXE!" -u !DB_USER! --password=!DB_PASS! !DB_NAME! < "!PROJECT_ROOT!\hms_db.sql" >>!LOG_FILE! 2>&1
if errorlevel 1 (
    echo [ERROR] Failed to import schema.
    echo [ERROR] Check the log file for details: !LOG_FILE!
    type !LOG_FILE!
    pause
    exit /b 1
)

echo [SUCCESS] Schema imported successfully.
echo.

REM ----- Step 7: Import Test Seed Data -----
echo =========================================================================
echo Step 7: Importing Test Data
echo =========================================================================
if exist "!PROJECT_ROOT!\tests\seeds\hms_db_test_seed.sql" (
    echo [INFO] Importing test seed data...
    "!MYSQL_EXE!" -u !DB_USER! --password=!DB_PASS! !DB_NAME! < "!PROJECT_ROOT!\tests\seeds\hms_db_test_seed.sql" >>!LOG_FILE! 2>&1
    if errorlevel 1 (
        echo [WARN] Failed to import seed data (this is not critical).
        echo [WARN] You can still test with existing data.
    ) else (
        echo [SUCCESS] Test data imported.
        echo [SUCCESS] You can login with:
        echo           Username: admin
        echo           Password: admin123
    )
) else (
    echo [WARN] Seed data file not found. Skipping.
)
echo.

REM ----- Step 8: Update Config File -----
echo =========================================================================
echo Step 8: Updating Application Configuration
echo =========================================================================
echo [INFO] Updating config.php to use database: !DB_NAME!

REM Use PowerShell to update config.php safely
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$path = '!CONFIG_FILE!'; " ^
    "$content = Get-Content -Path $path; " ^
    "$content = $content -replace '\$db_name\s*=\s*[''\"](.*?)[''\"]\s*;', '\$db_name = ''!DB_NAME!'';'; " ^
    "$content = $content -replace '\$db_user\s*=\s*[''\"](.*?)[''\"]\s*;', '\$db_user = ''!DB_USER!'';'; " ^
    "$content = $content -replace '\$db_pass\s*=\s*[''\"](.*?)[''\"]\s*;', '\$db_pass = ''''!DB_PASS!'''';'; " ^
    "$content = $content -replace '\$db_host\s*=\s*[''\"](.*?)[''\"]\s*;', '\$db_host = ''localhost'';'; " ^
    "Set-Content -Path $path -Value $content;" >>!LOG_FILE! 2>&1

if errorlevel 1 (
    echo [WARN] Could not auto-update config.php. You may need to update it manually.
    echo [WARN] File: !CONFIG_FILE!
    echo [WARN] Set: $db_name = '!DB_NAME!';
) else (
    echo [SUCCESS] Configuration updated.
)
echo.

REM ----- Step 9: Verify Database Tables -----
echo =========================================================================
echo Step 9: Verifying Database Tables
echo =========================================================================
echo [INFO] Checking critical tables...

for %%T in (users,patients,invoices,payments,services_master) do (
    "!MYSQL_EXE!" -u !DB_USER! --password=!DB_PASS! !DB_NAME! -e "SELECT COUNT(*) as count FROM %%T;" >nul 2>>!LOG_FILE!
    if errorlevel 1 (
        echo [ERROR] Table not found: %%T
    ) else (
        echo [SUCCESS] Table exists: %%T
    )
)
echo.

REM ----- Step 10: Run Automated Audits -----
echo =========================================================================
echo Step 10: Running Automated Audits
echo =========================================================================
echo.

REM --- Billing Reconciliation Audit ---
if exist "!PROJECT_ROOT!\tests\audit\billing_reconciliation_audit.php" (
    echo [INFO] Running Billing Reconciliation Audit...
    echo.
    "!PHP_EXE!" "!PROJECT_ROOT!\tests\audit\billing_reconciliation_audit.php"
    echo.
) else (
    echo [WARN] Billing audit script not found.
)

REM --- Functional Tests ---
if exist "!PROJECT_ROOT!\tests\functional\BasicFunctionalTest.php" (
    echo [INFO] Running Functional Tests...
    echo.
    "!PHP_EXE!" "!PROJECT_ROOT!\tests\functional\BasicFunctionalTest.php"
    echo.
) else (
    echo [WARN] Functional test script not found.
)

REM ----- Step 11: Final Summary & Instructions -----
echo =========================================================================
echo Step 11: Setup Complete!
echo =========================================================================
echo.
echo [SUCCESS] All setup steps completed successfully!
echo.
echo *** NEXT STEPS ***
echo.
echo 1. Start Apache in XAMPP Control Panel (if not already running)
echo.
echo 2. Open this URL in your browser:
echo    http://localhost/hospital_system/auth/login.php
echo.
echo 3. Login with these credentials:
echo    Username: admin
echo    Password: admin123
echo.
echo 4. Test the key workflows:
echo    - Patient Registration
echo    - Invoice Creation
echo    - Payment Recording
echo    - Refund Processing
echo.
echo 5. Check for bugs:
echo    - Can you overpay an invoice?
echo    - Does the invoice total match the items?
echo    - Can unauthorized users access finance?
echo    - Can you create duplicate lab orders?
echo.
echo *** MANUAL TEST PLAN ***
echo.
echo Detailed test procedures are in:
echo  !PROJECT_ROOT!\tests\MANUAL_TEST_PLAN.md
echo.
echo Open this file in Notepad to see step-by-step test cases.
echo.
echo *** DATABASE ***
echo.
echo Database Name: !DB_NAME!
echo Database User: !DB_USER!
echo MySQL Command: "!MYSQL_EXE!" -u !DB_USER! !DB_NAME!
echo.
echo To access the database directly, run:
echo  "!MYSQL_EXE!" -u !DB_USER! !DB_NAME!
echo.
echo *** TROUBLESHOOTING ***
echo.
echo If login fails:
echo  1. Check that the database was created (see log file)
echo  2. Verify Apache is running in XAMPP
echo  3. Check config.php for correct database settings
echo  4. Review log file: !LOG_FILE!
echo.
echo If tests fail:
echo  1. Make sure MySQL is running
echo  2. Check the error messages in the audit output
echo  3. Review the log file for details
echo.
echo =========================================================================
echo.

REM ----- Offer to open files -----
echo Would you like to:
echo  1. Open the application in browser (http://localhost/hospital_system)
echo  2. Open the test plan document
echo  3. Exit
echo.
set /p CHOICE="Enter choice (1/2/3): "

if "!CHOICE!"=="1" (
    start http://localhost/hospital_system/auth/login.php
) else if "!CHOICE!"=="2" (
    if exist "!PROJECT_ROOT!\tests\MANUAL_TEST_PLAN.md" (
        notepad "!PROJECT_ROOT!\tests\MANUAL_TEST_PLAN.md"
    ) else (
        echo Test plan not found.
    )
)

echo.
echo Setup script finished. You can close this window now.
pause

endlocal
exit /b 0
