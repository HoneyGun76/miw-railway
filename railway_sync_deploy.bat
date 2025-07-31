@echo off
setlocal enabledelayedexpansion

cls
echo ================================================================
echo              MIW Travel - Railway Sync & Deploy Tool
echo ================================================================
echo.

REM Check if Railway CLI is installed
where railway >nul 2>&1
if %errorlevel% neq 0 (
    echo [X] Railway CLI is not installed.
    echo.
    echo [INFO] Please install Railway CLI:
    echo 1. Download from: https://docs.railway.app/develop/cli
    echo 2. Or run: npm install -g @railway/cli
    echo.
    pause
    exit /b 1
)

echo [✓] Railway CLI is available
echo.

:MAIN_MENU
cls
echo ================================================================
echo              MIW Travel - Railway Sync & Deploy Tool
echo ================================================================
echo.
echo [1] Sync Railway variables to local .env
echo [2] Deploy to Railway
echo [3] Check Railway status
echo [4] View Railway logs
echo [5] Open Railway app
echo [6] Test local configuration
echo [7] Initialize Railway database
echo [8] Full sync and deploy
echo [0] Exit
echo.
set /p choice="Select an option (0-8): "

if "%choice%"=="1" goto SYNC_ENV
if "%choice%"=="2" goto DEPLOY
if "%choice%"=="3" goto STATUS
if "%choice%"=="4" goto LOGS
if "%choice%"=="5" goto OPEN_APP
if "%choice%"=="6" goto TEST_LOCAL
if "%choice%"=="7" goto INIT_DB
if "%choice%"=="8" goto FULL_SYNC
if "%choice%"=="0" goto EXIT
goto MAIN_MENU

:SYNC_ENV
echo.
echo [SYNCING RAILWAY VARIABLES TO LOCAL]
echo ----------------------------------------------------------------
echo Fetching Railway environment variables...
railway variables > .env.railway
if %errorlevel% equ 0 (
    echo [✓] Railway variables saved to .env.railway
    echo.
    echo Creating local .env file from Railway settings...
    
    REM Create .env file from Railway variables
    echo # Local development environment variables > .env
    echo # Synced from Railway on %date% %time% >> .env
    echo. >> .env
    
    REM Parse Railway variables and convert to .env format
    for /f "tokens=1,2 delims=│" %%a in (.env.railway) do (
        set "line=%%a"
        set "value=%%b"
        REM Remove leading/trailing spaces and special characters
        set "line=!line: =!"
        set "line=!line:║=!"
        set "value=!value: =!"
        set "value=!value:║=!"
        
        REM Skip header lines and empty lines
        if not "!line!"=="" if not "!line:~0,1!"=="═" if not "!line:~0,1!"=="╔" if not "!line:~0,1!"=="╚" (
            echo !line!=!value! >> .env
        )
    )
    
    echo [✓] Local .env file created from Railway variables
    echo [INFO] You can now use the same configuration locally
) else (
    echo [X] Failed to fetch Railway variables
    echo Please ensure you're logged in: railway login
)
pause
goto MAIN_MENU

:DEPLOY
echo.
echo [DEPLOYING TO RAILWAY]
echo ----------------------------------------------------------------
echo Checking current status...
railway status

echo.
echo Deploying application...
railway up

if %errorlevel% equ 0 (
    echo [✓] Deployment successful!
    echo [INFO] Your app is available at: https://miw.up.railway.app
    
    set /p open_app="Open app in browser? (y/n): "
    if /i "!open_app!"=="y" railway open
) else (
    echo [X] Deployment failed
    echo [INFO] Check logs with: railway logs
)
pause
goto MAIN_MENU

:STATUS
echo.
echo [RAILWAY PROJECT STATUS]
echo ----------------------------------------------------------------
railway status
echo.
echo Environment variables:
railway variables
pause
goto MAIN_MENU

