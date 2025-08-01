<?php
// Railway health check endpoint
http_response_code(200);
echo json_encode([
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'memory_usage' => memory_get_usage(true),
    'uptime' => time()
]);
?>
