<?php
/**
 * MIW Railway System - Centralized Diagnostic & Error Logging Dashboard
 * 
 * This diagnostic tool provides comprehensive error logging for:
 * - PHP errors and warnings
 * - MySQL database errors
 * - Apache/web server errors
 * - Custom application errors
 * 
 * Features auto-refresh triggered by backend operations (not timers)
 * 
 * @version 1.0.0
 */

require_once 'config.php';

class DiagnosticLogger {
    private $conn;
    private $logDir;
    private $isRailway;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->isRailway = defined('IS_RAILWAY') ? IS_RAILWAY : false;
        $this->logDir = $this->isRailway ? '/tmp/logs' : __DIR__ . '/logs';
        
        // Create logs directory if it doesn't exist
        if (!file_exists($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }
        
        $this->initializeLogging();
    }
    
    /**
     * Initialize logging systems
     */
    private function initializeLogging() {
        // Set up PHP error logging
        ini_set('log_errors', 1);
        ini_set('error_log', $this->logDir . '/php_errors.log');
        
        // Set custom error and exception handlers
        set_error_handler([$this, 'handlePhpError']);
        set_exception_handler([$this, 'handlePhpException']);
        
        // Create database table for logs if it doesn't exist
        $this->createLogTable();
    }
    
    /**
     * Create database table for storing logs
     */
    private function createLogTable() {
        if (!$this->conn) return;
        
        try {
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS diagnostic_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    log_type ENUM('php', 'mysql', 'apache', 'custom') NOT NULL,
                    severity ENUM('info', 'warning', 'error', 'critical') NOT NULL,
                    message TEXT NOT NULL,
                    file_path VARCHAR(500),
                    line_number INT,
                    stack_trace TEXT,
                    request_uri VARCHAR(500),
                    user_ip VARCHAR(45),
                    user_agent TEXT,
                    session_id VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_log_type (log_type),
                    INDEX idx_severity (severity),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("Failed to create diagnostic_logs table: " . $e->getMessage());
        }
    }
    
    /**
     * Custom PHP error handler
     */
    public function handlePhpError($severity, $message, $file, $line) {
        $severityName = $this->getSeverityName($severity);
        
        $this->logError('php', $severityName, $message, $file, $line, debug_backtrace());
        
        // Don't prevent the normal error handler
        return false;
    }
    
    /**
     * Custom PHP exception handler
     */
    public function handlePhpException($exception) {
        $this->logError(
            'php', 
            'critical', 
            $exception->getMessage(), 
            $exception->getFile(), 
            $exception->getLine(), 
            $exception->getTraceAsString()
        );
    }
    
    /**
     * Log MySQL errors
     */
    public function logMysqlError($message, $query = null) {
        $this->logError('mysql', 'error', $message, null, null, $query);
    }
    
    /**
     * Log Apache/web server errors
     */
    public function logApacheError($message, $severity = 'error') {
        $this->logError('apache', $severity, $message);
    }
    
    /**
     * Log custom application errors
     */
    public function logCustom($severity, $message, $context = []) {
        $this->logError('custom', $severity, $message, null, null, json_encode($context));
    }
    
