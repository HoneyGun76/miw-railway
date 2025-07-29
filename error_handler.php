<?php
/**
 * MIW Error Handler
 * Include this file in config.php to automatically log application errors
 */

// Set custom error handler
set_error_handler('miwErrorHandler');
set_exception_handler('miwExceptionHandler');
register_shutdown_function('miwShutdownHandler');

function miwErrorHandler($errno, $errstr, $errfile, $errline) {
    // Don't log errors if error reporting is turned off
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    // Add E_STRICT only if it's defined (for PHP < 8.4 compatibility)
    if (defined('E_STRICT')) {
        $errorTypes[E_STRICT] = 'Strict Notice';
    }
    
    $errorType = $errorTypes[$errno] ?? 'Unknown Error';
    
    // Log to our custom error logger
    logToDatabase($errorType, $errstr, $errfile, $errline);
    
    // Don't execute PHP internal error handler
    return true;
}

function miwExceptionHandler($exception) {
    $message = sprintf(
        "Uncaught %s: %s in %s:%d\nStack trace:\n%s",
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    logToDatabase('Exception', $message, $exception->getFile(), $exception->getLine());
}

function miwShutdownHandler() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $errorTypes = [
            E_ERROR => 'Fatal Error',
            E_PARSE => 'Parse Error',
            E_CORE_ERROR => 'Core Error',
            E_COMPILE_ERROR => 'Compile Error'
        ];
        
        $errorType = $errorTypes[$error['type']] ?? 'Fatal Error';
        logToDatabase($errorType, $error['message'], $error['file'], $error['line']);
    }
}

function logToDatabase($type, $message, $file, $line) {
    try {
        // Use global connection or create new one
        global $conn;
        
        if (!$conn) {
            // Create database connection for error logging
            $host = $_ENV['DATABASE_URL'] ?? 'localhost';
            if (strpos($host, 'postgres://') === 0) {
                // Parse Heroku DATABASE_URL
                $url = parse_url($host);
                $dsn = sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s",
                    $url['host'],
                    $url['port'] ?? 5432,
                    ltrim($url['path'], '/')
                );
                $conn = new PDO($dsn, $url['user'], $url['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
            }
        }
        
        if ($conn) {
            // Create table if it doesn't exist
            $conn->exec("
                CREATE TABLE IF NOT EXISTS error_logs (
                    id SERIAL PRIMARY KEY,
                    error_type VARCHAR(50),
                    error_message TEXT,
                    file_path VARCHAR(255),
                    line_number INTEGER,
                    user_agent TEXT,
                    ip_address VARCHAR(45),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            $stmt = $conn->prepare("
                INSERT INTO error_logs (error_type, error_message, file_path, line_number, user_agent, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $type,
                $message,
                $file,
                $line,
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        }
    } catch (Exception $e) {
        // Fallback to error_log if database logging fails
        error_log("MIW Error Handler: Failed to log to database - " . $e->getMessage());
        error_log("Original error: [$type] $message in $file:$line");
    }
}

// Function to manually log custom errors
function logError($type, $message, $context = []) {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $caller = $backtrace[0] ?? [];
    
    $file = $caller['file'] ?? 'unknown';
    $line = $caller['line'] ?? 0;
    
    if (!empty($context)) {
        $message .= ' | Context: ' . json_encode($context);
    }
    
    logToDatabase($type, $message, $file, $line);
}
?>
