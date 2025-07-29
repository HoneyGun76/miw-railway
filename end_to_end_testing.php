<?php
/**
 * End-to-End User Journey Testing
 * Simulates complete user workflow from form submission to invoice generation
 */

require_once 'config.php';

class EndToEndTester {
    private $conn;
    private $testResults = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function runEndToEndTests() {
        echo "<h1>🎭 End-to-End User Journey Testing</h1>";
        echo "<p>Testing Date: " . date('Y-m-d H:i:s') . "</p>";
        echo "<p>This suite tests the complete user journey from form submission to invoice generation.</p>";
        echo "<hr>";
        
        // Test both Haji and Umroh journeys
        $this->testHajiJourney();
        $this->testUmrohJourney();
        
        // Test edge cases in the journey
        $this->testJourneyEdgeCases();
        
        // Generate final summary
        $this->generateJourneySummary();
    }
    
    private function testHajiJourney() {
        echo "<h2>🕋 Haji Journey Testing</h2>";
        
        $testData = [
            'nik' => '1111111111111111',
            'nama' => 'Muhammad Hasan',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1980-03-15',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Masjid Raya No. 123, Jakarta Pusat',
            'no_telp' => '081111111111',
            'email' => 'muhammad.hasan@email.com',
            'nama_ayah' => 'Hassan Al-Baghdadi',
            'nama_ibu' => 'Aminah Zahra',
            'tinggi_badan' => '175',
            'berat_badan' => '75',
            'umur' => '43',
            'status_kesehatan' => 'Sehat',
            'pak_id' => '2', // Haji package
            'type_room_pilihan' => 'Quad',
            'payment_method' => 'BSI',
            'payment_type' => 'DP'
        ];
        
        $this->executeCompleteJourney('haji', $testData);
    }
    
    private function testUmrohJourney() {
        echo "<h2>🕌 Umroh Journey Testing</h2>";
        
        $testData = [
            'nik' => '2222222222222222',
            'nama' => 'Fatimah Zahra',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1985-07-20',
            'jenis_kelamin' => 'Perempuan',
            'alamat' => 'Jl. Sunda No. 456, Bandung',
            'no_telp' => '082222222222',
            'email' => 'fatimah.zahra@email.com',
            'nama_ayah' => 'Ali Rahman',
            'nama_ibu' => 'Khadijah Ummu',
            'tinggi_badan' => '160',
            'berat_badan' => '55',
            'umur' => '38',
            'status_kesehatan' => 'Sehat',
            'pak_id' => '1', // Umroh package
            'type_room_pilihan' => 'Triple',
            'payment_method' => 'BCA',
            'payment_type' => 'Lunas'
        ];
        
        $this->executeCompleteJourney('umroh', $testData);
    }
    
    private function testJourneyEdgeCases() {
        echo "<h2>⚠️ Journey Edge Cases Testing</h2>";
        
        // Test with minimal data
        echo "<h3>🎯 Minimal Data Journey</h3>";
        $minimalData = [
            'nik' => '3333333333333333',
            'nama' => 'Omar Minimal',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Minimal',
            'no_telp' => '083333333333',
            'email' => 'minimal@email.com',
            'nama_ayah' => 'Father Minimal',
            'pak_id' => '1',
            'type_room_pilihan' => 'Double'
        ];
        
        $this->executeCompleteJourney('haji', $minimalData);
        
        // Test with special characters
        echo "<h3>🔤 Special Characters Journey</h3>";
        $specialData = [
            'nik' => '4444444444444444',
            'nama' => "Ahmad D'Angelo-Rahman",
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1985-06-10',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Test & Co. No. 123/A',
            'no_telp' => '084444444444',
            'email' => 'ahmad.special@email.com',
            'nama_ayah' => "D'Angelo Rahman Sr.",
            'nama_ibu' => "Maria D'Angelo",
            'pak_id' => '1',
            'type_room_pilihan' => 'Quad'
        ];
        
        $this->executeCompleteJourney('umroh', $specialData);
    }
    
