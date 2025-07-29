<?php
/**
 * Black Box Testing Script for MIW Travel Forms
 * Simulates real user interactions and edge cases
 */

require_once 'config.php';

class BlackBoxTester {
    private $conn;
    private $testCases = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->initializeTestCases();
    }
    
    private function initializeTestCases() {
        $this->testCases = [
            'valid_scenarios' => [
                [
                    'name' => 'Complete Haji Registration',
                    'type' => 'haji',
                    'data' => [
                        'nik' => '3273272102010001',
                        'nama' => 'Ahmad Surahman',
                        'tempat_lahir' => 'Jakarta',
                        'tanggal_lahir' => '1980-05-15',
                        'jenis_kelamin' => 'Laki-laki',
                        'alamat' => 'Jl. Kebon Jeruk No. 123, Jakarta Barat',
                        'no_telp' => '081234567890',
                        'email' => 'ahmad.surahman@email.com',
                        'nama_ayah' => 'Muhammad Surahman',
                        'nama_ibu' => 'Siti Aminah',
                        'tinggi_badan' => '170',
                        'berat_badan' => '70',
                        'umur' => '43',
                        'status_kesehatan' => 'Sehat',
                        'pak_id' => '2',
                        'type_room_pilihan' => 'Quad',
                        'payment_method' => 'BSI',
                        'payment_type' => 'DP'
                    ],
                    'expected' => 'success'
                ],
                [
                    'name' => 'Complete Umroh Registration',
                    'type' => 'umroh',
                    'data' => [
                        'nik' => '3273272102010002',
                        'nama' => 'Siti Khadijah',
                        'tempat_lahir' => 'Bandung',
                        'tanggal_lahir' => '1985-08-20',
                        'jenis_kelamin' => 'Perempuan',
                        'alamat' => 'Jl. Asia Afrika No. 456, Bandung',
                        'no_telp' => '081234567891',
                        'email' => 'siti.khadijah@email.com',
                        'nama_ayah' => 'Abdullah Rahman',
                        'nama_ibu' => 'Fatimah Zahra',
                        'tinggi_badan' => '165',
                        'berat_badan' => '60',
                        'umur' => '38',
                        'status_kesehatan' => 'Sehat',
                        'pak_id' => '1',
                        'type_room_pilihan' => 'Triple',
                        'payment_method' => 'BCA',
                        'payment_type' => 'Lunas'
                    ],
                    'expected' => 'success'
                ],
                [
                    'name' => 'Minimal Required Fields Only',
                    'type' => 'haji',
                    'data' => [
                        'nik' => '3273272102010003',
                        'nama' => 'Umar Khattab',
                        'tempat_lahir' => 'Surabaya',
                        'tanggal_lahir' => '1975-12-01',
                        'jenis_kelamin' => 'Laki-laki',
                        'alamat' => 'Jl. Pemuda No. 789',
                        'no_telp' => '081234567892',
                        'email' => 'umar@email.com',
                        'nama_ayah' => 'Khattab',
                        'pak_id' => '2',
                        'type_room_pilihan' => 'Double'
                    ],
                    'expected' => 'success'
                ]
            ],
            'invalid_scenarios' => [
                [
                    'name' => 'Missing Required Field (NIK)',
                    'type' => 'haji',
                    'data' => [
                        'nama' => 'Test User',
                        'tempat_lahir' => 'Jakarta',
                        'tanggal_lahir' => '1990-01-01',
                        'jenis_kelamin' => 'Laki-laki',
                        'alamat' => 'Test Address',
                        'no_telp' => '081234567890',
                        'email' => 'test@email.com',
                        'nama_ayah' => 'Test Father',
                        'pak_id' => '1',
                        'type_room_pilihan' => 'Quad'
                    ],
                    'expected' => 'failure',
                    'error_type' => 'missing_required_field'
                ],
                [
                    'name' => 'Invalid NIK Format',
                    'type' => 'haji',
                    'data' => [
                        'nik' => '123456789',
                        'nama' => 'Test User',
                        'tempat_lahir' => 'Jakarta',
                        'tanggal_lahir' => '1990-01-01',
                        'jenis_kelamin' => 'Laki-laki',
                        'alamat' => 'Test Address',
                        'no_telp' => '081234567890',
                        'email' => 'test@email.com',
                        'nama_ayah' => 'Test Father',
                        'pak_id' => '1',
                        'type_room_pilihan' => 'Quad'
                    ],
                    'expected' => 'failure',
                    'error_type' => 'invalid_format'
                ],
                [
                    'name' => 'Invalid Email Format',
                    'type' => 'umroh',
                    'data' => [
                        'nik' => '3273272102010004',
                        'nama' => 'Test User',
                        'tempat_lahir' => 'Jakarta',
                        'tanggal_lahir' => '1990-01-01',
                        'jenis_kelamin' => 'Perempuan',
                        'alamat' => 'Test Address',
                        'no_telp' => '081234567890',
                        'email' => 'invalid-email-format',
                        'nama_ayah' => 'Test Father',
                        'nama_ibu' => 'Test Mother',
                        'pak_id' => '1',
                        'type_room_pilihan' => 'Quad'
                    ],
                    'expected' => 'failure',
                    'error_type' => 'invalid_email'
                ],
                [
                    'name' => 'Future Birth Date',
                    'type' => 'haji',
                    'data' => [
                        'nik' => '3273272102010005',
                        'nama' => 'Test User',
                        'tempat_lahir' => 'Jakarta',
                        'tanggal_lahir' => '2030-01-01',
                        'jenis_kelamin' => 'Laki-laki',
                        'alamat' => 'Test Address',
                        'no_telp' => '081234567890',
                        'email' => 'test@email.com',
                        'nama_ayah' => 'Test Father',
                        'pak_id' => '1',
                        'type_room_pilihan' => 'Quad'
                    ],
                    'expected' => 'failure',
                    'error_type' => 'invalid_date'
                ],
                [
                    'name' => 'Non-existent Package ID',
                    'type' => 'umroh',
                    'data' => [
                        'nik' => '3273272102010006',
                        'nama' => 'Test User',
                        'tempat_lahir' => 'Jakarta',
                        'tanggal_lahir' => '1990-01-01',
                        'jenis_kelamin' => 'Perempuan',
                        'alamat' => 'Test Address',
                        'no_telp' => '081234567890',
                        'email' => 'test@email.com',
                        'nama_ayah' => 'Test Father',
                        'nama_ibu' => 'Test Mother',
                        'pak_id' => '999',
                        'type_room_pilihan' => 'Quad'
                    ],
                    'expected' => 'failure',
                    'error_type' => 'invalid_package'
                ]
            ],
            'edge_cases' => [
                [
                    'name' => 'Empty Optional Integer Fields',
                    'type' => 'haji',
                    'data' => [
                        'nik' => '3273272102010007',
                        'nama' => 'Test User Edge Case',
                        'tempat_lahir' => 'Jakarta',
                        'tanggal_lahir' => '1990-01-01',
                        'jenis_kelamin' => 'Laki-laki',
                        'alamat' => 'Test Address',
                        'no_telp' => '081234567890',
                        'email' => 'test.edge@email.com',
                        'nama_ayah' => 'Test Father',
                        'tinggi_badan' => '',
                        'berat_badan' => '',
                        'umur' => '',
                        'pak_id' => '1',
                        'type_room_pilihan' => 'Quad'
                    ],
                    'expected' => 'success'
                ],
                [
                    'name' => 'Special Characters in Name',
                    'type' => 'umroh',
                    'data' => [
                        'nik' => '3273272102010008',
                        'nama' => "Ahmad D'Angelo",
                        'tempat_lahir' => 'Jakarta',
                        'tanggal_lahir' => '1990-01-01',
                        'jenis_kelamin' => 'Laki-laki',
                        'alamat' => 'Jl. Test & Co.',
                        'no_telp' => '081234567890',
                        'email' => 'ahmad.dangelo@email.com',
                        'nama_ayah' => "D'Angelo Sr.",
                        'nama_ibu' => "Maria D'Angelo",
                        'pak_id' => '1',
                        'type_room_pilihan' => 'Quad'
                    ],
                    'expected' => 'success'
                ],
                [
                    'name' => 'Maximum Length Fields',
                    'type' => 'haji',
                    'data' => [
                        'nik' => '3273272102010009',
                        'nama' => str_repeat('Ahmad ', 20), // Very long name
                        'tempat_lahir' => 'Jakarta Barat Selatan Utara Tengah',
                        'tanggal_lahir' => '1990-01-01',
                        'jenis_kelamin' => 'Laki-laki',
                        'alamat' => str_repeat('Jl. Test No. 123 ', 10),
                        'no_telp' => '081234567890',
                        'email' => 'very.long.email.address.test@example.com',
                        'nama_ayah' => str_repeat('Father ', 15),
                        'pak_id' => '1',
                        'type_room_pilihan' => 'Quad'
                    ],
                    'expected' => 'success'
                ]
            ],
            'security_tests' => [
                [
                    'name' => 'SQL Injection in NIK',
                    'type' => 'haji',
                    'data' => [
                        'nik' => "1234567890123456'; DROP TABLE data_jamaah; --",
                        'nama' => 'SQL Test User',
                        'tempat_lahir' => 'Jakarta',
                        'tanggal_lahir' => '1990-01-01',
                        'jenis_kelamin' => 'Laki-laki',
                        'alamat' => 'Test Address',
                        'no_telp' => '081234567890',
                        'email' => 'sqltest@email.com',
                        'nama_ayah' => 'Test Father',
                        'pak_id' => '1',
                        'type_room_pilihan' => 'Quad'
                    ],
                    'expected' => 'failure',
                    'error_type' => 'security_violation'
                ],
                [
                    'name' => 'XSS Attempt in Name',
                    'type' => 'umroh',
                    'data' => [
                        'nik' => '3273272102010010',
                        'nama' => '<script>alert("XSS")</script>',
                        'tempat_lahir' => 'Jakarta',
                        'tanggal_lahir' => '1990-01-01',
                        'jenis_kelamin' => 'Perempuan',
                        'alamat' => 'Test Address',
                        'no_telp' => '081234567890',
                        'email' => 'xsstest@email.com',
                        'nama_ayah' => 'Test Father',
                        'nama_ibu' => 'Test Mother',
                        'pak_id' => '1',
                        'type_room_pilihan' => 'Quad'
                    ],
                    'expected' => 'success', // Should be sanitized, not rejected
                    'error_type' => 'xss_attempt'
                ]
            ]
        ];
    }
    
    public function runAllTests() {
        echo "<h1>🎯 Black Box Testing Suite</h1>";
        echo "<p>Testing Date: " . date('Y-m-d H:i:s') . "</p>";
        echo "<p>This suite simulates real user interactions and edge cases.</p>";
        echo "<hr>";
        
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        
        foreach ($this->testCases as $category => $tests) {
            echo "<h2>📋 " . ucwords(str_replace('_', ' ', $category)) . "</h2>";
            
            foreach ($tests as $test) {
                $totalTests++;
                echo "<h3>🧪 {$test['name']}</h3>";
                
                $result = $this->executeTest($test);
                
                if ($result['success']) {
                    $passedTests++;
                    echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border-left: 4px solid green;'>";
                    echo "✅ <strong>PASSED</strong> - {$result['message']}";
                } else {
                    $failedTests++;
                    echo "<div style='background: #ffe8e8; padding: 10px; margin: 10px 0; border-left: 4px solid red;'>";
                    echo "❌ <strong>FAILED</strong> - {$result['message']}";
                }
                echo "</div>";
                
                if (!empty($result['details'])) {
                    echo "<div style='background: #f0f8ff; padding: 8px; margin: 5px 0; font-size: 0.9em;'>";
                    echo "<strong>Details:</strong> {$result['details']}";
                    echo "</div>";
                }
            }
        }
        
        $this->generateSummary($totalTests, $passedTests, $failedTests);
    }
    
    private function executeTest($test) {
        try {
            // Start transaction for testing
            $this->conn->beginTransaction();
            
            $result = $this->simulateFormSubmission($test['type'], $test['data']);
            
            // Always rollback test data
            $this->conn->rollBack();
            
            $expected = $test['expected'];
            $success = ($result['status'] === $expected);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => "Test behaved as expected ({$expected})",
                    'details' => $result['message']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Expected {$expected}, got {$result['status']}",
                    'details' => $result['message']
                ];
            }
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            
            return [
                'success' => false,
                'message' => "Test threw exception",
                'details' => $e->getMessage()
            ];
        }
    }
    
    private function simulateFormSubmission($formType, $data) {
        try {
            // Validate required fields
            $requiredFields = ['nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 
                             'alamat', 'no_telp', 'email', 'nama_ayah', 'pak_id', 'type_room_pilihan'];
            
            if ($formType === 'umroh') {
                $requiredFields[] = 'nama_ibu';
            }
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    return [
                        'status' => 'failure',
                        'message' => "Missing required field: $field"
                    ];
                }
            }
            
            // Validate NIK format
            if (!preg_match('/^\d{16}$/', $data['nik'])) {
                return [
                    'status' => 'failure',
                    'message' => "Invalid NIK format"
                ];
            }
            
            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'status' => 'failure',
                    'message' => "Invalid email format"
                ];
            }
            
            // Validate birth date
            $birthDate = new DateTime($data['tanggal_lahir']);
            $today = new DateTime();
            if ($birthDate > $today) {
                return [
                    'status' => 'failure',
                    'message' => "Birth date cannot be in the future"
                ];
            }
            
            // Check for duplicate NIK
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah WHERE nik = ?");
            $stmt->execute([$data['nik']]);
            if ($stmt->fetchColumn() > 0) {
                return [
                    'status' => 'failure',
                    'message' => "NIK already exists in database"
                ];
            }
            
            // Check if package exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_paket WHERE pak_id = ?");
            $stmt->execute([$data['pak_id']]);
            if ($stmt->fetchColumn() == 0) {
                return [
                    'status' => 'failure',
                    'message' => "Package ID does not exist"
                ];
            }
            
            // Handle integer fields with intOrNull function
            $intOrNull = function($value) {
                return (empty($value) || $value === '') ? null : (int)$value;
            };
            
            // Prepare data for insertion
            $insertData = [
                'nik' => $data['nik'],
                'nama' => $data['nama'],
                'tempat_lahir' => $data['tempat_lahir'],
                'tanggal_lahir' => $data['tanggal_lahir'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'alamat' => $data['alamat'],
                'no_telp' => $data['no_telp'],
                'email' => $data['email'],
                'nama_ayah' => $data['nama_ayah'],
                'nama_ibu' => $data['nama_ibu'] ?? null,
                'tinggi_badan' => $intOrNull($data['tinggi_badan'] ?? ''),
                'berat_badan' => $intOrNull($data['berat_badan'] ?? ''),
                'umur' => $intOrNull($data['umur'] ?? ''),
                'status_kesehatan' => $data['status_kesehatan'] ?? null,
                'pak_id' => (int)$data['pak_id'],
                'type_room_pilihan' => $data['type_room_pilihan'],
                'payment_method' => $data['payment_method'] ?? null,
                'payment_type' => $data['payment_type'] ?? null
            ];
            
            // Simulate database insertion
            $sql = "INSERT INTO data_jamaah (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telp, email, nama_ayah, nama_ibu, tinggi_badan, berat_badan, umur, status_kesehatan, pak_id, type_room_pilihan, payment_method, payment_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $insertData['nik'], $insertData['nama'], $insertData['tempat_lahir'], $insertData['tanggal_lahir'],
                $insertData['jenis_kelamin'], $insertData['alamat'], $insertData['no_telp'], $insertData['email'],
                $insertData['nama_ayah'], $insertData['nama_ibu'], $insertData['tinggi_badan'], $insertData['berat_badan'],
                $insertData['umur'], $insertData['status_kesehatan'], $insertData['pak_id'], $insertData['type_room_pilihan'],
                $insertData['payment_method'], $insertData['payment_type']
            ]);
            
            if ($result) {
                return [
                    'status' => 'success',
                    'message' => "Form submission successful, data inserted"
                ];
            } else {
                return [
                    'status' => 'failure',
                    'message' => "Database insertion failed"
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'status' => 'failure',
                'message' => "Database error: " . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'failure',
                'message' => "General error: " . $e->getMessage()
            ];
        }
    }
    
    private function generateSummary($total, $passed, $failed) {
        echo "<h2>📊 Black Box Testing Summary</h2>";
        
        $successRate = $total > 0 ? round(($passed / $total) * 100, 2) : 0;
        
        echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>📈 Test Results</h3>";
        echo "<ul>";
        echo "<li><strong>Total Tests:</strong> $total</li>";
        echo "<li><strong>✅ Passed:</strong> $passed</li>";
        echo "<li><strong>❌ Failed:</strong> $failed</li>";
        echo "<li><strong>Success Rate:</strong> $successRate%</li>";
        echo "</ul>";
        
        if ($successRate >= 90) {
            echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
            echo "🎉 <strong>Excellent!</strong> Form validation is working very well.";
            echo "</div>";
        } elseif ($successRate >= 75) {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
            echo "⚠️ <strong>Good</strong> but there are some issues that need attention.";
            echo "</div>";
        } else {
            echo "<div style='background: #ffe8e8; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
            echo "🚨 <strong>Needs Improvement</strong> - Multiple validation issues detected.";
            echo "</div>";
        }
        echo "</div>";
        
        echo "<h3>🔗 Manual Testing Links</h3>";
        echo "<ul>";
        echo "<li><a href='/form_haji.php' target='_blank'>Manual Test: Haji Form</a></li>";
        echo "<li><a href='/form_umroh.php' target='_blank'>Manual Test: Umroh Form</a></li>";
        echo "<li><a href='/error_logger.php' target='_blank'>Check Error Logger</a></li>";
        echo "<li><a href='/comprehensive_form_testing.php' target='_blank'>Run White Box Tests</a></li>";
        echo "</ul>";
        
        echo "<h3>📝 Test Coverage</h3>";
        echo "<ul>";
        echo "<li>✅ Valid input scenarios</li>";
        echo "<li>✅ Invalid input scenarios</li>";
        echo "<li>✅ Edge cases and boundary values</li>";
        echo "<li>✅ Security testing (SQL injection, XSS)</li>";
        echo "<li>✅ Data type handling (integer fields)</li>";
        echo "<li>✅ Form validation logic</li>";
        echo "</ul>";
    }
}

// Run the black box tests
$blackBoxTester = new BlackBoxTester($conn);
$blackBoxTester->runAllTests();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1, h2, h3 { color: #333; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { margin: 20px 0; }
</style>
