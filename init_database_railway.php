<?php
/**
 * Railway Database Initialization Script
 * 
 * This script initializes the database for Railway deployment
 * Supports both MySQL and PostgreSQL based on Railway configuration
 */

require_once 'config.php';

class RailwayDatabaseInit {
    private $conn;
    private $dbType;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        
        // Detect database type
        $this->dbType = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
    
    public function initialize() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Railway Database Initialization - MIW Travel</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
                .container { max-width: 800px; margin: 0 auto; }
                .section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .success { color: #27ae60; font-weight: bold; }
                .error { color: #e74c3c; font-weight: bold; }
                .warning { color: #f39c12; font-weight: bold; }
                .info { color: #3498db; font-weight: bold; }
                .btn { padding: 10px 15px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
                .btn-primary { background-color: #3498db; color: white; }
                .code-block { background-color: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: monospace; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="section">
                    <h1>🚀 Railway Database Initialization</h1>
                    <p>Initialize your MIW Travel database on Railway</p>
                    <div class="info">Database Type: <?= strtoupper($this->dbType) ?></div>
                </div>
                
                <?php
                if (isset($_POST['initialize'])) {
                    $this->runInitialization();
                } else {
                    $this->showInitializationForm();
                }
                ?>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function showInitializationForm() {
        echo '<div class="section">';
        echo '<h2>📋 Database Initialization</h2>';
        echo '<p>This will create all necessary tables for the MIW Travel system.</p>';
        
        echo '<div class="warning">⚠️ Warning: This will create/modify database tables. Make sure you have backups if needed.</div>';
        
        echo '<form method="post">';
        echo '<button type="submit" name="initialize" class="btn btn-primary">Initialize Database</button>';
        echo '</form>';
        echo '</div>';
    }
    
    private function runInitialization() {
        echo '<div class="section">';
        echo '<h2>🔄 Initializing Database...</h2>';
        
        try {
            // Create registrations table
            $this->createRegistrationsTable();
            
            // Create file metadata table
            $this->createFileMetadataTable();
            
            // Create admin users table
            $this->createAdminUsersTable();
            
            // Insert sample data if needed
            $this->insertSampleData();
            
            echo '<div class="success">✅ Database initialization completed successfully!</div>';
            echo '<div class="info">Your MIW Travel system is now ready to use on Railway.</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Initialization failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        echo '</div>';
    }
    
    private function createRegistrationsTable() {
        echo '<p>Creating registrations table...</p>';
        
        if ($this->dbType === 'mysql') {
            $sql = "
                CREATE TABLE IF NOT EXISTS registrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    jenis_daftar ENUM('umroh', 'haji') NOT NULL,
                    nama_lengkap VARCHAR(255) NOT NULL,
                    tempat_lahir VARCHAR(100) NOT NULL,
                    tanggal_lahir DATE NOT NULL,
                    jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
                    status_perkawinan ENUM('Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati') NOT NULL,
                    pekerjaan VARCHAR(100),
                    alamat TEXT NOT NULL,
                    kecamatan VARCHAR(100) NOT NULL,
                    kabupaten VARCHAR(100) NOT NULL,
                    provinsi VARCHAR(100) NOT NULL,
                    kode_pos VARCHAR(10),
                    no_hp VARCHAR(20) NOT NULL,
                    email VARCHAR(255),
                    pendidikan VARCHAR(100),
                    nama_ayah VARCHAR(255),
                    nama_ibu VARCHAR(255),
                    
                    -- Dokumen files
                    ktp_file VARCHAR(255),
                    kk_file VARCHAR(255),
                    pas_foto_file VARCHAR(255),
                    akta_lahir_file VARCHAR(255),
                    ijazah_file VARCHAR(255),
                    
                    -- Pembayaran
                    paket_dipilih VARCHAR(255),
                    nominal_pembayaran DECIMAL(15,2),
                    bukti_pembayaran_file VARCHAR(255),
                    
                    -- Metadata
                    tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status_verifikasi ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
                    catatan TEXT,
                    
                    INDEX idx_jenis_daftar (jenis_daftar),
                    INDEX idx_tanggal_daftar (tanggal_daftar),
                    INDEX idx_status_verifikasi (status_verifikasi)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
        } else {
            // PostgreSQL
            $sql = "
                CREATE TABLE IF NOT EXISTS registrations (
                    id SERIAL PRIMARY KEY,
                    jenis_daftar VARCHAR(10) NOT NULL CHECK (jenis_daftar IN ('umroh', 'haji')),
                    nama_lengkap VARCHAR(255) NOT NULL,
                    tempat_lahir VARCHAR(100) NOT NULL,
                    tanggal_lahir DATE NOT NULL,
                    jenis_kelamin VARCHAR(20) NOT NULL CHECK (jenis_kelamin IN ('Laki-laki', 'Perempuan')),
                    status_perkawinan VARCHAR(20) NOT NULL CHECK (status_perkawinan IN ('Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati')),
                    pekerjaan VARCHAR(100),
                    alamat TEXT NOT NULL,
                    kecamatan VARCHAR(100) NOT NULL,
                    kabupaten VARCHAR(100) NOT NULL,
                    provinsi VARCHAR(100) NOT NULL,
                    kode_pos VARCHAR(10),
                    no_hp VARCHAR(20) NOT NULL,
                    email VARCHAR(255),
                    pendidikan VARCHAR(100),
                    nama_ayah VARCHAR(255),
                    nama_ibu VARCHAR(255),
                    
                    -- Dokumen files
                    ktp_file VARCHAR(255),
                    kk_file VARCHAR(255),
                    pas_foto_file VARCHAR(255),
                    akta_lahir_file VARCHAR(255),
                    ijazah_file VARCHAR(255),
                    
                    -- Pembayaran
                    paket_dipilih VARCHAR(255),
                    nominal_pembayaran DECIMAL(15,2),
                    bukti_pembayaran_file VARCHAR(255),
                    
                    -- Metadata
                    tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status_verifikasi VARCHAR(20) DEFAULT 'pending' CHECK (status_verifikasi IN ('pending', 'verified', 'rejected')),
                    catatan TEXT
                );
                
                CREATE INDEX IF NOT EXISTS idx_jenis_daftar ON registrations(jenis_daftar);
                CREATE INDEX IF NOT EXISTS idx_tanggal_daftar ON registrations(tanggal_daftar);
                CREATE INDEX IF NOT EXISTS idx_status_verifikasi ON registrations(status_verifikasi);
            ";
        }
        
        $this->conn->exec($sql);
        echo '<div class="success">✅ Registrations table created</div>';
    }
    
    private function createFileMetadataTable() {
        echo '<p>Creating file metadata table...</p>';
        
        if ($this->dbType === 'mysql') {
            $sql = "
                CREATE TABLE IF NOT EXISTS file_metadata (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    directory VARCHAR(50) NOT NULL,
                    original_name VARCHAR(255),
                    file_size INT,
                    mime_type VARCHAR(100),
                    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_railway BOOLEAN DEFAULT TRUE,
                    storage_path TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_file (filename, directory),
                    INDEX idx_directory (directory),
                    INDEX idx_upload_time (upload_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
        } else {
            $sql = "
                CREATE TABLE IF NOT EXISTS file_metadata (
                    id SERIAL PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    directory VARCHAR(50) NOT NULL,
                    original_name VARCHAR(255),
                    file_size INTEGER,
                    mime_type VARCHAR(100),
                    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_railway BOOLEAN DEFAULT TRUE,
                    storage_path TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(filename, directory)
                );
                
                CREATE INDEX IF NOT EXISTS idx_directory ON file_metadata(directory);
                CREATE INDEX IF NOT EXISTS idx_upload_time ON file_metadata(upload_time);
            ";
        }
        
        $this->conn->exec($sql);
        echo '<div class="success">✅ File metadata table created</div>';
    }
    
    private function createAdminUsersTable() {
        echo '<p>Creating admin users table...</p>';
        
        if ($this->dbType === 'mysql') {
            $sql = "
                CREATE TABLE IF NOT EXISTS admin_users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    email VARCHAR(255),
                    full_name VARCHAR(255),
                    role ENUM('admin', 'moderator') DEFAULT 'admin',
                    is_active BOOLEAN DEFAULT TRUE,
                    last_login TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_username (username),
                    INDEX idx_is_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
        } else {
            $sql = "
                CREATE TABLE IF NOT EXISTS admin_users (
                    id SERIAL PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    email VARCHAR(255),
                    full_name VARCHAR(255),
                    role VARCHAR(20) DEFAULT 'admin' CHECK (role IN ('admin', 'moderator')),
                    is_active BOOLEAN DEFAULT TRUE,
                    last_login TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                
                CREATE INDEX IF NOT EXISTS idx_username ON admin_users(username);
                CREATE INDEX IF NOT EXISTS idx_is_active ON admin_users(is_active);
            ";
        }
        
        $this->conn->exec($sql);
        echo '<div class="success">✅ Admin users table created</div>';
    }
    
    private function insertSampleData() {
        echo '<p>Inserting sample admin user...</p>';
        
        // Check if admin user already exists
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
        $stmt->execute(['admin']);
        
        if ($stmt->fetchColumn() == 0) {
            // Create default admin user
            $defaultPassword = 'admin123';
            $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                INSERT INTO admin_users (username, password_hash, email, full_name, role) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                'admin',
                $hashedPassword,
                'admin@miw-travel.com',
                'System Administrator',
                'admin'
            ]);
            
            echo '<div class="warning">⚠️ Default admin user created:</div>';
            echo '<div class="code-block">';
            echo 'Username: admin<br>';
            echo 'Password: admin123<br>';
            echo '<strong>Please change this password immediately!</strong>';
            echo '</div>';
        } else {
            echo '<div class="info">ℹ️ Admin user already exists</div>';
        }
    }
}

// Initialize
$init = new RailwayDatabaseInit();
$init->initialize();
?>
