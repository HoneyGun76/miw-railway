<?php
/**
 * Railway Deployment Manager & Analytics Dashboard
 * 
 * Real-time monitoring, analytics, and management interface
 * for the MIW Travel system deployed on Railway
 */

require_once 'deployment_logger.php';

class RailwayDeploymentManager {
    private $logger;
    private $conn;
    private $startTime;
    
    public function __construct() {
        global $deploymentLogger, $conn;
        $this->logger = $deploymentLogger;
        $this->conn = $conn;
        $this->startTime = microtime(true);
        $this->logger->info('deployment_manager', 'Deployment Manager initialized');
    }
    
    public function renderDashboard() {
        $status = $this->logger->getDeploymentStatus();
        $analytics = $this->getAnalytics();
        $healthChecks = $this->performHealthChecks();
        
        echo $this->generateDashboardHTML($status, $analytics, $healthChecks);
    }
    
    private function getAnalytics() {
        return [
            'traffic_stats' => $this->getTrafficStats(),
            'performance_trends' => $this->getPerformanceTrends(),
            'error_analytics' => $this->getErrorAnalytics(),
            'user_activity' => $this->getUserActivity(),
            'system_resources' => $this->getSystemResources()
        ];
    }
    
    private function getTrafficStats() {
        try {
            $stats = [];
            
            // Get hourly traffic for last 24 hours
            $stmt = $this->conn->prepare("
                SELECT 
                    DATE_FORMAT(timestamp, '%H:00') as hour,
                    COUNT(*) as requests,
                    COUNT(DISTINCT ip_address) as unique_visitors
                FROM deployment_logs 
                WHERE category = 'page_access' 
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H')
                ORDER BY hour
            ");
            $stmt->execute();
            $stats['hourly'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get most accessed pages
            $stmt = $this->conn->prepare("
                SELECT 
                    JSON_EXTRACT(context, '$.method') as method,
                    message,
                    COUNT(*) as hits
                FROM deployment_logs 
                WHERE category = 'page_access' 
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY message
                ORDER BY hits DESC
                LIMIT 10
            ");
            $stmt->execute();
            $stats['top_pages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            $this->logger->error('analytics', 'Failed to get traffic stats: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    private function getPerformanceTrends() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00') as time_bucket,
                    AVG(JSON_EXTRACT(context, '$.execution_time')) as avg_execution_time,
                    AVG(JSON_EXTRACT(context, '$.memory_usage')) as avg_memory_usage,
                    MAX(JSON_EXTRACT(context, '$.peak_memory')) as max_memory_usage
                FROM deployment_logs 
                WHERE JSON_EXTRACT(context, '$.execution_time') IS NOT NULL
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H')
                ORDER BY time_bucket
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('analytics', 'Failed to get performance trends: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    private function getErrorAnalytics() {
        try {
            // Error trends by hour
            $stmt = $this->conn->prepare("
                SELECT 
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00') as hour,
                    level,
                    COUNT(*) as count
                FROM deployment_logs 
                WHERE level IN ('ERROR', 'CRITICAL', 'WARNING')
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H'), level
                ORDER BY hour, level
            ");
            $stmt->execute();
            $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Top error messages
            $stmt = $this->conn->prepare("
                SELECT message, level, COUNT(*) as count,
                       MAX(timestamp) as last_occurrence
                FROM deployment_logs 
                WHERE level IN ('ERROR', 'CRITICAL')
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY message, level
                ORDER BY count DESC
                LIMIT 10
            ");
            $stmt->execute();
            $topErrors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'trends' => $trends,
                'top_errors' => $topErrors
            ];
        } catch (Exception $e) {
            $this->logger->error('analytics', 'Failed to get error analytics: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    private function getUserActivity() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ip_address,
                    COUNT(*) as page_views,
                    MAX(timestamp) as last_activity,
                    COUNT(DISTINCT DATE(timestamp)) as active_days
                FROM deployment_logs 
                WHERE category = 'page_access'
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY ip_address
                ORDER BY page_views DESC
                LIMIT 20
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('analytics', 'Failed to get user activity: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    private function getSystemResources() {
        return [
            'memory' => [
                'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'limit' => ini_get('memory_limit')
            ],
            'disk' => [
                'total_gb' => round(disk_total_space('.') / 1024 / 1024 / 1024, 2),
                'free_gb' => round(disk_free_space('.') / 1024 / 1024 / 1024, 2),
                'used_percentage' => round((1 - disk_free_space('.') / disk_total_space('.')) * 100, 2)
            ],
            'load' => sys_getloadavg(),
            'php_version' => PHP_VERSION,
            'uptime' => $this->getSystemUptime()
        ];
    }
    
    private function getSystemUptime() {
        // Estimate uptime based on oldest log entry
        try {
            $stmt = $this->conn->prepare("
                SELECT MIN(timestamp) as earliest_log 
                FROM deployment_logs 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['earliest_log']) {
                $uptime = time() - strtotime($result['earliest_log']);
                return $this->formatUptime($uptime);
            }
        } catch (Exception $e) {
            // Fallback to process uptime
        }
        
        return 'Unknown';
    }
    
    private function formatUptime($seconds) {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return sprintf('%dd %dh %dm', $days, $hours, $minutes);
    }
    
    private function performHealthChecks() {
        $checks = [];
        
        // Database connectivity check
        $checks['database'] = $this->checkDatabase();
        
        // File system check
        $checks['filesystem'] = $this->checkFileSystem();
        
        // External dependencies check
        $checks['dependencies'] = $this->checkDependencies();
        
        // Configuration check
        $checks['configuration'] = $this->checkConfiguration();
        
        // Performance check
        $checks['performance'] = $this->checkPerformance();
        
        return $checks;
    }
    
    private function checkDatabase() {
        try {
            $start = microtime(true);
            $stmt = $this->conn->query("SELECT 1");
            $response_time = (microtime(true) - $start) * 1000;
            
            // Check critical tables
            $tables = ['data_paket', 'pendaftar_haji', 'pendaftar_umroh'];
            $table_status = [];
            
            foreach ($tables as $table) {
                try {
                    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM `$table`");
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    $table_status[$table] = ['status' => 'OK', 'count' => $count];
                } catch (Exception $e) {
                    $table_status[$table] = ['status' => 'ERROR', 'error' => $e->getMessage()];
                }
            }
            
            return [
                'status' => 'OK',
                'response_time_ms' => round($response_time, 2),
                'tables' => $table_status
            ];
        } catch (Exception $e) {
            return [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkFileSystem() {
        $uploadDir = isset($_ENV['RAILWAY_ENVIRONMENT']) ? '/tmp/uploads' : __DIR__ . '/uploads';
        
        $subdirs = ['documents', 'payments', 'photos', 'cancellations'];
        $dir_status = [];
        
        foreach ($subdirs as $subdir) {
            $path = $uploadDir . '/' . $subdir;
            $dir_status[$subdir] = [
                'exists' => is_dir($path),
                'writable' => is_writable($path),
                'files_count' => is_dir($path) ? count(scandir($path)) - 2 : 0
            ];
        }
        
        return [
            'status' => is_dir($uploadDir) && is_writable($uploadDir) ? 'OK' : 'ERROR',
            'base_directory' => $uploadDir,
            'subdirectories' => $dir_status
        ];
    }
    
    private function checkDependencies() {
        $extensions = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'openssl', 'curl'];
        $ext_status = [];
        
        foreach ($extensions as $ext) {
            $ext_status[$ext] = extension_loaded($ext);
        }
        
        return [
            'status' => !in_array(false, $ext_status) ? 'OK' : 'WARNING',
            'extensions' => $ext_status
        ];
    }
    
    private function checkConfiguration() {
        $config_checks = [
            'environment' => isset($_ENV['RAILWAY_ENVIRONMENT']),
            'database_configured' => isset($this->conn),
            'smtp_configured' => defined('SMTP_HOST') && !empty(SMTP_HOST),
            'upload_path_configured' => defined('UPLOAD_PATH') && !empty(UPLOAD_PATH),
            'timezone_set' => date_default_timezone_get() !== 'UTC'
        ];
        
        return [
            'status' => !in_array(false, $config_checks) ? 'OK' : 'WARNING',
            'checks' => $config_checks
        ];
    }
    
    private function checkPerformance() {
        $start = microtime(true);
        
        // Simulate some work
        for ($i = 0; $i < 1000; $i++) {
            md5($i);
        }
        
        $execution_time = (microtime(true) - $start) * 1000;
        $memory_usage = memory_get_usage(true) / 1024 / 1024;
        
        return [
            'status' => $execution_time < 100 && $memory_usage < 128 ? 'OK' : 'WARNING',
            'execution_time_ms' => round($execution_time, 2),
            'memory_usage_mb' => round($memory_usage, 2)
        ];
    }
    
    private function generateDashboardHTML($status, $analytics, $healthChecks) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Railway Deployment Manager - MIW Travel</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
                .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
                .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
                .card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .card h3 { color: #2c3e50; margin-bottom: 15px; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
                .status-indicator { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px; }
                .status-ok { background-color: #27ae60; }
                .status-warning { background-color: #f39c12; }
                .status-error { background-color: #e74c3c; }
                .metric { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px; background: #f8f9fa; border-radius: 5px; }
                .metric-label { font-weight: 600; color: #34495e; }
                .metric-value { color: #2980b9; font-weight: bold; }
                .chart-container { height: 200px; background: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center; margin: 10px 0; }
                .log-entry { padding: 8px; margin: 5px 0; background: #f8f9fa; border-left: 4px solid #3498db; border-radius: 3px; font-size: 12px; }
                .log-error { border-left-color: #e74c3c; }
                .log-warning { border-left-color: #f39c12; }
                .refresh-btn { position: fixed; bottom: 20px; right: 20px; background: #667eea; color: white; border: none; padding: 15px; border-radius: 50%; cursor: pointer; font-size: 18px; }
                .health-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
                .health-item { padding: 10px; background: #f8f9fa; border-radius: 5px; text-align: center; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f2f2f2; }
                .progress-bar { width: 100%; height: 20px; background-color: #ecf0f1; border-radius: 10px; overflow: hidden; }
                .progress-fill { height: 100%; background: linear-gradient(90deg, #27ae60, #f39c12); border-radius: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="container">
                    <h1>🚀 Railway Deployment Manager</h1>
                    <p>Real-time monitoring and analytics for MIW Travel System</p>
                    <p><strong>Environment:</strong> <?= htmlspecialchars($status['environment']) ?> | 
                       <strong>Service:</strong> <?= htmlspecialchars($_ENV['RAILWAY_SERVICE_NAME'] ?? 'Unknown') ?> |
                       <strong>Last Updated:</strong> <?= date('Y-m-d H:i:s') ?></p>
                </div>
            </div>

            <div class="container">
                <!-- Service Health Overview -->
                <div class="card">
                    <h3>🏥 Service Health Overview</h3>
                    <div class="health-grid">
                        <?php foreach ($healthChecks as $service => $check): ?>
                            <div class="health-item">
                                <div class="status-indicator status-<?= $check['status'] === 'OK' ? 'ok' : ($check['status'] === 'WARNING' ? 'warning' : 'error') ?>"></div>
                                <strong><?= ucfirst($service) ?></strong>
                                <div><?= $check['status'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <!-- System Resources -->
                    <div class="card">
                        <h3>📊 System Resources</h3>
                        <?php $resources = $analytics['system_resources']; ?>
                        <div class="metric">
                            <span class="metric-label">Memory Usage</span>
                            <span class="metric-value"><?= $resources['memory']['current_mb'] ?> MB / <?= $resources['memory']['limit'] ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Disk Usage</span>
                            <span class="metric-value"><?= $resources['disk']['used_percentage'] ?>% (<?= $resources['disk']['free_gb'] ?> GB free)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= min($resources['disk']['used_percentage'], 100) ?>%"></div>
                        </div>
                        <div class="metric">
                            <span class="metric-label">System Uptime</span>
                            <span class="metric-value"><?= $resources['uptime'] ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">PHP Version</span>
                            <span class="metric-value"><?= $resources['php_version'] ?></span>
                        </div>
                    </div>

                    <!-- Database Status -->
                    <div class="card">
                        <h3>🗄️ Database Status</h3>
                        <?php $db = $status['database_status']; ?>
                        <?php if (isset($db['status']) && $db['status'] === 'connected'): ?>
                            <div class="metric">
                                <span class="metric-label">Status</span>
                                <span class="metric-value">✅ Connected</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Driver</span>
                                <span class="metric-value"><?= htmlspecialchars($db['driver']) ?></span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Tables</span>
                                <span class="metric-value"><?= $db['tables_count'] ?> tables</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Response Time</span>
                                <span class="metric-value"><?= $healthChecks['database']['response_time_ms'] ?> ms</span>
                            </div>
                        <?php else: ?>
                            <div class="metric">
                                <span class="metric-label">Status</span>
                                <span class="metric-value">❌ Error</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Traffic Analytics -->
                    <div class="card">
                        <h3>📈 Traffic Analytics (24h)</h3>
                        <?php if (isset($analytics['traffic_stats']['hourly'])): ?>
                            <div class="chart-container">
                                <div>Traffic chart placeholder - <?= count($analytics['traffic_stats']['hourly']) ?> data points</div>
                            </div>
                            <h4>Top Pages:</h4>
                            <table>
                                <tr><th>Page</th><th>Hits</th></tr>
                                <?php foreach (array_slice($analytics['traffic_stats']['top_pages'], 0, 5) as $page): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(substr($page['message'], 0, 50)) ?>...</td>
                                        <td><?= $page['hits'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php else: ?>
                            <p>No traffic data available</p>
                        <?php endif; ?>
                    </div>

                    <!-- Error Analytics -->
                    <div class="card">
                        <h3>⚠️ Error Analytics</h3>
                        <?php if (isset($analytics['error_analytics']['top_errors'])): ?>
                            <?php foreach ($analytics['error_analytics']['top_errors'] as $error): ?>
                                <div class="log-entry log-<?= strtolower($error['level']) ?>">
                                    <strong><?= $error['level'] ?></strong> (<?= $error['count'] ?> times)
                                    <br><?= htmlspecialchars(substr($error['message'], 0, 100)) ?>...
                                    <br><small>Last: <?= $error['last_occurrence'] ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>✅ No recent errors</p>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Activity -->
                    <div class="card">
                        <h3>🔄 Recent Activity</h3>
                        <?php foreach ($status['recent_activity'] as $activity): ?>
                            <div class="log-entry">
                                <strong><?= $activity['timestamp'] ?></strong> [<?= $activity['level'] ?>]
                                <br><?= htmlspecialchars($activity['message']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- File System Status -->
                    <div class="card">
                        <h3>📁 File System</h3>
                        <?php $fs = $healthChecks['filesystem']; ?>
                        <div class="metric">
                            <span class="metric-label">Upload Directory</span>
                            <span class="metric-value"><?= $fs['status'] === 'OK' ? '✅' : '❌' ?> <?= htmlspecialchars($fs['base_directory']) ?></span>
                        </div>
                        <?php foreach ($fs['subdirectories'] as $dir => $info): ?>
                            <div class="metric">
                                <span class="metric-label"><?= ucfirst($dir) ?></span>
                                <span class="metric-value">
                                    <?= $info['exists'] ? '✅' : '❌' ?> 
                                    <?= $info['writable'] ? '✏️' : '🔒' ?> 
                                    (<?= $info['files_count'] ?> files)
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button class="refresh-btn" onclick="location.reload()" title="Refresh Dashboard">🔄</button>

            <script>
                // Auto-refresh every 30 seconds
                setTimeout(() => location.reload(), 30000);
                
                // Add real-time timestamp
                setInterval(() => {
                    const now = new Date().toLocaleString();
                    document.title = `Railway Manager - ${now}`;
                }, 1000);
            </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}

// Initialize and render if accessed directly
if (basename($_SERVER['REQUEST_URI'], '?') === basename(__FILE__)) {
    $manager = new RailwayDeploymentManager();
    $manager->renderDashboard();
}

?>
