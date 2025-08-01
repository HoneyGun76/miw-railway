@echo off
echo ========================================
echo   MIW Railway - Git Push (Both Ends)
echo ========================================
echo.

echo Staging changes...
git add .

echo.
echo Enter your commit message:
set /p commit_msg="> "

echo.
echo Committing changes...
git commit -m "%commit_msg%"

echo.
echo =====================================
echo   Pushing to Railway Deployment
echo =====================================
call push_railway.bat

echo.
echo =====================================
echo   Pushing to GitHub Repository  
echo =====================================
call push_github.bat

echo.
echo ========================================
echo   All push operations completed!
echo ========================================
echo.
echo ✅ Railway: Live deployment updated
echo ✅ GitHub: Repository synchronized
echo.
echo 📊 Access diagnostic dashboard at your Railway URL + /diagnostic.php
echo.
pause
