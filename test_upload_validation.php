<?php
/**
 * File Upload Validation Test
 * Tests if file uploads are working correctly after directory fix
 */

require_once 'config.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');

// Start HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Validation Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #007cba; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 4px; margin: 10px 0; }
        form { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; }
        input, button { margin: 5px 0; padding: 8px; }
        button { background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a8b; }
    </style>
</head>
<body>

<h1>🧪 File Upload Validation Test</h1>

<?php
// Test directory status first
echo "<div class='test'>";
echo "<h3>📁 Upload Directory Status</h3>";

// Test main upload directory
$uploadDir = getUploadDirectory();
echo "<p><strong>Main Upload Directory:</strong> {$uploadDir}</p>";

if (is_dir($uploadDir)) {
    echo "<div class='success'>✅ Directory exists</div>";
    
    if (is_writable($uploadDir)) {
        echo "<div class='success'>✅ Directory is writable</div>";
        
        // Try to create a test file
        $testFile = $uploadDir . '/test_write_' . date('YmdHis') . '.txt';
        if (file_put_contents($testFile, 'Test write successful at ' . date('Y-m-d H:i:s'))) {
            echo "<div class='success'>✅ Write test passed</div>";
            
            // Clean up test file
            if (unlink($testFile)) {
                echo "<div class='info'>🧹 Test file cleaned up</div>";
            }
        } else {
            echo "<div class='error'>❌ Write test failed</div>";
        }
    } else {
        echo "<div class='error'>❌ Directory is not writable</div>";
    }
} else {
    echo "<div class='error'>❌ Directory does not exist</div>";
}

// Test subdirectories
$subdirs = ['documents', 'payments', 'cancellations', 'photos'];
foreach ($subdirs as $subdir) {
    $fullPath = $uploadDir . '/' . $subdir;
    echo "<p><strong>{$subdir} subdirectory:</strong> {$fullPath}</p>";
    
    if (is_dir($fullPath) && is_writable($fullPath)) {
        echo "<div class='success'>✅ {$subdir} directory OK</div>";
    } else {
        echo "<div class='error'>❌ {$subdir} directory missing or not writable</div>";
    }
}

echo "</div>";

// Handle file upload if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<div class='test'>";
    echo "<h3>📤 Upload Test Result</h3>";
    
    $file = $_FILES['test_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = 'test_upload_' . date('YmdHis') . '_' . basename($file['name']);
        $destination = $uploadDir . '/documents/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            echo "<div class='success'>✅ File uploaded successfully!</div>";
            echo "<p><strong>File:</strong> {$filename}</p>";
            echo "<p><strong>Size:</strong> " . formatBytes($file['size']) . "</p>";
            echo "<p><strong>Location:</strong> {$destination}</p>";
            
            // Verify file exists
            if (file_exists($destination)) {
                echo "<div class='success'>✅ File verified on disk</div>";
                
                // Clean up test file after verification
                if (unlink($destination)) {
                    echo "<div class='info'>🧹 Test file cleaned up</div>";
                }
            } else {
                echo "<div class='error'>❌ File not found after upload</div>";
            }
        } else {
            echo "<div class='error'>❌ Failed to move uploaded file</div>";
        }
    } else {
        echo "<div class='error'>❌ Upload error: " . getUploadErrorMessage($file['error']) . "</div>";
    }
    
    echo "</div>";
}

// Helper function for file size formatting
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Helper function for upload error messages
function getUploadErrorMessage($error) {
    switch ($error) {
        case UPLOAD_ERR_INI_SIZE:
            return 'File exceeds upload_max_filesize directive';
        case UPLOAD_ERR_FORM_SIZE:
            return 'File exceeds MAX_FILE_SIZE directive';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}
?>

<div class='test'>
    <h3>📎 Test File Upload</h3>
    <form method="post" enctype="multipart/form-data">
        <p>Select a small test file to upload:</p>
        <input type="file" name="test_file" required>
        <br>
        <button type="submit">Test Upload</button>
    </form>
    <div class='info'>
        <p><strong>Note:</strong> This will upload a file to test the upload functionality and then automatically delete it.</p>
    </div>
</div>

<div class='test'>
    <h3>🔗 Quick Links</h3>
    <p>
        <a href="deploy_debug.php" target="_blank">📊 Deployment Diagnostics</a> |
        <a href="error_logger.php" target="_blank">📋 Error Logger</a> |
        <a href="testing_dashboard.php" target="_blank">🧪 Testing Dashboard</a>
    </p>
</div>

<div class='info'>
    <p><strong>Environment:</strong> <?= isRailway() ? 'Railway Production' : 'Local Development' ?></p>
    <p><strong>Upload Directory:</strong> <?= $uploadDir ?></p>
    <p><strong>Test Time:</strong> <?= date('Y-m-d H:i:s T') ?></p>
</div>

</body>
</html>
