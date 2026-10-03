@echo off
setlocal EnableExtensions EnableDelayedExpansion

REM =========================================================
REM Windows Setup + Audit Script for Hospital Management System
REM Usage: scripts\windows_setup_test.bat
REM =========================================================

set "PROJ_DIR=%~dp0.."
for %%I in ("%PROJ_DIR%") do set "PROJ_DIR=%%~fI"
set "XAMPP_DIR=C:\xampp"
set "PHP_EXE=%XAMPP_DIR%\php\php.exe"
set "MYSQL_EXE=%XAMPP_DIR%\mysql\bin\mysql.exe"
set "CONFIG_FILE=%PROJ_DIR%\config\config.php"

REM -----------------------------------------------------------------
REM Step 1: Verify XAMPP installation
REM -----------------------------------------------------------------
if not exist "%XAMPP_DIR%\apache\bin\httpd.exe" (
    echo [ERROR] XAMPP was not found at %XAMPP_DIR%
    echo Please install XAMPP and make sure Apache + MySQL are available.
    exit /b 1
)

if not exist "%PHP_EXE%" (
    echo [ERROR] PHP executable not found: %PHP_EXE%
    exit /b 1
)

if not exist "%MYSQL_EXE%" (
    echo [ERROR] MySQL executable not found: %MYSQL_EXE%
    exit /b 1
)

REM -----------------------------------------------------------------
REM Step 2: Check if Apache/MySQL are running
REM -----------------------------------------------------------------
REM XAMPP service start can be performed with xampp_start.exe if desired.
REM You can also start Apache/MySQL from XAMPP Control Panel manually.

if not exist "%XAMPP_DIR%\xampp_start.exe" (
    echo [WARN] xampp_start.exe not found. Please start Apache and MySQL manually.
) else (
    echo [INFO] XAMPP control utility found. If Apache/MySQL are not running, start them now.
)

REM -----------------------------------------------------------------
REM Step 3: Confirm project path
REM -----------------------------------------------------------------
if not exist "%PROJ_DIR%\hms_db.sql" (
    echo [ERROR] Project SQL file not found: %PROJ_DIR%\hms_db.sql
    echo Make sure this script is inside the repo scripts\ folder.
    exit /b 1
)

REM -----------------------------------------------------------------
REM Step 4: Create test database if needed
REM -----------------------------------------------------------------
set "DB_NAME=hms_db_test"

echo [INFO] Creating database %DB_NAME% if it does not exist...
"%MYSQL_EXE%" -u root -e "CREATE DATABASE IF NOT EXISTS %DB_NAME%;"
if errorlevel 1 (
    echo [ERROR] Could not connect to MySQL as root.
    echo Make sure MySQL is running and the root account is available.
    echo You may need to set a password or use a different local setup.
    exit /b 1
)

REM -----------------------------------------------------------------
REM Step 5: Import schema
REM -----------------------------------------------------------------
echo [INFO] Importing default schema...
"%MYSQL_EXE%" -u root %DB_NAME% < "%PROJ_DIR%\hms_db.sql"
if errorlevel 1 (
    echo [ERROR] Schema import failed.
    exit /b 1
)

REM -----------------------------------------------------------------
REM Step 6: Import test seed data
REM -----------------------------------------------------------------
if exist "%PROJ_DIR%\tests\seeds\hms_db_test_seed.sql" (
    echo [INFO] Importing seed data...
    "%MYSQL_EXE%" -u root %DB_NAME% < "%PROJ_DIR%\tests\seeds\hms_db_test_seed.sql"
    if errorlevel 1 (
        echo [ERROR] Seed data import failed.
        exit /b 1
    )
) else (
    echo [WARN] Seed file not found: %PROJ_DIR%\tests\seeds\hms_db_test_seed.sql
)

REM -----------------------------------------------------------------
REM Step 7: Configure database name in config.php automatically
REM -----------------------------------------------------------------
if exist "%CONFIG_FILE%" (
    echo [INFO] Updating config.php to use %DB_NAME%
    powershell -NoProfile -ExecutionPolicy Bypass -Command "$p = '%CONFIG_FILE%'; $c = Get-Content -Path $p; $c = $c -replace "\$db_name\s*=\s*'[^']*'", "\$db_name = 'hms_db_test'"; Set-Content -Path $p -Value $c;"
) else (
    echo [WARN] config.php not found at %CONFIG_FILE%
    echo You will need to edit the database settings manually.
)

REM -----------------------------------------------------------------
REM Step 8: Run audit script
REM -----------------------------------------------------------------
if exist "%PROJ_DIR%\tests\audit\billing_reconciliation_audit.php" (
    echo [INFO] Running billing audit...
    "%PHP_EXE%" "%PROJ_DIR%\tests\audit\billing_reconciliation_audit.php"
    echo.
    echo [INFO] Audit exit code: %ERRORLEVEL%
) else (
    echo [WARN] Billing audit script not found.
)

REM -----------------------------------------------------------------
REM Step 9: Run functional validation
REM -----------------------------------------------------------------
if exist "%PROJ_DIR%\tests\functional\BasicFunctionalTest.php" (
    echo [INFO] Running functional test suite...
    "%PHP_EXE%" "%PROJ_DIR%\tests\functional\BasicFunctionalTest.php"
    echo.
    echo [INFO] Functional test exit code: %ERRORLEVEL%
) else (
    echo [WARN] Functional test script not found.
)

REM -----------------------------------------------------------------
REM Step 10: Final message
REM -----------------------------------------------------------------
echo.
echo =============================================================
echo Hospital Management System test setup is complete.
echo Open the app here:
echo http://localhost/hospital_system/auth/login.php
echo.
echo If you used the seeded database, try:
echo Username: admin
echo Password: admin123
echo.
echo If login fails, check your DB credentials and app config.
echo =============================================================

exit /b 0
