<?php
/**
 * Database Diagnostic Tool
 * Check database status and table information
 */

require_once 'config.php';

echo "<h2>🗄️ Database Diagnostic Report</h2>";
echo "<hr>";

try {
    // Test database connection
    echo "<h3>✅ Database Connection</h3>";
    $stmt = $conn->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "<p><strong>PostgreSQL Version:</strong> " . htmlspecialchars($version) . "</p>";
    
    // List all tables
    echo "<h3>📋 Database Tables</h3>";
    $stmt = $conn->query("
        SELECT tablename, schemaname 
        FROM pg_tables 
        WHERE schemaname = 'public' 
        ORDER BY tablename
    ");
    
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tables)) {
        echo "<p>❌ No tables found in the database!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Table Name</th><th>Record Count</th><th>Status</th></tr>";
        
        foreach ($tables as $table) {
            $tableName = $table['tablename'];
            try {
                $countStmt = $conn->query("SELECT COUNT(*) FROM " . $tableName);
                $recordCount = $countStmt->fetchColumn();
                $status = "✅ OK";
            } catch (Exception $e) {
                $recordCount = "N/A";
                $status = "❌ Error: " . $e->getMessage();
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($tableName) . "</td>";
            echo "<td>" . $recordCount . "</td>";
            echo "<td>" . htmlspecialchars($status) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check required tables specifically
    echo "<h3>🎯 Required Tables Check</h3>";
    $requiredTables = ['data_paket', 'data_jamaah', 'data_invoice', 'data_pembatalan', 'error_logs'];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Required Table</th><th>Exists</th><th>Records</th><th>Sample Data</th></tr>";
    
    foreach ($requiredTables as $tableName) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM " . $tableName);
            $recordCount = $stmt->fetchColumn();
            $exists = "✅ Yes";
            
            // Get sample data
            $sampleStmt = $conn->query("SELECT * FROM " . $tableName . " LIMIT 1");
            $sample = $sampleStmt->fetch(PDO::FETCH_ASSOC);
            $sampleData = $sample ? "Available" : "Empty";
            
        } catch (Exception $e) {
            $exists = "❌ No";
            $recordCount = "N/A";
            $sampleData = "N/A";
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($tableName) . "</td>";
        echo "<td>" . $exists . "</td>";
        echo "<td>" . $recordCount . "</td>";
        echo "<td>" . $sampleData . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check data_jamaah table structure
    echo "<h3>🔍 data_jamaah Table Structure</h3>";
    try {
        $stmt = $conn->query("
            SELECT column_name, data_type, is_nullable, column_default
            FROM information_schema.columns 
            WHERE table_name = 'data_jamaah' 
            AND table_schema = 'public'
            ORDER BY ordinal_position
        ");
        
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($columns)) {
            echo "<p>❌ data_jamaah table not found!</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($column['column_name']) . "</td>";
                echo "<td>" . htmlspecialchars($column['data_type']) . "</td>";
                echo "<td>" . htmlspecialchars($column['is_nullable']) . "</td>";
                echo "<td>" . htmlspecialchars($column['column_default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking table structure: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Check recent errors
    echo "<h3>🚨 Recent Application Errors</h3>";
    try {
        $stmt = $conn->query("
            SELECT error_type, error_message, created_at 
            FROM error_logs 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        $errors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($errors)) {
            echo "<p>✅ No recent errors found!</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Time</th><th>Type</th><th>Message</th></tr>";
            
            foreach ($errors as $error) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($error['created_at']) . "</td>";
                echo "<td>" . htmlspecialchars($error['error_type']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($error['error_message'], 0, 100)) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p>Error checking error logs: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Database Connection Error</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><strong>Diagnostic completed at:</strong> " . date('Y-m-d H:i:s') . " UTC</p>";
echo "<p><a href='error_logger.php'>🔍 View Error Logger</a> | <a href='admin_dashboard.php'>📊 Admin Dashboard</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h2, h3 { color: #333; }
table { background: white; margin: 10px 0; }
th { background: #007cba; color: white; padding: 8px; }
td { padding: 8px; border: 1px solid #ddd; }
a { color: #007cba; text-decoration: none; margin-right: 15px; }
a:hover { text-decoration: underline; }
hr { margin: 20px 0; }
</style>
