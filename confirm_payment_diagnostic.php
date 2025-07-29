<?php
/**
 * Confirm Payment Diagnostic Tool
 * Specifically tests the confirm_payment.php flow to identify issues
 */

set_time_limit(30);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Confirm Payment Diagnostic</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.section { background: white; margin: 10px 0; padding: 15px; border-radius: 8px; border-left: 4px solid #007cba; }
.success { border-left-color: green; }
.error { border-left-color: red; }
.warning { border-left-color: orange; }
.test-result { margin: 5px 0; padding: 8px; border-radius: 4px; }
.pass { background: #e8f5e8; color: green; }
.fail { background: #ffe8e8; color: red; }
.info { background: #e8f4f8; color: #0066cc; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 Confirm Payment Diagnostic</h1>";
echo "<p>Testing Date: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Check file existence
echo "<div class='section'>";
echo "<h2>📁 File Existence Check</h2>";

$requiredFiles = [
    'confirm_payment.php',
    'closing_page.php',
    'config.php',
    'email_functions.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<div class='test-result pass'>✅ {$file} exists</div>";
    } else {
        echo "<div class='test-result fail'>❌ {$file} missing</div>";
    }
}
echo "</div>";

// Test 2: Check configuration
echo "<div class='section'>";
echo "<h2>⚙️ Configuration Check</h2>";

try {
    require_once 'config.php';
    echo "<div class='test-result pass'>✅ Config loaded successfully</div>";
    
    // Check database connection
    if (isset($conn) && $conn instanceof PDO) {
        echo "<div class='test-result pass'>✅ Database connection available</div>";
    } else {
        echo "<div class='test-result fail'>❌ Database connection unavailable</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result fail'>❌ Config error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Test 3: Check email functions
echo "<div class='section'>";
echo "<h2>📧 Email Functions Check</h2>";

try {
    require_once 'email_functions.php';
    echo "<div class='test-result pass'>✅ Email functions loaded</div>";
    
    if (function_exists('sendPaymentConfirmationEmail')) {
        echo "<div class='test-result pass'>✅ sendPaymentConfirmationEmail function exists</div>";
    } else {
        echo "<div class='test-result fail'>❌ sendPaymentConfirmationEmail function missing</div>";
    }
    
    // Test email configuration
    $emailConstants = ['EMAIL_ENABLED', 'SMTP_HOST', 'SMTP_USERNAME'];
    foreach ($emailConstants as $constant) {
        if (defined($constant)) {
            echo "<div class='test-result pass'>✅ {$constant} defined</div>";
        } else {
            echo "<div class='test-result fail'>❌ {$constant} not defined</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='test-result fail'>❌ Email functions error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Test 4: Check recent logs
echo "<div class='section'>";
echo "<h2>📋 Recent Error Logs</h2>";

$logFile = __DIR__ . '/error_logs/confirm_payment_' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    echo "<div class='test-result info'>📄 Log file: " . basename($logFile) . "</div>";
    echo "<div class='test-result info'>📊 Size: " . filesize($logFile) . " bytes</div>";
    
    // Show last 20 lines
    $lines = file($logFile);
    $recentLines = array_slice($lines, -20);
    
    echo "<h3>Recent Log Entries:</h3>";
    echo "<pre>" . htmlspecialchars(implode('', $recentLines)) . "</pre>";
} else {
    echo "<div class='test-result info'>ℹ️ No log file found for today</div>";
}
echo "</div>";

// Test 5: Simulate form submission test
echo "<div class='section'>";
echo "<h2>🧪 Form Submission Simulation</h2>";

// Check if we can access jamaah table
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM data_jamaah");
    $count = $stmt->fetchColumn();
    echo "<div class='test-result pass'>✅ Database accessible: {$count} jamaah records</div>";
    
    // Get a sample record for testing
    $stmt = $conn->query("SELECT * FROM data_jamaah LIMIT 1");
    $sampleRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sampleRecord) {
        echo "<div class='test-result pass'>✅ Sample record available</div>";
        echo "<div class='test-result info'>📝 Sample NIK: " . htmlspecialchars($sampleRecord['nik']) . "</div>";
        echo "<div class='test-result info'>📝 Sample Name: " . htmlspecialchars($sampleRecord['nama']) . "</div>";
    } else {
        echo "<div class='test-result warning'>⚠️ No sample records available for testing</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result fail'>❌ Database test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Test 6: Check session handling
echo "<div class='section'>";
echo "<h2>🍪 Session Handling Check</h2>";

session_start();

// Test session functionality
$_SESSION['test_diagnostic'] = 'test_value_' . time();
if (isset($_SESSION['test_diagnostic'])) {
    echo "<div class='test-result pass'>✅ Session functionality working</div>";
    unset($_SESSION['test_diagnostic']);
} else {
    echo "<div class='test-result fail'>❌ Session functionality not working</div>";
}

// Check for existing payment sessions
if (isset($_SESSION['payment_success'])) {
    echo "<div class='test-result info'>ℹ️ Existing payment success session found</div>";
    echo "<pre>" . htmlspecialchars(print_r($_SESSION['payment_success'], true)) . "</pre>";
}

if (isset($_SESSION['payment_error'])) {
    echo "<div class='test-result warning'>⚠️ Existing payment error session found</div>";
    echo "<pre>" . htmlspecialchars(print_r($_SESSION['payment_error'], true)) . "</pre>";
}
echo "</div>";

// Test 7: Check URL redirects
echo "<div class='section'>";
echo "<h2>🔗 Redirect Test</h2>";

echo "<div class='test-result info'>📋 Testing redirect destinations:</div>";

$redirectUrls = [
    'closing_page.php' => 'Success page',
    'invoice.php' => 'Error fallback page'
];

foreach ($redirectUrls as $url => $description) {
    if (file_exists($url)) {
        echo "<div class='test-result pass'>✅ {$description} ({$url}) exists</div>";
    } else {
        echo "<div class='test-result fail'>❌ {$description} ({$url}) missing</div>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🎯 Diagnostic Summary</h2>";
echo "<div class='test-result info'>";
echo "📌 <strong>Action Items:</strong><br>";
echo "1. Check recent log entries above for specific errors<br>";
echo "2. Verify file upload process if payment submission fails<br>";
echo "3. Test email functionality if notifications aren't working<br>";
echo "4. Check session data if redirect doesn't work<br>";
echo "</div>";
echo "</div>";

echo "<div style='margin-top: 20px; text-align: center;'>";
echo "<p><a href='testing_dashboard.php'>← Back to Testing Dashboard</a></p>";
echo "<p><a href='error_logger.php'>View Error Logger</a> | <a href='form_haji.php'>Test Haji Form</a></p>";
echo "</div>";

echo "</body></html>";
?>