:LOGS
echo.
echo [RAILWAY LOGS]
echo ----------------------------------------------------------------
railway logs --tail
pause
goto MAIN_MENU

:OPEN_APP
echo.
echo [OPENING RAILWAY APP]
echo ----------------------------------------------------------------
railway open
echo Application opened in browser
pause
goto MAIN_MENU

:TEST_LOCAL
echo.
echo [TESTING LOCAL CONFIGURATION]
echo ----------------------------------------------------------------
echo Testing local PHP configuration...

php -v
if %errorlevel% neq 0 (
    echo [X] PHP not found. Please ensure XAMPP is running and PHP is in PATH
    pause
    goto MAIN_MENU
)

echo.
echo Testing database connection...
php -r "
require_once 'config.local.php';
if (isset($pdo)) {
    echo 'Database connection: OK' . PHP_EOL;
    echo 'Database type: ' . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
} else {
    echo 'Database connection: FAILED' . PHP_EOL;
}
"

echo.
echo Testing upload directories...
if exist "uploads" (
    echo [✓] Upload directory exists
) else (
    echo [!] Creating upload directory...
    mkdir uploads
    mkdir uploads\documents
    mkdir uploads\payments
    mkdir uploads\photos
    mkdir uploads\cancellations
    echo [✓] Upload directories created
)

echo.
echo [INFO] Local configuration test completed
echo [INFO] You can view debug info at: http://localhost/MIW-Railway/miw-railway/?debug=1
pause
goto MAIN_MENU

:INIT_DB
echo.
echo [INITIALIZING RAILWAY DATABASE]
echo ----------------------------------------------------------------
echo Opening database initialization in browser...
start https://miw.up.railway.app/init_database_railway.php

echo.
echo [INFO] Complete the database initialization in your browser
echo [INFO] This will create all necessary tables for your application
pause
goto MAIN_MENU

:FULL_SYNC
echo.
echo [FULL SYNC AND DEPLOY]
echo ----------------------------------------------------------------
echo Step 1: Syncing Railway variables...
call :SYNC_ENV_SILENT

echo.
echo Step 2: Testing local configuration...
php -r "
require_once 'config.local.php';
if (isset(\$pdo)) {
    echo 'Local config: OK' . PHP_EOL;
} else {
    echo 'Local config: FAILED' . PHP_EOL;
}
"

echo.
echo Step 3: Deploying to Railway...
railway up

if %errorlevel% equ 0 (
    echo.
    echo Step 4: Opening application...
    railway open
    
    echo.
    echo [✓] FULL SYNC COMPLETED SUCCESSFULLY!
    echo ================================================================
    echo Your MIW Travel application is now synced and deployed!
    echo ================================================================
    echo.
    echo Local development: http://localhost/MIW-Railway/miw-railway/
    echo Railway production: https://miw.up.railway.app
    echo.
) else (
    echo [X] Deployment failed during full sync
)
pause
goto MAIN_MENU

:SYNC_ENV_SILENT
railway variables > .env.railway
echo # Local development environment variables > .env
echo # Synced from Railway on %date% %time% >> .env
echo. >> .env
for /f "tokens=1,2 delims=│" %%a in (.env.railway) do (
    set "line=%%a"
    set "value=%%b"
    set "line=!line: =!"
    set "line=!line:║=!"
    set "value=!value: =!"
    set "value=!value:║=!"
    if not "!line!"=="" if not "!line:~0,1!"=="═" if not "!line:~0,1!"=="╔" if not "!line:~0,1!"=="╚" (
        echo !line!=!value! >> .env
    )
)
goto :eof

:EXIT
echo.
echo ================================================================
echo         Thank you for using MIW Railway Sync Tool!
echo ================================================================
echo.
echo Your application is ready for development and production!
echo.
echo Local: http://localhost/MIW-Railway/miw-railway/
echo Railway: https://miw.up.railway.app
echo.
pause
exit
