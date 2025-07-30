# Railway Deployment Guide for MIW Travel System

## Prerequisites ✅
- [x] Railway CLI installed (`railway 4.5.6`)
- [x] Railway configuration files ready
- [x] Database optimized for Railway
- [x] File upload system configured for persistent storage

## Step 1: Authentication

### Option A: Browser Login
1. Visit: https://railway.com/login
2. Log in with your GitHub account
3. Go back to terminal and run: `railway whoami` to verify

### Option B: Token Authentication
1. Get API token from: https://railway.app/account/tokens
2. Run: `railway login --token YOUR_TOKEN_HERE`

## Step 2: Connect to Your Project
```bash
railway link 2725c7e0-071b-43ea-9be7-33142b967d77
```

## Step 3: Check Current Services
```bash
railway status
railway services
```

## Step 4: Set Environment Variables

### Core Application Variables
```bash
railway variables set APP_ENV=production
railway variables set DB_DRIVER=mysql
railway variables set MAX_FILE_SIZE=10M
railway variables set MAX_EXECUTION_TIME=300
railway variables set SECURE_HEADERS=true
railway variables set UPLOAD_PATH=/app/uploads/
```

### Email Configuration (Required)
```bash
railway variables set SMTP_HOST=smtp.gmail.com
railway variables set SMTP_USERNAME=your-email@gmail.com
railway variables set SMTP_PASSWORD=your-app-password
railway variables set SMTP_PORT=587
railway variables set SMTP_ENCRYPTION=tls
```

## Step 5: Deploy Application
```bash
railway up
```

## Step 6: Initialize Database
After deployment, visit: `https://your-app-url/init_database_railway.php`

## Railway Project Details
- **Project ID**: 2725c7e0-071b-43ea-9be7-33142b967d77
- **Project Name**: miw
- **Services**: 2 services running
- **Plan**: Hobby subscription

## File Structure Overview
```
MIW-Clean/
├── config.php                    # Railway-optimized configuration
├── railway_file_manager.php      # File upload handler with persistent storage
├── railway_diagnostics.php       # Health monitoring dashboard
├── init_database_railway.php     # Database initialization
├── railway.json                  # Railway deployment configuration
├── deploy_railway.bat            # Windows deployment script
├── deploy_railway.sh             # Unix deployment script
└── railway_manager.bat           # Management utility
```

## Key Features Configured for Railway

### 1. Persistent File Storage
- Upload path: `/app/uploads/`
- Automatic directory creation
- Multi-type file support (PDF, images, documents)

### 2. Database Support
- MySQL and PostgreSQL compatible
- Environment variable based configuration
- Connection pooling optimized

### 3. Email Integration
- SMTP configuration
- Gmail/custom email providers
- TLS/SSL encryption

### 4. Performance Optimization
- 30-second execution limits
- Error logging
- Security headers
- Timezone configuration (Asia/Jakarta)

## Useful Railway Commands

### Project Management
```bash
railway status          # Check deployment status
railway logs            # View application logs
railway open            # Open app in browser
railway shell           # Access railway environment
railway variables       # List environment variables
```

### Database Management
```bash
railway connect mysql   # Connect to MySQL database
railway variables | grep "MYSQL"  # View database variables
```

### Development
```bash
railway run php -v      # Run commands in Railway environment
railway logs --tail     # Follow live logs
```

## Troubleshooting

### Common Issues
1. **Login fails**: Try `railway logout` then `railway login` again
2. **Project not found**: Verify project ID: `2725c7e0-071b-43ea-9be7-33142b967d77`
3. **Database connection**: Check if MySQL service is running in Railway dashboard
4. **File uploads**: Ensure `/app/uploads/` directory permissions are correct

### Diagnostic Tools
- Visit: `https://your-app-url/railway_diagnostics.php`
- Check logs: `railway logs --tail`
- Test database: `railway connect mysql`

## Next Steps After Deployment
1. ✅ Verify application is running
2. ✅ Initialize database tables
3. ✅ Test user registration
4. ✅ Test file uploads
5. ✅ Configure email settings
6. ✅ Test admin dashboard

## Support Resources
- Railway Documentation: https://docs.railway.app/
- Railway CLI Reference: https://docs.railway.app/develop/cli
- MIW Application Documentation: Available in project files
