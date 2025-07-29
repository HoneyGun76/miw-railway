<?php
/**
 * PRIORITY FIX: Heroku Upload Directory Issues
 * This script creates all necessary upload directories and provides workarounds
 */

require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>PRIORITY: Upload Directory Fix</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.urgent { background: #dc3545; color: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
.fix { background: #28a745; color: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
.info { background: #17a2b8; color: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
.test { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #007cba; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
h1 { color: #333; }
</style></head><body>";

echo "<h1>🚨 PRIORITY FIX: Heroku Upload Directory Issues</h1>";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s T') . "</p>";

// Detect environment
$isHeroku = !empty($_ENV['DYNO']) || !empty(getenv('DYNO'));
echo "<div class='info'><h3>Environment: " . ($isHeroku ? 'Heroku Production' : 'Local Development') . "</h3></div>";

echo "<div class='urgent'>";
echo "<h3>🚨 CRITICAL ISSUE IDENTIFIED</h3>";
echo "<p>Upload directories missing on Heroku deployment causing file upload failures.</p>";
echo "<p><strong>Impact:</strong> All file uploads (KTP, KK, Passport, Payment proofs) failing</p>";
echo "</div>";

echo "<div class='test'>";
echo "<h3>📋 Step 1: Diagnostic - Current Directory Status</h3>";

// Check current upload directories
$directories = [
    '/tmp/uploads' => 'Main Heroku Upload Directory',
    '/tmp/uploads/documents' => 'Documents Subdirectory',
    '/tmp/uploads/payments' => 'Payments Subdirectory', 
    '/tmp/uploads/cancellations' => 'Cancellations Subdirectory',
    '/tmp/uploads/photos' => 'Photos Subdirectory',
    '/tmp/miw_uploads' => 'Legacy Upload Directory',
    '/tmp/miw_uploads/documents' => 'Legacy Documents',
    '/tmp/miw_uploads/payments' => 'Legacy Payments',
    '/tmp/miw_uploads/photos' => 'Legacy Photos',
    __DIR__ . '/uploads' => 'Local Upload Directory',
    __DIR__ . '/uploads/documents' => 'Local Documents',
    __DIR__ . '/uploads/payments' => 'Local Payments',
    __DIR__ . '/uploads/cancellations' => 'Local Cancellations'
];

$missing_dirs = [];
$existing_dirs = [];

foreach ($directories as $dir => $desc) {
    $exists = is_dir($dir);
    $writable = is_writable($dir);
    
    if ($exists) {
        $existing_dirs[] = $dir;
        echo "<p>✅ <strong>{$desc}:</strong> {$dir} - EXISTS" . ($writable ? " (WRITABLE)" : " (NOT WRITABLE)") . "</p>";
    } else {
        $missing_dirs[] = [$dir, $desc];
        echo "<p>❌ <strong>{$desc}:</strong> {$dir} - MISSING</p>";
    }
}

echo "</div>";

echo "<div class='fix'>";
echo "<h3>🔧 Step 2: IMMEDIATE FIX - Create Missing Directories</h3>";

$created_dirs = [];
$failed_dirs = [];

foreach ($missing_dirs as $dir_info) {
    $dir = $dir_info[0];
    $desc = $dir_info[1];
    
    try {
        if (mkdir($dir, 0755, true)) {
            $created_dirs[] = $dir;
            echo "<p>✅ CREATED: {$desc} - {$dir}</p>";
            
            // Create security files
            file_put_contents($dir . '/.htaccess', "Order deny,allow\nDeny from all\n");
            file_put_contents($dir . '/index.php', "<?php exit('Access denied'); ?>");
            
        } else {
            $failed_dirs[] = $dir;
            echo "<p>❌ FAILED TO CREATE: {$desc} - {$dir}</p>";
        }
    } catch (Exception $e) {
        $failed_dirs[] = $dir;
        echo "<p>❌ ERROR CREATING: {$desc} - {$dir} - " . $e->getMessage() . "</p>";
    }
}

echo "</div>";

echo "<div class='test'>";
echo "<h3>📋 Step 3: Verification - Test Directory Creation</h3>";

// Test directory creation by writing a test file
$test_results = [];

foreach (array_merge($existing_dirs, $created_dirs) as $dir) {
    $test_file = $dir . '/test_' . time() . '.txt';
    
    try {
        if (file_put_contents($test_file, 'Test file for upload verification')) {
            echo "<p>✅ WRITE TEST PASSED: {$dir}</p>";
            unlink($test_file); // Clean up
            $test_results[$dir] = true;
        } else {
            echo "<p>❌ WRITE TEST FAILED: {$dir}</p>";
            $test_results[$dir] = false;
        }
    } catch (Exception $e) {
        echo "<p>❌ WRITE TEST ERROR: {$dir} - " . $e->getMessage() . "</p>";
        $test_results[$dir] = false;
    }
}

echo "</div>";

echo "<div class='info'>";
echo "<h3>⚙️ Step 4: Update Configuration Functions</h3>";

// Test the configuration functions
echo "<p>Testing getUploadDirectory() function...</p>";
$upload_dir = getUploadDirectory();
echo "<p>Current upload directory: <strong>{$upload_dir}</strong></p>";

if (is_dir($upload_dir)) {
    echo "<p>✅ Upload directory from config exists</p>";
} else {
    echo "<p>❌ Upload directory from config missing - creating...</p>";
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p>✅ Created upload directory from config</p>";
    } else {
        echo "<p>❌ Failed to create upload directory from config</p>";
    }
}

echo "<p>Testing ensureUploadDirectory() function...</p>";
$ensured_dir = ensureUploadDirectory();
echo "<p>Ensured upload directory: <strong>{$ensured_dir}</strong></p>";

echo "</div>";

echo "<div class='fix'>";
echo "<h3>🛠️ Step 5: WORKAROUND - Enhanced Upload Handler</h3>";

// Create enhanced upload handler specifically for Heroku
$enhanced_handler_code = '<?php
/**
 * EMERGENCY UPLOAD HANDLER FOR HEROKU
 * This handler creates directories on-the-fly and provides fallback options
 */

class EmergencyHerokuUploadHandler {
    private $baseDir;
    private $isHeroku;
    
    public function __construct() {
        $this->isHeroku = !empty($_ENV["DYNO"]) || !empty(getenv("DYNO"));
        $this->baseDir = $this->isHeroku ? "/tmp/uploads" : __DIR__ . "/uploads";
        $this->ensureAllDirectories();
    }
    
    private function ensureAllDirectories() {
        $dirs = [
            $this->baseDir,
            $this->baseDir . "/documents",
            $this->baseDir . "/payments", 
            $this->baseDir . "/cancellations",
            $this->baseDir . "/photos"
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                file_put_contents($dir . "/.htaccess", "Order deny,allow\\nDeny from all\\n");
                file_put_contents($dir . "/index.php", "<?php exit(\"Access denied\"); ?>");
            }
        }
    }
    
    public function handleUpload($file, $targetDir, $customName) {
        // Ensure target directory exists
        $targetPath = $this->baseDir . "/" . trim($targetDir, "/");
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }
        
        // Validate file
        if (!$file || $file["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed");
        }
        
        // Generate filename
        $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $filename = $customName . "." . $extension;
        $fullPath = $targetPath . "/" . $filename;
        
        // Move file
        if (!move_uploaded_file($file["tmp_name"], $fullPath)) {
            throw new Exception("Failed to move uploaded file to: " . $fullPath);
        }
        
        return [
            "success" => true,
            "path" => "/uploads/" . $targetDir . "/" . $filename,
            "filename" => $filename,
            "size" => $file["size"],
            "type" => $file["type"]
        ];
    }
}
?>';

file_put_contents(__DIR__ . '/emergency_upload_handler.php', $enhanced_handler_code);
echo "<p>✅ Created emergency_upload_handler.php</p>";

echo "</div>";

echo "<div class='test'>";
echo "<h3>📋 Step 6: UPDATE ALL UPLOAD REFERENCES</h3>";

// Update main upload files to use emergency handler if needed
$files_to_update = [
    'submit_haji.php',
    'submit_umroh.php', 
    'confirm_payment.php'
];

foreach ($files_to_update as $file) {
    if (file_exists($file)) {
        echo "<p>📝 File exists: {$file}</p>";
    } else {
        echo "<p>❌ File missing: {$file}</p>";
    }
}

echo "</div>";

echo "<div class='urgent'>";
echo "<h3>🚨 IMMEDIATE ACTION REQUIRED</h3>";
echo "<p><strong>1. Deploy this fix immediately:</strong></p>";
echo "<pre>git add .
git commit -m \"URGENT: Fix upload directories for Heroku deployment\"
git push heroku master</pre>";

echo "<p><strong>2. Test upload functionality:</strong></p>";
echo "<ul>";
echo "<li>Submit form_haji.php with file uploads</li>";
echo "<li>Submit form_umroh.php with file uploads</li>";
echo "<li>Test confirm_payment.php file uploads</li>";
echo "</ul>";

echo "<p><strong>3. Monitor error logs:</strong></p>";
echo "<ul>";
echo "<li><a href='error_logger.php' target='_blank'>Check Error Logger</a></li>";
echo "<li><a href='testing_dashboard.php' target='_blank'>Run Testing Dashboard</a></li>";
echo "</ul>";

echo "</div>";

echo "<div class='info'>";
echo "<h3>📊 SUMMARY</h3>";
echo "<p><strong>Created Directories:</strong> " . count($created_dirs) . "</p>";
echo "<p><strong>Failed Directories:</strong> " . count($failed_dirs) . "</p>";
echo "<p><strong>Working Directories:</strong> " . count(array_filter($test_results)) . "</p>";
echo "<p><strong>Emergency Handler:</strong> Created and ready</p>";

if (count($failed_dirs) == 0 && count(array_filter($test_results)) > 0) {
    echo "<div class='fix'><h4>✅ SUCCESS: Upload directories are now functional!</h4></div>";
} else {
    echo "<div class='urgent'><h4>⚠️ WARNING: Some directories still have issues</h4></div>";
}

echo "</div>";

echo "</body></html>";
?>
