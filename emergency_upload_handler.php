<?php
/**
 * EMERGENCY UPLOAD HANDLER FOR HEROKU
 * This handler creates directories on-the-fly and provides fallback options
 */

class EmergencyHerokuUploadHandler {
    private $baseDir;
    private $isHeroku;
    
    public function __construct() {
        $this->isHeroku = !empty($_ENV["DYNO"]) || !empty(getenv("DYNO"));
        $this->baseDir = $this->isHeroku ? "/tmp/uploads" : __DIR__ . "/uploads";
        $this->ensureAllDirectories();
    }
    
    private function ensureAllDirectories() {
        $dirs = [
            $this->baseDir,
            $this->baseDir . "/documents",
            $this->baseDir . "/payments", 
            $this->baseDir . "/cancellations",
            $this->baseDir . "/photos"
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                file_put_contents($dir . "/.htaccess", "Order deny,allow\nDeny from all\n");
                file_put_contents($dir . "/index.php", "<?php exit(\"Access denied\"); ?>");
            }
        }
    }
    
    public function handleUpload($file, $targetDir, $customName) {
        // Ensure target directory exists
        $targetPath = $this->baseDir . "/" . trim($targetDir, "/");
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }
        
        // Validate file
        if (!$file || $file["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed");
        }
        
        // Generate filename
        $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $filename = $customName . "." . $extension;
        $fullPath = $targetPath . "/" . $filename;
        
        // Move file
        if (!move_uploaded_file($file["tmp_name"], $fullPath)) {
            throw new Exception("Failed to move uploaded file to: " . $fullPath);
        }
        
        return [
            "success" => true,
            "path" => "/uploads/" . $targetDir . "/" . $filename,
            "filename" => $filename,
            "size" => $file["size"],
            "type" => $file["type"]
        ];
    }
}
?>