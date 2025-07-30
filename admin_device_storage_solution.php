<?php
/**
 * Admin Device Storage Solution - Hybrid Approach
 * 
 * This creates a system where files can be served from admin's local device
 * while maintaining fallback to cloud/database storage
 */

require_once 'config.php';

class AdminDeviceStorageManager {
    private $conn;
    private $isHeroku;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->isHeroku = !empty($_ENV['DYNO']) || !empty(getenv('DYNO'));
    }
    
    /**
     * OPTION 1: Admin Device File Server
     * Create a local file server on admin device that Heroku can access
     */
    public function createAdminDeviceServer() {
        $serverCode = '<?php
/**
 * Admin Device File Server
 * Run this on admin device to serve files to Heroku
 * Usage: php -S 0.0.0.0:8080 admin_file_server.php
 */

// CORS headers for cross-origin access
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    exit(0);
}

// Validate request
if (!isset($_GET["file"]) || !isset($_GET["type"])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing file or type parameter"]);
    exit;
}

$filename = basename($_GET["file"]);
$type = $_GET["type"];

// Define allowed directories
$allowedTypes = [
    "documents" => "uploads/documents/",
    "payments" => "uploads/payments/", 
    "photos" => "uploads/photos/",
    "cancellations" => "uploads/cancellations/"
];

if (!isset($allowedTypes[$type])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid file type"]);
    exit;
}

$filePath = __DIR__ . "/" . $allowedTypes[$type] . $filename;

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(["error" => "File not found on admin device"]);
    exit;
}

// Validate file is actually uploaded file (security)
$uploadedFiles = glob(__DIR__ . "/" . $allowedTypes[$type] . "*");
if (!in_array($filePath, $uploadedFiles)) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized file access"]);
    exit;
}

