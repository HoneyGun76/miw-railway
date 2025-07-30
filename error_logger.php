<?php
/**
 * MIW Error Logger - Railway Log Viewer
 * Displays application errors and logs from Railway deployment
 */

session_start();
require_once 'config.php';

// Simple authentication (optional - can be enhanced)
$auth_required = true;
$admin_password = 'miw2025admin'; // Change this to a secure password

if ($auth_required && !isset($_SESSION['error_logger_auth'])) {
    if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
        $_SESSION['error_logger_auth'] = true;
    } else {
        showLoginForm();
        exit;
    }
}

function showLoginForm() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>MIW Error Logger - Login</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 50px; }
            .login-container { max-width: 400px; margin: 100px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
            h2 { text-align: center; color: #333; }
            input[type="password"] { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
            button { width: 100%; padding: 12px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
            button:hover { background: #005a8b; }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>🔒 MIW Error Logger</h2>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter admin password" required>
                <button type="submit">Access Logs</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: error_logger.php');
    exit;
}

// Handle AJAX requests for log refresh
if (isset($_GET['action']) && $_GET['action'] === 'refresh_logs') {
    header('Content-Type: application/json');
    echo json_encode([
        'logs' => getErrorLogs(),
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'success'
    ]);
    exit;
}

function getErrorLogs() {
    $logs = [];
    
    // 1. PHP Error Logs
    $phpErrors = getPHPErrors();
    if (!empty($phpErrors)) {
        $logs['PHP Errors'] = $phpErrors;
    }
    
    // 2. Application Errors from Database
    $appErrors = getApplicationErrors();
    if (!empty($appErrors)) {
        $logs['Application Errors'] = $appErrors;
    }
    
    // 3. File Upload Errors
    $uploadErrors = getFileUploadErrors();
    if (!empty($uploadErrors)) {
        $logs['File Upload Errors'] = $uploadErrors;
    }
    
    // 4. Database Connection Errors
    $dbErrors = getDatabaseErrors();
    if (!empty($dbErrors)) {
        $logs['Database Errors'] = $dbErrors;
    }
    
    // 5. Email System Errors
    $emailErrors = getEmailErrors();
    if (!empty($emailErrors)) {
        $logs['Email System Errors'] = $emailErrors;
    }
    
    return $logs;
}

function getPHPErrors() {
    $errors = [];
    
    // Read PHP error log if available
    $errorLogPaths = [
        '/app/storage/logs/error.log',
        '/tmp/error.log',
        ini_get('error_log')
    ];
    
    foreach ($errorLogPaths as $logPath) {
        if ($logPath && file_exists($logPath) && is_readable($logPath)) {
            $content = file_get_contents($logPath);
            if ($content) {
                $lines = explode("\n", $content);
                $recentLines = array_slice($lines, -50); // Last 50 lines
                foreach ($recentLines as $line) {
                    if (!empty(trim($line))) {
                        $errors[] = [
                            'timestamp' => extractTimestamp($line),
                            'message' => $line,
                            'type' => 'php_error'
                        ];
                    }
                }
            }
            break;
        }
    }
    
    return array_slice($errors, -20); // Return last 20 errors
}

function getApplicationErrors() {
    global $conn;
    $errors = [];
    
    try {
        // Create error log table if it doesn't exist
        $conn->exec("
            CREATE TABLE IF NOT EXISTS error_logs (
                id SERIAL PRIMARY KEY,
                error_type VARCHAR(50),
                error_message TEXT,
                file_path VARCHAR(255),
                line_number INTEGER,
                user_agent TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Get recent application errors
        $stmt = $conn->prepare("
            SELECT * FROM error_logs 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $errors[] = [
                'timestamp' => $row['created_at'],
                'type' => $row['error_type'],
                'message' => $row['error_message'],
                'file' => $row['file_path'],
                'line' => $row['line_number'],
                'ip' => $row['ip_address']
            ];
        }
        
    } catch (Exception $e) {
        $errors[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'system_error',
            'message' => 'Error accessing error logs: ' . $e->getMessage()
        ];
    }
    
    return $errors;
}

function getFileUploadErrors() {
    $errors = [];
    
    // Check for common file upload issues
    $uploadDir = '/tmp/miw_uploads';
    if (!is_dir($uploadDir)) {
        $errors[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'file_system',
            'message' => 'Upload directory does not exist: ' . $uploadDir
        ];
    } elseif (!is_writable($uploadDir)) {
        $errors[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'file_system',
            'message' => 'Upload directory is not writable: ' . $uploadDir
        ];
    }
    
    // Check disk space
    $freeSpace = disk_free_space('/tmp');
    if ($freeSpace !== false && $freeSpace < 100 * 1024 * 1024) { // Less than 100MB
        $errors[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'file_system',
            'message' => 'Low disk space: ' . formatBytes($freeSpace) . ' remaining'
        ];
    }
    
    return $errors;
}

function getDatabaseErrors() {
    global $conn;
    $errors = [];
    
    try {
        // Test database connection
        $stmt = $conn->query("SELECT 1");
        
        // Check for missing tables
        $requiredTables = ['data_paket', 'data_jamaah', 'data_invoice', 'data_pembatalan'];
        foreach ($requiredTables as $table) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?");
            $stmt->execute([$table]);
            if ($stmt->fetchColumn() == 0) {
                $errors[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'type' => 'database_schema',
                    'message' => 'Missing required table: ' . $table
                ];
            }
        }
        
    } catch (Exception $e) {
        $errors[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'database_connection',
            'message' => 'Database connection error: ' . $e->getMessage()
        ];
    }
    
    return $errors;
}

function getEmailErrors() {
    $errors = [];
    
    // Check SMTP configuration
    if (!defined('SMTP_HOST') || empty(SMTP_HOST)) {
        $errors[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'email_config',
            'message' => 'SMTP_HOST not configured'
        ];
    }
    
    if (!defined('SMTP_USERNAME') || empty(SMTP_USERNAME)) {
        $errors[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'email_config',
            'message' => 'SMTP_USERNAME not configured'
        ];
    }
    
    return $errors;
}

function extractTimestamp($logLine) {
    // Try to extract timestamp from log line
    if (preg_match('/\[(.*?)\]/', $logLine, $matches)) {
        return $matches[1];
    }
    return date('Y-m-d H:i:s');
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

// Log an application error (function to be called from other parts of the application)
function logApplicationError($type, $message, $file = '', $line = 0) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO error_logs (error_type, error_message, file_path, line_number, user_agent, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $type,
            $message,
            $file,
            $line,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) {
        // Silent fail to avoid infinite loops
        error_log("Failed to log application error: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIW Error Logger - Railway Logs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f7fa; color: #333; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header .subtitle { opacity: 0.9; font-size: 1.1em; }
        
        .controls {
            background: white;
            padding: 20px;
            margin: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .refresh-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .refresh-btn:hover { background: #218838; }
        .refresh-btn:disabled { background: #6c757d; cursor: not-allowed; }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .logout-btn:hover { background: #c82333; }
        
        .status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .status.online { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        
        .logs-container {
            margin: 20px;
            display: grid;
            gap: 20px;
        }
        
        .log-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .log-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .error-count {
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .log-content {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .log-entry {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f1f1;
            transition: background 0.2s;
        }
        
        .log-entry:hover { background: #f8f9fa; }
        .log-entry:last-child { border-bottom: none; }
        
        .log-timestamp {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .log-message {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #007cba;
            word-break: break-all;
        }
        
        .log-meta {
            font-size: 11px;
            color: #868e96;
            margin-top: 8px;
        }
        
        .no-errors {
            padding: 30px;
            text-align: center;
            color: #28a745;
            font-size: 18px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        
        .system-info {
            background: white;
            margin: 20px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #007cba;
        }
        
        .info-label { font-weight: bold; color: #495057; margin-bottom: 5px; }
        .info-value { color: #6c757d; font-family: monospace; }
        
        @media (max-width: 768px) {
            .controls { flex-direction: column; text-align: center; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔍 MIW Error Logger</h1>
        <div class="subtitle">Railway Application Monitoring & Error Tracking</div>
    </div>
    
    <div class="controls">
        <div>
            <button class="refresh-btn" onclick="refreshLogs()">🔄 Refresh Logs</button>
            <span class="status online">System Online</span>
        </div>
        <div>
            <span id="last-update">Last updated: <?php echo date('Y-m-d H:i:s'); ?></span>
            <a href="?logout=1" class="logout-btn">🚪 Logout</a>
        </div>
    </div>
    
    <div class="system-info">
        <h3 style="margin-bottom: 15px;">📊 System Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Environment</div>
                <div class="info-value"><?php echo $_ENV['APP_ENV'] ?? 'production'; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">PHP Version</div>
                <div class="info-value"><?php echo PHP_VERSION; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Memory Usage</div>
                <div class="info-value"><?php echo formatBytes(memory_get_usage(true)); ?> / <?php echo ini_get('memory_limit'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Disk Space</div>
                <div class="info-value"><?php echo formatBytes(disk_free_space('/tmp')); ?> free</div>
            </div>
            <div class="info-item">
                <div class="info-label">Database Status</div>
                <div class="info-value"><?php try { $conn->query("SELECT 1"); echo "✅ Connected"; } catch(Exception $e) { echo "❌ Error"; } ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Upload Directory</div>
                <div class="info-value"><?php echo is_dir('/tmp/miw_uploads') ? '✅ Available' : '❌ Missing'; ?></div>
            </div>
        </div>
    </div>
    
    <div class="logs-container" id="logs-container">
        <div class="loading">📡 Loading error logs...</div>
    </div>

    <script>
        let refreshInterval;
        
        function refreshLogs() {
            const refreshBtn = document.querySelector('.refresh-btn');
            refreshBtn.disabled = true;
            refreshBtn.textContent = '🔄 Refreshing...';
            
            fetch('?action=refresh_logs')
                .then(response => response.json())
                .then(data => {
                    displayLogs(data.logs);
                    document.getElementById('last-update').textContent = `Last updated: ${data.timestamp}`;
                })
                .catch(error => {
                    console.error('Error refreshing logs:', error);
                    document.getElementById('logs-container').innerHTML = 
                        '<div class="log-section"><div class="log-header">❌ Error Loading Logs</div><div class="log-content"><div class="log-entry"><div class="log-message">Failed to load logs: ' + error.message + '</div></div></div></div>';
                })
                .finally(() => {
                    refreshBtn.disabled = false;
                    refreshBtn.textContent = '🔄 Refresh Logs';
                });
        }
        
        function displayLogs(logs) {
            const container = document.getElementById('logs-container');
            container.innerHTML = '';
            
            if (!logs || Object.keys(logs).length === 0) {
                container.innerHTML = '<div class="log-section"><div class="no-errors">🎉 No errors found! System is running smoothly.</div></div>';
                return;
            }
            
            for (const [category, entries] of Object.entries(logs)) {
                const section = document.createElement('div');
                section.className = 'log-section';
                
                const header = document.createElement('div');
                header.className = 'log-header';
                header.innerHTML = `
                    <span>${getCategoryIcon(category)} ${category}</span>
                    <span class="error-count">${entries.length}</span>
                `;
                
                const content = document.createElement('div');
                content.className = 'log-content';
                
                entries.forEach(entry => {
                    const logEntry = document.createElement('div');
                    logEntry.className = 'log-entry';
                    
                    logEntry.innerHTML = `
                        <div class="log-timestamp">${entry.timestamp}</div>
                        <div class="log-message">${escapeHtml(entry.message)}</div>
                        ${entry.file ? `<div class="log-meta">File: ${entry.file}${entry.line ? `:${entry.line}` : ''}</div>` : ''}
                        ${entry.ip ? `<div class="log-meta">IP: ${entry.ip}</div>` : ''}
                    `;
                    
                    content.appendChild(logEntry);
                });
                
                section.appendChild(header);
                section.appendChild(content);
                container.appendChild(section);
            }
        }
        
        function getCategoryIcon(category) {
            const icons = {
                'PHP Errors': '🐛',
                'Application Errors': '⚠️',
                'File Upload Errors': '📁',
                'Database Errors': '🗄️',
                'Email System Errors': '📧'
            };
            return icons[category] || '❌';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Auto-refresh every 30 seconds
        function startAutoRefresh() {
            refreshInterval = setInterval(refreshLogs, 30000);
        }
        
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }
        
        // Initial load
        refreshLogs();
        startAutoRefresh();
        
        // Stop auto-refresh when tab is not visible
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });
    </script>
</body>
</html>
