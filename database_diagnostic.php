<?php
/**
 * Database Diagnostic Tool
 * Comprehensive database connection and integrity testing
 */

require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Diagnostic - MIW Travel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .content {
            padding: 30px;
        }
        
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border-left: 5px solid #667eea;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }
        
        .status-pass {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-fail {
            color: #dc3545;
            font-weight: bold;
        }
        
        .status-warning {
            color: #ffc107;
            font-weight: bold;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .data-table tr:hover {
            background: #f5f5f5;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        
        .btn:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Database Diagnostic Tool</h1>
            <p>Comprehensive database connection and integrity testing</p>
        </div>
        
        <div class="content">
            <?php
            
            // Test 1: Database Connection
            echo "<div class='test-section'>";
            echo "<h3>🔌 Database Connection Test</h3>";
            
            try {
                $stmt = $conn->query("SELECT version()");
                $version = $stmt->fetchColumn();
                echo "<p><span class='status-pass'>✅ PASS</span> - Database connection successful</p>";
                echo "<p><strong>Database Version:</strong> $version</p>";
                
                // Get connection info
                $host = $conn->query("SELECT inet_server_addr()")->fetchColumn();
                $database = $conn->query("SELECT current_database()")->fetchColumn();
                $user = $conn->query("SELECT current_user")->fetchColumn();
                
                echo "<table class='data-table'>";
                echo "<tr><th>Property</th><th>Value</th></tr>";
                echo "<tr><td>Host</td><td>" . ($host ?: 'localhost') . "</td></tr>";
                echo "<tr><td>Database</td><td>$database</td></tr>";
                echo "<tr><td>User</td><td>$user</td></tr>";
                echo "<tr><td>Connection Status</td><td><span class='status-pass'>Connected</span></td></tr>";
                echo "</table>";
                
            } catch (Exception $e) {
                echo "<p><span class='status-fail'>❌ FAIL</span> - Database connection failed</p>";
                echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
            }
            echo "</div>";
            
            // Test 2: Table Structure
            echo "<div class='test-section'>";
            echo "<h3>📊 Table Structure Test</h3>";
            
            $requiredTables = ['data_paket', 'data_jamaah', 'data_invoice', 'data_pembatalan', 'error_logs'];
            
            echo "<table class='data-table'>";
            echo "<tr><th>Table Name</th><th>Status</th><th>Record Count</th><th>Last Modified</th></tr>";
            
            foreach ($requiredTables as $table) {
                try {
                    // Check if table exists
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?");
                    $stmt->execute([$table]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        // Get record count
                        $stmt = $conn->query("SELECT COUNT(*) FROM $table");
                        $count = $stmt->fetchColumn();
                        
                        // Get last modified (if possible)
                        try {
                            $stmt = $conn->query("SELECT MAX(updated_at) FROM $table");
                            $lastModified = $stmt->fetchColumn() ?: 'N/A';
                        } catch (Exception $e) {
                            $lastModified = 'N/A';
                        }
                        
                        echo "<tr>";
                        echo "<td>$table</td>";
                        echo "<td><span class='status-pass'>✅ Exists</span></td>";
                        echo "<td>$count</td>";
                        echo "<td>$lastModified</td>";
                        echo "</tr>";
                    } else {
                        echo "<tr>";
                        echo "<td>$table</td>";
                        echo "<td><span class='status-fail'>❌ Missing</span></td>";
                        echo "<td>-</td>";
                        echo "<td>-</td>";
                        echo "</tr>";
                    }
                } catch (Exception $e) {
                    echo "<tr>";
                    echo "<td>$table</td>";
                    echo "<td><span class='status-fail'>❌ Error</span></td>";
                    echo "<td>-</td>";
                    echo "<td>Error: " . substr($e->getMessage(), 0, 50) . "...</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
            echo "</div>";
            
            // Test 3: Data Integrity
            echo "<div class='test-section'>";
            echo "<h3>🔍 Data Integrity Test</h3>";
            
            try {
                // Check for orphaned records
                $stmt = $conn->query("
                    SELECT 
                        COUNT(CASE WHEN dj.pak_id NOT IN (SELECT pak_id FROM data_paket) THEN 1 END) as orphaned_jamaah,
                        COUNT(CASE WHEN di.pak_id NOT IN (SELECT pak_id FROM data_paket) THEN 1 END) as orphaned_invoices
                    FROM data_jamaah dj 
                    FULL OUTER JOIN data_invoice di ON dj.pak_id = di.pak_id
                ");
                $integrity = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<table class='data-table'>";
                echo "<tr><th>Integrity Check</th><th>Result</th></tr>";
                
                if ($integrity['orphaned_jamaah'] == 0) {
                    echo "<tr><td>Jamaah-Package Relationship</td><td><span class='status-pass'>✅ Valid</span></td></tr>";
                } else {
                    echo "<tr><td>Jamaah-Package Relationship</td><td><span class='status-warning'>⚠️ {$integrity['orphaned_jamaah']} orphaned records</span></td></tr>";
                }
                
                if ($integrity['orphaned_invoices'] == 0) {
                    echo "<tr><td>Invoice-Package Relationship</td><td><span class='status-pass'>✅ Valid</span></td></tr>";
                } else {
                    echo "<tr><td>Invoice-Package Relationship</td><td><span class='status-warning'>⚠️ {$integrity['orphaned_invoices']} orphaned records</span></td></tr>";
                }
                
                echo "</table>";
                
            } catch (Exception $e) {
                echo "<p><span class='status-fail'>❌ FAIL</span> - Data integrity check failed</p>";
                echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
            }
            echo "</div>";
            
            // Test 4: Performance Metrics
            echo "<div class='test-section'>";
            echo "<h3>⚡ Performance Metrics</h3>";
            
            try {
                $start = microtime(true);
                $stmt = $conn->query("SELECT COUNT(*) FROM data_paket");
                $paketCount = $stmt->fetchColumn();
                $paketTime = (microtime(true) - $start) * 1000;
                
                $start = microtime(true);
                $stmt = $conn->query("SELECT COUNT(*) FROM data_jamaah");
                $jamaahCount = $stmt->fetchColumn();
                $jamaahTime = (microtime(true) - $start) * 1000;
                
                echo "<table class='data-table'>";
                echo "<tr><th>Query</th><th>Result</th><th>Execution Time</th></tr>";
                echo "<tr><td>SELECT COUNT(*) FROM data_paket</td><td>$paketCount records</td><td>" . number_format($paketTime, 2) . " ms</td></tr>";
                echo "<tr><td>SELECT COUNT(*) FROM data_jamaah</td><td>$jamaahCount records</td><td>" . number_format($jamaahTime, 2) . " ms</td></tr>";
                echo "</table>";
                
            } catch (Exception $e) {
                echo "<p><span class='status-fail'>❌ FAIL</span> - Performance test failed</p>";
                echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
            }
            echo "</div>";
            
            // Test 5: Sample Data Check
            echo "<div class='test-section'>";
            echo "<h3>📋 Sample Data Check</h3>";
            
            try {
                $stmt = $conn->query("SELECT pak_id, program_pilihan, jenis_paket, base_price_quad FROM data_paket LIMIT 5");
                $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($packages) > 0) {
                    echo "<p><span class='status-pass'>✅ PASS</span> - Sample packages available</p>";
                    echo "<table class='data-table'>";
                    echo "<tr><th>Package ID</th><th>Program</th><th>Type</th><th>Price (Quad)</th></tr>";
                    
                    foreach ($packages as $package) {
                        echo "<tr>";
                        echo "<td>{$package['pak_id']}</td>";
                        echo "<td>{$package['program_pilihan']}</td>";
                        echo "<td>{$package['jenis_paket']}</td>";
                        echo "<td>{$package['base_price_quad']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p><span class='status-warning'>⚠️ WARNING</span> - No sample packages found</p>";
                }
                
            } catch (Exception $e) {
                echo "<p><span class='status-fail'>❌ FAIL</span> - Sample data check failed</p>";
                echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
            }
            echo "</div>";
            
            ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="testing_dashboard.php" class="btn">← Back to Testing Dashboard</a>
                <a href="error_logger.php" class="btn">View Error Logs</a>
                <a href="?refresh=1" class="btn">🔄 Refresh</a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
