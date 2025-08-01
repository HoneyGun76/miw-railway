<?php
/**
 * Simple Autoloader Test for Railway Deployment
 * This script tests if the composer autoloader is working properly
 */

echo "<h1>Railway Autoloader Test</h1>";
echo "<hr>";

echo "<h2>1. Testing Composer Autoloader</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✅ Composer autoloader loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Composer autoloader failed: " . $e->getMessage() . "<br>";
}

echo "<h2>2. Testing PHPMailer (Email Functions)</h2>";
try {
    $mailer = new PHPMailer\PHPMailer\PHPMailer();
    echo "✅ PHPMailer class loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ PHPMailer failed: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Testing Other Dependencies</h2>";
try {
    $dompdf = new Dompdf\Dompdf();
    echo "✅ DomPDF available<br>";
} catch (Exception $e) {
    echo "❌ DomPDF failed: " . $e->getMessage() . "<br>";
}

try {
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "✅ PhpSpreadsheet available<br>";
} catch (Exception $e) {
    echo "❌ PhpSpreadsheet failed: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Environment Info</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Railway Environment: " . (getenv('RAILWAY_ENVIRONMENT') ? 'Yes' : 'No') . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Upload Max Size: " . ini_get('upload_max_filesize') . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";

echo "<h2>5. File System Test</h2>";
echo "Vendor directory exists: " . (is_dir(__DIR__ . '/vendor') ? 'Yes' : 'No') . "<br>";
echo "Autoload.php exists: " . (file_exists(__DIR__ . '/vendor/autoload.php') ? 'Yes' : 'No') . "<br>";
echo "Composer directory exists: " . (is_dir(__DIR__ . '/vendor/composer') ? 'Yes' : 'No') . "<br>";

echo "<hr>";
echo "<p>Test completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
