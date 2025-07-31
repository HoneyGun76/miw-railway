<?php
/**
 * MIW Travel System - Central Configuration
 * 
 * This is the ONLY configuration file for database connections.
 * Simple, clean, and handles both Railway production and local development.
 * 
 * @version 1.0.0
 */

// Environment detection  
$isRailway = !empty($_ENV['RAILWAY_ENVIRONMENT']) || !empty($_ENV['RAILWAY_PROJECT_ID']);
$isLocal = !$isRailway;

// Database configuration
$host = $isRailway 
    ? ($_ENV['MYSQLHOST'] ?? 'mysql.railway.internal')
    : 'localhost';
    
$port = $isRailway 
    ? ($_ENV['MYSQLPORT'] ?? 3306)
    : 3306;
    
$dbname = $isRailway 
    ? ($_ENV['MYSQLDATABASE'] ?? 'railway') 
    : 'miw_db';
    
$username = $isRailway 
    ? ($_ENV['MYSQLUSER'] ?? 'root')
    : 'root';
    
$password = $isRailway 
    ? ($_ENV['MYSQLPASSWORD'] ?? '')
    : '';

// Create database connection
$conn = null;
$pdo = null;

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    $conn = $pdo; // Legacy compatibility
    
    // Set timezone
    $pdo->exec("SET time_zone = '+07:00'");
    
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    $conn = null;
    $pdo = null;
}

// Basic constants
define('UPLOAD_PATH', $isRailway ? '/app/uploads/' : __DIR__ . '/uploads/');
define('IS_RAILWAY', $isRailway);
define('IS_LOCAL', $isLocal);

// Create upload directories if they don't exist  
$uploadDirs = [
    UPLOAD_PATH . 'documents',
    UPLOAD_PATH . 'payments', 
    UPLOAD_PATH . 'cancellations'
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Session handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Simple CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Simple input sanitization
function sanitizeInput($input) {
    if (is_string($input)) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

// Error logging function
function logError($message, $context = []) {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message;
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context);
    }
    error_log($logMessage);
}

?>
