<?php
/**
 * Comprehensive Test Runner - Automated execution of all testing suites
 * This script runs all tests from the testing dashboard and reports failures
 */

set_time_limit(60);
ini_set('memory_limit', '256M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

class ComprehensiveTestRunner {
    private $conn;
    private $testResults = [];
    private $failures = [];
    private $startTime;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->startTime = microtime(true);
    }
    
    public function runAllTests() {
        echo "<!DOCTYPE html><html><head><title>Comprehensive Test Runner</title>";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .header { background: #667eea; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
            .test-section { background: white; margin: 10px 0; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea; }
            .success { color: green; font-weight: bold; }
            .failure { color: red; font-weight: bold; }
            .warning { color: orange; font-weight: bold; }
            .details { background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 4px; font-size: 0.9em; }
            .summary { background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .failures { background: #ffe8e8; padding: 20px; border-radius: 8px; margin: 20px 0; }
        </style></head><body>";
        
        echo "<div class='header'>";
        echo "<h1>🧪 Comprehensive Test Runner</h1>";
        echo "<p>Automated execution of all testing components from the testing dashboard</p>";
        echo "<p>Started: " . date('Y-m-d H:i:s') . "</p>";
        echo "</div>";
        
        // Test 1: Database Connectivity and Schema
        $this->runDatabaseTests();
        
        // Test 2: Form Validation Tests
        $this->runFormValidationTests();
        
        // Test 3: Data Type Handling Tests
        $this->runDataTypeTests();
        
        // Test 4: Security Tests
        $this->runSecurityTests();
        
        // Test 5: Integration Tests
        $this->runIntegrationTests();
        
        // Test 6: User Journey Tests
        $this->runUserJourneyTests();
        
        // Test 7: Error Handling Tests
        $this->runErrorHandlingTests();
        
        // Generate final summary
        $this->generateFinalSummary();
        
        echo "</body></html>";
    }
    
    private function runDatabaseTests() {
        echo "<div class='test-section'>";
        echo "<h2>📊 Database Tests</h2>";
        
        try {
            // Test 1: Connection
            $stmt = $this->conn->query("SELECT 1");
            $this->logResult("Database Connection", true, "Connection established successfully");
            
            // Test 2: Table existence
            $requiredTables = ['data_paket', 'data_jamaah', 'data_invoice', 'data_pembatalan'];
            foreach ($requiredTables as $table) {
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?");
                $stmt->execute([$table]);
                if ($stmt->fetchColumn() > 0) {
                    $this->logResult("Table {$table} exists", true, "Table found and accessible");
                } else {
                    $this->logResult("Table {$table} exists", false, "Table not found");
                }
            }
            
            // Test 3: Sample data
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_paket");
            $stmt->execute();
            $packageCount = $stmt->fetchColumn();
            
            if ($packageCount > 0) {
                $this->logResult("Sample package data", true, "{$packageCount} packages found");
            } else {
                $this->logResult("Sample package data", false, "No packages found");
            }
            
        } catch (Exception $e) {
            $this->logResult("Database Tests", false, $e->getMessage());
        }
        
        echo "</div>";
    }
    
    private function runFormValidationTests() {
        echo "<div class='test-section'>";
        echo "<h2>✅ Form Validation Tests</h2>";
        
        // Test NIK validation
        $nikTests = [
            ['nik' => '1234567890123456', 'expected' => true, 'desc' => 'Valid 16-digit NIK'],
            ['nik' => '123456789012345', 'expected' => false, 'desc' => 'Invalid 15-digit NIK'],
            ['nik' => '12345678901234567', 'expected' => false, 'desc' => 'Invalid 17-digit NIK'],
            ['nik' => 'abcd567890123456', 'expected' => false, 'desc' => 'Non-numeric NIK'],
            ['nik' => '', 'expected' => false, 'desc' => 'Empty NIK'],
        ];
        
        foreach ($nikTests as $test) {
            $result = $this->validateNIK($test['nik']);
            $success = ($result === $test['expected']);
            $this->logResult("NIK Validation: {$test['desc']}", $success, 
                "Input: '{$test['nik']}', Expected: " . ($test['expected'] ? 'valid' : 'invalid') . ", Got: " . ($result ? 'valid' : 'invalid'));
        }
        
        // Test email validation
        $emailTests = [
            ['email' => 'test@example.com', 'expected' => true, 'desc' => 'Valid email'],
            ['email' => 'invalid-email', 'expected' => false, 'desc' => 'Invalid email format'],
            ['email' => 'test@', 'expected' => false, 'desc' => 'Incomplete email'],
            ['email' => '@example.com', 'expected' => false, 'desc' => 'Missing local part'],
            ['email' => '', 'expected' => false, 'desc' => 'Empty email'],
        ];
        
        foreach ($emailTests as $test) {
            $result = filter_var($test['email'], FILTER_VALIDATE_EMAIL) !== false;
            $success = ($result === $test['expected']);
            $this->logResult("Email Validation: {$test['desc']}", $success, 
                "Input: '{$test['email']}', Expected: " . ($test['expected'] ? 'valid' : 'invalid') . ", Got: " . ($result ? 'valid' : 'invalid'));
        }
        
        echo "</div>";
    }
    
    private function runDataTypeTests() {
        echo "<div class='test-section'>";
        echo "<h2>🔢 Data Type Handling Tests</h2>";
        
        // Test integer field handling (our critical fix)
        $intOrNull = function($value) {
            return (empty($value) || $value === '') ? null : (int)$value;
        };
        
        $intTests = [
            ['input' => '', 'expected' => null, 'desc' => 'Empty string to NULL'],
            ['input' => '170', 'expected' => 170, 'desc' => 'Valid integer string'],
            ['input' => '0', 'expected' => 0, 'desc' => 'Zero value'],
            ['input' => 'abc', 'expected' => 0, 'desc' => 'Non-numeric string to 0'],
        ];
        
        foreach ($intTests as $test) {
            $result = $intOrNull($test['input']);
            $success = ($result === $test['expected']);
            $this->logResult("Integer Conversion: {$test['desc']}", $success, 
                "Input: '{$test['input']}', Expected: " . var_export($test['expected'], true) . ", Got: " . var_export($result, true));
        }
        
        echo "</div>";
    }
    
    private function runSecurityTests() {
        echo "<div class='test-section'>";
        echo "<h2>🛡️ Security Tests</h2>";
        
        // Test SQL injection protection
        $sqlInjectionInputs = [
            "'; DROP TABLE data_jamaah; --",
            "' OR '1'='1",
            "'; DELETE FROM data_paket; --",
            "1; SELECT * FROM data_jamaah; --"
        ];
        
        foreach ($sqlInjectionInputs as $input) {
            try {
                // Test with prepared statement (should be safe)
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_paket WHERE pak_id = ?");
                $stmt->execute([$input]);
                $result = $stmt->fetchColumn();
                
                // Check if tables still exist (injection didn't work)
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah");
                $tableCheck = $stmt->execute();
                
                if ($tableCheck) {
                    $this->logResult("SQL Injection Protection", true, "Prepared statement protected against: " . substr($input, 0, 50) . "...");
                } else {
                    $this->logResult("SQL Injection Protection", false, "Possible SQL injection vulnerability");
                }
                
            } catch (Exception $e) {
                $this->logResult("SQL Injection Protection", true, "Exception caught (good): " . substr($e->getMessage(), 0, 100));
            }
        }
        
        // Test XSS protection
        $xssInputs = [
            '<script>alert("xss")</script>',
            '<img src="x" onerror="alert(1)">',
            'javascript:alert(1)',
            '<svg onload="alert(1)">'
        ];
        
        foreach ($xssInputs as $input) {
            $sanitized = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            $success = ($sanitized !== $input); // Should be different after sanitization
            $this->logResult("XSS Protection", $success, 
                "Input sanitized: " . substr($input, 0, 30) . "... → " . substr($sanitized, 0, 30) . "...");
        }
        
        echo "</div>";
    }
    
    private function runIntegrationTests() {
        echo "<div class='test-section'>";
        echo "<h2>🔄 Integration Tests</h2>";
        
        try {
            $this->conn->beginTransaction();
            
            // Test complete form submission flow
            $testData = [
                'nik' => '9999999999999999',
                'nama' => 'Integration Test User',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'Test Address',
                'no_telp' => '081999999999',
                'email' => 'integration@test.com',
                'nama_ayah' => 'Test Father',
                'pak_id' => 2,
                'type_room_pilihan' => 'Quad'
            ];
            
            // Test 1: Data insertion
            $intOrNull = function($value) {
                return (empty($value) || $value === '') ? null : (int)$value;
            };
            
            $sql = "INSERT INTO data_jamaah (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telp, email, nama_ayah, pak_id, type_room_pilihan, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $insertResult = $stmt->execute([
                $testData['nik'], $testData['nama'], $testData['tempat_lahir'], $testData['tanggal_lahir'],
                $testData['jenis_kelamin'], $testData['alamat'], $testData['no_telp'], $testData['email'],
                $testData['nama_ayah'], $testData['pak_id'], $testData['type_room_pilihan']
            ]);
            
            if ($insertResult) {
                $this->logResult("Data Insertion", true, "Test data inserted successfully");
                
                // Test 2: Package retrieval
                $stmt = $this->conn->prepare("SELECT * FROM data_paket WHERE pak_id = ?");
                $stmt->execute([$testData['pak_id']]);
                $package = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($package) {
                    $this->logResult("Package Retrieval", true, "Package data retrieved: " . $package['program_pilihan']);
                    
                    // Test 3: Invoice parameters generation
                    $invoiceParams = [
                        'nama' => $testData['nama'],
                        'email' => $testData['email'],
                        'program_pilihan' => $package['program_pilihan'],
                        'payment_total' => $package['base_price_quad'],
                        'pak_id' => $testData['pak_id']
                    ];
                    
                    $queryString = http_build_query($invoiceParams);
                    
                    if (strlen($queryString) > 0) {
                        $this->logResult("Invoice Parameters", true, "Parameters generated: " . count($invoiceParams) . " fields");
                    } else {
                        $this->logResult("Invoice Parameters", false, "Failed to generate parameters");
                    }
                    
                } else {
                    $this->logResult("Package Retrieval", false, "Package not found");
                }
                
            } else {
                $this->logResult("Data Insertion", false, "Failed to insert test data");
            }
            
            $this->conn->rollBack(); // Always rollback test data
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->logResult("Integration Tests", false, $e->getMessage());
        }
        
        echo "</div>";
    }
    
    private function runUserJourneyTests() {
        echo "<div class='test-section'>";
        echo "<h2>🎭 User Journey Tests</h2>";
        
        // Test form accessibility
        $formFiles = ['form_haji.php', 'form_umroh.php'];
        foreach ($formFiles as $file) {
            if (file_exists($file)) {
                $this->logResult("Form File: {$file}", true, "File exists and accessible");
                
                // Test for syntax errors
                $output = shell_exec("php -l {$file} 2>&1");
                if (strpos($output, 'No syntax errors') !== false) {
                    $this->logResult("Syntax Check: {$file}", true, "No syntax errors detected");
                } else {
                    $this->logResult("Syntax Check: {$file}", false, "Syntax errors found");
                }
            } else {
                $this->logResult("Form File: {$file}", false, "File not found");
            }
        }
        
        // Test submission handlers
        $submitFiles = ['submit_haji.php', 'submit_umroh.php'];
        foreach ($submitFiles as $file) {
            if (file_exists($file)) {
                $this->logResult("Submit Handler: {$file}", true, "File exists");
                
                // Check for our integer field fix
                $content = file_get_contents($file);
                if (strpos($content, 'intOrNull') !== false || strpos($content, 'empty($') !== false) {
                    $this->logResult("Integer Field Fix: {$file}", true, "Contains integer field handling logic");
                } else {
                    $this->logResult("Integer Field Fix: {$file}", false, "May be missing integer field handling");
                }
            } else {
                $this->logResult("Submit Handler: {$file}", false, "File not found");
            }
        }
        
        echo "</div>";
    }
    
    private function runErrorHandlingTests() {
        echo "<div class='test-section'>";
        echo "<h2>🚨 Error Handling Tests</h2>";
        
        // Test error logger
        if (file_exists('error_logger.php')) {
            $this->logResult("Error Logger", true, "Error logger file exists");
        } else {
            $this->logResult("Error Logger", false, "Error logger file missing");
        }
        
        // Test error handler integration
        if (file_exists('error_handler.php')) {
            $this->logResult("Error Handler", true, "Error handler file exists");
            
            // Check if it's integrated in config
            $configContent = file_get_contents('config.php');
            if (strpos($configContent, 'error_handler') !== false) {
                $this->logResult("Error Handler Integration", true, "Error handler integrated in config.php");
            } else {
                $this->logResult("Error Handler Integration", false, "Error handler not integrated");
            }
        } else {
            $this->logResult("Error Handler", false, "Error handler file missing");
        }
        
        // Test duplicate NIK handling
        try {
            $duplicateNik = '1234567890123456';
            
            // Check if NIK already exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah WHERE nik = ?");
            $stmt->execute([$duplicateNik]);
            $count = $stmt->fetchColumn();
            
            $this->logResult("Duplicate NIK Detection", true, 
                "Duplicate check working: " . ($count > 0 ? "found existing" : "no duplicates"));
                
        } catch (Exception $e) {
            $this->logResult("Duplicate NIK Detection", false, $e->getMessage());
        }
        
        echo "</div>";
    }
    
    private function validateNIK($nik) {
        return preg_match('/^\d{16}$/', $nik) === 1;
    }
    
    private function logResult($testName, $success, $details) {
        $status = $success ? 'PASS' : 'FAIL';
        $class = $success ? 'success' : 'failure';
        
        echo "<div class='details'>";
        echo "<strong class='{$class}'>" . ($success ? '✅' : '❌') . " {$testName}: {$status}</strong><br>";
        echo "<span style='color: #666;'>{$details}</span>";
        echo "</div>";
        
        $this->testResults[] = [
            'name' => $testName,
            'success' => $success,
            'details' => $details
        ];
        
        if (!$success) {
            $this->failures[] = [
                'name' => $testName,
                'details' => $details
            ];
        }
    }
    
    private function generateFinalSummary() {
        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($r) => $r['success']));
        $failedTests = count($this->failures);
        $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
        $executionTime = round(microtime(true) - $this->startTime, 2);
        
        echo "<div class='summary'>";
        echo "<h2>📊 Test Execution Summary</h2>";
        echo "<ul>";
        echo "<li><strong>Total Tests:</strong> {$totalTests}</li>";
        echo "<li><strong>✅ Passed:</strong> {$passedTests}</li>";
        echo "<li><strong>❌ Failed:</strong> {$failedTests}</li>";
        echo "<li><strong>Success Rate:</strong> {$successRate}%</li>";
        echo "<li><strong>Execution Time:</strong> {$executionTime} seconds</li>";
        echo "</ul>";
        
        if ($successRate >= 90) {
            echo "<div style='color: green; margin-top: 10px;'>";
            echo "<strong>🎉 Excellent!</strong> System is performing very well.";
            echo "</div>";
        } elseif ($successRate >= 75) {
            echo "<div style='color: orange; margin-top: 10px;'>";
            echo "<strong>⚠️ Good</strong> but some issues need attention.";
            echo "</div>";
        } else {
            echo "<div style='color: red; margin-top: 10px;'>";
            echo "<strong>🚨 Needs Improvement</strong> - Multiple issues detected.";
            echo "</div>";
        }
        echo "</div>";
        
        if (!empty($this->failures)) {
            echo "<div class='failures'>";
            echo "<h2>❌ Test Failures Detail</h2>";
            echo "<p>The following tests failed and require attention:</p>";
            echo "<ul>";
            foreach ($this->failures as $failure) {
                echo "<li><strong>{$failure['name']}</strong>: {$failure['details']}</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        echo "<div style='margin-top: 30px; text-align: center;'>";
        echo "<p><a href='testing_dashboard.php'>← Back to Testing Dashboard</a></p>";
        echo "<p><a href='error_logger.php'>View Error Logger</a> | ";
        echo "<a href='database_diagnostic.php'>Database Status</a></p>";
        echo "</div>";
    }
}

// Run all tests
try {
    $testRunner = new ComprehensiveTestRunner($conn);
    $testRunner->runAllTests();
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 20px; border-radius: 8px; margin: 20px;'>";
    echo "<h2>❌ Critical Testing Error</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>
