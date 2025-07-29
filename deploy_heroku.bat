@echo off
setlocal enabledelayedexpansion

echo ===============================================
echo   MIW Travel - Heroku Deployment Script
echo ===============================================
echo.

REM Check if Heroku CLI is installed
heroku --version >nul 2>&1
if !errorlevel! neq 0 (
    echo [ERROR] Heroku CLI is not installed or not in PATH
    echo Please install Heroku CLI from: https://devcenter.heroku.com/articles/heroku-cli
    pause
    exit /b 1
)

echo [INFO] Heroku CLI detected
echo.

REM Check if user is logged in to Heroku
heroku auth:whoami >nul 2>&1
if !errorlevel! neq 0 (
    echo [INFO] Not logged in to Heroku. Please log in:
    heroku login
    if !errorlevel! neq 0 (
        echo [ERROR] Failed to log in to Heroku
        pause
        exit /b 1
    )
)

echo [INFO] Logged in to Heroku
echo.

REM Set the app name
set APP_NAME=miw-travel-app-576ab80a8cab

echo [INFO] Using Heroku app: %APP_NAME%
echo.

REM Check if git repository exists
if not exist ".git" (
    echo [INFO] Initializing Git repository...
    git init
    git add .
    git commit -m "Initial commit for MIW-Clean deployment"
)

echo [INFO] Checking Heroku remote...
git remote | findstr heroku >nul 2>&1
if !errorlevel! neq 0 (
    echo [INFO] Adding Heroku remote...
    heroku git:remote -a %APP_NAME%
    if !errorlevel! neq 0 (
        echo [ERROR] Failed to add Heroku remote
        pause
        exit /b 1
    )
) else (
    echo [INFO] Heroku remote already exists
)

echo.
echo [INFO] Configuring Heroku environment variables...

REM Set essential environment variables
heroku config:set APP_ENV=production -a %APP_NAME%
heroku config:set MAX_EXECUTION_TIME=300 -a %APP_NAME%
heroku config:set MAX_FILE_SIZE=10M -a %APP_NAME%
heroku config:set SECURE_HEADERS=true -a %APP_NAME%

echo.
echo [INFO] Environment variables configured
echo.

REM Show current config
echo [INFO] Current Heroku config:
heroku config -a %APP_NAME%
echo.

echo [INFO] Adding PostgreSQL addon if not exists...
heroku addons:info heroku-postgresql -a %APP_NAME% >nul 2>&1
if !errorlevel! neq 0 (
    echo [INFO] Adding PostgreSQL addon...
    heroku addons:create heroku-postgresql:essential-0 -a %APP_NAME%
    if !errorlevel! neq 0 (
        echo [WARNING] Failed to add PostgreSQL addon. It might already exist.
    )
) else (
    echo [INFO] PostgreSQL addon already exists
)

echo.
echo [INFO] Preparing for deployment...

REM Commit any changes
git add .
git status --porcelain | findstr "^" >nul
if !errorlevel! equ 0 (
    echo [INFO] Committing changes...
    git commit -m "Deploy MIW-Clean to Heroku - %date% %time%"
)

echo.
echo [INFO] Deploying to Heroku...
echo [INFO] This may take several minutes...
echo.

git push heroku main --force
if !errorlevel! neq 0 (
    echo [ERROR] Deployment failed
    echo.
    echo Possible solutions:
    echo 1. Check your internet connection
    echo 2. Verify Heroku app name and permissions
    echo 3. Check for syntax errors in your code
    echo 4. Review Heroku build logs
    echo.
    pause
    exit /b 1
)

echo.
echo [SUCCESS] Deployment completed!
echo.

echo [INFO] Initializing database...
heroku run php init_database_postgresql.sql -a %APP_NAME%

echo.
echo [INFO] Opening application...
heroku open -a %APP_NAME%

echo.
echo [INFO] Checking application logs...
echo Press Ctrl+C to stop viewing logs
heroku logs --tail -a %APP_NAME%

pause
