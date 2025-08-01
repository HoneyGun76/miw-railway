@echo off
echo =====================================
echo    MIW Railway - Push to Railway
echo =====================================
echo.

echo Current status:
git status --short
echo.

echo Commits ready to push:
git log --oneline origin/main..HEAD
echo.

echo Pushing to Railway deployment...
git push origin main --force

if %errorlevel% equ 0 (
    echo.
    echo ✅ Successfully pushed to Railway!
    echo 🚀 Your changes are now live at Railway.
    echo 📊 Access diagnostic dashboard: your-railway-url/diagnostic.php
) else (
    echo.
    echo ❌ Push to Railway failed!
    echo Please check your authentication or network connection.
)

echo.
pause
