<?php
/**
 * Railway Diagnostic and Management Tool
 * 
 * This tool helps diagnose Railway deployment status,
 * manage environment variables, check database connections,
 * and monitor file upload functionality.
 */

require_once 'config.php';
require_once 'railway_file_manager.php';

class RailwayDiagnostics {
    private $conn;
    private $fileManager;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->fileManager = new RailwayFileManager();
    }
    
    /**
     * Run comprehensive Railway diagnostics
     */
    public function runDiagnostics() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Railway Diagnostics - MIW Travel</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
                .container { max-width: 1200px; margin: 0 auto; }
                .section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .success { color: #27ae60; font-weight: bold; }
                .error { color: #e74c3c; font-weight: bold; }
                .warning { color: #f39c12; font-weight: bold; }
                .info { color: #3498db; font-weight: bold; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f8f9fa; }
                .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
                .badge-success { background-color: #27ae60; color: white; }
                .badge-error { background-color: #e74c3c; color: white; }
                .badge-warning { background-color: #f39c12; color: white; }
                .btn { padding: 10px 15px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
                .btn-primary { background-color: #3498db; color: white; }
                .btn-success { background-color: #27ae60; color: white; }
                .btn-danger { background-color: #e74c3c; color: white; }
                .code-block { background-color: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: monospace; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="section">
                    <h1>🚀 Railway Diagnostics - MIW Travel System</h1>
                    <p>Comprehensive health check for your Railway deployment</p>
                    <p><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s T') ?></p>
                </div>
                
                <?php
                $this->checkRailwayEnvironment();
                $this->checkDatabaseConnection();
                $this->checkFileUploadSystem();
                $this->checkEnvironmentVariables();
                $this->checkApplicationHealth();
                $this->showRailwayCommands();
                ?>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Check Railway environment
     */
    private function checkRailwayEnvironment() {
        echo '<div class="section">';
        echo '<h2>🌐 Railway Environment Status</h2>';
        
        $railwayInfo = $this->fileManager->getRailwayInfo();
        $isRailway = $railwayInfo['is_railway'];
        
        if ($isRailway) {
            echo '<div class="success">✅ Running on Railway</div>';
            
            echo '<table>';
            echo '<tr><th>Property</th><th>Value</th><th>Status</th></tr>';
            
            $checks = [
                'Project ID' => $_ENV['RAILWAY_PROJECT_ID'] ?? getenv('RAILWAY_PROJECT_ID') ?? 'Not detected',
                'Environment' => $_ENV['RAILWAY_ENVIRONMENT'] ?? getenv('RAILWAY_ENVIRONMENT') ?? 'production',
                'Static URL' => $_ENV['RAILWAY_STATIC_URL'] ?? getenv('RAILWAY_STATIC_URL') ?? $_SERVER['HTTP_HOST'] ?? 'Not set',
                'Upload Directory' => $railwayInfo['upload_dir'],
                'Persistent Storage' => $railwayInfo['persistent_storage'] ? 'Yes' : 'No'
            ];
            
            foreach ($checks as $key => $value) {
                $status = $value !== 'Not detected' && $value !== 'Not set' ? 
                    '<span class="badge badge-success">OK</span>' : 
                    '<span class="badge badge-warning">Check</span>';
                echo "<tr><td>{$key}</td><td>{$value}</td><td>{$status}</td></tr>";
            }
            echo '</table>';
            
        } else {
            echo '<div class="warning">⚠️ Not running on Railway (Local development)</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Check database connection
     */
    private function checkDatabaseConnection() {
        echo '<div class="section">';
        echo '<h2>🗄️ Database Connection Status</h2>';
        
        if ($this->conn) {
            echo '<div class="success">✅ Database connected successfully</div>';
            
            try {
                // Get database info
                $stmt = $this->conn->query("SELECT VERSION() as version");
                $result = $stmt->fetch();
                
                echo '<table>';
                echo '<tr><th>Property</th><th>Value</th></tr>';
                echo '<tr><td>Database Version</td><td>' . ($result['version'] ?? 'Unknown') . '</td></tr>';
                echo '<tr><td>Connection Type</td><td>' . $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME) . '</td></tr>';
                
                // Check if tables exist
                $tables = ['registrations', 'file_metadata'];
                foreach ($tables as $table) {
                    try {
                        $this->conn->query("SELECT 1 FROM {$table} LIMIT 1");
                        echo "<tr><td>Table: {$table}</td><td><span class='badge badge-success'>Exists</span></td></tr>";
                    } catch (Exception $e) {
                        echo "<tr><td>Table: {$table}</td><td><span class='badge badge-error'>Missing</span></td></tr>";
                    }
                }
                echo '</table>';
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Database query failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
        } else {
            echo '<div class="error">❌ Database connection failed</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Check file upload system
     */
    private function checkFileUploadSystem() {
        echo '<div class="section">';
        echo '<h2>📁 File Upload System Status</h2>';
        
        $railwayInfo = $this->fileManager->getRailwayInfo();
        echo '<div class="info">' . $railwayInfo['message'] . '</div>';
        
        echo '<table>';
        echo '<tr><th>Component</th><th>Status</th><th>Details</th></tr>';
        
        // Check upload directories
        $directories = ['documents', 'payments', 'cancellations', 'photos'];
        foreach ($directories as $dir) {
            $fullPath = $railwayInfo['upload_dir'] . '/' . $dir;
            $exists = is_dir($fullPath);
            $writable = $exists && is_writable($fullPath);
            
            $status = $exists && $writable ? 
                '<span class="badge badge-success">Ready</span>' : 
                '<span class="badge badge-error">Issue</span>';
            
            $details = $exists ? 
                ($writable ? 'Writable' : 'Not writable') : 
                'Does not exist';
            
            echo "<tr><td>Directory: {$dir}</td><td>{$status}</td><td>{$details}</td></tr>";
        }
        
        // Check PHP upload settings
        $uploadSettings = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit')
        ];
        
        foreach ($uploadSettings as $setting => $value) {
            echo "<tr><td>PHP: {$setting}</td><td><span class='badge badge-success'>OK</span></td><td>{$value}</td></tr>";
        }
        
        echo '</table>';
        
        // Test file upload form
        echo '<h3>📤 Test File Upload</h3>';
        echo '<form method="post" enctype="multipart/form-data">';
        echo '<input type="file" name="test_file" accept=".pdf,.jpg,.jpeg,.png" required>';
        echo '<select name="test_directory">';
        foreach ($directories as $dir) {
            echo "<option value='{$dir}'>{$dir}</option>";
        }
        echo '</select>';
        echo '<button type="submit" name="test_upload" class="btn btn-primary">Test Upload</button>';
        echo '</form>';
        
        // Handle test upload
        if (isset($_POST['test_upload']) && isset($_FILES['test_file'])) {
            $result = $this->fileManager->handleUpload($_FILES['test_file'], $_POST['test_directory'], 'test_upload');
            
            if ($result['success']) {
                echo '<div class="success">✅ Test upload successful!</div>';
                echo '<div class="code-block">File: ' . $result['filename'] . '<br>URL: ' . $result['url'] . '</div>';
            } else {
                echo '<div class="error">❌ Test upload failed: ' . $result['error'] . '</div>';
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Check environment variables
     */
    private function checkEnvironmentVariables() {
        echo '<div class="section">';
        echo '<h2>⚙️ Environment Variables</h2>';
        
        $requiredVars = [
            'APP_ENV' => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'Not set',
            'DB_HOST' => $_ENV['DB_HOST'] ?? $_ENV['MYSQL_HOST'] ?? getenv('DB_HOST') ?? 'Not set',
            'DB_NAME' => $_ENV['DB_NAME'] ?? $_ENV['MYSQL_DATABASE'] ?? getenv('DB_NAME') ?? 'Not set',
            'DB_USER' => $_ENV['DB_USER'] ?? $_ENV['MYSQL_USER'] ?? getenv('DB_USER') ?? 'Not set',
            'SMTP_HOST' => $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?? 'Not set',
            'SMTP_USERNAME' => $_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?? 'Not set',
            'MAX_FILE_SIZE' => $_ENV['MAX_FILE_SIZE'] ?? getenv('MAX_FILE_SIZE') ?? 'Not set',
            'UPLOAD_PATH' => $_ENV['UPLOAD_PATH'] ?? getenv('UPLOAD_PATH') ?? 'Not set'
        ];
        
        echo '<table>';
        echo '<tr><th>Variable</th><th>Value</th><th>Status</th></tr>';
        
        foreach ($requiredVars as $var => $value) {
            $status = $value !== 'Not set' ? 
                '<span class="badge badge-success">Set</span>' : 
                '<span class="badge badge-error">Missing</span>';
            
            $displayValue = $value === 'Not set' ? $value : 
                (strpos($var, 'PASSWORD') !== false ? '***hidden***' : $value);
            
            echo "<tr><td>{$var}</td><td>{$displayValue}</td><td>{$status}</td></tr>";
        }
        echo '</table>';
        
        echo '</div>';
    }
    
    /**
     * Check application health
     */
    private function checkApplicationHealth() {
        echo '<div class="section">';
        echo '<h2>🏥 Application Health Check</h2>';
        
        $healthChecks = [
            'PHP Version' => PHP_VERSION,
            'PDO Available' => extension_loaded('pdo') ? 'Yes' : 'No',
            'GD Extension' => extension_loaded('gd') ? 'Yes' : 'No',
            'cURL Extension' => extension_loaded('curl') ? 'Yes' : 'No',
            'Session Status' => session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive',
            'Error Reporting' => error_reporting() ? 'Enabled' : 'Disabled',
            'Log Errors' => ini_get('log_errors') ? 'Yes' : 'No'
        ];
        
        echo '<table>';
        echo '<tr><th>Check</th><th>Status</th></tr>';
        
        foreach ($healthChecks as $check => $status) {
            $isGood = in_array($status, ['Yes', 'Active', PHP_VERSION]) || 
                     version_compare($status, '7.4', '>=');
            $badge = $isGood ? 'badge-success' : 'badge-warning';
            
            echo "<tr><td>{$check}</td><td><span class='badge {$badge}'>{$status}</span></td></tr>";
        }
        echo '</table>';
        
        echo '</div>';
    }
    
    /**
     * Show Railway management commands
     */
    private function showRailwayCommands() {
        echo '<div class="section">';
        echo '<h2>🛠️ Railway Management Commands</h2>';
        
        echo '<p>Use these Railway CLI commands to manage your deployment:</p>';
        
        $commands = [
            'View logs' => 'railway logs',
            'Open application' => 'railway open',
            'Check status' => 'railway status',
            'List variables' => 'railway variables',
            'Connect to database' => 'railway connect mysql',
            'Open shell' => 'railway shell',
            'Deploy changes' => 'railway up',
            'View services' => 'railway services',
            'Environment info' => 'railway environment',
            'Project info' => 'railway project'
        ];
        
        echo '<table>';
        echo '<tr><th>Action</th><th>Command</th></tr>';
        
        foreach ($commands as $action => $command) {
            echo "<tr><td>{$action}</td><td><code>{$command}</code></td></tr>";
        }
        echo '</table>';
        
        echo '<div class="code-block">';
        echo '<strong>Quick Setup Commands:</strong><br>';
        echo '# Link to your project<br>';
        echo 'railway link 2725c7e0-071b-43ea-9be7-33142b967d77<br><br>';
        
        echo '# Set environment variables<br>';
        echo 'railway variables set SMTP_USERNAME=your-email@gmail.com<br>';
        echo 'railway variables set SMTP_PASSWORD=your-app-password<br>';
        echo 'railway variables set APP_ENV=production<br><br>';
        
        echo '# Deploy current code<br>';
        echo 'railway up<br>';
        echo '</div>';
        
        echo '</div>';
    }
}

// Run diagnostics
$diagnostics = new RailwayDiagnostics();
$diagnostics->runDiagnostics();
?>
