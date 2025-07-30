<?php
/**
 * Railway File Storage Manager
 * Optimized for Railway.com's persistent storage
 * 
 * Railway provides persistent storage, which is better than Heroku's ephemeral filesystem
 * This manager handles file uploads with proper Railway integration
 */

require_once 'config.php';

class RailwayFileManager {
    private $uploadBaseDir;
    private $isRailway;
    private $maxFileSize;
    
    public function __construct() {
        $this->isRailway = $this->detectRailwayEnvironment();
        $this->uploadBaseDir = $this->isRailway ? '/app/uploads' : __DIR__ . '/uploads';
        $this->maxFileSize = $this->parseFileSize(MAX_FILE_SIZE ?? '10M');
        
        // Ensure upload directories exist
        $this->ensureDirectoriesExist();
    }
    
    /**
     * Detect if running on Railway
     */
    private function detectRailwayEnvironment() {
        return !empty($_ENV['RAILWAY_ENVIRONMENT']) || 
               !empty($_ENV['RAILWAY_PROJECT_ID']) || 
               !empty(getenv('RAILWAY_ENVIRONMENT')) ||
               !empty(getenv('RAILWAY_PROJECT_ID'));
    }
    
    /**
     * Ensure all necessary directories exist
     */
    private function ensureDirectoriesExist() {
        $directories = [
            $this->uploadBaseDir,
            $this->uploadBaseDir . '/documents',
            $this->uploadBaseDir . '/payments',
            $this->uploadBaseDir . '/cancellations',
            $this->uploadBaseDir . '/photos'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    error_log("Failed to create directory: " . $dir);
                }
            }
        }
    }
    
    /**
     * Handle file upload with Railway optimization
     */
    public function handleUpload($file, $targetDir, $customName = null) {
        try {
            // Validate file
            if (!$this->validateFile($file)) {
                throw new Exception('File validation failed');
            }
            
            // Prepare upload path
            $targetPath = $this->uploadBaseDir . '/' . trim($targetDir, '/');
            
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
            
            // Generate filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = ($customName ?: $this->generateUniqueFilename()) . '.' . $extension;
            $fullPath = $targetPath . '/' . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new Exception('Failed to move uploaded file');
            }
            
            // Store metadata for tracking
            $this->storeFileMetadata($filename, $targetDir, $file);
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $fullPath,
                'url' => $this->generateFileUrl($filename, $targetDir),
                'size' => $file['size'],
                'type' => $file['type']
            ];
            
        } catch (Exception $e) {
            error_log("Railway Upload Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $this->getUploadErrorMessage($file['error']));
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum allowed size');
        }
        
        // Check file type
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('File type not allowed');
        }
        
        return true;
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename() {
        return date('Y-m-d_His') . '_' . uniqid();
    }
    
    /**
     * Generate file URL for serving
     */
    private function generateFileUrl($filename, $directory) {
        $baseUrl = $this->isRailway ? 
            'https://' . ($_ENV['RAILWAY_STATIC_URL'] ?? $_SERVER['HTTP_HOST']) : 
            'http://' . $_SERVER['HTTP_HOST'];
        
        return $baseUrl . '/serve_file.php?file=' . urlencode($filename) . '&type=' . urlencode($directory);
    }
    
    /**
     * Store file metadata in database
     */
    private function storeFileMetadata($filename, $directory, $fileInfo) {
        try {
            global $conn;
            
            // Create table if it doesn't exist
            $this->ensureFileMetadataTable();
            
            $stmt = $conn->prepare("
                INSERT INTO file_metadata (filename, directory, original_name, file_size, mime_type, upload_time, is_railway, storage_path)
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
                ON DUPLICATE KEY UPDATE
                    original_name = VALUES(original_name),
                    file_size = VALUES(file_size),
                    mime_type = VALUES(mime_type),
                    upload_time = VALUES(upload_time),
                    is_railway = VALUES(is_railway),
                    storage_path = VALUES(storage_path)
            ");
            
            $stmt->execute([
                $filename,
                $directory,
                $fileInfo['name'],
                $fileInfo['size'],
                $fileInfo['type'],
                $this->isRailway,
                $this->uploadBaseDir . '/' . trim($directory, '/') . '/' . $filename
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to store file metadata: " . $e->getMessage());
        }
    }
    
    /**
     * Ensure file metadata table exists
     */
    private function ensureFileMetadataTable() {
        global $conn;
        
        try {
            $conn->exec("
                CREATE TABLE IF NOT EXISTS file_metadata (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    directory VARCHAR(50) NOT NULL,
                    original_name VARCHAR(255),
                    file_size INT,
                    mime_type VARCHAR(100),
                    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_railway BOOLEAN DEFAULT FALSE,
                    storage_path TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_file (filename, directory)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Exception $e) {
            error_log("Failed to create file_metadata table: " . $e->getMessage());
        }
    }
    
    /**
     * Serve file with proper headers
     */
    public function serveFile($filename, $directory, $action = 'preview') {
        $filePath = $this->uploadBaseDir . '/' . trim($directory, '/') . '/' . basename($filename);
        
        if (!file_exists($filePath)) {
            // Try to find file in database metadata
            $fileData = $this->getFileFromMetadata($filename, $directory);
            if (!$fileData) {
                http_response_code(404);
                die('File not found');
            }
            
            if (isset($fileData['storage_path']) && file_exists($fileData['storage_path'])) {
                $filePath = $fileData['storage_path'];
            } else {
                http_response_code(404);
                die('File not found in storage');
            }
        }
        
        // Get file info
        $fileSize = filesize($filePath);
        $mimeType = $this->getMimeType($filePath);
        
        // Set headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        
        if ($action === 'download') {
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        } else {
            header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        }
        
        header('Cache-Control: public, max-age=3600');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT');
        
        // Output file
        readfile($filePath);
        exit;
    }
    
    /**
     * Get file metadata from database
     */
    private function getFileFromMetadata($filename, $directory) {
        try {
            global $conn;
            
            $stmt = $conn->prepare("
                SELECT * FROM file_metadata 
                WHERE filename = ? AND directory = ?
                ORDER BY upload_time DESC
                LIMIT 1
            ");
            $stmt->execute([$filename, $directory]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get MIME type of file
     */
    private function getMimeType($filepath) {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filepath);
            finfo_close($finfo);
            return $mimeType;
        }
        
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    /**
     * Parse file size string to bytes
     */
    private function parseFileSize($size) {
        $size = trim($size);
        $last = strtolower($size[strlen($size)-1]);
        $num = (int) $size;
        
        switch($last) {
            case 'g': $num *= 1024;
            case 'm': $num *= 1024;
            case 'k': $num *= 1024;
        }
        
        return $num;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error) {
        switch($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Get Railway environment info
     */
    public function getRailwayInfo() {
        return [
            'is_railway' => $this->isRailway,
            'project_id' => $_ENV['RAILWAY_PROJECT_ID'] ?? getenv('RAILWAY_PROJECT_ID') ?? 'Not set',
            'environment' => $_ENV['RAILWAY_ENVIRONMENT'] ?? getenv('RAILWAY_ENVIRONMENT') ?? 'Not set',
            'upload_dir' => $this->uploadBaseDir,
            'persistent_storage' => true, // Railway has persistent storage
            'message' => $this->isRailway ? 
                'Running on Railway with persistent storage - files will not be lost!' : 
                'Running locally'
        ];
    }
    
    /**
     * List files in directory
     */
    public function listFiles($directory) {
        $dirPath = $this->uploadBaseDir . '/' . trim($directory, '/');
        $files = [];
        
        if (is_dir($dirPath)) {
            $items = scandir($dirPath);
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_file($dirPath . '/' . $item)) {
                    $files[] = [
                        'filename' => $item,
                        'size' => filesize($dirPath . '/' . $item),
                        'modified' => filemtime($dirPath . '/' . $item),
                        'url' => $this->generateFileUrl($item, $directory)
                    ];
                }
            }
        }
        
        return $files;
    }
}

// Usage example and auto-initialization for Railway
if ($GLOBALS['config'] ?? false) {
    $railwayFileManager = new RailwayFileManager();
}

?>
