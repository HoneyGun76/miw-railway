<?php
/**
 * Railway.com Configuration for MIW Travel System
 * 
 * This configuration is optimized for Railway deployment with:
 * - MySQL/PostgreSQL database support
 * - Railway-specific environment variables
 * - File upload handling for Railway's persistent storage
 * - Email configuration
 * 
 * @version 1.0.0
 * @platform Railway.com
 */

// Performance and error settings
set_time_limit(30);
ini_set('max_execution_time', 30);
error_reporting(E_ALL);
ini_set('display_errors', 0); // Production setting
ini_set('log_errors', 1);

// Railway Configuration
$config = [
    'database' => [
        // Railway MySQL service configuration
        'host' => $_ENV['DB_HOST'] ?? $_ENV['MYSQLHOST'] ?? 'mysql.railway.internal',
        'port' => $_ENV['DB_PORT'] ?? $_ENV['MYSQLPORT'] ?? 3306,
        'dbname' => $_ENV['DB_NAME'] ?? $_ENV['MYSQLDATABASE'] ?? 'railway',
        'username' => $_ENV['DB_USER'] ?? $_ENV['MYSQLUSER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? $_ENV['MYSQLPASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'driver' => 'mysql' // Force MySQL for Railway
    ],
    'email' => [
        'smtp_host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
        'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
        'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
        'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
        'smtp_encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
    ],
    'upload' => [
        'max_file_size' => $_ENV['MAX_FILE_SIZE'] ?? '10M',
        'upload_path' => $_ENV['UPLOAD_PATH'] ?? '/app/uploads/', // Railway persistent storage
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']
    ],
    'app' => [
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'max_execution_time' => $_ENV['MAX_EXECUTION_TIME'] ?? 300,
        'secure_headers' => $_ENV['SECURE_HEADERS'] ?? 'true',
        'port' => $_ENV['PORT'] ?? 3000, // Railway assigns PORT
        'railway_project_id' => $_ENV['RAILWAY_PROJECT_ID'] ?? '',
        'railway_environment' => $_ENV['RAILWAY_ENVIRONMENT'] ?? 'production'
    ]
];

// Create database connection
try {
    // Force MySQL connection for Railway
    $dsn = "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false
    ];
    
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $options);
    $conn = $pdo; // Alias for compatibility
    
    // Set timezone for MySQL
    $pdo->exec("SET time_zone = '+07:00'"); // Asia/Jakarta
    
} catch (PDOException $e) {
    error_log("Railway MySQL Connection Error: " . $e->getMessage());
    
    // For Railway, we might want to fail fast or provide fallback
    if ($config['app']['environment'] === 'production') {
        die('MySQL connection failed. Please check Railway MySQL service configuration.');
    } else {
        throw new Exception("MySQL connection failed: " . $e->getMessage());
    }
}

// Email settings
define('SMTP_HOST', $config['email']['smtp_host']);
define('SMTP_PORT', $config['email']['smtp_port']);
define('SMTP_USERNAME', $config['email']['smtp_username']);
define('SMTP_PASSWORD', $config['email']['smtp_password']);
define('SMTP_ENCRYPTION', $config['email']['smtp_encryption']);

define('EMAIL_FROM', $config['email']['smtp_username']);
define('EMAIL_FROM_NAME', 'MIW Travel');
define('EMAIL_SUBJECT', 'Pendaftaran Umroh/Haji Anda');
define('ADMIN_EMAIL', $config['email']['smtp_username']);
define('EMAIL_ENABLED', true);

// File upload settings
define('MAX_FILE_SIZE', $config['upload']['max_file_size']);
define('MAX_EXECUTION_TIME', $config['app']['max_execution_time']);
define('UPLOAD_PATH', $config['upload']['upload_path']);

// Apply execution time settings
ini_set('max_execution_time', MAX_EXECUTION_TIME);
date_default_timezone_set('Asia/Jakarta');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers for production
if ($config['app']['secure_headers'] === 'true') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Railway-specific environment detection
function isRailwayEnvironment() {
    return !empty($_ENV['RAILWAY_ENVIRONMENT']) || 
           !empty($_ENV['RAILWAY_PROJECT_ID']) || 
           !empty(getenv('RAILWAY_ENVIRONMENT'));
}

// Create upload directories if they don't exist (Railway persistent storage)
$uploadDirs = [
    rtrim(UPLOAD_PATH, '/') . '/documents',
    rtrim(UPLOAD_PATH, '/') . '/payments',
    rtrim(UPLOAD_PATH, '/') . '/cancellations',
    rtrim(UPLOAD_PATH, '/') . '/photos'
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

?>
