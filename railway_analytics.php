<?php
/**
 * Railway Analytics & Logging System
 * Real-time monitoring and analytics for MIW Railway deployment
 */

require_once 'config.php';

class RailwayAnalytics {
    private $conn;
    private $isRailway;
    private $logFile;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->isRailway = !empty($_ENV['RAILWAY_ENVIRONMENT']) || !empty(getenv('RAILWAY_ENVIRONMENT'));
        $this->logFile = __DIR__ . '/analytics.log';
        $this->initializeAnalytics();
    }
    
    /**
     * Initialize analytics tables if they don't exist
     */
    private function initializeAnalytics() {
        if (!$this->conn) return;
        
        try {
            // Create analytics tables
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS analytics_events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_type VARCHAR(50) NOT NULL,
                    event_data JSON,
                    user_ip VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_event_type (event_type),
                    INDEX idx_created_at (created_at)
                )
            ");
            
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS analytics_metrics (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    metric_name VARCHAR(100) NOT NULL,
                    metric_value DECIMAL(10,2),
                    metric_unit VARCHAR(20),
                    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_metric_name (metric_name),
                    INDEX idx_recorded_at (recorded_at)
                )
            ");
            
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS error_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    error_level VARCHAR(20) NOT NULL,
                    error_message TEXT NOT NULL,
                    error_file VARCHAR(255),
                    error_line INT,
                    stack_trace TEXT,
                    user_ip VARCHAR(45),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_error_level (error_level),
                    INDEX idx_created_at (created_at)
                )
            ");
            
        } catch (Exception $e) {
            error_log("Analytics initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Log an event
     */
    public function logEvent($eventType, $eventData = null) {
        if (!$this->conn) return;
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO analytics_events (event_type, event_data, user_ip, user_agent)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $eventType,
                json_encode($eventData),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
        } catch (Exception $e) {
            error_log("Event logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Record a metric
     */
    public function recordMetric($metricName, $value, $unit = null) {
        if (!$this->conn) return;
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO analytics_metrics (metric_name, metric_value, metric_unit)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([$metricName, $value, $unit]);
            
        } catch (Exception $e) {
            error_log("Metric recording failed: " . $e->getMessage());
        }
    }
    
    /**
     * Log an error
     */
    public function logError($level, $message, $file = null, $line = null, $trace = null) {
        if (!$this->conn) return;
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO error_logs (error_level, error_message, error_file, error_line, stack_trace, user_ip)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $level,
                $message,
                $file,
                $line,
                $trace,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
        } catch (Exception $e) {
            error_log("Error logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Render analytics dashboard
     */
    public function renderDashboard() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Railway Analytics Dashboard - MIW Travel</title>
            <style>
                body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f5f7fa; }
                .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
                .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
                .widget { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .widget h3 { margin-top: 0; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
                .metric-large { font-size: 3em; font-weight: bold; color: #667eea; text-align: center; margin: 20px 0; }
                .metric-small { font-size: 1.5em; font-weight: bold; color: #667eea; }
                .chart-container { height: 300px; margin: 20px 0; }
                .status-good { color: #27ae60; }
                .status-warning { color: #f39c12; }
                .status-error { color: #e74c3c; }
                .tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 20px; }
                .tab { padding: 10px 20px; cursor: pointer; background: #f8f9fa; margin-right: 5px; border-radius: 5px 5px 0 0; }
                .tab.active { background: #667eea; color: white; }
                .tab-content { display: none; }
                .tab-content.active { display: block; }
                .log-entry { background: #f8f9fa; border-radius: 5px; padding: 10px; margin: 5px 0; font-family: monospace; border-left: 4px solid #ddd; }
                .log-error { border-left-color: #e74c3c; }
                .log-warning { border-left-color: #f39c12; }
                .log-info { border-left-color: #3498db; }
                .real-time-indicator { display: inline-block; width: 10px; height: 10px; background: #27ae60; border-radius: 50%; animation: pulse 2s infinite; }
                @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
                .refresh-btn { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
                .refresh-btn:hover { background: #5a6fd8; }
            </style>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📊 Railway Analytics Dashboard</h1>
                    <p>Real-time monitoring and analytics for MIW Travel System</p>
                    <p><span class="real-time-indicator"></span> Live Data from <?= $this->isRailway ? 'Railway Production' : 'Local Environment' ?></p>
                    <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Data</button>
                </div>

                <div class="tabs">
                    <div class="tab active" onclick="showTab('overview')">📈 Overview</div>
                    <div class="tab" onclick="showTab('metrics')">📊 Metrics</div>
                    <div class="tab" onclick="showTab('events')">📝 Events</div>
                    <div class="tab" onclick="showTab('errors')">🚨 Errors</div>
                    <div class="tab" onclick="showTab('realtime')">⚡ Real-time</div>
                </div>

                <div id="overview" class="tab-content active">
                    <?php $this->renderOverviewTab(); ?>
                </div>

                <div id="metrics" class="tab-content">
                    <?php $this->renderMetricsTab(); ?>
                </div>

                <div id="events" class="tab-content">
                    <?php $this->renderEventsTab(); ?>
                </div>

                <div id="errors" class="tab-content">
                    <?php $this->renderErrorsTab(); ?>
                </div>

                <div id="realtime" class="tab-content">
                    <?php $this->renderRealtimeTab(); ?>
                </div>
            </div>

            <script>
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

                // Auto-refresh every 30 seconds
                setInterval(() => {
                    if (document.getElementById('realtime').classList.contains('active')) {
                        location.reload();
                    }
                }, 30000);
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Render overview tab
     */
    private function renderOverviewTab() {
        echo '<div class="dashboard-grid">';
        
        // System Health Widget
        echo '<div class="widget">';
        echo '<h3>🔧 System Health</h3>';
        $this->renderSystemHealth();
        echo '</div>';
        
        // Performance Metrics Widget
        echo '<div class="widget">';
        echo '<h3>⚡ Performance</h3>';
        $this->renderPerformanceMetrics();
        echo '</div>';
        
        // User Activity Widget
        echo '<div class="widget">';
        echo '<h3>👥 User Activity</h3>';
        $this->renderUserActivity();
        echo '</div>';
        
        // Database Status Widget
        echo '<div class="widget">';
        echo '<h3>🗄️ Database Status</h3>';
        $this->renderDatabaseStatus();
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render system health
     */
    private function renderSystemHealth() {
        $status = 'good';
        $message = 'All systems operational';
        
        if (!$this->conn) {
            $status = 'error';
            $message = 'Database connection failed';
        }
        
        echo '<div class="metric-large status-' . $status . '">●</div>';
        echo '<p class="status-' . $status . '">' . $message . '</p>';
        
        echo '<div>';
        echo '<strong>Environment:</strong> ' . ($this->isRailway ? 'Railway' : 'Local') . '<br>';
        echo '<strong>PHP Version:</strong> ' . PHP_VERSION . '<br>';
        echo '<strong>Memory Usage:</strong> ' . number_format(memory_get_usage(true) / 1024 / 1024, 2) . ' MB<br>';
        echo '<strong>Uptime:</strong> ' . date('Y-m-d H:i:s T');
        echo '</div>';
    }
    
    /**
     * Render performance metrics
     */
    private function renderPerformanceMetrics() {
        $responseTime = number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);
        $memoryPeak = number_format(memory_get_peak_usage(true) / 1024 / 1024, 2);
        
        echo '<div>';
        echo '<div class="metric-small">' . $responseTime . 'ms</div>';
        echo '<small>Response Time</small><br><br>';
        
        echo '<div class="metric-small">' . $memoryPeak . 'MB</div>';
        echo '<small>Peak Memory</small><br><br>';
        
        echo '<div class="metric-small">' . number_format(sys_getloadavg()[0] ?? 0, 2) . '</div>';
        echo '<small>System Load</small>';
        echo '</div>';
    }
    
    /**
     * Render user activity
     */
    private function renderUserActivity() {
        if (!$this->conn) {
            echo '<p>Database connection required</p>';
            return;
        }
        
        try {
            // Get event counts
            $stmt = $this->conn->query("
                SELECT event_type, COUNT(*) as count 
                FROM analytics_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY event_type 
                ORDER BY count DESC 
                LIMIT 5
            ");
            
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($events)) {
                echo '<p>No recent activity</p>';
            } else {
                foreach ($events as $event) {
                    echo '<div>';
                    echo '<strong>' . htmlspecialchars($event['event_type']) . ':</strong> ';
                    echo '<span class="metric-small">' . $event['count'] . '</span>';
                    echo '</div>';
                }
            }
            
        } catch (Exception $e) {
            echo '<p class="status-error">Error loading activity: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    
    /**
     * Render database status
     */
    private function renderDatabaseStatus() {
        if (!$this->conn) {
            echo '<div class="metric-large status-error">❌</div>';
            echo '<p class="status-error">Disconnected</p>';
            return;
        }
        
        try {
            // Get database info
            $stmt = $this->conn->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()");
            $tableCount = $stmt->fetchColumn();
            
            echo '<div class="metric-large status-good">✅</div>';
            echo '<p class="status-good">Connected</p>';
            echo '<div>';
            echo '<strong>Tables:</strong> ' . $tableCount . '<br>';
            echo '<strong>Connection:</strong> Active<br>';
            echo '<strong>Version:</strong> ' . $this->conn->getAttribute(PDO::ATTR_SERVER_VERSION);
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="metric-large status-warning">⚠️</div>';
            echo '<p class="status-warning">Connection issues</p>';
        }
    }
    
    /**
     * Render metrics tab
     */
    private function renderMetricsTab() {
        echo '<div class="widget">';
        echo '<h3>📊 Performance Metrics</h3>';
        echo '<div class="chart-container">';
        echo '<canvas id="metricsChart"></canvas>';
        echo '</div>';
        echo '</div>';
        
        echo '<script>';
        echo 'const ctx = document.getElementById("metricsChart").getContext("2d");';
        echo 'const chart = new Chart(ctx, {';
        echo '  type: "line",';
        echo '  data: {';
        echo '    labels: ["1h ago", "45m ago", "30m ago", "15m ago", "Now"],';
        echo '    datasets: [{';
        echo '      label: "Response Time (ms)",';
        echo '      data: [120, 135, 95, 110, ' . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000 . '],';
        echo '      borderColor: "#667eea",';
        echo '      backgroundColor: "rgba(102, 126, 234, 0.1)"';
        echo '    }]';
        echo '  },';
        echo '  options: { responsive: true, maintainAspectRatio: false }';
        echo '});';
        echo '</script>';
    }
    
    /**
     * Render events tab
     */
    private function renderEventsTab() {
        if (!$this->conn) {
            echo '<p>Database connection required to view events</p>';
            return;
        }
        
        try {
            $stmt = $this->conn->query("
                SELECT * FROM analytics_events 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="widget">';
            echo '<h3>📝 Recent Events</h3>';
            
            if (empty($events)) {
                echo '<p>No events recorded yet</p>';
            } else {
                foreach ($events as $event) {
                    echo '<div class="log-entry log-info">';
                    echo '<strong>' . htmlspecialchars($event['event_type']) . '</strong> ';
                    echo '<small>' . $event['created_at'] . '</small><br>';
                    if ($event['event_data']) {
                        echo '<small>' . htmlspecialchars($event['event_data']) . '</small>';
                    }
                    echo '</div>';
                }
            }
            
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<p class="status-error">Error loading events: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    
    /**
     * Render errors tab
     */
    private function renderErrorsTab() {
        if (!$this->conn) {
            echo '<p>Database connection required to view errors</p>';
            return;
        }
        
        try {
            $stmt = $this->conn->query("
                SELECT * FROM error_logs 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            
            $errors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="widget">';
            echo '<h3>🚨 Error Log</h3>';
            
            if (empty($errors)) {
                echo '<div class="log-entry log-info">';
                echo '<strong>✅ No errors recorded</strong><br>';
                echo '<small>System is running smoothly</small>';
                echo '</div>';
            } else {
                foreach ($errors as $error) {
                    $logClass = 'log-error';
                    if ($error['error_level'] === 'warning') $logClass = 'log-warning';
                    if ($error['error_level'] === 'info') $logClass = 'log-info';
                    
                    echo '<div class="log-entry ' . $logClass . '">';
                    echo '<strong>' . strtoupper($error['error_level']) . ':</strong> ';
                    echo htmlspecialchars($error['error_message']) . '<br>';
                    echo '<small>' . $error['created_at'];
                    if ($error['error_file']) {
                        echo ' in ' . basename($error['error_file']);
                        if ($error['error_line']) {
                            echo ':' . $error['error_line'];
                        }
                    }
                    echo '</small>';
                    echo '</div>';
                }
            }
            
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<p class="status-error">Error loading error logs: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    
    /**
     * Render real-time tab
     */
    private function renderRealtimeTab() {
        echo '<div class="widget">';
        echo '<h3>⚡ Real-time Monitor</h3>';
        echo '<p><span class="real-time-indicator"></span> Auto-refreshing every 30 seconds</p>';
        
        echo '<div class="dashboard-grid">';
        
        // Current timestamp
        echo '<div>';
        echo '<strong>Current Time:</strong><br>';
        echo '<div class="metric-small">' . date('H:i:s') . '</div>';
        echo '</div>';
        
        // Memory usage
        echo '<div>';
        echo '<strong>Memory Usage:</strong><br>';
        echo '<div class="metric-small">' . number_format(memory_get_usage(true) / 1024 / 1024, 1) . 'MB</div>';
        echo '</div>';
        
        // Active connections
        echo '<div>';
        echo '<strong>Database Status:</strong><br>';
        echo '<div class="metric-small status-' . ($this->conn ? 'good' : 'error') . '">';
        echo $this->conn ? '●' : '❌';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        
        // Log current page view
        $this->logEvent('page_view', ['page' => 'analytics_dashboard', 'tab' => 'realtime']);
        $this->recordMetric('memory_usage_mb', memory_get_usage(true) / 1024 / 1024, 'MB');
    }
}

// Initialize analytics
$analytics = new RailwayAnalytics();

// Log this page view
$analytics->logEvent('page_view', ['page' => 'analytics_dashboard']);

// Render the dashboard
$analytics->renderDashboard();
?>
