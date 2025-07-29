<?php
/**
 * Diagnostic Test Runner for MIW Travel Forms
 * Provides detailed error analysis and troubleshooting information
 */

require_once 'config.php';

class DiagnosticTestRunner {
    private $conn;
    private $errors = [];
    private $warnings = [];
    private $testResults = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function runDiagnostics() {
        echo "<h1>🔍 MIW Travel Forms Diagnostic Test Runner</h1>";
        echo "<p>Testing Date: " . date('Y-m-d H:i:s') . "</p>";
        echo "<hr>";
        
        // Step 1: Environment Check
        $this->checkEnvironment();
        
        // Step 2: Database Status
        $this->checkDatabase();
        
        // Step 3: File System Check
        $this->checkFileSystem();
        
        // Step 4: Package Data Check
        $this->checkPackageData();
        
        // Step 5: Form Submission Test
        $this->testFormSubmission();
        
        // Step 6: Generate Report
        $this->generateDiagnosticReport();
    }
    
    private function checkEnvironment() {
        echo "<h2>🌍 Environment Check</h2>";
        
        // Check if on Heroku
        $isHeroku = !empty($_ENV['DYNO']) || !empty(getenv('DYNO'));
        $this->logResult("Environment Detection", $isHeroku ? "Heroku Production" : "Local Development", "INFO");
        
        // Check PHP version
        $phpVersion = phpversion();
        $this->logResult("PHP Version", $phpVersion, $phpVersion >= '8.0' ? "PASS" : "WARN");
        
        // Check required extensions
        $requiredExtensions = ['pdo', 'pdo_pgsql', 'json', 'curl'];
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $this->logResult("Extension: $ext", $loaded ? "Loaded" : "Missing", $loaded ? "PASS" : "FAIL");
        }
        
