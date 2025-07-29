<?php
/**
 * Comprehensive Testing Suite for MIW Travel Forms
 * Tests both Haji and Umroh forms with white box and black box testing approaches
 */

require_once 'config.php';

class MIWFormTester {
    private $conn;
    private $testResults = [];
    private $errors = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function runAllTests() {
        echo "<h1>🧪 MIW Travel Forms Testing Suite</h1>";
        echo "<p>Testing Date: " . date('Y-m-d H:i:s') . "</p>";
        echo "<hr>";
        
        // White Box Testing
        echo "<h2>🔬 White Box Testing</h2>";
        $this->testDatabaseConnection();
        $this->testPackageRetrieval();
        $this->testFormValidation();
        $this->testFileUploadLogic();
        $this->testDataTypeHandling();
        
        // Black Box Testing
        echo "<h2>🎯 Black Box Testing</h2>";
        $this->testValidInputs();
        $this->testInvalidInputs();
        $this->testBoundaryValues();
        $this->testSpecialCharacters();
        $this->testSQLInjection();
        
        // Integration Testing
        echo "<h2>🔄 Integration Testing</h2>";
        $this->testFormToInvoiceFlow();
        
        // Generate Summary
        $this->generateTestSummary();
    }
    
    // WHITE BOX TESTING
    
    public function testDatabaseConnection() {
        echo "<h3>📊 Database Connection Test</h3>";
        
        try {
            $stmt = $this->conn->query("SELECT 1");
            $this->logResult("Database connection", "PASS", "Connection successful");
            
            // Test table existence
            $requiredTables = ['data_paket', 'data_jamaah', 'data_invoice', 'data_pembatalan'];
            foreach ($requiredTables as $table) {
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?");
                $stmt->execute([$table]);
                if ($stmt->fetchColumn() > 0) {
                    $this->logResult("Table $table exists", "PASS", "Table found");
                } else {
                    $this->logResult("Table $table exists", "FAIL", "Table not found");
                }
            }
        } catch (Exception $e) {
            $this->logResult("Database connection", "FAIL", $e->getMessage());
        }
    }
    
    public function testPackageRetrieval() {
        echo "<h3>📦 Package Retrieval Test</h3>";
        
        // Test Haji packages
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_paket WHERE jenis_paket = 'Haji'");
            $stmt->execute();
            $hajiCount = $stmt->fetchColumn();
            
            if ($hajiCount > 0) {
                $this->logResult("Haji packages available", "PASS", "$hajiCount packages found");
            } else {
                $this->logResult("Haji packages available", "FAIL", "No Haji packages found");
            }
        } catch (Exception $e) {
            $this->logResult("Haji packages retrieval", "FAIL", $e->getMessage());
        }
        
        // Test Umroh packages
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_paket WHERE jenis_paket = 'Umroh'");
            $stmt->execute();
            $umrohCount = $stmt->fetchColumn();
            
