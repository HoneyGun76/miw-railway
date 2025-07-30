<?php
/**
 * Simple Upload Directory Test
 * Quick test to verify upload directories are working properly
 */

// Check if we're on Heroku
$isRailway = !empty($_ENV['RAILWAY_ENVIRONMENT']) || !empty(getenv('RAILWAY_ENVIRONMENT'));
$uploadBaseDir = '/tmp/uploads';

echo "<!DOCTYPE html><html><head><title>Upload Directory Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style></head><body>";

echo "<h1>🔧 Upload Directory Test</h1>";
echo "<p><strong>Environment:</strong> " . ($isRailway ? 'Railway' : 'Local') . "</p>";
echo "<p><strong>Base Directory:</strong> {$uploadBaseDir}</p>";

// Test directories
$directories = [
    $uploadBaseDir => 'Main Upload Directory',
    $uploadBaseDir . '/documents' => 'Documents',
    $uploadBaseDir . '/payments' => 'Payments',
    $uploadBaseDir . '/cancellations' => 'Cancellations',
    $uploadBaseDir . '/photos' => 'Photos'
];

echo "<h2>📁 Directory Status</h2>";
echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
echo "<tr><th>Directory</th><th>Exists</th><th>Writable</th><th>Action</th></tr>";

foreach ($directories as $dir => $name) {
    $exists = is_dir($dir);
    $writable = is_writable($dir);
    $action = '';
    
    // Try to create if doesn't exist
    if (!$exists) {
        if (mkdir($dir, 0755, true)) {
            $exists = true;
            $writable = is_writable($dir);
            $action = 'Created';
        } else {
            $action = 'Failed to create';
        }
    }
    
    $exists_class = $exists ? 'success' : 'error';
    $writable_class = $writable ? 'success' : 'error';
    
    echo "<tr>";
    echo "<td>{$name}<br><small>{$dir}</small></td>";
    echo "<td class='{$exists_class}'>" . ($exists ? '✅ Yes' : '❌ No') . "</td>";
    echo "<td class='{$writable_class}'>" . ($writable ? '✅ Yes' : '❌ No') . "</td>";
    echo "<td>{$action}</td>";
    echo "</tr>";
}

echo "</table>";

// Test file operations
echo "<h2>📋 File Operations Test</h2>";
$testDir = $uploadBaseDir . '/documents';
$testFile = $testDir . '/test_' . time() . '.txt';

try {
    if (is_dir($testDir) && is_writable($testDir)) {
        // Test write
        $content = "Test content - " . date('Y-m-d H:i:s');
        $bytes = file_put_contents($testFile, $content);
        
        if ($bytes !== false) {
            echo "<p class='success'>✅ File write test: Success ({$bytes} bytes)</p>";
            
            // Test read
            $readContent = file_get_contents($testFile);
            if ($readContent === $content) {
                echo "<p class='success'>✅ File read test: Success</p>";
            } else {
                echo "<p class='error'>❌ File read test: Content mismatch</p>";
            }
            
            // Test delete
            if (unlink($testFile)) {
                echo "<p class='success'>✅ File delete test: Success</p>";
            } else {
                echo "<p class='error'>❌ File delete test: Failed</p>";
            }
        } else {
            echo "<p class='error'>❌ File write test: Failed</p>";
        }
    } else {
        echo "<p class='error'>❌ Test directory not accessible</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ File operation error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test upload handlers
echo "<h2>🔧 Upload Handler Test</h2>";
$handlers = ['upload_handler.php', 'heroku_file_manager.php', 'file_handler_heroku.php'];

foreach ($handlers as $handler) {
    if (file_exists(__DIR__ . '/' . $handler)) {
        echo "<p class='success'>✅ {$handler}: Found</p>";
        
        try {
            require_once $handler;
            echo "<p class='info'>📋 {$handler}: Loaded successfully</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ {$handler}: Load error - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='error'>❌ {$handler}: Not found</p>";
    }
}

echo "<h2>📊 Summary</h2>";
echo "<p>This test verifies that upload directories are properly created and accessible.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Run <code>init_upload_directories.php</code> for comprehensive setup</li>";
echo "<li>Test actual file uploads via forms</li>";
echo "<li>Monitor <code>error_logger.php</code> for any issues</li>";
echo "<li>Check <code>deploy_debug.php</code> on Heroku</li>";
echo "</ul>";

echo "</body></html>";
?>
