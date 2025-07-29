<?php
/**
 * Deployment Health Check Script
 * This script verifies that the deployment is working correctly
 */

header('Content-Type: application/json');

$health_check = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '2.0.0',
    'environment' => 'unknown',
    'database' => 'disconnected',
    'php_version' => phpversion(),
    'checks' => []
];

try {
    // Load configuration
    require_once 'config.php';
    
    $health_check['environment'] = getCurrentEnvironment();
    
    // Check database connection
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query('SELECT 1 as test');
        if ($stmt && $stmt->fetchColumn() == 1) {
            $health_check['database'] = 'connected';
            $health_check['checks']['database'] = 'OK';
        } else {
            $health_check['database'] = 'error';
            $health_check['checks']['database'] = 'Failed to execute test query';
        }
    } else {
        $health_check['database'] = 'not_initialized';
        $health_check['checks']['database'] = 'PDO not initialized';
    }
    
    // Check upload directory
    $upload_dir = getUploadDirectory();
    if (is_dir($upload_dir) && is_writable($upload_dir)) {
        $health_check['checks']['upload_directory'] = 'OK';
    } else {
        $health_check['checks']['upload_directory'] = 'Not writable or missing';
    }
    
    // Check required extensions
    $required_extensions = ['pdo', 'gd', 'curl', 'mbstring'];
    if (getDatabaseType() === 'postgresql') {
        $required_extensions[] = 'pgsql';
    } else {
        $required_extensions[] = 'mysqli';
    }
    
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            $health_check['checks']['ext_' . $ext] = 'OK';
        } else {
            $health_check['checks']['ext_' . $ext] = 'Missing';
            $health_check['status'] = 'warning';
        }
    }
    
    // Check configuration constants
    $required_constants = ['SMTP_HOST', 'SMTP_PORT', 'MAX_FILE_SIZE'];
    foreach ($required_constants as $const) {
        if (defined($const)) {
            $health_check['checks']['config_' . strtolower($const)] = 'OK';
        } else {
            $health_check['checks']['config_' . strtolower($const)] = 'Missing';
            $health_check['status'] = 'warning';
        }
    }
    
    // Check table structure
    if ($health_check['database'] === 'connected') {
        $tables = ['data_paket', 'data_jamaah', 'data_invoice', 'data_pembatalan'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                $health_check['checks']['table_' . $table] = "OK ($count records)";
            } catch (PDOException $e) {
                $health_check['checks']['table_' . $table] = 'Missing or error';
                $health_check['status'] = 'warning';
            }
        }
    }
    
} catch (Exception $e) {
    $health_check['status'] = 'error';
    $health_check['error'] = $e->getMessage();
}

// Set appropriate HTTP status code
if ($health_check['status'] === 'error') {
    http_response_code(500);
} elseif ($health_check['status'] === 'warning') {
    http_response_code(200); // Still return 200 for warnings
}

echo json_encode($health_check, JSON_PRETTY_PRINT);
?>
