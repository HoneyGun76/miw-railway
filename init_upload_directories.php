<?php
/**
 * Upload Directory Initialization for Heroku
 * 
 * This script ensures all upload directories are properly created on Heroku
 * and provides fallback mechanisms for file storage issues.
 */

require_once 'config.php';

class UploadDirectoryInitializer {
    private $isRailway;
    private $uploadBaseDir;
    private $results = [];
    
    public function __construct() {
        $this->isRailway = !empty($_ENV['RAILWAY_ENVIRONMENT']) || !empty(getenv('RAILWAY_ENVIRONMENT'));
        $this->uploadBaseDir = '/tmp/uploads';
    }
    
    public function initializeDirectories() {
        echo "<!DOCTYPE html><html><head><title>Upload Directory Initialization</title>";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
            .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
            .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
            .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
            .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background: #f8f9fa; }
        </style></head><body>";
        
        echo "<div class='container'>";
        echo "<h1>🚀 MIW Upload Directory Initialization</h1>";
        echo "<p><strong>Environment:</strong> " . ($this->isRailway ? 'Railway Production' : 'Local Development') . "</p>";
        echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s T') . "</p>";
        
        // Step 1: Check current environment
        $this->checkEnvironment();
        
        // Step 2: Create upload directories
        $this->createUploadDirectories();
        
        // Step 3: Test directory permissions
        $this->testDirectoryPermissions();
        
        // Step 4: Initialize file handlers
        $this->initializeFileHandlers();
        
        // Step 5: Create test file
        $this->testFileOperations();
        
        // Step 6: Generate summary
        $this->generateSummary();
        
        echo "</div></body></html>";
    }
    
