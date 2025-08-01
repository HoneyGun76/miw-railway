@echo off
echo =====================================
echo    MIW Railway - Push to GitHub
echo =====================================
echo.

echo Current status:
git status --short
echo.

echo Commits ready to push:
git log --oneline origin/main..HEAD
echo.

echo Pushing to GitHub repository...
git push origin main

if %errorlevel% equ 0 (
    echo.
    echo ✅ Successfully pushed to GitHub!
    echo 📂 View repository: https://github.com/HoneyGun76/miw-railway
) else (
    echo.
    echo ❌ Push to GitHub failed!
    echo Please check your authentication or network connection.
)

echo.
pause
