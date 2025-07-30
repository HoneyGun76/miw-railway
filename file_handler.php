<?php
// Railway-compatible file handler with enhanced error handling
require_once 'config.php';

// Validate request parameters
if (!isset($_GET['file']) || !isset($_GET['type'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('Missing required parameters: file and type');
}

$filename = urldecode($_GET['file']);
$type = $_GET['type'];
$action = isset($_GET['action']) ? $_GET['action'] : 'preview';

// Validate file type
$validTypes = ['documents', 'payments', 'cancellations'];
if (!in_array($type, $validTypes)) {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid file type');
}

// Use standard file serving for Railway deployment
if (!file_exists($filename)) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

// Serve the file
if ($action === 'download') {
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
} else {
    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
}

header('Content-Type: ' . mime_content_type($filename));
header('Content-Length: ' . filesize($filename));
readfile($filename);
?>
