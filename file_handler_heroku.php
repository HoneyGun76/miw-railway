<?php
/**
 * Heroku File Handler
 * Handles file uploads and storage for Heroku's ephemeral filesystem
 */

class HerokuFileHandler {
    private $temp_dir;
    private $max_file_size;
    private $allowed_types;
    
    public function __construct() {
        $this->temp_dir = sys_get_temp_dir() . '/uploads';
        $this->max_file_size = 10 * 1024 * 1024; // 10MB
        $this->allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        
        // Ensure temp directory exists
        $this->ensureDirectoryExists();
    }
    
    private function ensureDirectoryExists() {
        if (!is_dir($this->temp_dir)) {
            mkdir($this->temp_dir, 0777, true);
        }
        
        // Create subdirectories
        $subdirs = ['documents', 'payments', 'cancellations', 'photos'];
        foreach ($subdirs as $subdir) {
            $path = $this->temp_dir . '/' . $subdir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }
    
    public function uploadFile($file, $type = 'documents', $prefix = '') {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $this->getUploadErrorMessage($file['error']));
        }
        
        // Validate file size
        if ($file['size'] > $this->max_file_size) {
            throw new Exception('File size exceeds maximum allowed size');
        }
        
        // Validate file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowed_types)) {
            throw new Exception('File type not allowed');
        }
        
        // Generate unique filename
        $timestamp = date('YmdHis');
        $filename = $prefix . '_' . $timestamp . '.' . $extension;
        $destination = $this->temp_dir . '/' . $type . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Return relative path for database storage
        return '/tmp/uploads/' . $type . '/' . $filename;
    }
    
    public function getFile($path) {
        $full_path = sys_get_temp_dir() . $path;
        
        if (!file_exists($full_path)) {
            throw new Exception('File not found');
        }
        
        return $full_path;
    }
    
    public function deleteFile($path) {
        $full_path = sys_get_temp_dir() . $path;
        
        if (file_exists($full_path)) {
            return unlink($full_path);
        }
        
        return true; // File doesn't exist, consider it deleted
    }
    
    public function serveFile($path, $filename = null) {
        try {
            $full_path = $this->getFile($path);
            
            if (!$filename) {
                $filename = basename($path);
            }
            
            $mime_type = mime_content_type($full_path);
            $file_size = filesize($full_path);
            
            header('Content-Type: ' . $mime_type);
            header('Content-Length: ' . $file_size);
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=3600');
            
            readfile($full_path);
            exit;
            
        } catch (Exception $e) {
            http_response_code(404);
            echo 'File not found';
            exit;
        }
    }
    
    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
    
    public function cleanupOldFiles($max_age_hours = 24) {
        $cutoff_time = time() - ($max_age_hours * 3600);
        
        $directories = [
            $this->temp_dir . '/documents',
            $this->temp_dir . '/payments',
            $this->temp_dir . '/photos'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) continue;
            
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoff_time) {
                    unlink($file);
                }
            }
        }
    }
    
    public function getTempDirectory() {
        return $this->temp_dir;
    }
}

// Global instance for backward compatibility
$heroku_file_handler = new HerokuFileHandler();

// Helper functions for backward compatibility
function upload_file_heroku($file, $type = 'documents', $prefix = '') {
    global $heroku_file_handler;
    return $heroku_file_handler->uploadFile($file, $type, $prefix);
}

function serve_file_heroku($path, $filename = null) {
    global $heroku_file_handler;
    return $heroku_file_handler->serveFile($path, $filename);
}

function delete_file_heroku($path) {
    global $heroku_file_handler;
    return $heroku_file_handler->deleteFile($path);
}
?>