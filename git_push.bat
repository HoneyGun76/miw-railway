@echo off
echo ====================================
echo MIW Railway - Git Force Push Script
echo ====================================
echo.

echo Checking git status...
git status

echo.
echo Adding all changes...
git add .

echo.
echo Committing changes...
git commit -m "Force push: Updated MIW Railway codebase with diagnostic dashboard and unified config"

echo.
echo Force pushing to main branch...
git push --force-with-lease origin main

echo.
echo ====================================
echo Git force push completed!
echo ====================================
echo.
pause
