@echo off
setlocal enabledelayedexpansion

cls
echo ================================================================
echo              MIW Travel - Railway Management Tool
echo ================================================================
echo.

REM Check if Railway CLI is installed
where railway >nul 2>&1
if %errorlevel% neq 0 (
    echo [X] Railway CLI is not installed.
    echo.
    echo [INFO] Installing Railway CLI...
    echo Please run: npm install -g @railway/cli
    echo Or download from: https://docs.railway.app/develop/cli
    echo.
    echo Attempting to install via npm...
    npm install -g @railway/cli
    if %errorlevel% neq 0 (
        echo [X] Failed to install Railway CLI
        echo Please install manually and run this script again
        pause
        exit /b 1
    )
)

echo [✓] Railway CLI is available
echo.

:MAIN_MENU
cls
echo ================================================================
echo              MIW Travel - Railway Management Tool
echo ================================================================
echo.
echo [1] Connect to Railway Project
echo [2] Deploy Application
echo [3] View Application Logs
echo [4] Open Application in Browser
echo [5] Check Project Status
echo [6] Manage Environment Variables
echo [7] Database Management
echo [8] File System Check
echo [9] Emergency Diagnostics
echo [0] Exit
echo.
set /p choice="Select an option (0-9): "

if "%choice%"=="1" goto CONNECT
if "%choice%"=="2" goto DEPLOY
if "%choice%"=="3" goto LOGS
if "%choice%"=="4" goto OPEN
if "%choice%"=="5" goto STATUS
if "%choice%"=="6" goto VARIABLES
if "%choice%"=="7" goto DATABASE
if "%choice%"=="8" goto FILESYSTEM
if "%choice%"=="9" goto DIAGNOSTICS
if "%choice%"=="0" goto EXIT
goto MAIN_MENU

:CONNECT
echo.
echo [CONNECTING TO RAILWAY PROJECT]
echo ----------------------------------------------------------------
echo Logging into Railway...
railway login

echo.
echo Linking to your MIW project...
railway link 2725c7e0-071b-43ea-9be7-33142b967d77

if %errorlevel% equ 0 (
    echo [✓] Successfully connected to Railway project
) else (
    echo [X] Failed to connect to project
)
pause
goto MAIN_MENU

:DEPLOY
echo.
echo [DEPLOYING APPLICATION]
echo ----------------------------------------------------------------
echo Current status:
railway status

echo.
echo Deploying latest changes...
railway up

if %errorlevel% equ 0 (
    echo [✓] Deployment successful!
    set /p open_app="Open application in browser? (y/n): "
    if /i "!open_app!"=="y" railway open
) else (
    echo [X] Deployment failed
    echo Checking logs...
    railway logs --tail
)
pause
goto MAIN_MENU

:LOGS
echo.
echo [APPLICATION LOGS]
echo ----------------------------------------------------------------
echo Fetching latest logs...
railway logs --tail
pause
goto MAIN_MENU

:OPEN
echo.
echo [OPENING APPLICATION]
echo ----------------------------------------------------------------
railway open
echo Application opened in your browser
pause
goto MAIN_MENU

:STATUS
echo.
echo [PROJECT STATUS]
echo ----------------------------------------------------------------
railway status
echo.
echo Service details:
railway services
pause
goto MAIN_MENU

:VARIABLES
echo.
echo [ENVIRONMENT VARIABLES]
echo ----------------------------------------------------------------
echo Current variables:
railway variables

echo.
echo [VARIABLE MANAGEMENT]
echo [1] Set SMTP Configuration
echo [2] Set Database Configuration
echo [3] Set Application Configuration
echo [4] List all variables
echo [5] Back to main menu
echo.
set /p var_choice="Select option: "

if "%var_choice%"=="1" goto SET_SMTP
if "%var_choice%"=="2" goto SET_DB
if "%var_choice%"=="3" goto SET_APP
if "%var_choice%"=="4" goto LIST_VARS
goto MAIN_MENU

