<?php
/**
 * MIW Travel System - Smart Configuration
 * 
 * This configuration automatically detects the environment:
 * - Railway.com (production)
 * - Local development (XAMPP)
 * 
 * @version 1.0.0
 * @author MIW Development Team
 */

// Detect environment
$isRailway = isset($_ENV['RAILWAY_ENVIRONMENT']) || getenv('RAILWAY_ENVIRONMENT');
$isLocal = !$isRailway;

if ($isLocal) {
    // Load local development configuration
    require_once __DIR__ . '/config.local.php';
} else {
    // Load Railway production configuration
    require_once __DIR__ . '/config.railway.php';
}

// Environment-specific debugging
if ($isLocal && isset($_GET['debug'])) {
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
    echo "<h3>🔧 MIW Configuration Debug</h3>";
    echo "<p><strong>Environment:</strong> " . ($isRailway ? 'Railway Production' : 'Local Development') . "</p>";
    echo "<p><strong>Config File:</strong> " . ($isRailway ? 'config.railway.php' : 'config.local.php') . "</p>";
    echo "<p><strong>Database Host:</strong> " . (defined('DB_HOST') ? DB_HOST : 'Not defined') . "</p>";
    echo "<p><strong>Upload Path:</strong> " . (defined('UPLOAD_PATH') ? UPLOAD_PATH : 'Not defined') . "</p>";
    echo "<p><strong>SMTP Host:</strong> " . (defined('SMTP_HOST') ? SMTP_HOST : 'Not defined') . "</p>";
    echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
    echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s T') . "</p>";
    echo "</div>";
}

?>
        'railway_project_id' => $_ENV['RAILWAY_PROJECT_ID'] ?? '',
        'railway_environment' => $_ENV['RAILWAY_ENVIRONMENT'] ?? 'production'
    ]
];

// Create database connection
try {
    $driver = $config['database']['driver'];
    
    if ($driver === 'mysql') {
        // MySQL connection for Railway
        $dsn = "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    } else {
        // PostgreSQL connection
        $dsn = "pgsql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']}";
    }
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false
    ];
    
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $options);
    $conn = $pdo; // Alias for compatibility
    
    // Set timezone
    if ($driver === 'mysql') {
        $pdo->exec("SET time_zone = '+07:00'"); // Asia/Jakarta
    } else {
        $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
    }
    
} catch (PDOException $e) {
    error_log("Railway Database Connection Error: " . $e->getMessage());
    
    // For Railway, we might want to fail fast or provide fallback
    if ($config['app']['environment'] === 'production') {
        die('Database connection failed. Please check Railway service configuration.');
    } else {
        throw new Exception("Database connection failed: " . $e->getMessage());
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
