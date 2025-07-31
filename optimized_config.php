<?php
/**
 * Optimized Configuration for Production
 */

// Load base configuration
require_once 'safe_config.php';

// Performance optimizations
if (function_exists('opcache_compile_file')) {
    // Enable OPcache optimizations
    ini_set('opcache.enable', 1);
    ini_set('opcache.memory_consumption', 128);
    ini_set('opcache.max_accelerated_files', 4000);
}

// Session optimization
ini_set('session.cache_limiter', 'nocache');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);

// Error handling for production
if (!defined('DEVELOPMENT_MODE')) {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'error.log');
}

// Security headers
function setSecurityHeaders() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net fonts.googleapis.com; font-src \'self\' fonts.gstatic.com; img-src \'self\' data:;');
    }
}

// Apply security headers
setSecurityHeaders();
?>