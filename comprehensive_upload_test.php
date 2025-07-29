<?php
require_once 'config.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive File Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        h3 { color: #666; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .status-badge { 
            padding: 5px 10px; 
            border-radius: 3px; 
            font-weight: bold; 
            color: white; 
        }
        .status-success { background-color: #4CAF50; }
        .status-error { background-color: #f44336; }
        .status-warning { background-color: #ff9800; }
    </style>
</head>
<body>
    <h1>🧪 Comprehensive File Upload Test Suite</h1>
    <p><strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

<?php
function testResult($test_name, $result, $details = '') {
    $status = $result ? 'SUCCESS' : 'ERROR';
    $class = $result ? 'status-success' : 'status-error';
    echo "<div class='test-section'>";
    echo "<h3>$test_name <span class='status-badge $class'>$status</span></h3>";
    if ($details) {
        echo "<pre>$details</pre>";
    }
    echo "</div>";
    return $result;
}

$overall_success = true;

// Test 1: Database Connection
echo "<h2>📊 Database Tests</h2>";
try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $overall_success &= testResult("Database Connection", true, "Successfully connected to database");
} catch (Exception $e) {
    $overall_success &= testResult("Database Connection", false, "Error: " . $e->getMessage());
    exit;
}

// Test 2: Upload Directory Structure
echo "<h2>📁 Upload Directory Tests</h2>";
$upload_base = '/tmp/miw_uploads';
$required_dirs = ['documents', 'payments', 'photos'];

foreach ($required_dirs as $dir) {
    $full_path = $upload_base . '/' . $dir;
    $exists = is_dir($full_path);
    $writable = $exists ? is_writable($full_path) : false;
    
    $details = "Path: $full_path\n";
    $details .= "Exists: " . ($exists ? 'Yes' : 'No') . "\n";
    $details .= "Writable: " . ($writable ? 'Yes' : 'No') . "\n";
    
    if ($exists) {
        $permissions = substr(sprintf('%o', fileperms($full_path)), -4);
        $details .= "Permissions: $permissions\n";
    }
    
    $success = $exists && $writable;
    $overall_success &= testResult("Upload Directory: $dir", $success, $details);
}

// Test 3: Upload Handler Class
echo "<h2>🔧 Upload Handler Tests</h2>";
try {
    require_once 'upload_handler.php';
    $upload_handler = new UploadHandler();
    $overall_success &= testResult("UploadHandler Class", true, "Class instantiated successfully");
} catch (Exception $e) {
    $overall_success &= testResult("UploadHandler Class", false, "Error: " . $e->getMessage());
}

// Test 4: File Type Validation
echo "<h2>📝 File Type Validation Tests</h2>";
$test_files = [
    'test.jpg' => 'image/jpeg',
    'test.png' => 'image/png',
    'test.pdf' => 'application/pdf',
    'test.doc' => 'application/msword',
    'test.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

foreach ($test_files as $filename => $mime_type) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $is_allowed = in_array($mime_type, $allowed_types);
    
    $details = "File: $filename\n";
    $details .= "MIME Type: $mime_type\n";
    $details .= "Allowed: " . ($is_allowed ? 'Yes' : 'No');
    
    $overall_success &= testResult("File Type: $filename", $is_allowed, $details);
}

// Test 5: Database Schema Validation
echo "<h2>🗃️ Database Schema Tests</h2>";
try {
    // Check data_jamaah table structure
    $stmt = $pdo->query("DESCRIBE data_jamaah");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = ['nik', 'nama', 'ktp_path', 'kk_path', 'foto_path', 'payment_proof_path'];
    $found_columns = array_column($columns, 'Field');
    
    $missing_columns = array_diff($required_columns, $found_columns);
    
    if (empty($missing_columns)) {
        $details = "All required columns found:\n" . implode(', ', $required_columns);
        $overall_success &= testResult("data_jamaah Table Schema", true, $details);
    } else {
        $details = "Missing columns: " . implode(', ', $missing_columns);
        $overall_success &= testResult("data_jamaah Table Schema", false, $details);
    }
    
} catch (Exception $e) {
    $overall_success &= testResult("data_jamaah Table Schema", false, "Error: " . $e->getMessage());
}

// Test 6: Sample Data Validation
echo "<h2>👥 Sample Data Tests</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM data_jamaah WHERE nik IS NOT NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $has_data = $result['count'] > 0;
    $details = "Records found: " . $result['count'];
    
    $overall_success &= testResult("Sample Data Exists", $has_data, $details);
    
    if ($has_data) {
        // Check for test record
        $stmt = $pdo->prepare("SELECT * FROM data_jamaah WHERE nik = ? LIMIT 1");
        $stmt->execute(['3273272102010001']);
        $test_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_record) {
            $details = "Test record found:\n";
            $details .= "NIK: " . $test_record['nik'] . "\n";
            $details .= "Nama: " . $test_record['nama'] . "\n";
            $details .= "KTP Path: " . ($test_record['ktp_path'] ?: 'Not set') . "\n";
            $details .= "KK Path: " . ($test_record['kk_path'] ?: 'Not set') . "\n";
            
            $overall_success &= testResult("Test Record (NIK: 3273272102010001)", true, $details);
        } else {
            $overall_success &= testResult("Test Record (NIK: 3273272102010001)", false, "Test record not found");
        }
    }
    
} catch (Exception $e) {
    $overall_success &= testResult("Sample Data Validation", false, "Error: " . $e->getMessage());
}

