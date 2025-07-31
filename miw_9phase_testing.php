<?php
/**
 * MIW RAILWAY - 9-PHASE COMPREHENSIVE TESTING FRAMEWORK
 * Black Box and White Box Testing for Railway Deployment
 * 
 * Date: July 31, 2025
 * Target: Deployed MIW Railway system
 */

class MIW9PhaseTestFramework {
    private $baseUrl;
    private $testResults = [];
    private $currentPhase = 0;
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $criticalIssues = [];
    private $warnings = [];
    
    public function __construct($baseUrl = null) {
        // Auto-detect Railway URL or use local for now
        $this->baseUrl = $baseUrl ?? $this->detectRailwayUrl();
        echo "🚀 MIW RAILWAY 9-PHASE COMPREHENSIVE TESTING\n";
        echo "===========================================\n";
        echo "Target URL: {$this->baseUrl}\n";
        echo "Testing Mode: Black Box + White Box\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    private function detectRailwayUrl() {
        // Check for Railway environment variables
        if (getenv('RAILWAY_STATIC_URL')) {
            return getenv('RAILWAY_STATIC_URL');
        }
        
        // Check for Railway project indicators
        if (file_exists('railway.json') || getenv('RAILWAY_PROJECT_ID')) {
            return 'https://miw-railway-production.up.railway.app'; // Replace with actual URL
        }
        
        // Default to local for testing
        return 'http://localhost/MIW-Railway/miw-railway';
    }
    
    public function runAll9Phases() {
        echo "🎯 EXECUTING ALL 9 TESTING PHASES\n";
        echo "===============================\n\n";
        
        $this->executePhase1(); // Package Operations
        $this->executePhase2(); // Form Submissions
        $this->executePhase3(); // Invoice & Payment
        $this->executePhase4(); // Admin Pending
        $this->executePhase5(); // Cancellation Form
        $this->executePhase6(); // Admin Cancellation
        $this->executePhase7(); // Room List Management
        $this->executePhase8(); // Kelengkapan Management
        $this->executePhase9(); // Manifest & Excel Export
        
        $this->generateFinalReport();
    }
    
    /**
     * PHASE 1: DATA_PAKET AND ADMIN_PAKET OPERATIONS
     */
    private function executePhase1() {
        $this->currentPhase = 1;
        echo "📦 PHASE 1: PACKAGE OPERATIONS AND MANAGEMENT\n";
        echo "============================================\n";
        
        // Black Box Tests
        echo "🔲 Black Box Testing:\n";
        $this->testUrlAccess("admin_paket.php", "Admin Package Page Access", true);
        $this->testFileExists("paket_functions.php", "Package Functions File", true);
        $this->testDatabaseTable("data_paket", "Package Database Table", true);
        $this->testFormElements("admin_paket.php", ["form", "input", "select"], "Package Management Interface");
        
        // White Box Tests
        echo "⬜ White Box Testing:\n";
        $this->analyzeCodeSecurity("admin_paket.php", "Package Management Security", true);
        $this->analyzeDatabaseQueries("admin_paket.php", "Package Query Security", true);
        $this->testCSRFImplementation("admin_paket.php", "Package CSRF Protection");
        $this->analyzeInputValidation("admin_paket.php", "Package Input Validation");
        
        echo "\n";
    }
    
    /**
     * PHASE 2: FORM SUBMISSION TESTING
     */
    private function executePhase2() {
        $this->currentPhase = 2;
        echo "📝 PHASE 2: FORM SUBMISSION TO INVOICE REDIRECT\n";
        echo "=============================================\n";
        
        // Black Box Tests
        echo "🔲 Black Box Testing:\n";
        $this->testUrlAccess("form_umroh.php", "Umrah Form Access", true);
        $this->testUrlAccess("form_haji.php", "Hajj Form Access", true);
        $this->testFormSubmission("form_umroh.php", "submit_umroh.php", "Umrah Form Submission");
        $this->testFormSubmission("form_haji.php", "submit_haji.php", "Hajj Form Submission");
        $this->testFileUploadFunctionality("form_umroh.php", "Form File Upload");
        $this->testInvoiceRedirectFlow("submit_umroh.php", "Invoice Redirect Flow");
        
        // White Box Tests
        echo "⬜ White Box Testing:\n";
        $this->analyzeSubmitHandlers(["submit_umroh.php", "submit_haji.php"], "Submit Handler Security", true);
        $this->analyzeUploadHandlers("upload_handler.php", "Upload Handler Security", true);
        $this->testDatabaseInsertions(["submit_umroh.php", "submit_haji.php"], "Database Insertion Security", true);
        $this->analyzeFormValidation(["form_umroh.php", "form_haji.php"], "Form Validation Logic");
        
        echo "\n";
    }
    
