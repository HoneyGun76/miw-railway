<?php
// Web-based SQL Import for Railway MySQL
require_once 'config.railway.php';

$success = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_sql'])) {
    try {
        $conn = new PDO($dsn, $username, $password, $options);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read the SQL dump file
        $sqlFile = 'data_miw_complete_dump.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL dump file not found: $sqlFile");
        }
        
        $sqlContent = file_get_contents($sqlFile);
        if (!$sqlContent) {
            throw new Exception("Could not read SQL file content");
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Split SQL content into individual statements
        $statements = array_filter(
            array_map('trim', 
                preg_split('/;\s*$/m', $sqlContent)
            ), 
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^(--|\/\*|\s*$)/', $stmt);
            }
        );
        
        $successCount = 0;
        $totalStatements = count($statements);
        
        foreach ($statements as $statement) {
            // Skip comments and empty lines
            if (preg_match('/^(--|\/\*|SET|START|COMMIT|\s*$)/', $statement)) {
                continue;
            }
            
            try {
                $conn->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                // Log the error but continue with other statements
                $errors[] = "Statement error: " . substr($statement, 0, 100) . "... - " . $e->getMessage();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $success[] = "✅ Successfully executed $successCount statements out of $totalStatements";
        $success[] = "✅ data_miw schema imported to Railway MySQL";
        
        // Verify the import
        $tables = ['data_paket', 'data_jamaah', 'data_invoice', 'data_pembatalan', 'admin_users'];
        foreach ($tables as $table) {
            try {
                $stmt = $conn->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                $success[] = "✅ Table '$table': $count records";
            } catch (Exception $e) {
                $errors[] = "❌ Table '$table': " . $e->getMessage();
            }
        }
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        $errors[] = "❌ Import failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Railway MySQL SQL Import - MIW Travel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 2.2em; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        .alert { padding: 15px; margin: 15px 0; border-radius: 6px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .import-section { background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 20px 0; }
        .btn { padding: 15px 30px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .file-info { background: white; padding: 20px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #007bff; }
        .progress-bar { background: #e9ecef; height: 10px; border-radius: 5px; overflow: hidden; margin: 10px 0; }
        .progress-fill { background: #28a745; height: 100%; transition: width 0.3s ease; }
        ul { list-style-type: none; padding: 0; }
        li { padding: 8px 0; border-bottom: 1px solid #eee; }
        .quick-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 30px; }
        .quick-link { background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #dee2e6; }
        .quick-link a { color: #007bff; text-decoration: none; font-weight: 500; }
        .quick-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🗄️ Railway MySQL SQL Import</h1>
        <p>Import complete data_miw database schema from SQL dump file</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <h4>🎉 Import Successful!</h4>
            <ul>
                <?php foreach ($success as $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h4>❌ Import Errors</h4>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="import-section">
        <h3>📁 SQL File Information</h3>
        
        <?php
        $sqlFile = 'data_miw_complete_dump.sql';
        if (file_exists($sqlFile)):
            $fileSize = filesize($sqlFile);
            $fileSizeKB = round($fileSize / 1024, 2);
            $fileModified = date('Y-m-d H:i:s', filemtime($sqlFile));
        ?>
            <div class="file-info">
                <strong>✅ SQL Dump File Found</strong><br>
                📄 File: <?= $sqlFile ?><br>
                📏 Size: <?= $fileSizeKB ?> KB<br>
                🕒 Modified: <?= $fileModified ?><br>
                📋 Contains: Complete data_miw schema with sample data
            </div>

            <div class="alert alert-info">
                <h4>📋 This import will create:</h4>
                <ul>
                    <li>✅ <strong>data_paket</strong> - Travel packages (5 sample records)</li>
                    <li>✅ <strong>data_jamaah</strong> - Customer registrations (5 sample records)</li>
                    <li>✅ <strong>data_invoice</strong> - Invoice management (5 sample records)</li>
                    <li>✅ <strong>data_pembatalan</strong> - Cancellation requests (2 sample records)</li>
                    <li>✅ <strong>admin_users</strong> - Admin accounts (3 users)</li>
                    <li>✅ <strong>payment_confirmations</strong> - Payment tracking</li>
                    <li>✅ <strong>file_metadata</strong> - Document management</li>
                    <li>✅ <strong>manifest_data</strong> - Travel manifests</li>
                </ul>
            </div>

            <form method="POST" onsubmit="return confirm('This will replace existing data. Are you sure?');">
                <button type="submit" name="import_sql" class="btn btn-success">
                    🚀 Import data_miw Schema to Railway MySQL
                </button>
            </form>
            
        <?php else: ?>
            <div class="alert alert-danger">
                <strong>❌ SQL dump file not found: <?= $sqlFile ?></strong><br>
                Please ensure the SQL dump file is in the same directory as this script.
            </div>
        <?php endif; ?>
    </div>

    <div class="quick-links">
        <div class="quick-link">
            <strong>🌐 Main Website</strong><br>
            <a href="https://miw.railway.app" target="_blank">miw.railway.app</a>
        </div>
        <div class="quick-link">
            <strong>👨‍💼 Admin Dashboard</strong><br>
            <a href="admin_dashboard.php" target="_blank">Admin Panel</a>
        </div>
        <div class="quick-link">
            <strong>📊 Database Report</strong><br>
            <a href="db_verification_report.php" target="_blank">Verification</a>
        </div>
        <div class="quick-link">
            <strong>🔧 Quick Init</strong><br>
            <a href="quick_database_init.php" target="_blank">Simple Setup</a>
        </div>
    </div>

    <div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 8px;">
        <h4>💡 Alternative Import Methods</h4>
        <p><strong>Command Line:</strong></p>
        <code style="background: #f8f9fa; padding: 10px; display: block; border-radius: 4px; margin: 10px 0;">
            mysql -h mysql.railway.internal -u root -p"ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe" railway &lt; data_miw_complete_dump.sql
        </code>
        <p><strong>Railway CLI:</strong></p>
        <code style="background: #f8f9fa; padding: 10px; display: block; border-radius: 4px; margin: 10px 0;">
            railway run -- mysql -h mysql.railway.internal -u root railway &lt; data_miw_complete_dump.sql
        </code>
    </div>
</div>

</body>
</html>
