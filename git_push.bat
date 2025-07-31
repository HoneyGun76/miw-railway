@echo off
echo Staging changes...
git add .

echo.
echo Enter your commit message:
set /p commit_msg="> "

echo.
echo Committing changes...
git commit -m "%commit_msg%"

echo.
echo Force pushing to Railway...
git push origin main --force

echo.
echo Done! Your changes are now live on Railway.
echo Access diagnostic dashboard at your Railway URL + /diagnostic.php
pause