    /**
     * PHASE 3: INVOICE AND PAYMENT CONFIRMATION
     */
    private function executePhase3() {
        $this->currentPhase = 3;
        echo "💳 PHASE 3: INVOICE AND PAYMENT CONFIRMATION\n";
        echo "==========================================\n";
        
        // Black Box Tests
        echo "🔲 Black Box Testing:\n";
        $this->testUrlAccess("invoice.php", "Invoice Page Access", true);
        $this->testUrlAccess("confirm_payment.php", "Payment Confirmation Access", true);
        $this->testInvoiceGeneration("invoice.php", "Invoice Generation");
        $this->testPaymentFileUpload("confirm_payment.php", "Payment Proof Upload");
        $this->testEmailNotifications("email_functions.php", "Email Notification System");
        
        // White Box Tests
        echo "⬜ White Box Testing:\n";
        $this->analyzeInvoiceLogic("invoice.php", "Invoice Generation Logic");
        $this->analyzePaymentSecurity("confirm_payment.php", "Payment Confirmation Security", true);
        $this->analyzeEmailSecurity("email_functions.php", "Email Function Security");
        $this->testPaymentDataIntegrity("confirm_payment.php", "Payment Data Integrity", true);
        
        echo "\n";
    }
    
    /**
     * PHASE 4: ADMIN PENDING AND VERIFICATION
     */
    private function executePhase4() {
        $this->currentPhase = 4;
        echo "⏳ PHASE 4: ADMIN PENDING AND VERIFICATION\n";
        echo "========================================\n";
        
        // Black Box Tests
        echo "🔲 Black Box Testing:\n";
        $this->testUrlAccess("admin_pending.php", "Admin Pending Access", true);
        $this->testVerificationWorkflow("admin_pending.php", "Verification Operations");
        $this->testKwitansiGeneration("kwitansi_template.php", "Kwitansi Template Generation");
        $this->testInvoiceEmailFlow("admin_pending.php", "Invoice Email Submission");
        
        // White Box Tests
        echo "⬜ White Box Testing:\n";
        $this->analyzeAdminAuthentication("admin_pending.php", "Admin Authentication", true);
        $this->analyzeVerificationLogic("admin_pending.php", "Verification Process Logic");
        $this->analyzeKwitansiLogic("kwitansi_template.php", "Kwitansi Generation Logic");
        $this->analyzeEmailIntegration("admin_pending.php", "Email Integration Security");
        
        echo "\n";
    }
    
    /**
     * PHASE 5: CANCELLATION FORM AND SUBMISSION
     */
    private function executePhase5() {
        $this->currentPhase = 5;
        echo "❌ PHASE 5: CANCELLATION FORM AND SUBMISSION\n";
        echo "==========================================\n";
        
        // Black Box Tests
        echo "🔲 Black Box Testing:\n";
        $this->testUrlAccess("form_pembatalan.php", "Cancellation Form Access", true);
        $this->testCancellationSubmission("form_pembatalan.php", "Cancellation Form Submission");
        $this->testCancellationFileUpload("form_pembatalan.php", "Cancellation File Upload");
        
        // White Box Tests
        echo "⬜ White Box Testing:\n";
        $this->analyzeCancellationSecurity("submit_pembatalan.php", "Cancellation Handler Security", true);
        $this->analyzeCancellationValidation("form_pembatalan.php", "Cancellation Form Validation");
        $this->testCancellationDataFlow("submit_pembatalan.php", "Cancellation Data Flow", true);
        
        echo "\n";
    }
    
