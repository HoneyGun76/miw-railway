#!/bin/bash
set -e

echo "==============================================="
echo "   MIW Travel - Heroku Deployment Script"
echo "==============================================="
echo ""

# Check if Heroku CLI is installed
if ! command -v heroku &> /dev/null; then
    echo "[ERROR] Heroku CLI is not installed"
    echo "Please install Heroku CLI from: https://devcenter.heroku.com/articles/heroku-cli"
    exit 1
fi

echo "[INFO] Heroku CLI detected"
echo ""

# Check if user is logged in to Heroku
if ! heroku auth:whoami &> /dev/null; then
    echo "[INFO] Not logged in to Heroku. Please log in:"
    heroku login
    if [ $? -ne 0 ]; then
        echo "[ERROR] Failed to log in to Heroku"
        exit 1
    fi
fi

echo "[INFO] Logged in to Heroku"
echo ""

# Set the app name
APP_NAME="miw-travel-app-576ab80a8cab"

echo "[INFO] Using Heroku app: $APP_NAME"
echo ""

# Check if git repository exists
if [ ! -d ".git" ]; then
    echo "[INFO] Initializing Git repository..."
    git init
    git add .
    git commit -m "Initial commit for MIW-Clean deployment"
fi

echo "[INFO] Checking Heroku remote..."
if ! git remote | grep -q heroku; then
    echo "[INFO] Adding Heroku remote..."
    heroku git:remote -a $APP_NAME
    if [ $? -ne 0 ]; then
        echo "[ERROR] Failed to add Heroku remote"
        exit 1
    fi
else
    echo "[INFO] Heroku remote already exists"
fi

echo ""
echo "[INFO] Configuring Heroku environment variables..."

# Set essential environment variables
heroku config:set APP_ENV=production -a $APP_NAME
heroku config:set MAX_EXECUTION_TIME=300 -a $APP_NAME
heroku config:set MAX_FILE_SIZE=10M -a $APP_NAME
heroku config:set SECURE_HEADERS=true -a $APP_NAME

echo ""
echo "[INFO] Environment variables configured"
echo ""

# Show current config
echo "[INFO] Current Heroku config:"
heroku config -a $APP_NAME
echo ""

echo "[INFO] Adding PostgreSQL addon if not exists..."
if ! heroku addons:info heroku-postgresql -a $APP_NAME &> /dev/null; then
    echo "[INFO] Adding PostgreSQL addon..."
    heroku addons:create heroku-postgresql:essential-0 -a $APP_NAME
    if [ $? -ne 0 ]; then
        echo "[WARNING] Failed to add PostgreSQL addon. It might already exist."
    fi
else
    echo "[INFO] PostgreSQL addon already exists"
fi

echo ""
echo "[INFO] Preparing for deployment..."

# Commit any changes
git add .
if [ -n "$(git status --porcelain)" ]; then
    echo "[INFO] Committing changes..."
    git commit -m "Deploy MIW-Clean to Heroku - $(date)"
fi

echo ""
echo "[INFO] Deploying to Heroku..."
echo "[INFO] This may take several minutes..."
echo ""

git push heroku main --force
if [ $? -ne 0 ]; then
    echo "[ERROR] Deployment failed"
    echo ""
    echo "Possible solutions:"
    echo "1. Check your internet connection"
    echo "2. Verify Heroku app name and permissions"
    echo "3. Check for syntax errors in your code"
    echo "4. Review Heroku build logs"
    echo ""
    exit 1
fi

echo ""
echo "[SUCCESS] Deployment completed!"
echo ""

echo "[INFO] Initializing database..."
heroku pg:psql -a $APP_NAME < init_database_postgresql.sql

echo ""
echo "[INFO] Opening application..."
heroku open -a $APP_NAME

echo ""
echo "[INFO] Checking application logs..."
echo "Press Ctrl+C to stop viewing logs"
heroku logs --tail -a $APP_NAME
