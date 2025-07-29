<?php
/**
 * Confirm Payment Diagnostic for Haji Form
 * Specifically tests the form_haji -> invoice -> confirm_payment flow
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
require_once 'email_functions.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirm Payment Diagnostic - Haji Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; margin: 10px 0; padding: 15px; border-radius: 8px; border-left: 4px solid #007cba; }
        .success { border-left-color: green; }
        .error { border-left-color: red; }
        .warning { border-left-color: orange; }
        .test-result { margin: 5px 0; padding: 8px; border-radius: 4px; }
        .pass { background: #e8f5e8; color: green; }
        .fail { background: #ffe8e8; color: red; }
        .info { background: #e8f4f8; color: #333; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #005a9e; }
    </style>
</head>
<body>

<h1>🩺 Confirm Payment Diagnostic - Haji Form Flow</h1>
<p>Testing the complete Haji form submission to payment confirmation flow</p>

<?php
echo "<div class='section'>";
echo "<h2>🔍 Step 1: Environment Check</h2>";

// Check basic environment
try {
    echo "<div class='test-result pass'>✅ Database Connection: " . get_class($conn) . "</div>";
    
    // Check if Haji packages exist
    $stmt = $conn->query("SELECT COUNT(*) FROM data_paket WHERE jenis_paket = 'Haji'");
    $hajiCount = $stmt->fetchColumn();
    echo "<div class='test-result " . ($hajiCount > 0 ? 'pass' : 'fail') . "'>";
    echo ($hajiCount > 0 ? '✅' : '❌') . " Haji Packages: {$hajiCount} found</div>";
    
    // Check upload directory
    $uploadDir = '/tmp/miw_uploads';
    $dirExists = is_dir($uploadDir);
    echo "<div class='test-result " . ($dirExists ? 'pass' : 'fail') . "'>";
    echo ($dirExists ? '✅' : '❌') . " Upload Directory: " . ($dirExists ? 'exists' : 'missing') . "</div>";
    
    // Check email function
    $emailExists = function_exists('sendPaymentConfirmationEmail');
    echo "<div class='test-result " . ($emailExists ? 'pass' : 'fail') . "'>";
    echo ($emailExists ? '✅' : '❌') . " Email Function: " . ($emailExists ? 'available' : 'missing') . "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result fail'>❌ Environment check failed: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>🧪 Step 2: Test Data Creation</h2>";

try {
    // Create a test Haji registration record
    $testNik = '9999999999999998'; // Different from flow test
    $testData = [
        'nik' => $testNik,
        'nama' => 'Test Haji User Diagnostic',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'alamat' => 'Test Address Haji Diagnostic',
        'no_telp' => '081999888666',
        'email' => 'haji.diagnostic@example.com',
        'nama_ayah' => 'Test Father Haji',
        'pak_id' => 2, // Assuming this is a Haji package
        'type_room_pilihan' => 'Quad',
        'payment_method' => 'BSI',
        'payment_type' => 'DP',
        'payment_status' => 'pending'
    ];
    
    // Check if test record already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM data_jamaah WHERE nik = ?");
    $stmt->execute([$testNik]);
    $exists = $stmt->fetchColumn();
    
    if ($exists > 0) {
        echo "<div class='test-result info'>📝 Test record already exists, using existing data</div>";
    } else {
        $conn->beginTransaction();
        
        $sql = "INSERT INTO data_jamaah (
            nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telp, email, nama_ayah,
            pak_id, type_room_pilihan, payment_method, payment_type, payment_status, created_at
        ) VALUES (
            :nik, :nama, :tempat_lahir, :tanggal_lahir, :jenis_kelamin, :alamat, :no_telp, :email, :nama_ayah,
            :pak_id, :type_room_pilihan, :payment_method, :payment_type, :payment_status, NOW()
        )";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($testData);
        $conn->commit();
        
        echo "<div class='test-result pass'>✅ Test record created successfully</div>";
    }
    
    // Fetch the record with package details
    $stmt = $conn->prepare("
        SELECT j.*, p.program_pilihan, p.tanggal_keberangkatan, p.currency,
               CASE j.type_room_pilihan
                   WHEN 'Quad' THEN p.base_price_quad
                   WHEN 'Triple' THEN p.base_price_triple
                   WHEN 'Double' THEN p.base_price_double
               END as biaya_paket
        FROM data_jamaah j
        JOIN data_paket p ON j.pak_id = p.pak_id
        WHERE j.nik = ?
    ");
    $stmt->execute([$testNik]);
    $testRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testRecord) {
        echo "<div class='test-result pass'>✅ Test record retrieved with package details</div>";
        echo "<div class='test-result info'>📋 Program: " . htmlspecialchars($testRecord['program_pilihan']) . "</div>";
        echo "<div class='test-result info'>💰 Price: " . number_format($testRecord['biaya_paket']) . " " . $testRecord['currency'] . "</div>";
    } else {
        throw new Exception("Failed to retrieve test record with package details");
    }
    
} catch (Exception $e) {
    echo "<div class='test-result fail'>❌ Test data creation failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    $testRecord = null;
}

echo "</div>";

if ($testRecord) {
    echo "<div class='section'>";
    echo "<h2>🎯 Step 3: Simulate Payment Confirmation</h2>";
    
    // Simulate the POST data that would come from invoice.php
    $simulatedPost = [
        'nik' => $testRecord['nik'],
        'nama' => $testRecord['nama'],
        'no_telp' => $testRecord['no_telp'],
        'program_pilihan' => $testRecord['program_pilihan'],
        'payment_total' => $testRecord['biaya_paket'],
        'payment_method' => $testRecord['payment_method'],
        'payment_type' => $testRecord['payment_type'],
        'email' => $testRecord['email'],
        'type_room_pilihan' => $testRecord['type_room_pilihan'],
        'tanggal_keberangkatan' => $testRecord['tanggal_keberangkatan'],
        'transfer_account_name' => 'Test Transfer Account Haji'
    ];
    
    echo "<div class='test-result info'>📤 Simulated POST Data:</div>";
    echo "<pre>" . htmlspecialchars(print_r($simulatedPost, true)) . "</pre>";
    
    try {
        // Test confirm_payment.php logic without file upload
        $conn->beginTransaction();
        
        // Test required field validation
        $requiredFields = ['nik', 'transfer_account_name', 'nama', 'program_pilihan'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($simulatedPost[$field]) || empty($simulatedPost[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (empty($missingFields)) {
            echo "<div class='test-result pass'>✅ Required field validation: PASSED</div>";
        } else {
            echo "<div class='test-result fail'>❌ Required field validation: FAILED - Missing: " . implode(', ', $missingFields) . "</div>";
        }
        
        // Test database update simulation (without file upload)
        $currentTime = date('H:i:s');
        $currentDate = date('Y-m-d');
        
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
            $currentTime,
            $currentDate,
            $simulatedPost['nik']
        ]);
        
        if ($updateResult && $stmt->rowCount() > 0) {
            echo "<div class='test-result pass'>✅ Database update: PASSED ({$stmt->rowCount()} row affected)</div>";
        } else {
            echo "<div class='test-result fail'>❌ Database update: FAILED (no rows affected)</div>";
        }
        
        // Test email function
        $paymentData = [
            'nama' => $simulatedPost['nama'],
            'nik' => $simulatedPost['nik'],
            'no_telp' => $simulatedPost['no_telp'],
            'email' => $simulatedPost['email'],
            'program_pilihan' => $simulatedPost['program_pilihan'],
            'tanggal_keberangkatan' => $simulatedPost['tanggal_keberangkatan'],
            'biaya_paket' => $simulatedPost['payment_total'],
            'type_room_pilihan' => $simulatedPost['type_room_pilihan'],
            'transfer_account_name' => $simulatedPost['transfer_account_name'],
            'payment_time' => $currentTime,
            'payment_date' => $currentDate,
            'payment_type' => $simulatedPost['payment_type'],
            'payment_method' => $simulatedPost['payment_method'],
            'currency' => $testRecord['currency']
        ];
        
        // Determine registration type
        $registrationType = (stripos($simulatedPost['program_pilihan'], 'haji') !== false) ? 'Haji' : 'Umroh';
        
        echo "<div class='test-result info'>📧 Testing email with registration type: {$registrationType}</div>";
        
        if (function_exists('sendPaymentConfirmationEmail')) {
            try {
                $emailResult = sendPaymentConfirmationEmail($paymentData, [], $registrationType);
                if ($emailResult['success']) {
                    echo "<div class='test-result pass'>✅ Email function: SUCCESS - " . htmlspecialchars($emailResult['message']) . "</div>";
                } else {
                    echo "<div class='test-result warning'>⚠️ Email function: FAILED - " . htmlspecialchars($emailResult['message']) . "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='test-result fail'>❌ Email function: EXCEPTION - " . htmlspecialchars($e->getMessage()) . "</div>";
                echo "<div class='test-result info'>📍 Error on line: " . $e->getLine() . " in " . basename($e->getFile()) . "</div>";
            }
        } else {
            echo "<div class='test-result fail'>❌ Email function: NOT AVAILABLE</div>";
        }
        
        $conn->commit();
        echo "<div class='test-result pass'>✅ Transaction committed successfully</div>";
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<div class='test-result fail'>❌ Payment confirmation simulation failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='test-result info'>📍 Error on line: " . $e->getLine() . " in " . basename($e->getFile()) . "</div>";
    }
    
    echo "</div>";
}

echo "<div class='section'>";
echo "<h2>🔧 Step 4: File System Check</h2>";

// Check critical files
$criticalFiles = [
    'confirm_payment.php' => 'Payment confirmation handler',
    'invoice.php' => 'Invoice generation page',
    'form_haji.php' => 'Haji registration form',
    'submit_haji.php' => 'Haji form submission handler',
    'email_functions.php' => 'Email functionality',
    'config.php' => 'Database configuration'
];

foreach ($criticalFiles as $file => $description) {
    $exists = file_exists($file);
    echo "<div class='test-result " . ($exists ? 'pass' : 'fail') . "'>";
    echo ($exists ? '✅' : '❌') . " {$file}: " . ($exists ? 'exists' : 'missing') . " ({$description})</div>";
    
    if ($exists && $file === 'confirm_payment.php') {
        $size = filesize($file);
        echo "<div class='test-result info'>📏 File size: " . number_format($size) . " bytes</div>";
        
        // Check for syntax errors
        $syntax = shell_exec("php -l $file 2>&1");
        if (strpos($syntax, 'No syntax errors') !== false) {
            echo "<div class='test-result pass'>✅ Syntax check: PASSED</div>";
        } else {
            echo "<div class='test-result fail'>❌ Syntax check: FAILED</div>";
            echo "<div class='test-result info'>📝 Output: " . htmlspecialchars($syntax) . "</div>";
        }
    }
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>📊 Summary & Recommendations</h2>";

echo "<div class='test-result info'>";
echo "<strong>Quick Actions:</strong><br>";
echo "• <a href='form_haji.php' class='btn'>Test Haji Form</a><br>";
echo "• <a href='error_logger.php' class='btn'>Check Error Logger</a><br>";
echo "• <a href='confirm_payment_diagnostic.php' class='btn'>Run General Diagnostic</a><br>";
echo "• <a href='testing_dashboard.php' class='btn'>Testing Dashboard</a>";
echo "</div>";

echo "<div class='test-result info'>";
echo "<strong>Common Issues to Check:</strong><br>";
echo "1. File upload limits (check php.ini: upload_max_filesize, post_max_size)<br>";
echo "2. Email configuration (SMTP settings in config.php)<br>";
echo "3. Database connection stability<br>";
echo "4. Payment file upload validation<br>";
echo "5. Session handling for payment success/error messages";
echo "</div>";

echo "</div>";
?>

</body>
</html>
