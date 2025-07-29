<?php
/**
 * Test Upload Directory Issue
 * Reproduces the exact errors mentioned in test_confirm_payment_post
 */

require_once 'config.php';
require_once 'upload_handler.php';

echo "<!DOCTYPE html><html><head><title>Upload Directory Test</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .result { margin: 10px 0; padding: 10px; border-radius: 4px; } .error { background: #ffe8e8; color: red; } .success { background: #e8f5e8; color: green; } .info { background: #e8f4f8; color: #333; }</style></head><body>";

echo "<h1>🧪 Upload Directory Issue Test</h1>";

// Test 1: Check upload directory using config functions
echo "<div class='result info'><h3>Test 1: Upload Directory Check</h3>";

$uploadDir = getUploadDirectory();
echo "Expected upload directory: {$uploadDir}<br>";

if (is_dir($uploadDir)) {
    echo "<div class='success'>✅ Upload directory exists</div>";
} else {
    echo "<div class='error'>❌ Upload Directory: missing</div>";
}

if (is_writable($uploadDir)) {
    echo "<div class='success'>✅ Upload directory is writable</div>";
} else {
    echo "<div class='error'>❌ Upload directory is not writable</div>";
}

echo "</div>";

// Test 2: Simulate the exact test_confirm_payment_post logic
echo "<div class='result info'><h3>Test 2: Simulate test_confirm_payment_post Logic</h3>";

try {
    // Simulate the exact POST data that would cause the error
    $simulatedPost = [
        'nik' => '9999999999999998',
        'nama' => 'Test User Haji',
        'transfer_account_name' => 'Test Transfer Account',
        'program_pilihan' => 'Haji Test Program'
    ];
    
    echo "Simulated POST data created<br>";
    
    // Check database connection and jamaah record
    $stmt = $conn->prepare("SELECT * FROM data_jamaah WHERE nik = ?");
    $stmt->execute([$simulatedPost['nik']]);
    $jamaah = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($jamaah) {
        echo "<div class='success'>✅ NIK found in database: " . htmlspecialchars($jamaah['nama']) . "</div>";
    } else {
        echo "<div class='error'>❌ NIK not found in database</div>";
    }
    
    // Test database update with payment_status = 'confirmation_submitted'
    echo "Testing database update with 'confirmation_submitted'...<br>";
    
    $conn->beginTransaction();
    
    $updateSql = "UPDATE data_jamaah SET 
        transfer_account_name = ?, 
        payment_time = ?,
        payment_date = ?,
        payment_status = 'confirmation_submitted',
        updated_at = NOW()
        WHERE nik = ?";
    
    $stmt = $conn->prepare($updateSql);
    $updateResult = $stmt->execute([
        $simulatedPost['transfer_account_name'],
        date('H:i:s'),
        date('Y-m-d'),
        $simulatedPost['nik']
    ]);
    
    if ($updateResult && $stmt->rowCount() > 0) {
        echo "<div class='success'>✅ Database update: PASSED ({$stmt->rowCount()} row affected)</div>";
        echo "<div class='success'>✅ payment_status 'confirmation_submitted' accepted</div>";
    } else {
        echo "<div class='error'>❌ Database update: FAILED</div>";
    }
    
    $conn->rollBack(); // Don't save test changes
    echo "<div class='info'>🔄 Test changes rolled back</div>";
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "<div class='error'>❌ Payment confirmation simulation failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>📍 Error details: " . $e->getFile() . " line " . $e->getLine() . "</div>";
}

echo "</div>";

// Test 3: Check upload handler behavior
echo "<div class='result info'><h3>Test 3: Upload Handler Test</h3>";

try {
    $uploadHandler = new UploadHandler();
    echo "<div class='success'>✅ UploadHandler created successfully</div>";
    
    // Test error handling for missing file
    echo "Testing upload handler with no file...<br>";
    $result = $uploadHandler->handleUpload(null, 'payments', 'test');
    
    if ($result === false) {
        $errors = $uploadHandler->getErrors();
        echo "<div class='info'>Expected error: " . implode(', ', $errors) . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Upload handler error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<div class='result info'><h3>Summary</h3>";
echo "If you see any ❌ errors above, those need to be fixed.<br>";
echo "Based on your report, the main issues were:<br>";
echo "1. Upload Directory: missing (FIXED if ✅ shown above)<br>";
echo "2. String data truncation on payment_status (FIXED if ✅ shown above)";
echo "</div>";

echo "</body></html>";
?>