    /**
     * Central error logging function
     */
    private function logError($type, $severity, $message, $file = null, $line = null, $trace = null) {
        $timestamp = date('Y-m-d H:i:s');
        
        // Log to file
        $logFile = $this->logDir . "/{$type}_errors.log";
        $logEntry = sprintf(
            "[%s] [%s] %s in %s on line %s\n",
            $timestamp,
            strtoupper($severity),
            $message,
            $file ?: 'unknown',
            $line ?: 'unknown'
        );
        
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Log to database
        if ($this->conn) {
            try {
                $stmt = $this->conn->prepare("
                    INSERT INTO diagnostic_logs 
                    (log_type, severity, message, file_path, line_number, stack_trace, 
                     request_uri, user_ip, user_agent, session_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $type,
                    $severity,
                    $message,
                    $file,
                    $line,
                    is_array($trace) ? json_encode($trace) : $trace,
                    $_SERVER['REQUEST_URI'] ?? '',
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    session_id()
                ]);
            } catch (Exception $e) {
                // Fallback to file logging if database fails
                @file_put_contents($logFile, "[DB ERROR] " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
        
        // Trigger frontend refresh (write to trigger file)
        $this->triggerRefresh();
    }
    
    /**
     * Get severity name from PHP error level
     */
    private function getSeverityName($severity) {
        switch ($severity) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return 'error';
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'warning';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'info';
            default:
                return 'info';
        }
    }
    
    /**
     * Trigger frontend refresh by updating a timestamp file
     */
    private function triggerRefresh() {
        $triggerFile = $this->logDir . '/refresh_trigger.txt';
        @file_put_contents($triggerFile, time());
    }
    
    /**
     * Get latest logs from database
     */
    public function getLatestLogs($limit = 100, $type = null, $severity = null) {
        if (!$this->conn) return [];
        
        try {
            $where = [];
            $params = [];
            
            if ($type) {
                $where[] = "log_type = ?";
                $params[] = $type;
            }
            
            if ($severity) {
                $where[] = "severity = ?";
                $params[] = $severity;
            }
            
            $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
            
            $stmt = $this->conn->prepare("
                SELECT * FROM diagnostic_logs 
                $whereClause
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $params[] = $limit;
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get logs from file
     */
    public function getFileLog($type, $lines = 50) {
        $logFile = $this->logDir . "/{$type}_errors.log";
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $content = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice(array_reverse($content), 0, $lines);
    }
    
    /**
     * Get log statistics
     */
    public function getLogStats() {
        $stats = [
            'total' => 0,
            'by_type' => [],
            'by_severity' => [],
            'recent_count' => 0
        ];
        
        if (!$this->conn) return $stats;
        
        try {
            // Total logs
            $stmt = $this->conn->query("SELECT COUNT(*) FROM diagnostic_logs");
            $stats['total'] = $stmt->fetchColumn();
            
            // By type
            $stmt = $this->conn->query("
                SELECT log_type, COUNT(*) as count 
                FROM diagnostic_logs 
                GROUP BY log_type
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['by_type'][$row['log_type']] = $row['count'];
            }
            
            // By severity
            $stmt = $this->conn->query("
                SELECT severity, COUNT(*) as count 
                FROM diagnostic_logs 
                GROUP BY severity
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['by_severity'][$row['severity']] = $row['count'];
            }
            
            // Recent (last hour)
            $stmt = $this->conn->query("
                SELECT COUNT(*) 
                FROM diagnostic_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stats['recent_count'] = $stmt->fetchColumn();
            
        } catch (Exception $e) {
            // Stats not available
        }
        
        return $stats;
    }
    
    /**
     * Check if refresh is needed
     */
    public function needsRefresh($lastCheck = 0) {
        $triggerFile = $this->logDir . '/refresh_trigger.txt';
        
        if (!file_exists($triggerFile)) {
            return false;
        }
        
        $lastTrigger = (int) file_get_contents($triggerFile);
        return $lastTrigger > $lastCheck;
    }
    
    /**
     * Render the diagnostic dashboard
     */
    public function renderDashboard() {
        $stats = $this->getLogStats();
        $latestLogs = $this->getLatestLogs(20);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>MIW Railway - Diagnostic Dashboard</title>
            <style>
                body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f5f7fa; }
                .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
                .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
                .widget { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .widget h3 { margin-top: 0; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
                .log-type-php { border-left: 4px solid #8e44ad; }
                .log-type-mysql { border-left: 4px solid #e67e22; }
                .log-type-apache { border-left: 4px solid #3498db; }
                .log-type-custom { border-left: 4px solid #27ae60; }
                .severity-error { background: #fff5f5; color: #e53e3e; }
                .severity-warning { background: #fffbf0; color: #d69e2e; }
                .severity-info { background: #f0f8ff; color: #3182ce; }
                .severity-critical { background: #ffe6e6; color: #c53030; font-weight: bold; }
                .log-entry { background: #f8f9fa; border-radius: 5px; padding: 12px; margin: 8px 0; border-left: 4px solid #ddd; }
                .log-timestamp { font-size: 0.85em; color: #666; }
                .log-message { margin: 5px 0; }
                .log-details { font-size: 0.8em; color: #888; }
                .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
                .stat-card { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; }
                .stat-number { font-size: 2em; font-weight: bold; color: #667eea; }
                .refresh-indicator { display: inline-block; width: 10px; height: 10px; background: #27ae60; border-radius: 50%; animation: pulse 2s infinite; }
                @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }
                .controls { margin: 20px 0; }
                .btn { display: inline-block; padding: 8px 16px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 2px; border: none; cursor: pointer; }
                .btn:hover { background: #5a6fd8; }
                .btn-clear { background: #e74c3c; }
                .tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 20px; }
                .tab { padding: 10px 20px; cursor: pointer; background: #f8f9fa; margin-right: 5px; border-radius: 5px 5px 0 0; }
                .tab.active { background: #667eea; color: white; }
                .tab-content { display: none; }
                .tab-content.active { display: block; }
                .auto-refresh-status { color: #27ae60; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🩺 MIW Railway Diagnostic Dashboard</h1>
                    <p>Centralized Error Logging & Monitoring System</p>
                    <p><span class="refresh-indicator"></span> <span class="auto-refresh-status">Auto-refresh on backend operations</span></p>
                    <p><strong>Environment:</strong> <?= $this->isRailway ? 'Railway Production' : 'Local Development' ?></p>
                </div>

                <div class="dashboard-grid">
                    <!-- Statistics Overview -->
                    <div class="widget">
                        <h3>📊 Error Statistics</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?= $stats['total'] ?></div>
                                <div>Total Logs</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= $stats['recent_count'] ?></div>
                                <div>Last Hour</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= $stats['by_severity']['error'] ?? 0 ?></div>
                                <div>Errors</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= $stats['by_severity']['warning'] ?? 0 ?></div>
                                <div>Warnings</div>
                            </div>
                        </div>
                    </div>

                    <!-- Log Type Distribution -->
                    <div class="widget">
                        <h3>📈 Log Types</h3>
                        <?php foreach ($stats['by_type'] as $type => $count): ?>
                            <div class="log-entry log-type-<?= $type ?>">
                                <strong><?= strtoupper($type) ?></strong>: <?= $count ?> entries
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- System Status -->
                    <div class="widget">
                        <h3>🔧 System Status</h3>
                        <div class="log-entry <?= $this->conn ? 'severity-info' : 'severity-error' ?>">
                            <strong>Database:</strong> <?= $this->conn ? '✅ Connected' : '❌ Disconnected' ?>
                        </div>
                        <div class="log-entry severity-info">
                            <strong>PHP Version:</strong> <?= PHP_VERSION ?>
                        </div>
                        <div class="log-entry severity-info">
                            <strong>Memory Usage:</strong> <?= number_format(memory_get_usage(true) / 1024 / 1024, 2) ?> MB
                        </div>
                        <div class="log-entry severity-info">
                            <strong>Error Logging:</strong> ✅ Active
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="widget">
                        <h3>⚡ Quick Actions</h3>
                        <div class="controls">
                            <button onclick="location.reload()" class="btn">🔄 Refresh Now</button>
                            <a href="?clear_logs=1" class="btn btn-clear" onclick="return confirm('Clear all logs?')">🗑️ Clear Logs</a>
                            <button onclick="downloadLogs()" class="btn">📥 Download Logs</button>
                        </div>
                    </div>
                </div>

                <div class="tabs">
                    <div class="tab active" onclick="showTab('all')">🔍 All Logs</div>
                    <div class="tab" onclick="showTab('php')">🐘 PHP Errors</div>
                    <div class="tab" onclick="showTab('mysql')">🗄️ MySQL Errors</div>
                    <div class="tab" onclick="showTab('apache')">🌐 Apache Errors</div>
                    <div class="tab" onclick="showTab('custom')">⚙️ Custom Logs</div>
                </div>

                <div id="all" class="tab-content active">
                    <div class="widget">
                        <h3>📋 Latest Error Logs</h3>
                        <?php if (empty($latestLogs)): ?>
                            <div class="log-entry severity-info">
                                <strong>✅ No errors found</strong><br>
                                <small>System is running smoothly</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($latestLogs as $log): ?>
                                <div class="log-entry log-type-<?= $log['log_type'] ?> severity-<?= $log['severity'] ?>">
                                    <div class="log-timestamp"><?= $log['created_at'] ?> [<?= strtoupper($log['log_type']) ?>]</div>
                                    <div class="log-message"><strong><?= htmlspecialchars($log['message']) ?></strong></div>
                                    <?php if ($log['file_path']): ?>
                                        <div class="log-details">
                                            File: <?= htmlspecialchars($log['file_path']) ?>
                                            <?php if ($log['line_number']): ?>
                                                (Line: <?= $log['line_number'] ?>)
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php foreach (['php', 'mysql', 'apache', 'custom'] as $logType): ?>
                    <div id="<?= $logType ?>" class="tab-content">
                        <div class="widget">
                            <h3><?= strtoupper($logType) ?> Error Logs</h3>
                            <?php 
                            $typeLogs = $this->getLatestLogs(50, $logType);
                            if (empty($typeLogs)): 
                            ?>
                                <div class="log-entry severity-info">
                                    <strong>✅ No <?= $logType ?> errors found</strong>
                                </div>
                            <?php else: ?>
                                <?php foreach ($typeLogs as $log): ?>
                                    <div class="log-entry log-type-<?= $log['log_type'] ?> severity-<?= $log['severity'] ?>">
                                        <div class="log-timestamp"><?= $log['created_at'] ?></div>
                                        <div class="log-message"><strong><?= htmlspecialchars($log['message']) ?></strong></div>
                                        <?php if ($log['file_path']): ?>
                                            <div class="log-details">
                                                File: <?= htmlspecialchars($log['file_path']) ?>
                                                <?php if ($log['line_number']): ?>
                                                    (Line: <?= $log['line_number'] ?>)
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <script>
                let lastRefreshCheck = Math.floor(Date.now() / 1000);

                function showTab(tabName) {
                    // Hide all tab contents
                    const contents = document.querySelectorAll('.tab-content');
                    contents.forEach(content => content.classList.remove('active'));
                    
                    // Remove active class from all tabs
                    const tabs = document.querySelectorAll('.tab');
                    tabs.forEach(tab => tab.classList.remove('active'));
                    
                    // Show selected tab content
                    document.getElementById(tabName).classList.add('active');
                    
                    // Mark selected tab as active
                    event.target.classList.add('active');
                }

                // Check for refresh trigger every 2 seconds
                function checkForRefresh() {
                    fetch('?check_refresh=' + lastRefreshCheck)
                        .then(response => response.json())
                        .then(data => {
                            if (data.refresh_needed) {
                                location.reload();
                            }
                        })
                        .catch(error => {
                            // Silently handle errors
                        });
                }

                setInterval(checkForRefresh, 2000);

                function downloadLogs() {
                    window.open('?download_logs=1', '_blank');
                }

                // Add timestamp to title for real-time monitoring
                setInterval(() => {
                    const now = new Date().toLocaleTimeString();
                    document.title = `Diagnostic Dashboard - ${now}`;
                }, 1000);
            </script>
        </body>
        </html>
        <?php
    }
}

// Handle AJAX requests and actions
if (isset($_GET['check_refresh'])) {
    header('Content-Type: application/json');
    $logger = new DiagnosticLogger();
    $lastCheck = (int) $_GET['check_refresh'];
    echo json_encode(['refresh_needed' => $logger->needsRefresh($lastCheck)]);
    exit;
}

if (isset($_GET['clear_logs'])) {
    global $conn;
    if ($conn) {
        try {
            $conn->exec("TRUNCATE TABLE diagnostic_logs");
        } catch (Exception $e) {
            // Ignore errors
        }
    }
    header('Location: diagnostic.php');
    exit;
}

if (isset($_GET['download_logs'])) {
    $logger = new DiagnosticLogger();
    $logs = $logger->getLatestLogs(1000);
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="miw_railway_logs_' . date('Y-m-d_H-i-s') . '.txt"');
    
    echo "MIW Railway System - Error Logs Export\n";
    echo "Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "========================================\n\n";
    
    foreach ($logs as $log) {
        echo "[{$log['created_at']}] [{$log['log_type']}] [{$log['severity']}] {$log['message']}\n";
        if ($log['file_path']) {
            echo "  File: {$log['file_path']}:{$log['line_number']}\n";
        }
        echo "\n";
    }
    exit;
}

// Initialize diagnostic logger and render dashboard
$diagnosticLogger = new DiagnosticLogger();

// Log this access
$diagnosticLogger->logCustom('info', 'Diagnostic dashboard accessed', [
    'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

// Render the dashboard
$diagnosticLogger->renderDashboard();
?>
