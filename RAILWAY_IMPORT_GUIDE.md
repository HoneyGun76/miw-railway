# 🗄️ Railway MySQL Import Guide for data_miw Database

This guide provides multiple methods to import your local `data_miw` database to Railway MySQL using the methods you suggested.

## 📁 **Available Files**

- `data_miw_complete_dump.sql` - Complete SQL dump with schema and data
- `import_to_railway.bat` - Windows batch script for command-line import
- `import_to_railway.ps1` - PowerShell script with better error handling
- `sql_import_web.php` - Web-based import interface

## 🚀 **Method 1: Using Railway CLI (Recommended)**

### Prerequisites
```bash
# Install Railway CLI
npm install -g @railway/cli

# Or download from https://railway.app/cli
```

### Import Steps
```bash
# Navigate to your project directory
cd "c:\xampp\htdocs\MIW-Railway\miw-railway"

# Login to Railway
railway login

# Import using Railway CLI
set MYSQL_PWD=ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe
type data_miw_complete_dump.sql | railway run mysql -h mysql.railway.internal -u root railway
```

### Using the Batch Script
```cmd
# Run the automated script
import_to_railway.bat
```

### Using PowerShell Script
```powershell
# Run with better error handling
.\import_to_railway.ps1
```

---

## 🌐 **Method 2: Web-Based Import (Easiest)**

Simply visit: **https://miw.railway.app/sql_import_web.php**

1. ✅ Checks if SQL dump file exists
2. 🔍 Shows what will be imported
3. 🚀 Click "Import data_miw Schema"
4. ✅ Verifies the import success

---

## 💻 **Method 3: Local MySQL Client**

### Using MySQL Command Line
```bash
mysql -h mysql.railway.internal -u root -p"ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe" railway < data_miw_complete_dump.sql
```

### Using MySQL Workbench
1. **Connect to Railway MySQL:**
   - Host: `mysql.railway.internal`
   - Port: `3306`
   - Username: `root`
   - Password: `ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe`
   - Database: `railway`

2. **Import SQL File:**
   - File → Run SQL Script
   - Select `data_miw_complete_dump.sql`
   - Execute

---

## 🛠️ **Method 4: Database Clients**

### DBeaver
1. Create new connection with Railway MySQL credentials
2. Right-click database → Tools → Execute SQL Script
3. Select `data_miw_complete_dump.sql`

### TablePlus
1. Connect to Railway MySQL
2. File → Import → From SQL Dump
3. Select `data_miw_complete_dump.sql`

---

## 📊 **What Gets Imported**

### **Database Tables**
- ✅ `data_paket` - Travel packages (5 sample records)
- ✅ `data_jamaah` - Customer registrations (5 sample records)  
- ✅ `data_invoice` - Invoice management (5 sample records)
- ✅ `data_pembatalan` - Cancellation requests (2 sample records)
- ✅ `admin_users` - Admin accounts (3 users)
- ✅ `payment_confirmations` - Payment tracking (3 sample records)
- ✅ `file_metadata` - Document management system
- ✅ `manifest_data` - Travel manifest system

### **Sample Data Included**
- **Travel Packages**: Umroh and Haji packages with realistic pricing
- **Customer Records**: Complete profiles with NIK, addresses, payment status
- **Admin Users**: Default accounts (admin/operator, password: admin123)
- **Invoices**: Payment tracking with various statuses
- **Foreign Keys**: Proper relationships between tables

---

## 🔧 **Railway MySQL Connection Details**

```
Host: mysql.railway.internal
Port: 3306
Database: railway
Username: root
Password: ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe
```

---

## ✅ **Verification After Import**

Visit these URLs to verify the import:

1. **Database Verification**: https://miw.railway.app/db_verification_report.php
2. **Admin Dashboard**: https://miw.railway.app/admin_dashboard.php
3. **Main Website**: https://miw.railway.app
4. **Current Data Check**: https://miw.railway.app/check_current_data.php

---

## 🎯 **Recommended Approach**

1. **🌐 Try Web Import First**: Visit `sql_import_web.php` - easiest method
2. **💻 Use Railway CLI**: If web import fails, try command line
3. **🛠️ MySQL Workbench**: For detailed control and error checking
4. **🔄 Quick Init Fallback**: Use `quick_database_init.php` as backup

---

## 🚨 **Troubleshooting**

### **Railway CLI Issues**
- Ensure you're logged in: `railway login`
- Check project context: `railway status`
- Verify MySQL service is running in Railway dashboard

### **Connection Issues**
- Verify Railway MySQL credentials in dashboard
- Check if Railway MySQL service is deployed and active
- Try connecting with a local MySQL client first

### **Import Errors**
- Check SQL dump file exists and is readable
- Verify file encoding (should be UTF-8)
- Try web-based import for better error reporting

---

## 📝 **Summary**

Your `data_miw` database can be imported to Railway MySQL using any of these methods. The **web-based import** (`sql_import_web.php`) is recommended for its simplicity and error handling. The complete SQL dump file contains all necessary schema and sample data to get your application running immediately.

**Status**: ✅ Ready to import complete `data_miw` schema to Railway MySQL