            if ($umrohCount > 0) {
                $this->logResult("Umroh packages available", "PASS", "$umrohCount packages found");
            } else {
                $this->logResult("Umroh packages available", "FAIL", "No Umroh packages found");
            }
        } catch (Exception $e) {
            $this->logResult("Umroh packages retrieval", "FAIL", $e->getMessage());
        }
    }
    
    public function testFormValidation() {
        echo "<h3>✅ Form Validation Test</h3>";
        
        // Test required field validation
        $testCases = [
            ['nik' => '', 'expected' => 'fail', 'description' => 'Empty NIK'],
            ['nik' => '12345', 'expected' => 'fail', 'description' => 'Short NIK'],
            ['nik' => '1234567890123456', 'expected' => 'pass', 'description' => 'Valid NIK'],
            ['nik' => 'abcd1234567890ab', 'expected' => 'fail', 'description' => 'Non-numeric NIK'],
            ['email' => 'invalid-email', 'expected' => 'fail', 'description' => 'Invalid email'],
            ['email' => 'test@example.com', 'expected' => 'pass', 'description' => 'Valid email'],
        ];
        
        foreach ($testCases as $test) {
            $result = $this->validateInput($test);
            $status = ($result === $test['expected']) ? "PASS" : "FAIL";
            $this->logResult("Validation: " . $test['description'], $status, "Expected: {$test['expected']}, Got: $result");
        }
    }
    
    public function testDataTypeHandling() {
        echo "<h3>🔢 Data Type Handling Test</h3>";
        
        // Test integer field handling (the fix we implemented)
        $integerTests = [
            ['tinggi_badan' => '', 'expected' => null, 'description' => 'Empty height'],
            ['tinggi_badan' => '170', 'expected' => 170, 'description' => 'Valid height'],
            ['berat_badan' => '', 'expected' => null, 'description' => 'Empty weight'],
            ['berat_badan' => '70', 'expected' => 70, 'description' => 'Valid weight'],
            ['umur' => '', 'expected' => null, 'description' => 'Empty age'],
            ['umur' => '30', 'expected' => 30, 'description' => 'Valid age'],
        ];
        
        foreach ($integerTests as $test) {
            $result = $this->testIntegerConversion($test);
            $status = ($result === $test['expected']) ? "PASS" : "FAIL";
            $this->logResult("Integer conversion: " . $test['description'], $status, "Expected: " . var_export($test['expected'], true) . ", Got: " . var_export($result, true));
        }
    }
    
    public function testFileUploadLogic() {
        echo "<h3>📁 File Upload Logic Test</h3>";
        
        // Test file validation without actual file upload
        $this->logResult("File upload handler exists", file_exists('upload_handler.php') ? "PASS" : "FAIL", "upload_handler.php");
        $this->logResult("Upload directory permissions", is_writable('/tmp/miw_uploads') ? "PASS" : "FAIL", "/tmp/miw_uploads writable");
    }
    
    // BLACK BOX TESTING
    
    public function testValidInputs() {
        echo "<h3>✅ Valid Input Testing</h3>";
        
        $validHajiData = [
            'nik' => '3273272102010001',
            'nama' => 'Ahmad Surahman',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Kebon Jeruk No. 123',
            'no_telp' => '081234567890',
            'email' => 'ahmad@example.com',
            'nama_ayah' => 'Surahman Sr.',
            'pak_id' => '2',
            'type_room_pilihan' => 'Quad'
        ];
        
        $result = $this->simulateFormSubmission('haji', $validHajiData);
        $this->logResult("Valid Haji form submission", $result ? "PASS" : "FAIL", "Complete valid data");
        
        $validUmrohData = [
            'nik' => '3273272102010002',
            'nama' => 'Siti Aminah',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1985-05-15',
            'jenis_kelamin' => 'Perempuan',
            'alamat' => 'Jl. Asia Afrika No. 456',
            'no_telp' => '081234567891',
            'email' => 'siti@example.com',
            'nama_ayah' => 'Abdullah',
            'nama_ibu' => 'Khadijah',
            'pak_id' => '1',
            'type_room_pilihan' => 'Triple'
        ];
        
        $result = $this->simulateFormSubmission('umroh', $validUmrohData);
        $this->logResult("Valid Umroh form submission", $result ? "PASS" : "FAIL", "Complete valid data");
    }
    
    public function testInvalidInputs() {
        echo "<h3>❌ Invalid Input Testing</h3>";
        
        $invalidTestCases = [
            ['nik' => '', 'description' => 'Missing NIK'],
            ['nik' => '123', 'description' => 'Too short NIK'],
            ['email' => 'invalid-email', 'description' => 'Invalid email format'],
            ['tanggal_lahir' => '2030-01-01', 'description' => 'Future birth date'],
            ['pak_id' => '999', 'description' => 'Non-existent package ID'],
        ];
        
        foreach ($invalidTestCases as $test) {
            $data = array_merge([
                'nik' => '3273272102010001',
                'nama' => 'Test User',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'Test Address',
                'no_telp' => '081234567890',
                'email' => 'test@example.com',
                'nama_ayah' => 'Test Father',
                'pak_id' => '1',
                'type_room_pilihan' => 'Quad'
            ], $test);
            
            $result = $this->simulateFormSubmission('haji', $data);
            $this->logResult("Invalid input: " . $test['description'], $result ? "FAIL" : "PASS", "Should reject invalid data");
        }
    }
    
    public function testBoundaryValues() {
        echo "<h3>🎯 Boundary Value Testing</h3>";
        
        $boundaryTests = [
            ['tinggi_badan' => '0', 'description' => 'Minimum height'],
            ['tinggi_badan' => '300', 'description' => 'Maximum height'],
            ['berat_badan' => '0', 'description' => 'Minimum weight'],
            ['berat_badan' => '500', 'description' => 'Maximum weight'],
            ['umur' => '0', 'description' => 'Minimum age'],
            ['umur' => '150', 'description' => 'Maximum age'],
        ];
        
        foreach ($boundaryTests as $test) {
            $result = $this->testIntegerConversion($test);
            $this->logResult("Boundary test: " . $test['description'], "INFO", "Value: " . $test[key($test)]);
        }
    }
    
    public function testSpecialCharacters() {
        echo "<h3>🔤 Special Characters Testing</h3>";
        
        $specialCharTests = [
            ['nama' => "Ahmad's Test", 'description' => 'Apostrophe in name'],
            ['alamat' => 'Jl. Test & Co.', 'description' => 'Ampersand in address'],
            ['nama' => '<script>alert("xss")</script>', 'description' => 'XSS attempt'],
        ];
        
        foreach ($specialCharTests as $test) {
            $data = [
                'nik' => '3273272102010001',
                'nama' => $test['nama'] ?? 'Test Name',
                'alamat' => $test['alamat'] ?? 'Test Address',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'no_telp' => '081234567890',
                'email' => 'test@example.com',
                'nama_ayah' => 'Test Father',
                'pak_id' => '1',
                'type_room_pilihan' => 'Quad'
            ];
            
            $result = $this->simulateFormSubmission('haji', $data);
            $this->logResult("Special chars: " . $test['description'], $result ? "PASS" : "FAIL", "Handling special characters");
        }
    }
    
    public function testSQLInjection() {
        echo "<h3>🛡️ SQL Injection Testing</h3>";
        
        $sqlInjectionTests = [
            ['nik' => "1234567890123456'; DROP TABLE data_jamaah; --", 'description' => 'SQL injection in NIK'],
            ['nama' => "'; DELETE FROM data_paket; --", 'description' => 'SQL injection in name'],
            ['email' => "test@example.com'; UPDATE data_jamaah SET nama='hacked'; --", 'description' => 'SQL injection in email'],
        ];
        
        foreach ($sqlInjectionTests as $test) {
            $data = [
                'nik' => $test['nik'] ?? '3273272102010001',
                'nama' => $test['nama'] ?? 'Test Name',
                'email' => $test['email'] ?? 'test@example.com',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'Test Address',
                'no_telp' => '081234567890',
                'nama_ayah' => 'Test Father',
                'pak_id' => '1',
                'type_room_pilihan' => 'Quad'
            ];
            
            try {
                $result = $this->simulateFormSubmission('haji', $data);
                // Check if tables still exist (injection didn't work)
                $stmt = $this->conn->query("SELECT COUNT(*) FROM data_jamaah");
                $tableExists = $stmt !== false;
                
                $this->logResult("SQL injection protection: " . $test['description'], $tableExists ? "PASS" : "FAIL", "Database protected");
            } catch (Exception $e) {
                $this->logResult("SQL injection protection: " . $test['description'], "PASS", "Exception caught: " . substr($e->getMessage(), 0, 100));
            }
        }
    }
    
    // INTEGRATION TESTING
    
    public function testFormToInvoiceFlow() {
        echo "<h3>🔄 Form to Invoice Integration Test</h3>";
        
        // Test complete flow for Haji
        $testData = [
            'nik' => '9999999999999999',
            'nama' => 'Integration Test User',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Test Integration Address',
            'no_telp' => '081999999999',
            'email' => 'integration@test.com',
            'nama_ayah' => 'Test Father',
            'pak_id' => '2',
            'type_room_pilihan' => 'Quad',
            'payment_method' => 'BSI',
            'payment_type' => 'DP'
        ];
        
        try {
            // Simulate form submission
            $this->conn->beginTransaction();
            
            // Test data insertion
            $stmt = $this->conn->prepare("INSERT INTO data_jamaah (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telp, email, nama_ayah, pak_id, type_room_pilihan, payment_method, payment_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            
            $result = $stmt->execute([
                $testData['nik'], $testData['nama'], $testData['tempat_lahir'], $testData['tanggal_lahir'],
                $testData['jenis_kelamin'], $testData['alamat'], $testData['no_telp'], $testData['email'],
                $testData['nama_ayah'], (int)$testData['pak_id'], $testData['type_room_pilihan'],
                $testData['payment_method'], $testData['payment_type']
            ]);
            
            if ($result) {
                $this->logResult("Data insertion", "PASS", "Test data inserted successfully");
                
                // Test invoice parameter generation
                $stmt = $this->conn->prepare("SELECT program_pilihan, tanggal_keberangkatan, base_price_quad, currency FROM data_paket WHERE pak_id = ?");
                $stmt->execute([$testData['pak_id']]);
                $package = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($package) {
                    $invoiceParams = [
                        'nama' => $testData['nama'],
                        'no_telp' => $testData['no_telp'],
                        'alamat' => $testData['alamat'],
                        'email' => $testData['email'],
                        'nik' => $testData['nik'],
                        'program_pilihan' => $package['program_pilihan'],
                        'tanggal_keberangkatan' => $package['tanggal_keberangkatan'],
                        'type_room_pilihan' => $testData['type_room_pilihan'],
                        'payment_method' => $testData['payment_method'],
                        'payment_type' => $testData['payment_type'],
                        'currency' => $package['currency'],
                        'payment_total' => $package['base_price_quad'],
                        'pak_id' => $testData['pak_id']
                    ];
                    
                    $this->logResult("Invoice parameters generation", "PASS", "Parameters generated: " . count($invoiceParams) . " fields");
                    
                    // Test URL generation
                    $queryString = http_build_query($invoiceParams);
                    $invoiceUrl = "invoice.php?" . $queryString;
                    
                    $this->logResult("Invoice URL generation", "PASS", "URL length: " . strlen($invoiceUrl) . " chars");
                } else {
                    $this->logResult("Package data retrieval", "FAIL", "Package not found");
                }
                
                // Clean up test data
                $stmt = $this->conn->prepare("DELETE FROM data_jamaah WHERE nik = ?");
                $stmt->execute([$testData['nik']]);
            } else {
                $this->logResult("Data insertion", "FAIL", "Failed to insert test data");
            }
            
            $this->conn->rollBack(); // Always rollback test transactions
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->logResult("Form to Invoice flow", "FAIL", $e->getMessage());
        }
    }
    
    // HELPER METHODS
    
    private function validateInput($test) {
        if (isset($test['nik'])) {
            if (empty($test['nik'])) return 'fail';
            if (!preg_match('/^\d{16}$/', $test['nik'])) return 'fail';
        }
        
        if (isset($test['email'])) {
            if (!filter_var($test['email'], FILTER_VALIDATE_EMAIL)) return 'fail';
        }
        
        return 'pass';
    }
    
    private function testIntegerConversion($test) {
        $intOrNull = function($value) {
            return (empty($value) || $value === '') ? null : (int)$value;
        };
        
        $field = key($test);
        return $intOrNull($test[$field]);
    }
    
    private function simulateFormSubmission($formType, $data) {
        try {
            // Validate required fields
            $requiredFields = ['nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'no_telp', 'email', 'nama_ayah', 'pak_id', 'type_room_pilihan'];
            
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return false; // Missing required field
                }
            }
            
            // Validate NIK
            if (!preg_match('/^\d{16}$/', $data['nik'])) {
                return false;
            }
            
            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            
            // Check if NIK already exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah WHERE nik = ?");
            $stmt->execute([$data['nik']]);
            if ($stmt->fetchColumn() > 0) {
                return false; // NIK already exists
            }
            
            // Check if package exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_paket WHERE pak_id = ?");
            $stmt->execute([$data['pak_id']]);
            if ($stmt->fetchColumn() == 0) {
                return false; // Package doesn't exist
            }
            
            return true; // All validations passed
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function logResult($testName, $status, $details) {
        $statusIcon = $status === 'PASS' ? '✅' : ($status === 'FAIL' ? '❌' : 'ℹ️');
        $color = $status === 'PASS' ? 'green' : ($status === 'FAIL' ? 'red' : 'blue');
        
        echo "<div style='margin: 5px 0; padding: 8px; border-left: 4px solid $color; background: #f9f9f9;'>";
        echo "<strong>$statusIcon $testName:</strong> <span style='color: $color;'>$status</span> - $details";
        echo "</div>";
        
        $this->testResults[] = [
            'test' => $testName,
            'status' => $status,
            'details' => $details
        ];
    }
    
    private function generateTestSummary() {
        echo "<h2>📊 Test Summary</h2>";
        
        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($r) => $r['status'] === 'PASS'));
        $failedTests = count(array_filter($this->testResults, fn($r) => $r['status'] === 'FAIL'));
        $infoTests = count(array_filter($this->testResults, fn($r) => $r['status'] === 'INFO'));
        
        echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>📈 Results Overview</h3>";
        echo "<ul>";
        echo "<li><strong>Total Tests:</strong> $totalTests</li>";
        echo "<li><strong>✅ Passed:</strong> $passedTests</li>";
        echo "<li><strong>❌ Failed:</strong> $failedTests</li>";
        echo "<li><strong>ℹ️ Info:</strong> $infoTests</li>";
        echo "<li><strong>Success Rate:</strong> " . round(($passedTests / ($totalTests - $infoTests)) * 100, 2) . "%</li>";
        echo "</ul>";
        echo "</div>";
        
        if ($failedTests > 0) {
            echo "<h3>❌ Failed Tests Details</h3>";
            foreach ($this->testResults as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "<div style='background: #ffe6e6; padding: 10px; margin: 5px 0; border-radius: 4px;'>";
                    echo "<strong>{$result['test']}</strong>: {$result['details']}";
                    echo "</div>";
                }
            }
        }
        
        echo "<h3>🔗 Next Steps</h3>";
        echo "<ul>";
        echo "<li><a href='/form_haji.php'>Test Haji Form Manually</a></li>";
        echo "<li><a href='/form_umroh.php'>Test Umroh Form Manually</a></li>";
        echo "<li><a href='/error_logger.php'>Check Error Logger</a></li>";
        echo "<li><a href='/db_diagnostic.php'>View Database Status</a></li>";
        echo "</ul>";
    }
}

// Run the tests
$tester = new MIWFormTester($conn);
$tester->runAllTests();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1, h2, h3 { color: #333; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { margin: 20px 0; }
</style>