:SET_SMTP
echo.
echo [SMTP CONFIGURATION]
echo ----------------------------------------------------------------
set /p smtp_user="Enter SMTP Username (email): "
set /p smtp_pass="Enter SMTP Password (app password): "
set /p smtp_host="Enter SMTP Host (default: smtp.gmail.com): "
if "%smtp_host%"=="" set smtp_host=smtp.gmail.com

railway variables set SMTP_USERNAME=%smtp_user%
railway variables set SMTP_PASSWORD=%smtp_pass%
railway variables set SMTP_HOST=%smtp_host%
railway variables set SMTP_PORT=587
railway variables set SMTP_ENCRYPTION=tls

echo [✓] SMTP configuration set
pause
goto VARIABLES

:SET_DB
echo.
echo [DATABASE CONFIGURATION]
echo ----------------------------------------------------------------
echo Setting database variables...
railway variables set DB_DRIVER=mysql
railway variables set APP_ENV=production

echo [✓] Database configuration set
pause
goto VARIABLES

:SET_APP
echo.
echo [APPLICATION CONFIGURATION]
echo ----------------------------------------------------------------
railway variables set MAX_FILE_SIZE=10M
railway variables set MAX_EXECUTION_TIME=300
railway variables set SECURE_HEADERS=true
railway variables set UPLOAD_PATH=/app/uploads/

echo [✓] Application configuration set
pause
goto VARIABLES

:LIST_VARS
echo.
echo [ALL VARIABLES]
echo ----------------------------------------------------------------
railway variables
pause
goto VARIABLES

:DATABASE
echo.
echo [DATABASE MANAGEMENT]
echo ----------------------------------------------------------------
echo [1] Connect to MySQL Database
echo [2] View Database Info
echo [3] Initialize Database Tables
echo [4] Back to main menu
echo.
set /p db_choice="Select option: "

if "%db_choice%"=="1" goto DB_CONNECT
if "%db_choice%"=="2" goto DB_INFO
if "%db_choice%"=="3" goto DB_INIT
goto MAIN_MENU

:DB_CONNECT
echo.
echo [CONNECTING TO DATABASE]
echo ----------------------------------------------------------------
railway connect mysql
pause
goto DATABASE

:DB_INFO
echo.
echo [DATABASE INFORMATION]
echo ----------------------------------------------------------------
railway variables | findstr "MYSQL\|DB_"
pause
goto DATABASE

:DB_INIT
echo.
echo [DATABASE INITIALIZATION]
echo ----------------------------------------------------------------
echo Opening database initialization page...
railway open /init_database_railway.php
pause
goto DATABASE

:FILESYSTEM
echo.
echo [FILE SYSTEM CHECK]
echo ----------------------------------------------------------------
echo Opening file system diagnostics...
railway open /railway_diagnostics.php
pause
goto MAIN_MENU

:DIAGNOSTICS
echo.
echo [EMERGENCY DIAGNOSTICS]
echo ----------------------------------------------------------------
echo Running comprehensive diagnostics...

echo.
echo [1] Project Status:
railway status

echo.
echo [2] Recent Logs:
railway logs -n 20

echo.
echo [3] Environment Variables:
railway variables | findstr "APP_ENV\|DB_\|SMTP_"

echo.
echo [4] Services:
railway services

echo.
echo [5] Opening diagnostic page...
railway open /railway_diagnostics.php

pause
goto MAIN_MENU

:EXIT
echo.
echo ================================================================
echo              Thank you for using Railway Management Tool
echo ================================================================
echo.
echo Your MIW Travel application is running on Railway
echo.
echo Useful commands:
echo • railway logs          - View application logs
echo • railway open          - Open your application
echo • railway status        - Check deployment status
echo • railway variables     - Manage environment variables
echo.
pause
exit /b 0