    private function checkEnvironment() {
        echo "<div class='info'>";
        echo "<h2>📋 Environment Check</h2>";
        
        $env_vars = [
            'DYNO' => $_ENV['DYNO'] ?? getenv('DYNO') ?? 'Not set',
            'PWD' => $_ENV['PWD'] ?? getenv('PWD') ?? getcwd(),
            'TMPDIR' => $_ENV['TMPDIR'] ?? getenv('TMPDIR') ?? sys_get_temp_dir(),
            'HOME' => $_ENV['HOME'] ?? getenv('HOME') ?? 'Not set'
        ];
        
        echo "<table>";
        echo "<tr><th>Variable</th><th>Value</th></tr>";
        foreach ($env_vars as $var => $value) {
            echo "<tr><td>{$var}</td><td>{$value}</td></tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Upload Base Directory:</strong> {$this->uploadBaseDir}</p>";
        echo "<p><strong>System Temp Directory:</strong> " . sys_get_temp_dir() . "</p>";
        echo "</div>";
    }
    
    private function createUploadDirectories() {
        echo "<div class='info'>";
        echo "<h2>📁 Creating Upload Directories</h2>";
        
        $directories = [
            $this->uploadBaseDir => 'Main Upload Directory',
            $this->uploadBaseDir . '/documents' => 'Documents Directory',
            $this->uploadBaseDir . '/payments' => 'Payments Directory',
            $this->uploadBaseDir . '/cancellations' => 'Cancellations Directory',
            $this->uploadBaseDir . '/photos' => 'Photos Directory'
        ];
        
        echo "<table>";
        echo "<tr><th>Directory</th><th>Description</th><th>Status</th><th>Action</th></tr>";
        
        foreach ($directories as $dir => $description) {
            $existed = is_dir($dir);
            $created = false;
            $writable = false;
            
            if (!$existed) {
                $created = mkdir($dir, 0755, true);
                $this->results[] = "Created directory: {$dir}";
            } else {
                $created = true;
                $this->results[] = "Directory exists: {$dir}";
            }
            
            if ($created) {
                $writable = is_writable($dir);
                
                // Create .htaccess for security
                $htaccessFile = $dir . '/.htaccess';
                if (!file_exists($htaccessFile)) {
                    file_put_contents($htaccessFile, "Order deny,allow\nDeny from all\n");
                }
                
                // Create index.php for security
                $indexFile = $dir . '/index.php';
                if (!file_exists($indexFile)) {
                    file_put_contents($indexFile, "<?php\n// Access denied\nexit('Access denied');\n?>");
                }
            }
            
            $status = $existed ? "✅ Existed" : ($created ? "✅ Created" : "❌ Failed");
            $writable_status = $writable ? "✅ Writable" : "❌ Not writable";
            $action = $created ? $writable_status : "N/A";
            
            echo "<tr><td>{$dir}</td><td>{$description}</td><td>{$status}</td><td>{$action}</td></tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    
    private function testDirectoryPermissions() {
        echo "<div class='info'>";
        echo "<h2>🔐 Testing Directory Permissions</h2>";
        
        $directories = [
            $this->uploadBaseDir . '/documents',
            $this->uploadBaseDir . '/payments',
            $this->uploadBaseDir . '/cancellations',
            $this->uploadBaseDir . '/photos'
        ];
        
        echo "<table>";
        echo "<tr><th>Directory</th><th>Read</th><th>Write</th><th>Execute</th><th>Overall Status</th></tr>";
        
        foreach ($directories as $dir) {
            $readable = is_readable($dir);
            $writable = is_writable($dir);
            $executable = is_executable($dir);
            
            $read_status = $readable ? "✅" : "❌";
            $write_status = $writable ? "✅" : "❌";
            $exec_status = $executable ? "✅" : "❌";
            $overall = ($readable && $writable && $executable) ? "✅ Good" : "⚠️ Issues";
            
            echo "<tr><td>{$dir}</td><td>{$read_status}</td><td>{$write_status}</td><td>{$exec_status}</td><td>{$overall}</td></tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    
    private function initializeFileHandlers() {
        echo "<div class='info'>";
        echo "<h2>🔧 Initializing File Handlers</h2>";
        
        $handlers = [
            'upload_handler.php' => 'Main Upload Handler',
            'heroku_file_manager.php' => 'Heroku File Manager',
            'file_handler_heroku.php' => 'Heroku File Handler'
        ];
        
        echo "<table>";
        echo "<tr><th>Handler</th><th>Description</th><th>Status</th><th>Initialization</th></tr>";
        
        foreach ($handlers as $file => $description) {
            $exists = file_exists(__DIR__ . '/' . $file);
            $initialized = false;
            $error = '';
            
            if ($exists) {
                try {
                    require_once $file;
                    
                    if ($file === 'upload_handler.php' && class_exists('UploadHandler')) {
                        $handler = new UploadHandler();
                        $initialized = true;
                    } elseif ($file === 'heroku_file_manager.php' && class_exists('HerokuFileManager')) {
                        $manager = new HerokuFileManager();
                        $initialized = true;
                    } elseif ($file === 'file_handler_heroku.php' && class_exists('HerokuFileHandler')) {
                        $handler = new HerokuFileHandler();
                        $initialized = true;
                    }
                    
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
            
            $status = $exists ? "✅ Found" : "❌ Missing";
            $init_status = $initialized ? "✅ Success" : ($error ? "❌ Error: {$error}" : "⚠️ Not initialized");
            
            echo "<tr><td>{$file}</td><td>{$description}</td><td>{$status}</td><td>{$init_status}</td></tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    
    private function testFileOperations() {
        echo "<div class='info'>";
        echo "<h2>📋 Testing File Operations</h2>";
        
        $testDir = $this->uploadBaseDir . '/documents';
        $testFile = $testDir . '/test_upload_' . time() . '.txt';
        $testContent = "Test upload content - " . date('Y-m-d H:i:s');
        
        try {
            // Test file creation
            $created = file_put_contents($testFile, $testContent);
            if ($created) {
                echo "<p>✅ File creation: Success ({$created} bytes written)</p>";
                
                // Test file reading
                $content = file_get_contents($testFile);
                if ($content === $testContent) {
                    echo "<p>✅ File reading: Success</p>";
                } else {
                    echo "<p>❌ File reading: Content mismatch</p>";
                }
                
                // Test file info
                $size = filesize($testFile);
                $mtime = filemtime($testFile);
                echo "<p>📊 File info: Size {$size} bytes, Modified " . date('Y-m-d H:i:s', $mtime) . "</p>";
                
                // Test file deletion
                $deleted = unlink($testFile);
                if ($deleted) {
                    echo "<p>✅ File deletion: Success</p>";
                } else {
                    echo "<p>❌ File deletion: Failed</p>";
                }
                
            } else {
                echo "<p>❌ File creation: Failed</p>";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ File operations error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        echo "</div>";
    }
    
    private function generateSummary() {
        echo "<div class='success'>";
        echo "<h2>📊 Initialization Summary</h2>";
        
        echo "<h3>Actions Performed:</h3>";
        echo "<ul>";
        foreach ($this->results as $result) {
            echo "<li>" . htmlspecialchars($result) . "</li>";
        }
        echo "</ul>";
        
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Test file uploads via form submissions</li>";
        echo "<li>Monitor error_logger.php for any upload issues</li>";
        echo "<li>Run testing_dashboard.php to validate all upload workflows</li>";
        echo "<li>Check deploy_debug.php to verify directory status on Railway</li>";
        echo "</ol>";
        
        if ($this->isRailway) {
            echo "<div class='success'>";
            echo "<h4>✅ Railway Storage Benefits:</h4>";
            echo "<ul>";
            echo "<li>Files are stored on persistent filesystem and persist across deployments</li>";
            echo "<li>No need for external cloud storage for basic file operations</li>";
            echo "<li>Reliable file serving and download functionality</li>";
            echo "<li>Monitor file storage and implement cleanup routines</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        echo "</div>";
    }
}

// Initialize upload directories
$initializer = new UploadDirectoryInitializer();
$initializer->initializeDirectories();
?>
