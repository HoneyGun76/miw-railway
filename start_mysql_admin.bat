@echo off
echo ========================================
echo MIW RAILWAY - MySQL Service Starter
echo ========================================
echo.
echo This script will start the MySQL service for MIW Railway
echo Requires Administrator privileges
echo.
pause

echo Starting MySQL80 service...
net start MySQL80

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ MySQL service started successfully!
    echo.
    echo Next steps:
    echo 1. Test database: php database_connectivity_check.php
    echo 2. Test admin login: admin_login.php
    echo 3. Run final testing: php miw_9phase_testing.php
    echo.
) else (
    echo.
    echo ❌ Failed to start MySQL service
    echo.
    echo Possible solutions:
    echo 1. Run this script as Administrator
    echo 2. Check if MySQL is already running
    echo 3. Use XAMPP Control Panel instead
    echo 4. Restart your computer and try again
    echo.
)

pause