        // Check error reporting
        $errorReporting = error_reporting();
        $this->logResult("Error Reporting", $errorReporting > 0 ? "Enabled" : "Disabled", "INFO");
    }
    
    private function checkDatabase() {
        echo "<h2>🗄️ Database Status Check</h2>";
        
        // Check connection
        if ($this->conn) {
            $this->logResult("Database Connection", "Connected", "PASS");
            
            // Check required tables
            $requiredTables = ['data_paket', 'data_jamaah', 'data_invoice', 'error_logs'];
            foreach ($requiredTables as $table) {
                try {
                    $stmt = $this->conn->query("SELECT COUNT(*) FROM $table");
                    $count = $stmt->fetchColumn();
                    $this->logResult("Table: $table", "$count records", "PASS");
                } catch (Exception $e) {
                    $this->logResult("Table: $table", "Error: " . $e->getMessage(), "FAIL");
                    $this->errors[] = "Missing table: $table";
                }
            }
            
            // Check database permissions
            try {
                $testNik = 'DIAGNOSTIC_TEST_' . time();
                $stmt = $this->conn->prepare("INSERT INTO data_jamaah (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telp, email, nama_ayah, pak_id, type_room_pilihan, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $result = $stmt->execute([
                    $testNik, 'Diagnostic Test', 'Test City', '1990-01-01', 'Laki-laki',
                    'Test Address', '081234567890', 'test@diagnostic.com', 'Test Father', 1, 'Quad'
                ]);
                
                if ($result) {
                    $this->logResult("Database Write Permission", "Can insert data", "PASS");
                    
                    // Clean up test data
                    $stmt = $this->conn->prepare("DELETE FROM data_jamaah WHERE nik = ?");
                    $stmt->execute([$testNik]);
                    $this->logResult("Database Delete Permission", "Can delete data", "PASS");
                } else {
                    $this->logResult("Database Write Permission", "Cannot insert data", "FAIL");
                }
            } catch (Exception $e) {
                $this->logResult("Database Permissions", "Error: " . $e->getMessage(), "FAIL");
                $this->errors[] = "Database permission error: " . $e->getMessage();
            }
            
        } else {
            $this->logResult("Database Connection", "Failed", "FAIL");
            $this->errors[] = "Database connection failed";
        }
    }
    
    private function checkFileSystem() {
        echo "<h2>📁 File System Check</h2>";
        
        // Check critical files
        $criticalFiles = [
            'config.php' => 'Configuration file',
            'submit_haji.php' => 'Haji form processor',
            'submit_umroh.php' => 'Umroh form processor',
            'form_haji.php' => 'Haji form interface',
            'form_umroh.php' => 'Umroh form interface',
            'upload_handler.php' => 'File upload handler',
            'email_functions.php' => 'Email functionality'
        ];
        
        foreach ($criticalFiles as $file => $description) {
            $exists = file_exists(__DIR__ . '/' . $file);
            $this->logResult("File: $file", $exists ? "Exists" : "Missing", $exists ? "PASS" : "FAIL");
            if (!$exists) {
                $this->errors[] = "Missing critical file: $file ($description)";
            }
        }
        
        // Check upload directories
        $uploadDirs = ['uploads', 'uploads/documents', 'uploads/payments', 'uploads/cancellations'];
        foreach ($uploadDirs as $dir) {
            $fullPath = __DIR__ . '/' . $dir;
            $exists = is_dir($fullPath);
            $writable = $exists ? is_writable($fullPath) : false;
            
            if ($exists && $writable) {
                $this->logResult("Directory: $dir", "Exists and writable", "PASS");
            } elseif ($exists && !$writable) {
                $this->logResult("Directory: $dir", "Exists but not writable", "WARN");
                $this->warnings[] = "Directory $dir is not writable";
            } else {
                $this->logResult("Directory: $dir", "Missing (will be created on demand)", "INFO");
            }
        }
    }
    
    private function checkPackageData() {
        echo "<h2>📦 Package Data Check</h2>";
        
        try {
            // Check Haji packages
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_paket WHERE jenis_paket = 'Haji'");
            $stmt->execute();
            $hajiCount = $stmt->fetchColumn();
            $this->logResult("Haji Packages", "$hajiCount available", $hajiCount > 0 ? "PASS" : "FAIL");
            
            if ($hajiCount == 0) {
                $this->errors[] = "No Haji packages found in database";
            }
            
            // Check Umroh packages
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_paket WHERE jenis_paket = 'Umroh'");
            $stmt->execute();
            $umrohCount = $stmt->fetchColumn();
            $this->logResult("Umroh Packages", "$umrohCount available", $umrohCount > 0 ? "PASS" : "FAIL");
            
            if ($umrohCount == 0) {
                $this->errors[] = "No Umroh packages found in database";
            }
            
            // Check package data completeness
            $stmt = $this->conn->query("SELECT pak_id, program_pilihan, tanggal_keberangkatan, base_price_quad FROM data_paket LIMIT 1");
            $samplePackage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($samplePackage) {
                $requiredFields = ['pak_id', 'program_pilihan', 'tanggal_keberangkatan', 'base_price_quad'];
                $missingFields = [];
                
                foreach ($requiredFields as $field) {
                    if (empty($samplePackage[$field])) {
                        $missingFields[] = $field;
                    }
                }
                
                if (empty($missingFields)) {
                    $this->logResult("Package Data Completeness", "All required fields present", "PASS");
                } else {
                    $this->logResult("Package Data Completeness", "Missing fields: " . implode(', ', $missingFields), "WARN");
                    $this->warnings[] = "Package data missing fields: " . implode(', ', $missingFields);
                }
            }
            
        } catch (Exception $e) {
            $this->logResult("Package Data Check", "Error: " . $e->getMessage(), "FAIL");
            $this->errors[] = "Package data check failed: " . $e->getMessage();
        }
    }
    
    private function testFormSubmission() {
        echo "<h2>📝 Form Submission Test</h2>";
        
        try {
            // Test basic form validation
            $testData = [
                'nik' => 'DIAGNOSTIC_' . time(),
                'nama' => 'Diagnostic Test User',
                'tempat_lahir' => 'Test City',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'Test Address for Diagnostic',
                'no_telp' => '081234567890',
                'email' => 'diagnostic@test.com',
                'nama_ayah' => 'Test Father',
                'pak_id' => '1',
                'type_room_pilihan' => 'Quad'
            ];
            
            // Check if package exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_paket WHERE pak_id = ?");
            $stmt->execute([$testData['pak_id']]);
            $packageExists = $stmt->fetchColumn() > 0;
            
            if (!$packageExists) {
                // Try to find any package
                $stmt = $this->conn->query("SELECT pak_id FROM data_paket LIMIT 1");
                $firstPackage = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($firstPackage) {
                    $testData['pak_id'] = $firstPackage['pak_id'];
                    $this->logResult("Package Selection", "Using package ID: " . $testData['pak_id'], "INFO");
                } else {
                    $this->logResult("Package Selection", "No packages available for testing", "FAIL");
                    $this->errors[] = "No packages available for form testing";
                    return;
                }
            } else {
                $this->logResult("Package Selection", "Package ID 1 available", "PASS");
            }
            
            // Test form validation logic
            $this->conn->beginTransaction();
            
            // Insert test data
            $stmt = $this->conn->prepare("
                INSERT INTO data_jamaah 
                (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telp, email, nama_ayah, pak_id, type_room_pilihan, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $testData['nik'], $testData['nama'], $testData['tempat_lahir'], $testData['tanggal_lahir'],
                $testData['jenis_kelamin'], $testData['alamat'], $testData['no_telp'], $testData['email'],
                $testData['nama_ayah'], (int)$testData['pak_id'], $testData['type_room_pilihan']
            ]);
            
            if ($result) {
                $this->logResult("Form Data Insertion", "Test data inserted successfully", "PASS");
                
                // Test duplicate NIK detection
                $stmt2 = $this->conn->prepare("
                    INSERT INTO data_jamaah 
                    (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telp, email, nama_ayah, pak_id, type_room_pilihan, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                try {
                    $duplicateResult = $stmt2->execute([
                        $testData['nik'], 'Duplicate Test', $testData['tempat_lahir'], $testData['tanggal_lahir'],
                        $testData['jenis_kelamin'], $testData['alamat'], $testData['no_telp'], 'duplicate@test.com',
                        $testData['nama_ayah'], (int)$testData['pak_id'], $testData['type_room_pilihan']
                    ]);
                    
                    if ($duplicateResult) {
                        $this->logResult("Duplicate NIK Detection", "Failed - duplicate allowed", "FAIL");
                        $this->errors[] = "Duplicate NIK detection not working";
                    } else {
                        $this->logResult("Duplicate NIK Detection", "Working - duplicate rejected", "PASS");
                    }
                } catch (Exception $e) {
                    $this->logResult("Duplicate NIK Detection", "Working - exception thrown", "PASS");
                }
                
            } else {
                $this->logResult("Form Data Insertion", "Failed to insert test data", "FAIL");
                $this->errors[] = "Form data insertion failed";
            }
            
            $this->conn->rollBack(); // Always rollback test data
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->logResult("Form Submission Test", "Exception: " . $e->getMessage(), "FAIL");
            $this->errors[] = "Form submission test failed: " . $e->getMessage();
        }
    }
    
    private function logResult($test, $result, $status) {
        $icon = $status === 'PASS' ? '✅' : ($status === 'FAIL' ? '❌' : ($status === 'WARN' ? '⚠️' : 'ℹ️'));
        $color = $status === 'PASS' ? 'green' : ($status === 'FAIL' ? 'red' : ($status === 'WARN' ? 'orange' : 'blue'));
        
        echo "<div style='margin: 8px 0; padding: 10px; border-left: 4px solid $color; background: #f9f9f9;'>";
        echo "<strong>$icon $test:</strong> <span style='color: $color;'>$result</span>";
        echo "</div>";
        
        $this->testResults[] = [
            'test' => $test,
            'result' => $result,
            'status' => $status
        ];
    }
    
    private function generateDiagnosticReport() {
        echo "<h2>📊 Diagnostic Report</h2>";
        
        $totalTests = count($this->testResults);
        $passed = count(array_filter($this->testResults, fn($r) => $r['status'] === 'PASS'));
        $failed = count(array_filter($this->testResults, fn($r) => $r['status'] === 'FAIL'));
        $warnings = count(array_filter($this->testResults, fn($r) => $r['status'] === 'WARN'));
        $info = count(array_filter($this->testResults, fn($r) => $r['status'] === 'INFO'));
        
        echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>📈 Summary</h3>";
        echo "<ul>";
        echo "<li><strong>Total Checks:</strong> $totalTests</li>";
        echo "<li><strong>✅ Passed:</strong> $passed</li>";
        echo "<li><strong>❌ Failed:</strong> $failed</li>";
        echo "<li><strong>⚠️ Warnings:</strong> $warnings</li>";
        echo "<li><strong>ℹ️ Info:</strong> $info</li>";
        echo "</ul>";
        echo "</div>";
        
        if (!empty($this->errors)) {
            echo "<div style='background: #ffe6e6; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>❌ Critical Issues Found</h3>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li><strong>Error:</strong> $error</li>";
            }
            echo "</ul>";
            echo "<h4>🔧 Recommended Actions:</h4>";
            echo "<ul>";
            if (in_array("No Haji packages found in database", $this->errors) || in_array("No Umroh packages found in database", $this->errors)) {
                echo "<li>Run database initialization script to populate package data</li>";
            }
            if (strpos(implode(' ', $this->errors), 'Database') !== false) {
                echo "<li>Check database connection and credentials in config.php</li>";
            }
            if (strpos(implode(' ', $this->errors), 'Missing') !== false) {
                echo "<li>Ensure all required files are uploaded to the server</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        if (!empty($this->warnings)) {
            echo "<div style='background: #fff4e6; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>⚠️ Warnings</h3>";
            echo "<ul>";
            foreach ($this->warnings as $warning) {
                echo "<li><strong>Warning:</strong> $warning</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>🔗 Next Steps</h3>";
        echo "<ul>";
        echo "<li><a href='/testing_dashboard.php'>Return to Testing Dashboard</a></li>";
        echo "<li><a href='/comprehensive_form_testing.php'>Run Comprehensive Form Tests</a></li>";
        echo "<li><a href='/black_box_testing.php'>Run Black Box Tests</a></li>";
        echo "<li><a href='/error_logger.php'>Check Error Logger</a></li>";
        if ($failed > 0) {
            echo "<li><strong>Fix the critical issues above before running form tests</strong></li>";
        }
        echo "</ul>";
        echo "</div>";
        
        $healthScore = $totalTests > 0 ? round((($passed + $info) / $totalTests) * 100, 1) : 0;
        echo "<div style='text-align: center; font-size: 1.5em; margin: 20px 0;'>";
        if ($healthScore >= 90) {
            echo "🟢 <strong>System Health: $healthScore% - Excellent</strong>";
        } elseif ($healthScore >= 75) {
            echo "🟡 <strong>System Health: $healthScore% - Good</strong>";
        } elseif ($healthScore >= 50) {
            echo "🟠 <strong>System Health: $healthScore% - Needs Attention</strong>";
        } else {
            echo "🔴 <strong>System Health: $healthScore% - Critical Issues</strong>";
        }
        echo "</div>";
    }
}

// Run diagnostics
$diagnosticRunner = new DiagnosticTestRunner($conn);
$diagnosticRunner->runDiagnostics();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1, h2, h3 { color: #333; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { margin: 20px 0; }
</style>
