<?php
/**
 * Error Logger Test Script
 * This script generates various types of errors to test the error logging system
 */

require_once 'config.php';

echo "<h2>MIW Error Logger Test</h2>";
echo "<p>Generating test errors to demonstrate the error logging system...</p>";

// Test 1: Custom application error
echo "<h3>Test 1: Custom Application Error</h3>";
logError('test_error', 'This is a test application error', ['user_id' => 123, 'action' => 'test']);
echo "✓ Custom error logged<br>";

// Test 2: PHP Warning
echo "<h3>Test 2: PHP Warning</h3>";
$undefined_var = $this_variable_does_not_exist; // This will generate a notice
echo "✓ PHP notice generated<br>";

// Test 3: Database error simulation
echo "<h3>Test 3: Database Error Simulation</h3>";
try {
    $stmt = $conn->prepare("SELECT * FROM non_existent_table");
    $stmt->execute();
} catch (Exception $e) {
    logError('database_error', 'Attempted to query non-existent table: ' . $e->getMessage());
    echo "✓ Database error logged<br>";
}

// Test 4: File system error
echo "<h3>Test 4: File System Error</h3>";
try {
    $result = file_get_contents('/non/existent/path/file.txt');
    if ($result === false) {
        logError('file_error', 'Failed to read file: /non/existent/path/file.txt');
        echo "✓ File system error logged<br>";
    }
} catch (Exception $e) {
    logError('file_error', 'File system error: ' . $e->getMessage());
    echo "✓ File system error logged<br>";
}

// Test 5: Email error simulation
echo "<h3>Test 5: Email Error Simulation</h3>";
logError('email_error', 'SMTP connection failed: Unable to connect to Gmail SMTP server', [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'error_code' => 'CONNECTION_TIMEOUT'
]);
echo "✓ Email error logged<br>";

// Test 6: User input validation error
echo "<h3>Test 6: Validation Error</h3>";
logError('validation_error', 'Invalid passport number format provided', [
    'field' => 'passport_number',
    'value' => 'INVALID123',
    'expected_format' => 'XX1234567'
]);
echo "✓ Validation error logged<br>";

echo "<hr>";
echo "<p><strong>Test completed!</strong> Check the <a href='error_logger.php' target='_blank'>Error Logger</a> to see all logged errors.</p>";
echo "<p>Password for error logger: <code>miw2025admin</code></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
h2 { color: #333; }
h3 { color: #666; margin-top: 20px; }
p { line-height: 1.6; }
code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
