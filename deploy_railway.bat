@echo off
setlocal enabledelayedexpansion

cls
echo ================================================================
echo              MIW Travel - Railway Deployment Script
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

echo [✓] Railway CLI is installed
echo.

REM Login to Railway
echo [STEP 1] Logging into Railway...
echo ----------------------------------------------------------------
railway login
if %errorlevel% neq 0 (
    echo [X] Railway login failed
    pause
    exit /b 1
)
echo [✓] Successfully logged into Railway
echo.

REM Link to existing project
echo [STEP 2] Linking to your Railway project...
echo ----------------------------------------------------------------
railway link 2725c7e0-071b-43ea-9be7-33142b967d77
if %errorlevel% neq 0 (
    echo [X] Failed to link to Railway project
    echo Please check your project ID: 2725c7e0-071b-43ea-9be7-33142b967d77
    pause
    exit /b 1
)
echo [✓] Successfully linked to Railway project
echo.

REM Check current status
echo [STEP 3] Checking current Railway services...
echo ----------------------------------------------------------------
railway status
echo.

REM Set environment variables
echo [STEP 4] Setting up environment variables...
echo ----------------------------------------------------------------
echo Setting application variables...
railway variables set DB_DRIVER=mysql
railway variables set APP_ENV=production
railway variables set MAX_FILE_SIZE=10M
railway variables set MAX_EXECUTION_TIME=300
railway variables set SECURE_HEADERS=true
railway variables set UPLOAD_PATH=/app/uploads/

echo.
echo [INFO] Email configuration required:
echo Please set these variables manually:
echo ----------------------------------------------------------------
echo railway variables set SMTP_HOST=smtp.gmail.com
echo railway variables set SMTP_USERNAME=your-email@gmail.com
echo railway variables set SMTP_PASSWORD=your-app-password
echo railway variables set SMTP_PORT=587
echo railway variables set SMTP_ENCRYPTION=tls
echo.

set /p email_setup="Do you want to set up email now? (y/n): "
if /i "%email_setup%"=="y" (
    set /p smtp_user="Enter your SMTP username (email): "
    set /p smtp_pass="Enter your SMTP password (app password): "
    
    railway variables set SMTP_USERNAME=!smtp_user!
    railway variables set SMTP_PASSWORD=!smtp_pass!
    railway variables set SMTP_HOST=smtp.gmail.com
    railway variables set SMTP_PORT=587
    railway variables set SMTP_ENCRYPTION=tls
    
    echo [✓] Email configuration set
)

echo.
echo [STEP 5] Preparing Railway-specific configuration...
echo ----------------------------------------------------------------
REM Copy Railway config as primary config
copy "config.railway.php" "config.php" >nul 2>&1
echo [✓] Railway configuration activated

REM Create railway.json for deployment configuration
echo Creating railway.json...
(
echo {
echo   "build": {
echo     "builder": "NIXPACKS"
echo   },
echo   "deploy": {
echo     "healthcheckPath": "/",
echo     "restartPolicyType": "ON_FAILURE",
echo     "restartPolicyMaxRetries": 10
echo   }
echo }
) > railway.json
echo [✓] railway.json created

echo.
echo [STEP 6] Deploying to Railway...
echo ----------------------------------------------------------------
railway up
if %errorlevel% equ 0 (
    echo [✓] Application deployed successfully!
) else (
    echo [X] Deployment failed
    echo Check logs with: railway logs
    pause
    exit /b 1
)

echo.
echo ================================================================
echo                     DEPLOYMENT COMPLETED!
echo ================================================================
echo.
echo [SUCCESS] Your MIW application is now live on Railway!
echo.
echo [USEFUL COMMANDS]
echo ----------------------------------------------------------------
echo • View logs:        railway logs
echo • Open app:         railway open
echo • Check status:     railway status
echo • View variables:   railway variables
echo • Connect to DB:    railway connect mysql
echo • Open shell:       railway shell
echo.
echo [NEXT STEPS]
echo ----------------------------------------------------------------
echo 1. Initialize database by visiting: [your-app-url]/init_database_universal.php
echo 2. Test registration forms
echo 3. Verify email functionality
echo 4. Check file uploads
echo 5. Test admin dashboard
echo.

REM Open the application
set /p open_app="Open your Railway app now? (y/n): "
if /i "%open_app%"=="y" (
    railway open
)

echo.
echo ================================================================
echo         🚀 MIW Travel System is now LIVE on Railway! 🚀
echo ================================================================
echo.
echo Your customers can now register for Haji and Umroh packages!
echo Railway provides excellent performance and persistent storage.
echo.
pause
