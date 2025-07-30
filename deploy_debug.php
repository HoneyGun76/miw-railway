<?php
/**
 * Deploy Debug Information
 * System information and deployment diagnostics for Railway environment
 */

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deploy Debug - MIW Travel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
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
        
        .info-section {
            margin-bottom: 30px;
            padding: 20px;
            border-left: 5px solid #667eea;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
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
        
        .status-good {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-warning {
            color: #ffc107;
            font-weight: bold;
        }
        
        .status-error {
            color: #dc3545;
            font-weight: bold;
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
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Deploy Debug Information</h1>
            <p>System information and deployment diagnostics</p>
        </div>
        
        <div class="content">
            <!-- PHP Information -->
            <div class="info-section">
                <h3>🔧 PHP Environment</h3>
                <div class="info-grid">
                    <div class="info-card">
                        <h4>PHP Configuration</h4>
                        <table class="data-table">
                            <tr><th>Property</th><th>Value</th></tr>
                            <tr><td>PHP Version</td><td><?php echo PHP_VERSION; ?></td></tr>
                            <tr><td>PHP SAPI</td><td><?php echo php_sapi_name(); ?></td></tr>
                            <tr><td>Memory Limit</td><td><?php echo ini_get('memory_limit'); ?></td></tr>
                            <tr><td>Max Execution Time</td><td><?php echo ini_get('max_execution_time'); ?> seconds</td></tr>
                            <tr><td>Upload Max Filesize</td><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
                            <tr><td>Post Max Size</td><td><?php echo ini_get('post_max_size'); ?></td></tr>
                        </table>
                    </div>
                    
                    <div class="info-card">
                        <h4>PHP Extensions</h4>
                        <table class="data-table">
                            <tr><th>Extension</th><th>Status</th></tr>
                            <?php 
                            $required_extensions = ['pdo', 'pdo_pgsql', 'mbstring', 'openssl', 'curl', 'gd'];
                            foreach ($required_extensions as $ext) {
                                $loaded = extension_loaded($ext);
                                $status = $loaded ? "<span class='status-good'>✅ Loaded</span>" : "<span class='status-error'>❌ Missing</span>";
                                echo "<tr><td>$ext</td><td>$status</td></tr>";
                            }
                            ?>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Environment Information -->
            <div class="info-section">
                <h3>🌍 Environment Variables</h3>
                <div class="info-grid">
                    <div class="info-card">
                        <h4>Railway Environment</h4>
                        <table class="data-table">
                            <tr><th>Variable</th><th>Value</th></tr>
                            <?php 
                            $env_vars = [
                                'RAILWAY_ENVIRONMENT' => 'Environment',
                                'PORT' => 'Port',
                                'DATABASE_URL' => 'Database URL (masked)',
                                'RAILWAY_PROJECT_ID' => 'Project ID',
                                'RAILWAY_SERVICE_NAME' => 'Service Name',
                                'RAILWAY_GIT_COMMIT_SHA' => 'Git Commit'
                            ];
                            
                            foreach ($env_vars as $var => $description) {
                                $value = getenv($var);
                                if ($var === 'DATABASE_URL' && $value) {
                                    $value = preg_replace('/\/\/[^:]+:[^@]+@/', '//***:***@', $value);
                                }
                                $display_value = $value ? $value : '<span class="status-warning">Not Set</span>';
                                echo "<tr><td>$var</td><td>$display_value</td></tr>";
                            }
                            ?>
                        </table>
                    </div>
                    
                    <div class="info-card">
                        <h4>System Information</h4>
                        <table class="data-table">
                            <tr><th>Property</th><th>Value</th></tr>
                            <tr><td>Operating System</td><td><?php echo php_uname('s'); ?></td></tr>
                            <tr><td>Hostname</td><td><?php echo gethostname(); ?></td></tr>
                            <tr><td>Server Software</td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td></tr>
                            <tr><td>Document Root</td><td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></td></tr>
                            <tr><td>Current User</td><td><?php echo get_current_user(); ?></td></tr>
                            <tr><td>Process ID</td><td><?php echo getmypid(); ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- File System Information -->
            <div class="info-section">
                <h3>📁 File System</h3>
                <div class="info-grid">
                    <div class="info-card">
                        <h4>Directory Permissions</h4>
                        <table class="data-table">
                            <tr><th>Directory</th><th>Exists</th><th>Writable</th></tr>
                            <?php 
                            $directories = [
                                '/tmp' => 'Temp Directory',
                                '/tmp/uploads' => 'Main Upload Directory (Railway)',
                                '/tmp/uploads/documents' => 'Documents Directory',
                                '/tmp/uploads/payments' => 'Payments Directory',
                                '/tmp/uploads/cancellations' => 'Cancellations Directory',
                                '/tmp/miw_uploads' => 'Legacy Upload Directory',
                                '/tmp/miw_uploads/documents' => 'Legacy Documents',
                                '/tmp/miw_uploads/payments' => 'Legacy Payments',
                                '/tmp/miw_uploads/photos' => 'Legacy Photos',
                                getcwd() => 'Current Directory',
                                getcwd() . '/uploads' => 'Local Upload Directory'
                            ];
                            
                            foreach ($directories as $dir => $description) {
                                $exists = is_dir($dir);
                                $writable = is_writable($dir);
                                
                                $exists_status = $exists ? "<span class='status-good'>✅ Yes</span>" : "<span class='status-error'>❌ No</span>";
                                $writable_status = $writable ? "<span class='status-good'>✅ Yes</span>" : "<span class='status-error'>❌ No</span>";
                                
                                echo "<tr><td>$dir</td><td>$exists_status</td><td>$writable_status</td></tr>";
                            }
                            ?>
                        </table>
                    </div>
                    
                    <div class="info-card">
                        <h4>Disk Usage</h4>
                        <table class="data-table">
                            <?php 
                            $disk_total = disk_total_space('.');
                            $disk_free = disk_free_space('.');
                            $disk_used = $disk_total - $disk_free;
                            ?>
                            <tr><th>Metric</th><th>Value</th></tr>
                            <tr><td>Total Space</td><td><?php echo number_format($disk_total / 1024 / 1024, 2); ?> MB</td></tr>
                            <tr><td>Free Space</td><td><?php echo number_format($disk_free / 1024 / 1024, 2); ?> MB</td></tr>
                            <tr><td>Used Space</td><td><?php echo number_format($disk_used / 1024 / 1024, 2); ?> MB</td></tr>
                            <tr><td>Usage Percentage</td><td><?php echo number_format(($disk_used / $disk_total) * 100, 2); ?>%</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Performance Information -->
            <div class="info-section">
                <h3>⚡ Performance Metrics</h3>
                <div class="info-grid">
                    <div class="info-card">
                        <h4>Memory Usage</h4>
                        <table class="data-table">
                            <?php 
                            $memory_usage = memory_get_usage(true);
                            $memory_peak = memory_get_peak_usage(true);
                            $memory_limit = ini_get('memory_limit');
                            
                            // Convert memory limit to bytes
                            $limit_bytes = 0;
                            if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
                                $limit_bytes = $matches[1];
                                switch (strtoupper($matches[2])) {
                                    case 'G': $limit_bytes *= 1024;
                                    case 'M': $limit_bytes *= 1024;
                                    case 'K': $limit_bytes *= 1024;
                                }
                            }
                            ?>
                            <tr><th>Metric</th><th>Value</th></tr>
                            <tr><td>Current Usage</td><td><?php echo number_format($memory_usage / 1024 / 1024, 2); ?> MB</td></tr>
                            <tr><td>Peak Usage</td><td><?php echo number_format($memory_peak / 1024 / 1024, 2); ?> MB</td></tr>
                            <tr><td>Memory Limit</td><td><?php echo $memory_limit; ?></td></tr>
                            <?php if ($limit_bytes > 0): ?>
                            <tr><td>Usage Percentage</td><td><?php echo number_format(($memory_usage / $limit_bytes) * 100, 2); ?>%</td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    
                    <div class="info-card">
                        <h4>Request Information</h4>
                        <table class="data-table">
                            <tr><th>Property</th><th>Value</th></tr>
                            <tr><td>Request Method</td><td><?php echo $_SERVER['REQUEST_METHOD'] ?? 'Unknown'; ?></td></tr>
                            <tr><td>Request URI</td><td><?php echo $_SERVER['REQUEST_URI'] ?? 'Unknown'; ?></td></tr>
                            <tr><td>User Agent</td><td><?php echo substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 50) . '...'; ?></td></tr>
                            <tr><td>Remote IP</td><td><?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></td></tr>
                            <tr><td>Request Time</td><td><?php echo date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] ?? time()); ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Database Connection Test -->
            <div class="info-section">
                <h3>🗄️ Database Connection</h3>
                <div class="info-card">
                    <?php 
                    try {
                        require_once 'config.php';
                        $stmt = $conn->query("SELECT version()");
                        $db_version = $stmt->fetchColumn();
                        echo "<p><span class='status-good'>✅ Database connection successful</span></p>";
                        echo "<p><strong>Database Version:</strong> $db_version</p>";
                        
                        // Test query performance
                        $start = microtime(true);
                        $stmt = $conn->query("SELECT COUNT(*) FROM data_paket");
                        $count = $stmt->fetchColumn();
                        $query_time = (microtime(true) - $start) * 1000;
                        
                        echo "<p><strong>Test Query:</strong> Retrieved $count packages in " . number_format($query_time, 2) . " ms</p>";
                        
                    } catch (Exception $e) {
                        echo "<p><span class='status-error'>❌ Database connection failed</span></p>";
                        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
                    }
                    ?>
                </div>
            </div>
            
            <!-- phpinfo (condensed) -->
            <div class="info-section">
                <h3>📋 PHP Configuration Summary</h3>
                <div class="code-block">
                    <?php 
                    ob_start();
                    phpinfo(INFO_GENERAL | INFO_CONFIGURATION);
                    $phpinfo = ob_get_clean();
                    
                    // Extract key configuration items
                    preg_match_all('/^([^=]+)=(.*)$/m', $phpinfo, $matches);
                    $config_items = array_combine($matches[1], $matches[2]);
                    
                    echo "PHP Version: " . PHP_VERSION . "\n";
                    echo "Build Date: " . PHP_RELEASE_VERSION . "\n";
                    echo "Zend Version: " . zend_version() . "\n";
                    echo "Extensions: " . implode(', ', array_slice(get_loaded_extensions(), 0, 10)) . "...\n";
                    ?>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="testing_dashboard.php" class="btn">← Back to Testing Dashboard</a>
                <a href="database_diagnostic.php" class="btn">Database Diagnostic</a>
                <a href="error_logger.php" class="btn">Error Logger</a>
                <a href="?refresh=1" class="btn">🔄 Refresh</a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 60 seconds
        setTimeout(function() {
            window.location.reload();
        }, 60000);
    </script>
</body>
</html>
