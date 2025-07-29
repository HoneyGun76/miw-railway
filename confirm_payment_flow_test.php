<?php
/**
 * Confirm Payment Flow Test
 * Tests the exact flow from form_haji submission to confirm_payment
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Confirm Payment Flow Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.section { background: white; margin: 10px 0; padding: 15px; border-radius: 8px; border-left: 4px solid #007cba; }
.success { border-left-color: green; }
.error { border-left-color: red; }
.warning { border-left-color: orange; }
.step { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
.btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
.btn:hover { background: #005a9e; }
</style></head><body>";

echo "<h1>🧪 Confirm Payment Flow Test</h1>";
echo "<p>Testing the complete flow from Haji form to payment confirmation</p>";

try {
    require_once 'config.php';
    echo "<div class='section success'><h2>✅ Step 1: Configuration Loaded</h2></div>";
    
    // Check if we have sample data to work with
    $stmt = $conn->query("SELECT * FROM data_jamaah WHERE payment_status != 'verified' LIMIT 1");
    $testRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testRecord) {
        echo "<div class='section warning'>";
        echo "<h2>⚠️ No Test Data Available</h2>";
        echo "<p>Creating sample record for testing...</p>";
        
        // Create a test record
        $testData = [
            'nik' => '9999999999999999',
            'nama' => 'Test Payment User',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Test Address Payment',
            'no_telp' => '081999888777',
            'email' => 'payment.test@example.com',
            'nama_ayah' => 'Test Father Payment',
            'pak_id' => 2,
            'type_room_pilihan' => 'Quad',
            'payment_method' => 'BSI',
            'payment_type' => 'DP',
            'payment_status' => 'pending'
        ];
        
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
        
        // Fetch the created record
        $stmt = $conn->prepare("SELECT * FROM data_jamaah WHERE nik = ?");
        $stmt->execute([$testData['nik']]);
        $testRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>✅ Test record created successfully</p>";
        echo "</div>";
    }
    
    echo "<div class='section success'>";
    echo "<h2>✅ Step 2: Test Data Available</h2>";
    echo "<div class='step'>";
    echo "<strong>Test Record Details:</strong><br>";
    echo "NIK: " . htmlspecialchars($testRecord['nik']) . "<br>";
    echo "Name: " . htmlspecialchars($testRecord['nama']) . "<br>";
    echo "Email: " . htmlspecialchars($testRecord['email']) . "<br>";
    echo "Status: " . htmlspecialchars($testRecord['payment_status']) . "<br>";
    echo "</div>";
    echo "</div>";
    
    // Now test the confirm_payment flow
    echo "<div class='section'>";
    echo "<h2>🔄 Step 3: Testing Confirm Payment Process</h2>";
    
    // Simulate POST data that would come from invoice.php
    $simulatedPostData = [
        'nik' => $testRecord['nik'],
        'nama' => $testRecord['nama'],
        'no_telp' => $testRecord['no_telp'],
        'program_pilihan' => $testRecord['program_pilihan'] ?? 'Haji Test Program',
        'payment_total' => '50000000',
        'payment_method' => $testRecord['payment_method'],
        'payment_type' => $testRecord['payment_type'],
        'email' => $testRecord['email'],
        'type_room_pilihan' => $testRecord['type_room_pilihan'],
        'tanggal_keberangkatan' => '2026-01-01',
        'transfer_account_name' => 'Test Transfer Account'
    ];
    
    echo "<div class='step'>";
    echo "<strong>Simulated POST Data:</strong><br>";
    echo "<pre>" . htmlspecialchars(print_r($simulatedPostData, true)) . "</pre>";
    echo "</div>";
    
    // Test the core logic of confirm_payment.php without file upload
    echo "<h3>Testing Payment Processing Logic:</h3>";
    
    try {
        $conn->beginTransaction();
        
        // Validate required fields
        $requiredFields = ['nik', 'transfer_account_name', 'nama', 'program_pilihan'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($simulatedPostData[$field]) || empty($simulatedPostData[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (empty($missingFields)) {
            echo "<div class='step'>✅ Required field validation: PASSED</div>";
        } else {
            echo "<div class='step'>❌ Required field validation: FAILED - Missing: " . implode(', ', $missingFields) . "</div>";
        }
        
        // Test database update
        $updateSql = "UPDATE data_jamaah SET 
            transfer_account_name = ?, 
            payment_confirmation_date = CURRENT_DATE,
            payment_confirmation_time = CURRENT_TIME,
            payment_status = 'confirmation_submitted',
            updated_at = NOW()
            WHERE nik = ?";
        
        $stmt = $conn->prepare($updateSql);
        $updateResult = $stmt->execute([
            $simulatedPostData['transfer_account_name'],
            $simulatedPostData['nik']
        ]);
        
        if ($updateResult && $stmt->rowCount() > 0) {
            echo "<div class='step'>✅ Database update: PASSED</div>";
        } else {
            echo "<div class='step'>❌ Database update: FAILED</div>";
        }
        
        // Test data retrieval
        $fetchSql = "SELECT *, 
                     (SELECT program_pilihan FROM data_paket WHERE pak_id = data_jamaah.pak_id) as program_pilihan,
                     (SELECT base_price_quad FROM data_paket WHERE pak_id = data_jamaah.pak_id) as biaya_paket,
                     (SELECT tanggal_keberangkatan FROM data_paket WHERE pak_id = data_jamaah.pak_id) as tanggal_keberangkatan,
                     (SELECT currency FROM data_paket WHERE pak_id = data_jamaah.pak_id) as currency
                     FROM data_jamaah WHERE nik = ?";
        
        $stmt = $conn->prepare($fetchSql);
        $stmt->execute([$simulatedPostData['nik']]);
        $jamaahData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($jamaahData) {
            echo "<div class='step'>✅ Data retrieval: PASSED</div>";
            echo "<div class='step'><strong>Retrieved data:</strong><br>";
            echo "Program: " . htmlspecialchars($jamaahData['program_pilihan'] ?? 'N/A') . "<br>";
            echo "Price: " . htmlspecialchars($jamaahData['biaya_paket'] ?? 'N/A') . "<br>";
            echo "Currency: " . htmlspecialchars($jamaahData['currency'] ?? 'N/A') . "<br>";
            echo "</div>";
        } else {
            echo "<div class='step'>❌ Data retrieval: FAILED</div>";
        }
        
        // Test email function availability
        require_once 'email_functions.php';
        
        if (function_exists('sendPaymentConfirmationEmail')) {
            echo "<div class='step'>✅ Email function: AVAILABLE</div>";
            
            // Prepare email data
            $paymentData = [
                'nama' => $simulatedPostData['nama'],
                'nik' => $simulatedPostData['nik'],
                'no_telp' => $jamaahData['no_telp'],
                'email' => $jamaahData['email'],
                'program_pilihan' => $jamaahData['program_pilihan'],
                'tanggal_keberangkatan' => $jamaahData['tanggal_keberangkatan'],
                'biaya_paket' => $jamaahData['biaya_paket'],
                'type_room_pilihan' => $simulatedPostData['type_room_pilihan'],
                'transfer_account_name' => $simulatedPostData['transfer_account_name'],
                'payment_time' => date('H:i:s'),
                'payment_date' => date('Y-m-d'),
                'payment_type' => $simulatedPostData['payment_type'],
                'payment_method' => $simulatedPostData['payment_method'],
                'currency' => $jamaahData['currency'] ?? 'IDR'
            ];
            
            // Determine registration type
            $registrationType = (stripos($simulatedPostData['program_pilihan'], 'haji') !== false) ? 'Haji' : 'Umroh';
            
            echo "<div class='step'>📧 Testing email with registration type: {$registrationType}</div>";
            
            try {
                $emailResult = sendPaymentConfirmationEmail($paymentData, [], $registrationType);
                echo "<div class='step'>✅ Email test: " . ($emailResult['success'] ? 'SUCCESS' : 'FAILED') . "</div>";
                if (!$emailResult['success']) {
                    echo "<div class='step'>❌ Email error: " . htmlspecialchars($emailResult['message']) . "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='step'>❌ Email exception: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div class='step'>❌ Email function: NOT AVAILABLE</div>";
        }
        
        // Test session setting
        $_SESSION['payment_success'] = [
            'status' => true,
            'timestamp' => time(),
            'message' => 'Payment confirmation submitted successfully',
            'email_status' => 'Test email status'
        ];
        
        echo "<div class='step'>✅ Session data set for closing page</div>";
        
        $conn->commit();
        echo "<div class='step'>✅ Transaction committed successfully</div>";
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<div class='step'>❌ Transaction failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "</div>";
    
    // Test closing page validation
    echo "<div class='section'>";
    echo "<h2>🚪 Step 4: Testing Closing Page Validation</h2>";
    
    $isValidPayment = (
        isset($_SESSION['payment_success']) &&
        is_array($_SESSION['payment_success']) &&
        !empty($_SESSION['payment_success']['status']) &&
        $_SESSION['payment_success']['status'] === true &&
        !empty($_SESSION['payment_success']['timestamp']) &&
        (time() - $_SESSION['payment_success']['timestamp']) < 300
    );
    
    if ($isValidPayment) {
        echo "<div class='step'>✅ Closing page validation: PASSED</div>";
        echo "<div class='step'>✅ Redirect to closing_page.php would work</div>";
    } else {
        echo "<div class='step'>❌ Closing page validation: FAILED</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section error'>";
    echo "<h2>❌ Fatal Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<div class='section'>";
echo "<h2>🎯 Test Summary</h2>";
echo "<p>This test simulates the complete confirm_payment.php flow without file upload.</p>";
echo "<p>Check each step above to identify where the issue might be occurring.</p>";
echo "<p><a href='testing_dashboard.php'>← Back to Testing Dashboard</a></p>";
echo "</div>";

// Clean up test session
unset($_SESSION['payment_success']);

echo "</body></html>";
?>