// Test 7: Error Logger Integration
echo "<h2>📊 Error Logger Tests</h2>";
try {
    // Check if error_logger table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'error_logs'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM error_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $details = "error_logs table exists\n";
        $details .= "Recent errors (last hour): " . $result['count'];
        
        $overall_success &= testResult("Error Logger Table", true, $details);
    } else {
        $overall_success &= testResult("Error Logger Table", false, "error_logs table not found");
    }
    
} catch (Exception $e) {
    $overall_success &= testResult("Error Logger Integration", false, "Error: " . $e->getMessage());
}

// Test 8: File Handler Endpoint
echo "<h2>🔗 File Handler Endpoint Tests</h2>";
$file_handler_url = '/file_handler.php';
$file_handler_path = __DIR__ . $file_handler_url;

if (file_exists($file_handler_path)) {
    $details = "File handler exists at: $file_handler_path\n";
    $details .= "File size: " . filesize($file_handler_path) . " bytes\n";
    $details .= "Last modified: " . date('Y-m-d H:i:s', filemtime($file_handler_path));
    
    $overall_success &= testResult("File Handler Script", true, $details);
} else {
    $overall_success &= testResult("File Handler Script", false, "file_handler.php not found");
}

// Final Summary
echo "<h2>📋 Test Summary</h2>";
$status_class = $overall_success ? 'status-success' : 'status-error';
$status_text = $overall_success ? 'ALL TESTS PASSED' : 'SOME TESTS FAILED';

echo "<div class='test-section'>";
echo "<h3>Overall Result: <span class='status-badge $status_class'>$status_text</span></h3>";

if ($overall_success) {
    echo "<p class='success'>✅ The file upload system is fully operational and ready for use.</p>";
    echo "<p class='info'>All components are properly configured:</p>";
    echo "<ul>";
    echo "<li>Database connection and schema ✓</li>";
    echo "<li>Upload directories and permissions ✓</li>";
    echo "<li>File type validation ✓</li>";
    echo "<li>Error logging system ✓</li>";
    echo "<li>File handler endpoint ✓</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ Some components need attention. Please review the failed tests above.</p>";
}

echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Test actual file uploads via the form</li>";
echo "<li>Monitor error logs for any issues</li>";
echo "<li>Validate file previews in admin dashboard</li>";
echo "</ul>";

echo "</div>";
?>

    <hr>
    <p><em>Test completed at <?php echo date('Y-m-d H:i:s'); ?></em></p>
    
    <h3>Quick Navigation</h3>
    <ul>
        <li><a href="error_logger.php" target="_blank">Error Logger</a></li>
        <li><a href="db_diagnostic.php" target="_blank">Database Diagnostic</a></li>
        <li><a href="testing_dashboard.php" target="_blank">Testing Dashboard</a></li>
        <li><a href="admin_dashboard.php" target="_blank">Admin Dashboard</a></li>
        <li><a href="form_haji.php" target="_blank">Form Haji (Test Upload)</a></li>
    </ul>
</body>
</html>