    private function executeCompleteJourney($formType, $testData) {
        echo "<h3>🚀 Testing {$testData['nama']} Journey</h3>";
        
        try {
            $this->conn->beginTransaction();
            
            // Step 1: Form Validation
            $validationResult = $this->validateFormData($testData);
            $this->logStep("Form Data Validation", $validationResult['success'], $validationResult['message']);
            
            if (!$validationResult['success']) {
                $this->conn->rollBack();
                return;
            }
            
            // Step 2: Package Retrieval
            $packageResult = $this->retrievePackageData($testData['pak_id']);
            $this->logStep("Package Data Retrieval", $packageResult['success'], $packageResult['message']);
            
            if (!$packageResult['success']) {
                $this->conn->rollBack();
                return;
            }
            
            // Step 3: Data Insertion
            $insertResult = $this->insertJamaahData($testData);
            $this->logStep("Jamaah Data Insertion", $insertResult['success'], $insertResult['message']);
            
            if (!$insertResult['success']) {
                $this->conn->rollBack();
                return;
            }
            
            // Step 4: Invoice Parameter Generation
            $invoiceResult = $this->generateInvoiceParameters($testData, $packageResult['data']);
            $this->logStep("Invoice Parameters Generation", $invoiceResult['success'], $invoiceResult['message']);
            
            // Step 5: URL Generation and Redirect Simulation
            if ($invoiceResult['success']) {
                $urlResult = $this->generateInvoiceUrl($invoiceResult['data']);
                $this->logStep("Invoice URL Generation", $urlResult['success'], $urlResult['message']);
                
                // Step 6: Invoice Page Simulation
                if ($urlResult['success']) {
                    $pageResult = $this->simulateInvoicePage($urlResult['url']);
                    $this->logStep("Invoice Page Loading", $pageResult['success'], $pageResult['message']);
                }
            }
            
            // Step 7: Cleanup (rollback test data)
            $this->conn->rollBack();
            $this->logStep("Test Data Cleanup", true, "Test transaction rolled back successfully");
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->logStep("Journey Execution", false, "Exception: " . $e->getMessage());
        }
        
        echo "<hr style='margin: 20px 0;'>";
    }
    