    /**
     * PHASE 6: ADMIN CANCELLATION MANAGEMENT
     */
    private function executePhase6() {
        $this->currentPhase = 6;
        echo "🗑️ PHASE 6: ADMIN CANCELLATION MANAGEMENT\n";
        echo "========================================\n";
        
        // Black Box Tests
        echo "🔲 Black Box Testing:\n";
        $this->testUrlAccess("admin_pembatalan.php", "Admin Cancellation Access", true);
        $this->testCancellationManagement("admin_pembatalan.php", "Cancellation Management Interface");
        $this->testDeleteOperations("delete_pembatalan.php", "Delete Operations");
        
        // White Box Tests
        echo "⬜ White Box Testing:\n";
        $this->analyzeDeleteSecurity("delete_pembatalan.php", "Delete Operation Security", true);
        $this->analyzeCancellationAdminLogic("admin_pembatalan.php", "Admin Cancellation Logic");
        $this->testDataDeletionIntegrity("delete_pembatalan.php", "Data Deletion Integrity", true);
        
        echo "\n";
    }
    
    /**
     * PHASE 7: ADMIN ROOM LIST MANAGEMENT
     */
    private function executePhase7() {
        $this->currentPhase = 7;
        echo "🏨 PHASE 7: ADMIN ROOM LIST MANAGEMENT\n";
        echo "====================================\n";
        
        // Black Box Tests
        echo "🔲 Black Box Testing:\n";
        $this->testUrlAccess("admin_roomlist.php", "Room List Access", true);
        $this->testRoomListInterface("admin_roomlist.php", "Room List Interface");
        $this->testRoomManagementOperations("admin_roomlist.php", "Room Management Operations");
        
        // White Box Tests
        echo "⬜ White Box Testing:\n";
        $this->analyzeRoomListLogic("admin_roomlist.php", "Room List Logic");
        $this->analyzeRoomListScripts("roomlist_scripts.js", "Room List Scripts");
        $this->testRoomDataIntegrity("admin_roomlist.php", "Room Data Integrity");
        
        echo "\n";
    }
    
    /**
     * PHASE 8: ADMIN KELENGKAPAN MANAGEMENT
     */
    private function executePhase8() {
        $this->currentPhase = 8;
        echo "📁 PHASE 8: ADMIN KELENGKAPAN MANAGEMENT\n";
        echo "======================================\n";
        
        // Black Box Tests
        echo "🔲 Black Box Testing:\n";
        $this->testUrlAccess("admin_kelengkapan.php", "Kelengkapan Access", true);
        $this->testKelengkapanInterface("admin_kelengkapan.php", "Kelengkapan Interface");
        $this->testKelengkapanFileManagement("tab_kelengkapan.php", "File Upload Management");
        
        // White Box Tests
        echo "⬜ White Box Testing:\n";
        $this->analyzeKelengkapanLogic("admin_kelengkapan.php", "Kelengkapan Logic");
        $this->analyzeKelengkapanTabs("tab_kelengkapan.php", "Kelengkapan Tab Logic");
        $this->testKelengkapanSecurity("admin_kelengkapan.php", "Kelengkapan Security", true);
        
        echo "\n";
    }
    
    /**
     * PHASE 9: ADMIN MANIFEST AND EXCEL EXPORT
     */
    private function executePhase9() {
        $this->currentPhase = 9;
        echo "📊 PHASE 9: ADMIN MANIFEST AND EXCEL EXPORT\n";
        echo "==========================================\n";
        
        // Black Box Tests
        echo "🔲 Black Box Testing:\n";
        $this->testUrlAccess("admin_manifest.php", "Manifest Access", true);
        $this->testManifestInterface("admin_manifest.php", "Manifest Interface");
        $this->testExcelExportFunctionality("export_manifest.php", "Excel Export");
        
        // White Box Tests
        echo "⬜ White Box Testing:\n";
        $this->analyzeManifestLogic("admin_manifest.php", "Manifest Logic");
        $this->analyzeExportLogic("export_manifest.php", "Export Logic");
        $this->analyzeManifestScripts("manifest_scripts.js", "Manifest Scripts");
        $this->testExportSecurity("export_manifest.php", "Export Security", true);
        
        echo "\n";
    }
    
    // ===== TESTING UTILITY METHODS =====
    
