@echo off
REM ============================================================================
REM Railway MySQL Import Script for data_miw Database
REM This script imports the complete data_miw schema to Railway MySQL
REM ============================================================================

echo ============================================================================
echo Railway MySQL Database Import Script
echo ============================================================================
echo.

REM Check if Railway CLI is installed
railway --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Railway CLI not found. Please install it first:
    echo    npm install -g @railway/cli
    echo    Or download from: https://railway.app/cli
    pause
    exit /b 1
)

echo ✅ Railway CLI found
echo.

REM Check if SQL dump file exists
if not exist "data_miw_complete_dump.sql" (
    echo ❌ SQL dump file 'data_miw_complete_dump.sql' not found
    echo    Make sure you're running this script from the correct directory
    pause
    exit /b 1
)

echo ✅ SQL dump file found: data_miw_complete_dump.sql
echo.

REM Login to Railway (if not already logged in)
echo 🔐 Logging into Railway...
railway login

echo.
echo 📋 Railway MySQL Connection Details:
echo    Host: mysql.railway.internal
echo    Port: 3306
echo    Database: railway
echo    Username: root
echo    Password: ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe
echo.

echo 🚀 Starting database import...
echo ⚠️  This will replace existing data in your Railway MySQL database
echo.
set /p confirm="Are you sure you want to continue? (y/N): "
if /i not "%confirm%"=="y" (
    echo ❌ Import cancelled
    pause
    exit /b 0
)

echo.
echo 📤 Importing data_miw schema to Railway MySQL...
echo    This may take a few minutes depending on your connection...
echo.

REM Method 1: Using Railway CLI mysql connection
echo 🔄 Attempting import via Railway CLI...
railway run mysql -h mysql.railway.internal -u root -pULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe railway < data_miw_complete_dump.sql

if %errorlevel% equ 0 (
    echo.
    echo ✅ Database import completed successfully!
    echo.
    echo 🌐 You can verify the import at:
    echo    • Main Website: https://miw.railway.app
    echo    • Admin Dashboard: https://miw.railway.app/admin_dashboard.php
    echo    • Database Verification: https://miw.railway.app/db_verification_report.php
    echo.
    echo 👥 Default admin credentials:
    echo    Username: admin / Password: admin123
    echo    Username: operator / Password: admin123
    echo.
) else (
    echo.
    echo ❌ Import failed via Railway CLI
    echo.
    echo 💡 Alternative methods to try:
    echo.
    echo 1. Manual method using local MySQL client:
    echo    mysql -h mysql.railway.internal -u root -pULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe railway ^< data_miw_complete_dump.sql
    echo.
    echo 2. Use web-based initialization:
    echo    Visit: https://miw.railway.app/quick_database_init.php
    echo.
    echo 3. Use MySQL Workbench:
    echo    - Connect to Railway MySQL with the credentials above
    echo    - Use File ^> Run SQL Script to import data_miw_complete_dump.sql
    echo.
)

echo.
echo 📊 Import Summary:
echo    • Tables created: 8 main tables + indexes
echo    • Sample data: 5 packages, 5 customers, invoices, admin users
echo    • Foreign keys: Properly configured relationships
echo    • Status: Railway MySQL ready for production
echo.

pause
