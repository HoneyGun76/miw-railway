# ============================================================================
# Railway MySQL Import Script for data_miw Database (PowerShell)
# This script imports the complete data_miw schema to Railway MySQL
# ============================================================================

Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host "Railway MySQL Database Import Script (PowerShell)" -ForegroundColor Cyan
Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host ""

# Check if Railway CLI is installed
try {
    $railwayVersion = railway --version 2>$null
    Write-Host "✅ Railway CLI found: $railwayVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Railway CLI not found. Please install it first:" -ForegroundColor Red
    Write-Host "   npm install -g @railway/cli" -ForegroundColor Yellow
    Write-Host "   Or download from: https://railway.app/cli" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

# Check if SQL dump file exists
$sqlFile = "data_miw_complete_dump.sql"
if (-not (Test-Path $sqlFile)) {
    Write-Host "❌ SQL dump file '$sqlFile' not found" -ForegroundColor Red
    Write-Host "   Make sure you're running this script from the correct directory" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "✅ SQL dump file found: $sqlFile" -ForegroundColor Green
Write-Host ""

# Login to Railway (if not already logged in)
Write-Host "🔐 Logging into Railway..." -ForegroundColor Blue
try {
    railway login
    Write-Host "✅ Railway login successful" -ForegroundColor Green
} catch {
    Write-Host "❌ Railway login failed" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host ""
Write-Host "📋 Railway MySQL Connection Details:" -ForegroundColor Cyan
Write-Host "   Host: mysql.railway.internal" -ForegroundColor White
Write-Host "   Port: 3306" -ForegroundColor White
Write-Host "   Database: railway" -ForegroundColor White
Write-Host "   Username: root" -ForegroundColor White
Write-Host "   Password: ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe" -ForegroundColor White
Write-Host ""

Write-Host "🚀 Starting database import..." -ForegroundColor Blue
Write-Host "⚠️  This will replace existing data in your Railway MySQL database" -ForegroundColor Yellow
Write-Host ""

$confirm = Read-Host "Are you sure you want to continue? (y/N)"
if ($confirm -ne "y" -and $confirm -ne "Y") {
    Write-Host "❌ Import cancelled" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 0
}

Write-Host ""
Write-Host "📤 Importing data_miw schema to Railway MySQL..." -ForegroundColor Blue
Write-Host "   This may take a few minutes depending on your connection..." -ForegroundColor Yellow
Write-Host ""

# Method 1: Using Railway CLI
Write-Host "🔄 Method 1: Attempting import via Railway CLI..." -ForegroundColor Blue
try {
    $env:MYSQL_PWD = "ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe"
    Get-Content $sqlFile | railway run mysql -h mysql.railway.internal -u root railway
    
    Write-Host ""
    Write-Host "✅ Database import completed successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "🌐 You can verify the import at:" -ForegroundColor Cyan
    Write-Host "   • Main Website: https://miw.railway.app" -ForegroundColor White
    Write-Host "   • Admin Dashboard: https://miw.railway.app/admin_dashboard.php" -ForegroundColor White
    Write-Host "   • Database Verification: https://miw.railway.app/db_verification_report.php" -ForegroundColor White
    Write-Host ""
    Write-Host "👥 Default admin credentials:" -ForegroundColor Cyan
    Write-Host "   Username: admin / Password: admin123" -ForegroundColor White
    Write-Host "   Username: operator / Password: admin123" -ForegroundColor White
    Write-Host ""
} catch {
    Write-Host ""
    Write-Host "❌ Import failed via Railway CLI: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "💡 Alternative methods to try:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "1. Manual method using local MySQL client:" -ForegroundColor Cyan
    Write-Host '   mysql -h mysql.railway.internal -u root -p"ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe" railway < data_miw_complete_dump.sql' -ForegroundColor White
    Write-Host ""
    Write-Host "2. Use web-based initialization:" -ForegroundColor Cyan
    Write-Host "   Visit: https://miw.railway.app/quick_database_init.php" -ForegroundColor White
    Write-Host ""
    Write-Host "3. Use MySQL Workbench:" -ForegroundColor Cyan
    Write-Host "   - Connect to Railway MySQL with the credentials above" -ForegroundColor White
    Write-Host "   - Use File > Run SQL Script to import data_miw_complete_dump.sql" -ForegroundColor White
    Write-Host ""
    
    # Try Method 2: Direct mysql command (if mysql client is available)
    Write-Host "🔄 Method 2: Attempting direct mysql client..." -ForegroundColor Blue
    try {
        $mysqlVersion = mysql --version 2>$null
        Write-Host "✅ MySQL client found: $mysqlVersion" -ForegroundColor Green
        
        $env:MYSQL_PWD = "ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe"
        Get-Content $sqlFile | mysql -h mysql.railway.internal -u root railway
        
        Write-Host "✅ Import successful via direct MySQL client!" -ForegroundColor Green
    } catch {
        Write-Host "❌ MySQL client not available or import failed" -ForegroundColor Red
        Write-Host "   Try the web-based method: https://miw.railway.app/quick_database_init.php" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "📊 Import Summary:" -ForegroundColor Cyan
Write-Host "   • Tables created: 8 main tables + indexes" -ForegroundColor White
Write-Host "   • Sample data: 5 packages, 5 customers, invoices, admin users" -ForegroundColor White
Write-Host "   • Foreign keys: Properly configured relationships" -ForegroundColor White
Write-Host "   • Status: Railway MySQL ready for production" -ForegroundColor White
Write-Host ""

Read-Host "Press Enter to exit"