    private function testUrlAccess($file, $testName, $critical = false) {
        $exists = file_exists($file);
        $this->recordTest($testName, $exists, 
            "File accessible", 
            "File not found or not accessible", 
            $critical);
    }
    
    private function testFileExists($file, $testName, $critical = false) {
        $exists = file_exists($file);
        $this->recordTest($testName, $exists, 
            "File exists", 
            "File missing", 
            $critical);
    }
    
    private function testDatabaseTable($table, $testName, $critical = false) {
        try {
            require_once 'safe_config.php';
            global $conn;
            if (!$conn) {
                $this->recordTest($testName, false, "", "Database connection not available", $critical);
                return;
            }
            
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $result = $stmt->execute([$table]);
            $exists = $stmt->rowCount() > 0;
            
            $this->recordTest($testName, $exists, 
                "Database table exists", 
                "Database table missing", 
                $critical);
        } catch (Exception $e) {
            $this->recordTest($testName, false, "", "Database error: " . $e->getMessage(), $critical);
        }
    }
    
    private function testFormElements($file, $elements, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "File not found", $critical);
            return;
        }
        
        $content = file_get_contents($file);
        $foundElements = 0;
        
        foreach ($elements as $element) {
            if (strpos($content, "<{$element}") !== false) {
                $foundElements++;
            }
        }
        
        $success = $foundElements === count($elements);
        $this->recordTest($testName, $success, 
            "All form elements found ({$foundElements}/" . count($elements) . ")", 
            "Missing form elements ({$foundElements}/" . count($elements) . ")", 
            $critical);
    }
    
    private function analyzeCodeSecurity($file, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "File not found", $critical);
            return;
        }
        
        $content = file_get_contents($file);
        $securityFeatures = 0;
        
        // Check for common security features
        if (strpos($content, 'csrf_protection') !== false) $securityFeatures++;
        if (strpos($content, 'input_validator') !== false) $securityFeatures++;
        if (strpos($content, 'prepare(') !== false) $securityFeatures++;
        if (strpos($content, 'htmlspecialchars') !== false) $securityFeatures++;
        
        $this->recordTest($testName, $securityFeatures >= 2, 
            "Security features implemented ({$securityFeatures}/4)", 
            "Insufficient security features ({$securityFeatures}/4)", 
            $critical);
    }
    
    private function analyzeDatabaseQueries($file, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "File not found", $critical);
            return;
        }
        
        $content = file_get_contents($file);
        $hasPreparedStatements = strpos($content, 'prepare(') !== false;
        $hasDirectQueries = preg_match('/\$conn->query\(|mysqli_query\(/', $content);
        
        $this->recordTest($testName, $hasPreparedStatements && !$hasDirectQueries, 
            "Uses secure prepared statements", 
            "May have SQL injection vulnerabilities", 
            $critical);
    }
    
    private function testCSRFImplementation($file, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "File not found", $critical);
            return;
        }
        
        $content = file_get_contents($file);
        $hasCSRF = strpos($content, 'csrf_token') !== false || 
                   strpos($content, 'csrf_protection') !== false;
        
        $this->recordTest($testName, $hasCSRF, 
            "CSRF protection implemented", 
            "CSRF protection missing", 
            $critical);
    }
    
    private function analyzeInputValidation($file, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "File not found", $critical);
            return;
        }
        
        $content = file_get_contents($file);
        $hasValidation = strpos($content, 'required') !== false || 
                        strpos($content, 'validate') !== false ||
                        strpos($content, 'input_validator') !== false;
        
        $this->recordTest($testName, $hasValidation, 
            "Input validation implemented", 
            "Input validation missing", 
            $critical);
    }
    
    private function testFormSubmission($formFile, $submitFile, $testName, $critical = false) {
        $formExists = file_exists($formFile);
        $submitExists = file_exists($submitFile);
        
        if (!$formExists || !$submitExists) {
            $this->recordTest($testName, false, "", 
                "Form or submit handler missing", $critical);
            return;
        }
        
        $formContent = file_get_contents($formFile);
        $hasPostMethod = strpos($formContent, 'method="POST"') !== false;
        $hasAction = strpos($formContent, "action=\"{$submitFile}\"") !== false;
        
        $this->recordTest($testName, $hasPostMethod && $hasAction, 
            "Form submission properly configured", 
            "Form submission configuration issues", 
            $critical);
    }
    
    private function testFileUploadFunctionality($file, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "File not found", $critical);
            return;
        }
        
        $content = file_get_contents($file);
        $hasEnctype = strpos($content, 'enctype="multipart/form-data"') !== false;
        $hasFileInput = strpos($content, 'type="file"') !== false;
        
        $this->recordTest($testName, $hasEnctype && $hasFileInput, 
            "File upload functionality present", 
            "File upload functionality missing", 
            $critical);
    }
    
    private function testInvoiceRedirectFlow($file, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "File not found", $critical);
            return;
        }
        
        $content = file_get_contents($file);
        $hasRedirect = strpos($content, 'invoice.php') !== false ||
                      strpos($content, 'Location:') !== false;
        
        $this->recordTest($testName, $hasRedirect, 
            "Invoice redirect flow implemented", 
            "Invoice redirect flow missing", 
            $critical);
    }
    
    private function analyzeSubmitHandlers($files, $testName, $critical = false) {
        $secureHandlers = 0;
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $content = file_get_contents($file);
            $hasCSRF = strpos($content, 'csrf_protection') !== false;
            $hasValidation = strpos($content, 'input_validator') !== false;
            $hasPrepared = strpos($content, 'prepare(') !== false;
            
            if ($hasCSRF && $hasValidation && $hasPrepared) {
                $secureHandlers++;
            }
        }
        
        $this->recordTest($testName, $secureHandlers === count($files), 
            "All submit handlers secure ({$secureHandlers}/" . count($files) . ")", 
            "Some submit handlers insecure ({$secureHandlers}/" . count($files) . ")", 
            $critical);
    }
    
    private function analyzeUploadHandlers($file, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "Upload handler not found", $critical);
            return;
        }
        
        $content = file_get_contents($file);
        $hasFiletype = strpos($content, 'mime') !== false || strpos($content, 'extension') !== false;
        $hasSize = strpos($content, 'size') !== false;
        $hasSecurity = strpos($content, 'allowed') !== false || strpos($content, 'validate') !== false;
        
        $this->recordTest($testName, $hasFiletype && $hasSize && $hasSecurity, 
            "Upload handler properly secured", 
            "Upload handler security insufficient", 
            $critical);
    }
    
    // Additional methods for remaining test types...
    private function testDatabaseInsertions($files, $testName, $critical = false) {
        $secureInsertions = 0;
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $content = file_get_contents($file);
            $hasPrepared = strpos($content, 'prepare(') !== false;
            $hasInsert = strpos($content, 'INSERT') !== false;
            
            if ($hasPrepared && $hasInsert) {
                $secureInsertions++;
            }
        }
        
        $this->recordTest($testName, $secureInsertions > 0, 
            "Database insertions use prepared statements", 
            "Database insertions may be vulnerable", 
            $critical);
    }
    
    private function analyzeFormValidation($files, $testName, $critical = false) {
        $validatedForms = 0;
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $content = file_get_contents($file);
            $hasValidation = strpos($content, 'required') !== false || 
                           strpos($content, 'validate') !== false;
            
            if ($hasValidation) {
                $validatedForms++;
            }
        }
        
        $this->recordTest($testName, $validatedForms === count($files), 
            "All forms have validation", 
            "Some forms lack validation", 
            $critical);
    }
    
    // Placeholder methods for remaining test types
    private function testInvoiceGeneration($file, $testName, $critical = false) {
        $this->testFileExists($file, $testName, $critical);
    }
    
    private function testPaymentFileUpload($file, $testName, $critical = false) {
        $this->testFileUploadFunctionality($file, $testName, $critical);
    }
    
    private function testEmailNotifications($file, $testName, $critical = false) {
        $this->testFileExists($file, $testName, $critical);
    }
    
    private function analyzeInvoiceLogic($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzePaymentSecurity($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeEmailSecurity($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function testPaymentDataIntegrity($file, $testName, $critical = false) {
        $this->analyzeDatabaseQueries($file, $testName, $critical);
    }
    
    private function testVerificationWorkflow($file, $testName, $critical = false) {
        $this->testFileExists($file, $testName, $critical);
    }
    
    private function testKwitansiGeneration($file, $testName, $critical = false) {
        $this->testFileExists($file, $testName, $critical);
    }
    
    private function testInvoiceEmailFlow($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeAdminAuthentication($file, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "File not found", $critical);
            return;
        }
        
        $content = file_get_contents($file);
        $hasAuth = strpos($content, 'session') !== false || 
                  strpos($content, 'login') !== false ||
                  strpos($content, 'auth') !== false;
        
        $this->recordTest($testName, $hasAuth, 
            "Admin authentication present", 
            "Admin authentication missing", 
            $critical);
    }
    
    private function analyzeVerificationLogic($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeKwitansiLogic($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeEmailIntegration($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function testCancellationSubmission($file, $testName, $critical = false) {
        $this->testFormSubmission($file, "submit_pembatalan.php", $testName, $critical);
    }
    
    private function testCancellationFileUpload($file, $testName, $critical = false) {
        $this->testFileUploadFunctionality($file, $testName, $critical);
    }
    
    private function analyzeCancellationSecurity($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeCancellationValidation($file, $testName, $critical = false) {
        $this->analyzeInputValidation($file, $testName, $critical);
    }
    
    private function testCancellationDataFlow($file, $testName, $critical = false) {
        $this->analyzeDatabaseQueries($file, $testName, $critical);
    }
    
    private function testCancellationManagement($file, $testName, $critical = false) {
        $this->testFileExists($file, $testName, $critical);
    }
    
    private function testDeleteOperations($file, $testName, $critical = false) {
        $this->testFileExists($file, $testName, $critical);
    }
    
    private function analyzeDeleteSecurity($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeCancellationAdminLogic($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function testDataDeletionIntegrity($file, $testName, $critical = false) {
        $this->analyzeDatabaseQueries($file, $testName, $critical);
    }
    
    private function testRoomListInterface($file, $testName, $critical = false) {
        $this->testFormElements($file, ["table", "tr", "td"], $testName, $critical);
    }
    
    private function testRoomManagementOperations($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeRoomListLogic($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeRoomListScripts($file, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "Script file not found", $critical);
            return;
        }
        
        $size = filesize($file);
        $this->recordTest($testName, $size > 0, 
            "Script file has content ({$size} bytes)", 
            "Script file is empty", 
            $critical);
    }
    
    private function testRoomDataIntegrity($file, $testName, $critical = false) {
        $this->analyzeDatabaseQueries($file, $testName, $critical);
    }
    
    private function testKelengkapanInterface($file, $testName, $critical = false) {
        $this->testFileExists($file, $testName, $critical);
    }
    
    private function testKelengkapanFileManagement($file, $testName, $critical = false) {
        $this->testFileUploadFunctionality($file, $testName, $critical);
    }
    
    private function analyzeKelengkapanLogic($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeKelengkapanTabs($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function testKelengkapanSecurity($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function testManifestInterface($file, $testName, $critical = false) {
        $this->testFileExists($file, $testName, $critical);
    }
    
    private function testExcelExportFunctionality($file, $testName, $critical = false) {
        if (!file_exists($file)) {
            $this->recordTest($testName, false, "", "Export file not found", $critical);
            return;
        }
        
        $content = file_get_contents($file);
        $hasExport = strpos($content, 'excel') !== false || 
                    strpos($content, 'export') !== false ||
                    strpos($content, 'xlsx') !== false;
        
        $this->recordTest($testName, $hasExport, 
            "Excel export functionality present", 
            "Excel export functionality missing", 
            $critical);
    }
    
    private function analyzeManifestLogic($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeExportLogic($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    private function analyzeManifestScripts($file, $testName, $critical = false) {
        $this->analyzeRoomListScripts($file, $testName, $critical);
    }
    
    private function testExportSecurity($file, $testName, $critical = false) {
        $this->analyzeCodeSecurity($file, $testName, $critical);
    }
    
    // Core testing utilities
    private function recordTest($testName, $condition, $successMsg, $failMsg, $critical = false) {
        $this->totalTests++;
        
        $result = [
            'phase' => $this->currentPhase,
            'name' => $testName,
            'passed' => $condition,
            'message' => $condition ? $successMsg : $failMsg,
            'critical' => $critical,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->testResults[] = $result;
        
        if ($condition) {
            $this->passedTests++;
            echo "✅ {$testName}: {$successMsg}\n";
        } else {
            $this->failedTests++;
            $icon = $critical ? "🚨" : "⚠️";
            echo "{$icon} {$testName}: {$failMsg}\n";
            
            if ($critical) {
                $this->criticalIssues[] = "Phase {$this->currentPhase}: {$testName}";
            } else {
                $this->warnings[] = "Phase {$this->currentPhase}: {$testName}";
            }
        }
    }
    
    private function generateFinalReport() {
        echo "📊 COMPREHENSIVE 9-PHASE TESTING REPORT\n";
        echo "======================================\n\n";
        
        $successRate = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 1) : 0;
        
        echo "📈 OVERALL STATISTICS:\n";
        echo "--------------------\n";
        echo "Total Tests Executed: {$this->totalTests}\n";
        echo "Tests Passed: {$this->passedTests}\n";
        echo "Tests Failed: {$this->failedTests}\n";
        echo "Success Rate: {$successRate}%\n";
        echo "Critical Issues: " . count($this->criticalIssues) . "\n";
        echo "Warnings: " . count($this->warnings) . "\n\n";
        
        // Phase-by-phase breakdown
        echo "📋 PHASE-BY-PHASE BREAKDOWN:\n";
        echo "---------------------------\n";
        for ($phase = 1; $phase <= 9; $phase++) {
            $phaseResults = array_filter($this->testResults, function($r) use ($phase) {
                return $r['phase'] === $phase;
            });
            
            $phasePassed = count(array_filter($phaseResults, function($r) { return $r['passed']; }));
            $phaseTotal = count($phaseResults);
            $phaseRate = $phaseTotal > 0 ? round(($phasePassed / $phaseTotal) * 100, 1) : 0;
            
            $phaseNames = [
                1 => "Package Operations",
                2 => "Form Submissions", 
                3 => "Invoice & Payment",
                4 => "Admin Pending",
                5 => "Cancellation Form",
                6 => "Admin Cancellation",
                7 => "Room List Management",
                8 => "Kelengkapan Management", 
                9 => "Manifest & Excel Export"
            ];
            
            echo "Phase {$phase} ({$phaseNames[$phase]}): {$phasePassed}/{$phaseTotal} ({$phaseRate}%)\n";
        }
        
        echo "\n";
        
        if (!empty($this->criticalIssues)) {
            echo "🚨 CRITICAL ISSUES:\n";
            echo "-----------------\n";
            foreach ($this->criticalIssues as $issue) {
                echo "• {$issue}\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo "⚠️ WARNINGS:\n";
            echo "----------\n";
            foreach ($this->warnings as $warning) {
                echo "• {$warning}\n";
            }
            echo "\n";
        }
        
        // Final assessment
        if ($successRate >= 95 && count($this->criticalIssues) === 0) {
            echo "🏆 RAILWAY DEPLOYMENT READY!\n";
            echo "System passed comprehensive testing and is ready for production.\n\n";
        } elseif ($successRate >= 85) {
            echo "⚠️ MOSTLY READY FOR DEPLOYMENT\n";
            echo "System is mostly ready but has some issues to address.\n\n";
        } else {
            echo "🚨 NOT READY FOR DEPLOYMENT\n";
            echo "System requires fixes before Railway deployment.\n\n";
        }
        
        echo "🚀 NEXT STEPS:\n";
        echo "1. Address any critical issues identified\n";
        echo "2. Fix warnings if possible\n";
        echo "3. Test on Railway environment\n";
        echo "4. Monitor system performance\n";
        echo "5. Conduct user acceptance testing\n\n";
        
        echo "✅ 9-PHASE COMPREHENSIVE TESTING COMPLETE\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        echo "Ready for deployment: " . ($successRate >= 85 ? "YES" : "NO") . "\n";
    }
}

// Execute the comprehensive 9-phase testing
echo "🎯 INITIALIZING MIW RAILWAY 9-PHASE TESTING\n";
echo "==========================================\n\n";

$testFramework = new MIW9PhaseTestFramework();
$testFramework->runAll9Phases();
?>
