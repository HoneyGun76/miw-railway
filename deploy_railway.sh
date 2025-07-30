#!/bin/bash

# Railway Deployment Script for MIW Travel System
# This script helps deploy and manage MIW on Railway.com

set -e

echo "======================================================"
echo "          MIW Travel - Railway Deployment"
echo "======================================================"
echo ""

# Check if Railway CLI is installed
if ! command -v railway &> /dev/null; then
    echo "❌ Railway CLI is not installed."
    echo "📥 Installing Railway CLI..."
    
    # Install Railway CLI
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        curl -fsSL https://railway.app/install.sh | sh
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Linux
        curl -fsSL https://railway.app/install.sh | sh
    else
        # Windows (WSL) or other
        echo "Please install Railway CLI manually from: https://docs.railway.app/develop/cli"
        exit 1
    fi
fi

echo "✅ Railway CLI is available"

# Login to Railway
echo ""
echo "🔐 Logging into Railway..."
railway login

# Link to existing project
echo ""
echo "🔗 Linking to Railway project..."
railway link 2725c7e0-071b-43ea-9be7-33142b967d77

# Check current services
echo ""
echo "📋 Current Railway services:"
railway status

# Environment variables setup
echo ""
echo "⚙️  Setting up environment variables..."

# Database environment variables (if using MySQL service)
echo "Setting database variables..."
railway variables set DB_DRIVER=mysql
railway variables set APP_ENV=production
railway variables set MAX_FILE_SIZE=10M
railway variables set MAX_EXECUTION_TIME=300
railway variables set SECURE_HEADERS=true
railway variables set UPLOAD_PATH=/app/uploads/

# Email configuration (you'll need to set these manually)
echo ""
echo "📧 Email configuration needed:"
echo "Please set these manually in Railway dashboard or via CLI:"
echo "railway variables set SMTP_HOST=smtp.gmail.com"
echo "railway variables set SMTP_USERNAME=your-email@gmail.com"
echo "railway variables set SMTP_PASSWORD=your-app-password"
echo "railway variables set SMTP_PORT=587"
echo "railway variables set SMTP_ENCRYPTION=tls"

# Deploy the application
echo ""
echo "🚀 Deploying to Railway..."
railway up

echo ""
echo "✅ Deployment initiated!"
echo ""
echo "🔗 Useful Railway commands:"
echo "railway logs                 - View application logs"
echo "railway open                 - Open your app in browser"
echo "railway status               - Check deployment status"
echo "railway shell                - Open shell in Railway environment"
echo "railway variables            - List environment variables"
echo "railway connect mysql        - Connect to MySQL database"
echo ""
echo "📋 Next steps:"
echo "1. Set up email variables in Railway dashboard"
echo "2. Initialize database: visit /init_database_universal.php"
echo "3. Test registration forms"
echo "4. Check file uploads"
echo ""
echo "🎉 Railway deployment completed!"