// Serve file
$mimeType = mime_content_type($filePath);
header("Content-Type: " . $mimeType);
header("Content-Length: " . filesize($filePath));
header("Content-Disposition: inline; filename=\"" . $filename . "\"");
readfile($filePath);
?>';

        file_put_contents(__DIR__ . '/admin_file_server.php', $serverCode);
        return 'admin_file_server.php created';
    }
    
    /**
     * OPTION 2: Admin URL Registry
     * Admin registers their device URL with the system
     */
    public function createAdminUrlRegistry() {
        // Create table for admin device URLs
        try {
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS admin_device_registry (
                    id SERIAL PRIMARY KEY,
                    admin_id VARCHAR(50) NOT NULL,
                    device_url VARCHAR(255) NOT NULL,
                    device_name VARCHAR(100),
                    is_active BOOLEAN DEFAULT TRUE,
                    last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(admin_id)
                )
            ");
            
            return 'admin_device_registry table created';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    
    /**
     * OPTION 3: File Sync System
     * Auto-sync files between Heroku and admin device
     */
    public function createFileSyncSystem() {
        $syncCode = '<?php
/**
 * File Sync System - Admin Device Side
 * Automatically syncs uploaded files from Heroku to admin device
 */

require_once "config_local.php"; // Local database config

class FileSyncClient {
    private $herokuUrl;
    private $localUploadDir;
    private $syncInterval = 300; // 5 minutes
    
    public function __construct($herokuUrl, $localUploadDir = "./uploads/") {
        $this->herokuUrl = rtrim($herokuUrl, "/");
        $this->localUploadDir = rtrim($localUploadDir, "/") . "/";
        
        // Ensure local directories exist
        $this->ensureDirectories();
    }
    
    private function ensureDirectories() {
        $dirs = ["documents", "payments", "photos", "cancellations"];
        foreach ($dirs as $dir) {
            $fullPath = $this->localUploadDir . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }
    }
    
    public function syncFiles() {
        echo "[" . date("Y-m-d H:i:s") . "] Starting file sync...\n";
        
        // Get list of files from Heroku
        $fileList = $this->getFileListFromHeroku();
        
        if (!$fileList) {
            echo "Failed to get file list from Heroku\n";
            return;
        }
        
        foreach ($fileList as $file) {
            $this->syncFile($file);
        }
        
        echo "[" . date("Y-m-d H:i:s") . "] Sync completed\n";
    }
    
    private function getFileListFromHeroku() {
        $url = $this->herokuUrl . "/file_sync_api.php?action=list";
        
        $context = stream_context_create([
            "http" => [
                "timeout" => 30,
                "method" => "GET"
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        return $response ? json_decode($response, true) : null;
    }
    
    private function syncFile($fileInfo) {
        $localPath = $this->localUploadDir . $fileInfo["directory"] . "/" . $fileInfo["filename"];
        
        // Check if file exists locally and is up to date
        if (file_exists($localPath)) {
            $localTime = filemtime($localPath);
            $remoteTime = strtotime($fileInfo["upload_time"]);
            
            if ($localTime >= $remoteTime) {
                return; // File is up to date
            }
        }
        
        // Download file from Heroku
        $url = $this->herokuUrl . "/file_sync_api.php?action=download&file=" . 
               urlencode($fileInfo["filename"]) . "&type=" . urlencode($fileInfo["directory"]);
        
        $fileData = file_get_contents($url);
        
        if ($fileData !== false) {
            file_put_contents($localPath, $fileData);
            echo "Synced: {$fileInfo["filename"]}\n";
        } else {
            echo "Failed to sync: {$fileInfo["filename"]}\n";
        }
    }
    
    public function startDaemon() {
        echo "Starting file sync daemon...\n";
        
        while (true) {
            $this->syncFiles();
            sleep($this->syncInterval);
        }
    }
}

// Usage
if (php_sapi_name() === "cli") {
    $herokuUrl = "https://miw-travel-app-576ab80a8cab.herokuapp.com";
    $localDir = __DIR__ . "/uploads/";
    
    $sync = new FileSyncClient($herokuUrl, $localDir);
    
    if (isset($argv[1]) && $argv[1] === "daemon") {
        $sync->startDaemon();
    } else {
        $sync->syncFiles();
    }
}
?>';

        file_put_contents(__DIR__ . '/file_sync_client.php', $syncCode);
        return 'file_sync_client.php created';
    }
    
    /**
     * OPTION 4: Enhanced File Serving with Admin Device Fallback
     */
    public function createEnhancedFileServer() {
        $serverCode = '<?php
/**
 * Enhanced File Server with Admin Device Support
 * Checks multiple sources: Database -> Admin Device -> Cloud -> Error
 */

require_once "config.php";

class EnhancedFileServer {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function serveFile($filename, $directory) {
        $cleanFilename = basename($filename);
        
        // 1. Try database storage first (most reliable)
        if ($this->serveFromDatabase($cleanFilename, $directory)) {
            return;
        }
        
        // 2. Try admin device (if available)
        if ($this->serveFromAdminDevice($cleanFilename, $directory)) {
            return;
        }
        
        // 3. Try local/temporary storage
        if ($this->serveFromLocal($cleanFilename, $directory)) {
            return;
        }
        
        // 4. File not found anywhere
        $this->handleFileNotFound($cleanFilename, $directory);
    }
    
    private function serveFromDatabase($filename, $directory) {
        try {
            $stmt = $this->conn->prepare("
                SELECT file_data, mime_type, original_name, file_size
                FROM file_storage_enhanced 
                WHERE filename = ? AND directory = ? AND file_data IS NOT NULL
            ");
            $stmt->execute([$filename, $directory]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($file && $file["file_data"]) {
                header("Content-Type: " . $file["mime_type"]);
                header("Content-Length: " . $file["file_size"]);
                header("Content-Disposition: inline; filename=\"" . $file["original_name"] . "\"");
                echo $file["file_data"];
                return true;
            }
        } catch (Exception $e) {
            error_log("Database file serving error: " . $e->getMessage());
        }
        
        return false;
    }
    
    private function serveFromAdminDevice($filename, $directory) {
        try {
            // Get active admin device URLs
            $stmt = $this->conn->prepare("
                SELECT device_url FROM admin_device_registry 
                WHERE is_active = TRUE AND last_ping > NOW() - INTERVAL 10 MINUTE
                ORDER BY last_ping DESC
            ");
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($devices as $deviceUrl) {
                $url = rtrim($deviceUrl, "/") . "/admin_file_server.php?file=" . 
                       urlencode($filename) . "&type=" . urlencode($directory);
                
                // Try to get file from admin device
                $context = stream_context_create([
                    "http" => [
                        "timeout" => 5,
                        "method" => "GET"
                    ]
                ]);
                
                $fileData = @file_get_contents($url, false, $context);
                
                if ($fileData !== false && !empty($fileData)) {
                    // Detect mime type from filename
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $mimeTypes = [
                        "pdf" => "application/pdf",
                        "jpg" => "image/jpeg",
                        "jpeg" => "image/jpeg", 
                        "png" => "image/png"
                    ];
                    
                    $mimeType = $mimeTypes[$extension] ?? "application/octet-stream";
                    
                    header("Content-Type: " . $mimeType);
                    header("Content-Length: " . strlen($fileData));
                    header("Content-Disposition: inline; filename=\"" . $filename . "\"");
                    header("X-Served-From: admin-device");
                    echo $fileData;
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log("Admin device file serving error: " . $e->getMessage());
        }
        
        return false;
    }
    
    private function serveFromLocal($filename, $directory) {
        $possiblePaths = [
            __DIR__ . "/uploads/" . $directory . "/" . $filename,
            "/tmp/uploads/" . $directory . "/" . $filename
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $mimeType = mime_content_type($path);
                header("Content-Type: " . $mimeType);
                header("Content-Length: " . filesize($path));
                header("Content-Disposition: inline; filename=\"" . $filename . "\"");
                header("X-Served-From: local-storage");
                readfile($path);
                return true;
            }
        }
        
        return false;
    }
    
    private function handleFileNotFound($filename, $directory) {
        // Check if we have metadata about this file
        try {
            $stmt = $this->conn->prepare("
                SELECT original_name, file_size, upload_time 
                FROM file_metadata 
                WHERE filename = ? AND directory = ?
            ");
            $stmt->execute([$filename, $directory]);
            $metadata = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($metadata) {
                http_response_code(404);
                echo json_encode([
                    "error" => "File not found in any storage location",
                    "message" => "File exists in metadata but cannot be served from any source",
                    "filename" => $filename,
                    "original_name" => $metadata["original_name"],
                    "upload_time" => $metadata["upload_time"],
                    "suggestions" => [
                        "Check if admin device file server is running",
                        "Verify file exists on admin device",
                        "Contact customer to re-upload documents",
                        "Configure cloud storage for permanent solution"
                    ]
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    "error" => "File not found",
                    "filename" => $filename
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Database error while checking file metadata",
                "message" => $e->getMessage()
            ]);
        }
    }
}

// Handle file serving request
if (isset($_GET["file"]) && isset($_GET["type"])) {
    $server = new EnhancedFileServer();
    $server->serveFile($_GET["file"], $_GET["type"]);
} else {
    http_response_code(400);
    echo json_encode(["error" => "Missing file or type parameter"]);
}
?>';

        file_put_contents(__DIR__ . '/enhanced_file_server.php', $serverCode);
        return 'enhanced_file_server.php created';
    }
}

// Usage example
$adminStorage = new AdminDeviceStorageManager();

echo "<h1>Admin Device Storage Solution</h1>";

echo "<h2>Option 1: Admin Device File Server</h2>";
echo "<p>" . $adminStorage->createAdminDeviceServer() . "</p>";

echo "<h2>Option 2: Admin URL Registry</h2>";
echo "<p>" . $adminStorage->createAdminUrlRegistry() . "</p>";

echo "<h2>Option 3: File Sync System</h2>";
echo "<p>" . $adminStorage->createFileSyncSystem() . "</p>";

echo "<h2>Option 4: Enhanced File Server</h2>";
echo "<p>" . $adminStorage->createEnhancedFileServer() . "</p>";

echo "<div style='background: #e1f5fe; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>📋 Implementation Steps:</h3>";
echo "<ol>";
echo "<li><strong>On Admin Device:</strong> Run <code>php -S 0.0.0.0:8080 admin_file_server.php</code></li>";
echo "<li><strong>Configure Router:</strong> Port forward 8080 to admin device</li>";
echo "<li><strong>Register Device:</strong> Add admin device URL to registry</li>";
echo "<li><strong>Update Heroku:</strong> Deploy enhanced file server</li>";
echo "<li><strong>Test:</strong> Verify files can be served from admin device</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>⚠️ Considerations:</h3>";
echo "<ul>";
echo "<li><strong>Security:</strong> Admin device exposed to internet</li>";
echo "<li><strong>Reliability:</strong> Admin device must be always online</li>";
echo "<li><strong>Network:</strong> Requires static IP or dynamic DNS</li>";
echo "<li><strong>Multiple Admins:</strong> Each needs their own server setup</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>💡 Alternative: Hybrid Approach</h3>";
echo "<p>Combine database storage (for small files) + admin device storage (for large files) + cloud storage (as backup)</p>";
echo "<p>This gives you the best of all worlds with multiple fallback options.</p>";
echo "</div>";
?>
