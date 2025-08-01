@echo off
echo ============================================
echo MIW Railway - Git Sync & Force Push Script
echo ============================================
echo.

echo [1/5] Removing deleted files from git tracking...
git add -A
echo Done.
echo.

echo [2/5] Checking what will be committed...
git status --short
echo.

echo [3/5] Enter your commit message:
set /p commit_msg="> "

echo.
echo [4/5] Committing all changes (including deletions)...
git commit -m "%commit_msg%"

echo.
echo [5/5] Force pushing to Railway (this will sync deletions)...
git push origin main --force

echo.
echo ✅ SUCCESS: Repository synced with local directory!
echo 🗑️  Deleted files have been removed from git repository
echo 🚀 Your changes are now live on Railway
echo 📊 Access diagnostic dashboard at your Railway URL + /diagnostic.php
echo.
pause
