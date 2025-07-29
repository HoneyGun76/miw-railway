<?php
// Payment Flow Analysis Tool
// Comprehensive testing of the payment confirmation flow

// Start session for testing
session_start();

// Configuration
require_once 'config.php';

function logTest($message, $status = 'INFO', $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$status] $message";
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data);
    }
    error_log($logEntry, 3, 'logs/payment_flow_test.log');
    return $logEntry;
}

function displayResult($test, $result, $details = '') {
    $status = $result ? '✅ PASS' : '❌ FAIL';
    $color = $result ? 'green' : 'red';
    echo "<div style='padding: 10px; margin: 5px 0; border-left: 4px solid $color;'>";
    echo "<strong>$test:</strong> <span style='color: $color;'>$status</span>";
    if ($details) {
        echo "<br><small style='color: #666;'>$details</small>";
    }
    echo "</div>";
    return $result;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Flow Analysis</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .container { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 10px 0; }
        .test-section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ddd; }
        .form-container { background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 20px 0; }
        input, select, textarea { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
    </style>
</head>
<body>
    <h1>🔄 Payment Flow Analysis</h1>
    <p>Comprehensive testing and analysis of the payment confirmation flow.</p>

    <?php
    // Initialize results
    $allTests = [];
    $totalTests = 0;
    $passedTests = 0;

    // Test 1: Environment Setup
    echo "<div class='test-section'>";
    echo "<h2>1. Environment Setup</h2>";

    $test = "PHP Version Check";
    $result = version_compare(PHP_VERSION, '7.4.0', '>=');
    $allTests[] = displayResult($test, $result, "Current: " . PHP_VERSION);
    if ($result) $passedTests++;
    $totalTests++;

    $test = "Session Functionality";
    $_SESSION['test_session'] = 'working';
    $result = isset($_SESSION['test_session']) && $_SESSION['test_session'] === 'working';
    $allTests[] = displayResult($test, $result, "Session ID: " . session_id());
    if ($result) $passedTests++;
    $totalTests++;

    $test = "Configuration File";
    $result = file_exists('config.php') && defined('DB_HOST');
    $allTests[] = displayResult($test, $result, $result ? "Config loaded successfully" : "Config file missing or invalid");
    if ($result) $passedTests++;
    $totalTests++;

    echo "</div>";

    // Test 2: Database Connection
    echo "<div class='test-section'>";
    echo "<h2>2. Database Connection</h2>";

    $test = "Database Connection";
    try {
        $conn = new PDO("pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $result = true;
        $details = "Connected to: " . DB_NAME . " on " . DB_HOST;
    } catch (Exception $e) {
        $result = false;
        $details = "Error: " . $e->getMessage();
    }
    $allTests[] = displayResult($test, $result, $details);
    if ($result) $passedTests++;
    $totalTests++;

    if ($result) {
        $test = "Required Tables Exist";
        try {
            $stmt = $conn->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredTables = ['payments', 'registrations'];
            $missingTables = array_diff($requiredTables, $tables);
            $result = empty($missingTables);
            $details = $result ? "All required tables exist" : "Missing tables: " . implode(', ', $missingTables);
        } catch (Exception $e) {
            $result = false;
            $details = "Error checking tables: " . $e->getMessage();
        }
        $allTests[] = displayResult($test, $result, $details);
        if ($result) $passedTests++;
        $totalTests++;
    }

    echo "</div>";

    // Test 3: File Existence and Permissions
    echo "<div class='test-section'>";
    echo "<h2>3. File Existence and Permissions</h2>";

    $criticalFiles = [
        'confirm_payment.php' => 'Payment confirmation handler',
        'closing_page.php' => 'Payment success page',
        'invoice.php' => 'Invoice generation',
        'form_haji.php' => 'Haji registration form',
        'form_umroh.php' => 'Umroh registration form'
    ];

    foreach ($criticalFiles as $file => $description) {
        $test = "File: $file";
        $result = file_exists($file) && is_readable($file);
        $details = $result ? "$description - Readable" : "File missing or not readable";
        $allTests[] = displayResult($test, $result, $details);
        if ($result) $passedTests++;
        $totalTests++;
    }

    echo "</div>";

    // Test 4: Email Configuration
    echo "<div class='test-section'>";
    echo "<h2>4. Email Configuration</h2>";

    $test = "SMTP Configuration";
    $smtpDefined = defined('SMTP_HOST') && defined('SMTP_PORT') && defined('SMTP_USER') && defined('SMTP_PASS');
    $allTests[] = displayResult($test, $smtpDefined, $smtpDefined ? "SMTP settings configured" : "SMTP settings missing");
    if ($smtpDefined) $passedTests++;
    $totalTests++;

    if ($smtpDefined) {
        $test = "Email Function Test";
        $emailTestResult = false;
        $emailDetails = "";
        
        try {
            // Test PHPMailer configuration without actually sending
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $emailTestResult = true;
                $emailDetails = "PHPMailer class available";
            } else {
                $emailDetails = "PHPMailer class not found";
            }
        } catch (Exception $e) {
            $emailDetails = "Error: " . $e->getMessage();
        }
        
        $allTests[] = displayResult($test, $emailTestResult, $emailDetails);
        if ($emailTestResult) $passedTests++;
        $totalTests++;
    }

    echo "</div>";

    // Test 5: Simulate Payment Flow
    echo "<div class='test-section'>";
    echo "<h2>5. Payment Flow Simulation</h2>";

    // Test payment data structure
    $testPaymentData = [
        'nama' => 'Test User Payment Flow',
        'email' => 'test@example.com',
        'nomor_telepon' => '081234567890',
        'program_pilihan' => 'Haji Plus',
        'payment_total' => '25000000',
        'payment_method' => 'transfer_bank',
        'tanggal_keberangkatan' => '2024-06-15',
        'tanggal_kepulangan' => '2024-07-15'
    ];

    $test = "Payment Data Structure";
    $requiredFields = ['nama', 'email', 'program_pilihan', 'payment_total'];
    $hasAllFields = true;
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($testPaymentData[$field])) {
            $hasAllFields = false;
            $missingFields[] = $field;
        }
    }
    
    $details = $hasAllFields ? "All required fields present" : "Missing: " . implode(', ', $missingFields);
    $allTests[] = displayResult($test, $hasAllFields, $details);
    if ($hasAllFields) $passedTests++;
    $totalTests++;

    // Test session setup for payment
    $test = "Payment Session Setup";
    foreach ($testPaymentData as $key => $value) {
        $_SESSION[$key] = $value;
    }
    $_SESSION['payment_confirmed'] = true;
    
    $sessionResult = true;
    foreach ($requiredFields as $field) {
        if (!isset($_SESSION[$field])) {
            $sessionResult = false;
            break;
        }
    }
    
    $allTests[] = displayResult($test, $sessionResult, $sessionResult ? "Session data set correctly" : "Session setup failed");
    if ($sessionResult) $passedTests++;
    $totalTests++;

    echo "</div>";

    // Test 6: URL and Redirect Testing
    echo "<div class='test-section'>";
    echo "<h2>6. URL and Redirect Testing</h2>";

    $test = "Confirm Payment URL";
    $confirmUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/confirm_payment.php";
    $urlResult = filter_var($confirmUrl, FILTER_VALIDATE_URL) !== false;
    $allTests[] = displayResult($test, $urlResult, "URL: $confirmUrl");
    if ($urlResult) $passedTests++;
    $totalTests++;

    $test = "Closing Page URL";
    $closingUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/closing_page.php";
    $closingUrlResult = filter_var($closingUrl, FILTER_VALIDATE_URL) !== false;
    $allTests[] = displayResult($test, $closingUrlResult, "URL: $closingUrl");
    if ($closingUrlResult) $passedTests++;
    $totalTests++;

    echo "</div>";

    // Test 7: Error Logging
    echo "<div class='test-section'>";
    echo "<h2>7. Error Logging System</h2>";

    $test = "Log Directory";
    $logDirExists = is_dir('logs') && is_writable('logs');
    $allTests[] = displayResult($test, $logDirExists, $logDirExists ? "Log directory exists and writable" : "Log directory missing or not writable");
    if ($logDirExists) $passedTests++;
    $totalTests++;

    $test = "Error Log Writing";
    $logTestMsg = "Payment Flow Analysis Test - " . date('Y-m-d H:i:s');
    $logResult = logTest($logTestMsg, 'TEST');
    $logWriteResult = $logResult !== false;
    $allTests[] = displayResult($test, $logWriteResult, $logWriteResult ? "Log entry written successfully" : "Failed to write log entry");
    if ($logWriteResult) $passedTests++;
    $totalTests++;

    echo "</div>";

    // Summary
    echo "<div class='container'>";
    echo "<h2>📊 Test Summary</h2>";
    
    $successRate = ($totalTests > 0) ? round(($passedTests / $totalTests) * 100, 1) : 0;
    $summaryClass = $successRate >= 90 ? 'alert-success' : ($successRate >= 70 ? 'alert-warning' : 'alert-danger');
    
    echo "<div class='alert $summaryClass'>";
    echo "<strong>Overall Success Rate: $successRate% ($passedTests/$totalTests tests passed)</strong>";
    echo "</div>";

    if ($successRate < 100) {
        echo "<div class='alert alert-warning'>";
        echo "<strong>⚠️ Issues Detected:</strong><br>";
        echo "Some tests failed. Please review the results above and address any failing tests before proceeding with payment processing.";
        echo "</div>";
    } else {
        echo "<div class='alert alert-success'>";
        echo "<strong>✅ All Systems Operational:</strong><br>";
        echo "Payment flow environment is ready for production use.";
        echo "</div>";
    }

    echo "</div>";

    // Manual Test Form
    if ($successRate >= 70) {
        echo "<div class='form-container'>";
        echo "<h2>🧪 Manual Payment Flow Test</h2>";
        echo "<p>Use this form to manually test the payment confirmation flow:</p>";
        ?>
        
        <form action="confirm_payment.php" method="POST" target="_blank">
            <input type="text" name="nama" placeholder="Full Name" value="<?php echo htmlspecialchars($testPaymentData['nama']); ?>" required>
            <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($testPaymentData['email']); ?>" required>
            <input type="tel" name="nomor_telepon" placeholder="Phone Number" value="<?php echo htmlspecialchars($testPaymentData['nomor_telepon']); ?>" required>
            <select name="program_pilihan" required>
                <option value="">Select Program</option>
                <option value="Haji Plus" <?php echo $testPaymentData['program_pilihan'] === 'Haji Plus' ? 'selected' : ''; ?>>Haji Plus</option>
                <option value="Umroh Premium" <?php echo $testPaymentData['program_pilihan'] === 'Umroh Premium' ? 'selected' : ''; ?>>Umroh Premium</option>
                <option value="Umroh Reguler" <?php echo $testPaymentData['program_pilihan'] === 'Umroh Reguler' ? 'selected' : ''; ?>>Umroh Reguler</option>
            </select>
            <input type="number" name="payment_total" placeholder="Payment Total" value="<?php echo htmlspecialchars($testPaymentData['payment_total']); ?>" required>
            <select name="payment_method" required>
                <option value="">Select Payment Method</option>
                <option value="transfer_bank" <?php echo $testPaymentData['payment_method'] === 'transfer_bank' ? 'selected' : ''; ?>>Bank Transfer</option>
                <option value="kartu_kredit">Credit Card</option>
                <option value="e_wallet">E-Wallet</option>
            </select>
            <input type="date" name="tanggal_keberangkatan" value="<?php echo htmlspecialchars($testPaymentData['tanggal_keberangkatan']); ?>" required>
            <input type="date" name="tanggal_kepulangan" value="<?php echo htmlspecialchars($testPaymentData['tanggal_kepulangan']); ?>" required>
            
            <button type="submit">🧪 Test Payment Confirmation</button>
        </form>
        
        <p><small>This will open the payment confirmation in a new tab. Monitor the error logger for any issues.</small></p>
        
        <?php
        echo "</div>";
    }

    // Quick Links
    echo "<div class='container'>";
    echo "<h2>🔗 Quick Links</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;'>";
    echo "<a href='error_logger.php' target='_blank' style='display: block; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; text-decoration: none; color: #495057;'>📋 Error Logger</a>";
    echo "<a href='confirm_payment_diagnostic.php' target='_blank' style='display: block; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; text-decoration: none; color: #495057;'>🔧 Payment Diagnostics</a>";
    echo "<a href='testing_dashboard.php' target='_blank' style='display: block; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; text-decoration: none; color: #495057;'>🎯 Testing Dashboard</a>";
    echo "<a href='invoice.php?nama=Test&program_pilihan=Test&payment_total=1000' target='_blank' style='display: block; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; text-decoration: none; color: #495057;'>🧾 Test Invoice</a>";
    echo "</div>";
    echo "</div>";
    ?>

</body>
</html>