    private function validateFormData($data) {
        $requiredFields = ['nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 
                          'alamat', 'no_telp', 'email', 'nama_ayah', 'pak_id', 'type_room_pilihan'];
        
        // Check required fields
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                return ['success' => false, 'message' => "Missing required field: $field"];
            }
        }
        
        // Validate NIK format
        if (!preg_match('/^\d{16}$/', $data['nik'])) {
            return ['success' => false, 'message' => "Invalid NIK format"];
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => "Invalid email format"];
        }
        
        // Validate birth date
        try {
            $birthDate = new DateTime($data['tanggal_lahir']);
            $today = new DateTime();
            if ($birthDate > $today) {
                return ['success' => false, 'message' => "Birth date cannot be in the future"];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Invalid birth date format"];
        }
        
        return ['success' => true, 'message' => "All validations passed"];
    }
    
    private function retrievePackageData($pakId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM data_paket WHERE pak_id = ?");
            $stmt->execute([$pakId]);
            $package = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$package) {
                return ['success' => false, 'message' => "Package not found"];
            }
            
            return [
                'success' => true, 
                'message' => "Package retrieved: {$package['program_pilihan']}", 
                'data' => $package
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
    }
    
    private function insertJamaahData($data) {
        try {
            // Helper function for integer fields
            $intOrNull = function($value) {
                return (empty($value) || $value === '') ? null : (int)$value;
            };
            
            $sql = "INSERT INTO data_jamaah (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telp, email, nama_ayah, nama_ibu, tinggi_badan, berat_badan, umur, status_kesehatan, pak_id, type_room_pilihan, payment_method, payment_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $data['nik'], $data['nama'], $data['tempat_lahir'], $data['tanggal_lahir'],
                $data['jenis_kelamin'], $data['alamat'], $data['no_telp'], $data['email'],
                $data['nama_ayah'], $data['nama_ibu'] ?? null, 
                $intOrNull($data['tinggi_badan'] ?? ''), $intOrNull($data['berat_badan'] ?? ''),
                $intOrNull($data['umur'] ?? ''), $data['status_kesehatan'] ?? null,
                (int)$data['pak_id'], $data['type_room_pilihan'],
                $data['payment_method'] ?? null, $data['payment_type'] ?? null
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => "Jamaah data inserted successfully"];
            } else {
                return ['success' => false, 'message' => "Failed to insert jamaah data"];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
    }
    
    private function generateInvoiceParameters($jamaahData, $packageData) {
        try {
            // Calculate price based on room type
            $priceField = 'base_price_' . strtolower($jamaahData['type_room_pilihan']);
            $price = $packageData[$priceField] ?? $packageData['base_price_quad'];
            
            // Generate invoice parameters
            $invoiceParams = [
                'nama' => $jamaahData['nama'],
                'no_telp' => $jamaahData['no_telp'],
                'alamat' => $jamaahData['alamat'],
                'email' => $jamaahData['email'],
                'nik' => $jamaahData['nik'],
                'program_pilihan' => $packageData['program_pilihan'],
                'tanggal_keberangkatan' => $packageData['tanggal_keberangkatan'],
                'type_room_pilihan' => $jamaahData['type_room_pilihan'],
                'payment_method' => $jamaahData['payment_method'] ?? 'BSI',
                'payment_type' => $jamaahData['payment_type'] ?? 'DP',
                'currency' => $packageData['currency'],
                'payment_total' => $price,
                'pak_id' => $jamaahData['pak_id']
            ];
            
            return [
                'success' => true, 
                'message' => "Invoice parameters generated (" . count($invoiceParams) . " parameters)",
                'data' => $invoiceParams
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Error generating parameters: " . $e->getMessage()];
        }
    }
    
    private function generateInvoiceUrl($invoiceParams) {
        try {
            $queryString = http_build_query($invoiceParams);
            $url = "invoice.php?" . $queryString;
            
            if (strlen($url) > 2048) {
                return ['success' => false, 'message' => "URL too long: " . strlen($url) . " characters"];
            }
            
            return [
                'success' => true, 
                'message' => "Invoice URL generated (" . strlen($url) . " characters)",
                'url' => $url
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Error generating URL: " . $e->getMessage()];
        }
    }
    
    private function simulateInvoicePage($url) {
        try {
            // Parse URL to check parameters
            $urlParts = parse_url($url);
            parse_str($urlParts['query'], $params);
            
            // Check if all required parameters are present
            $requiredParams = ['nama', 'email', 'program_pilihan', 'payment_total', 'pak_id'];
            foreach ($requiredParams as $param) {
                if (!isset($params[$param])) {
                    return ['success' => false, 'message' => "Missing parameter: $param"];
                }
            }
            
            // Simulate invoice page loading
            if (file_exists('invoice.php')) {
                return ['success' => true, 'message' => "Invoice page would load successfully with " . count($params) . " parameters"];
            } else {
                return ['success' => false, 'message' => "invoice.php file not found"];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Error simulating page: " . $e->getMessage()];
        }
    }
    
    private function logStep($stepName, $success, $message) {
        $icon = $success ? '✅' : '❌';
        $color = $success ? 'green' : 'red';
        $status = $success ? 'SUCCESS' : 'FAILED';
        
        echo "<div style='margin: 8px 0; padding: 10px; border-left: 4px solid $color; background: #f9f9f9;'>";
        echo "<strong>$icon $stepName:</strong> <span style='color: $color;'>$status</span> - $message";
        echo "</div>";
        
        $this->testResults[] = [
            'step' => $stepName,
            'success' => $success,
            'message' => $message
        ];
    }
    
    private function generateJourneySummary() {
        echo "<h2>📊 Journey Testing Summary</h2>";
        
        $totalSteps = count($this->testResults);
        $successfulSteps = count(array_filter($this->testResults, fn($r) => $r['success']));
        $failedSteps = $totalSteps - $successfulSteps;
        $successRate = $totalSteps > 0 ? round(($successfulSteps / $totalSteps) * 100, 2) : 0;
        
        echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>📈 Overall Journey Results</h3>";
        echo "<ul>";
        echo "<li><strong>Total Steps Tested:</strong> $totalSteps</li>";
        echo "<li><strong>✅ Successful Steps:</strong> $successfulSteps</li>";
        echo "<li><strong>❌ Failed Steps:</strong> $failedSteps</li>";
        echo "<li><strong>Success Rate:</strong> $successRate%</li>";
        echo "</ul>";
        
        if ($successRate >= 95) {
            echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
            echo "🎉 <strong>Excellent!</strong> The user journey is working very well.";
            echo "</div>";
        } elseif ($successRate >= 80) {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
            echo "⚠️ <strong>Good</strong> but there are some steps that need attention.";
            echo "</div>";
        } else {
            echo "<div style='background: #ffe8e8; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
            echo "🚨 <strong>Needs Improvement</strong> - Multiple journey steps are failing.";
            echo "</div>";
        }
        echo "</div>";
        
        echo "<h3>🔍 Journey Steps Tested</h3>";
        echo "<ul>";
        echo "<li>✅ Form data validation</li>";
        echo "<li>✅ Package data retrieval</li>";
        echo "<li>✅ Database insertion</li>";
        echo "<li>✅ Invoice parameter generation</li>";
        echo "<li>✅ URL generation</li>";
        echo "<li>✅ Invoice page loading simulation</li>";
        echo "</ul>";
        
        echo "<h3>🔗 Manual Testing Links</h3>";
        echo "<ul>";
        echo "<li><a href='/form_haji.php' target='_blank'>Test Haji Form Manually</a></li>";
        echo "<li><a href='/form_umroh.php' target='_blank'>Test Umroh Form Manually</a></li>";
        echo "<li><a href='/comprehensive_form_testing.php' target='_blank'>Run White Box Tests</a></li>";
        echo "<li><a href='/black_box_testing.php' target='_blank'>Run Black Box Tests</a></li>";
        echo "<li><a href='/error_logger.php' target='_blank'>Check Error Logger</a></li>";
        echo "</ul>";
        
        if ($failedSteps > 0) {
            echo "<h3>❌ Failed Steps Summary</h3>";
            foreach ($this->testResults as $result) {
                if (!$result['success']) {
                    echo "<div style='background: #ffe6e6; padding: 8px; margin: 5px 0; border-radius: 4px;'>";
                    echo "<strong>{$result['step']}</strong>: {$result['message']}";
                    echo "</div>";
                }
            }
        }
    }
}

// Run the end-to-end tests
$endToEndTester = new EndToEndTester($conn);
$endToEndTester->runEndToEndTests();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1, h2, h3 { color: #333; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { margin: 20px 0; }
</style>
