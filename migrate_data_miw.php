<?php
/**
 * Data MIW Database Schema Migration for Railway MySQL
 * This script recreates the complete data_miw database schema on Railway
 */

require_once 'config.php';

// Check if we have a database connection
if (!$conn) {
    die('❌ MySQL connection failed. Please check your Railway configuration.');
}

// Handle POST request to migrate database
if ($_POST['action'] === 'migrate' && isset($_POST['confirm'])) {
    $success = [];
    $errors = [];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Drop existing tables if they exist (clean migration)
        $dropTables = [
            'file_metadata',
            'payment_confirmations', 
            'pembatalan',
            'registrations',
            'data_jamaah',
            'admin_users',
            'data_paket'
        ];
        
        foreach ($dropTables as $table) {
            try {
                $conn->exec("DROP TABLE IF EXISTS $table");
                $success[] = "🗑️ Dropped existing table: $table";
            } catch (Exception $e) {
                // Continue if table doesn't exist
            }
        }
        
        // Create data_paket table (travel packages)
        $sql = "CREATE TABLE data_paket (
            pak_id INT AUTO_INCREMENT PRIMARY KEY,
            jenis_paket ENUM('Umroh', 'Haji') NOT NULL,
            program_pilihan VARCHAR(255) NOT NULL,
            tanggal_keberangkatan DATE NOT NULL,
            base_price_quad DECIMAL(15,2) NOT NULL DEFAULT 0,
            base_price_triple DECIMAL(15,2) NOT NULL DEFAULT 0,
            base_price_double DECIMAL(15,2) NOT NULL DEFAULT 0,
            durasi_hari INT NOT NULL DEFAULT 0,
            deskripsi TEXT,
            fasilitas TEXT,
            hotel_makkah VARCHAR(255),
            hotel_madinah VARCHAR(255),
            maskapai VARCHAR(255),
            status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
            kuota_tersisa INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_jenis (jenis_paket),
            INDEX idx_status (status),
            INDEX idx_tanggal (tanggal_keberangkatan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->exec($sql);
        $success[] = "✅ Table 'data_paket' created successfully";
        
        // Create data_jamaah table (registrations)
        $sql = "CREATE TABLE data_jamaah (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pak_id INT,
            nama_lengkap VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            no_telepon VARCHAR(20) NOT NULL,
            alamat TEXT NOT NULL,
            jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
            tanggal_lahir DATE NOT NULL,
            tempat_lahir VARCHAR(255),
            no_ktp VARCHAR(20) NOT NULL,
            no_passport VARCHAR(20),
            nama_ayah VARCHAR(255),
            nama_ibu VARCHAR(255),
            pekerjaan VARCHAR(255),
            pendidikan VARCHAR(255),
            room_type ENUM('quad', 'triple', 'double') NOT NULL,
            nama_roommate VARCHAR(255),
            hubungan_roommate VARCHAR(100),
            total_biaya DECIMAL(15,2) NOT NULL,
            uang_muka DECIMAL(15,2) DEFAULT 0,
            sisa_pembayaran DECIMAL(15,2) DEFAULT 0,
            status_pembayaran ENUM('pending', 'dp_paid', 'full_paid', 'verified', 'cancelled') DEFAULT 'pending',
            status_dokumen ENUM('incomplete', 'submitted', 'verified') DEFAULT 'incomplete',
            catatan TEXT,
            emergency_contact_name VARCHAR(255),
            emergency_contact_phone VARCHAR(20),
            emergency_contact_relation VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (pak_id) REFERENCES data_paket(pak_id) ON DELETE SET NULL,
            INDEX idx_email (email),
            INDEX idx_ktp (no_ktp),
            INDEX idx_status_pembayaran (status_pembayaran),
            INDEX idx_status_dokumen (status_dokumen),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->exec($sql);
        $success[] = "✅ Table 'data_jamaah' created successfully";
        
        // Create file_metadata table (document uploads)
        $sql = "CREATE TABLE file_metadata (
            id INT AUTO_INCREMENT PRIMARY KEY,
            jamaah_id INT,
            file_type ENUM('ktp', 'passport', 'photo', 'payment_proof', 'document', 'visa', 'medical') NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verification_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            verified_by INT NULL,
            verified_at TIMESTAMP NULL,
            notes TEXT,
            FOREIGN KEY (jamaah_id) REFERENCES data_jamaah(id) ON DELETE CASCADE,
            INDEX idx_jamaah (jamaah_id),
            INDEX idx_file_type (file_type),
            INDEX idx_verification (verification_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->exec($sql);
        $success[] = "✅ Table 'file_metadata' created successfully";
        
        // Create admin_users table
        $sql = "CREATE TABLE admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            role ENUM('admin', 'operator', 'viewer') DEFAULT 'operator',
            permissions JSON,
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            login_attempts INT DEFAULT 0,
            locked_until TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by INT NULL,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->exec($sql);
        $success[] = "✅ Table 'admin_users' created successfully";
        
        // Create pembatalan table (cancellations)
        $sql = "CREATE TABLE pembatalan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            jamaah_id INT,
            nama_lengkap VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            no_telepon VARCHAR(20) NOT NULL,
            alasan_pembatalan TEXT NOT NULL,
            kategori_alasan ENUM('kesehatan', 'keluarga', 'keuangan', 'lainnya') NOT NULL,
            nomor_rekening VARCHAR(50),
            nama_bank VARCHAR(100),
            nama_pemilik_rekening VARCHAR(255),
            jumlah_refund DECIMAL(15,2) DEFAULT 0,
            biaya_admin DECIMAL(15,2) DEFAULT 0,
            jumlah_dikembalikan DECIMAL(15,2) DEFAULT 0,
            status_pembatalan ENUM('pending', 'approved', 'rejected', 'refunded') DEFAULT 'pending',
            tanggal_pengajuan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            tanggal_proses TIMESTAMP NULL,
            tanggal_refund TIMESTAMP NULL,
            catatan_admin TEXT,
            processed_by INT NULL,
            FOREIGN KEY (jamaah_id) REFERENCES data_jamaah(id) ON DELETE SET NULL,
            FOREIGN KEY (processed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
            INDEX idx_status (status_pembatalan),
            INDEX idx_email (email),
            INDEX idx_tanggal (tanggal_pengajuan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->exec($sql);
        $success[] = "✅ Table 'pembatalan' created successfully";
        
        // Create payment_confirmations table
        $sql = "CREATE TABLE payment_confirmations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            jamaah_id INT,
            nama_lengkap VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            no_telepon VARCHAR(20) NOT NULL,
            jenis_pembayaran ENUM('dp', 'pelunasan', 'full') NOT NULL,
            jumlah_transfer DECIMAL(15,2) NOT NULL,
            tanggal_transfer DATE NOT NULL,
            waktu_transfer TIME,
            bank_pengirim VARCHAR(100) NOT NULL,
            nama_pengirim VARCHAR(255) NOT NULL,
            rekening_tujuan VARCHAR(100),
            bukti_transfer VARCHAR(255),
            keterangan TEXT,
            status_konfirmasi ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
            tanggal_konfirmasi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL,
            verified_by INT NULL,
            jumlah_diterima DECIMAL(15,2) NULL,
            catatan_admin TEXT,
            FOREIGN KEY (jamaah_id) REFERENCES data_jamaah(id) ON DELETE SET NULL,
            FOREIGN KEY (verified_by) REFERENCES admin_users(id) ON DELETE SET NULL,
            INDEX idx_status (status_konfirmasi),
            INDEX idx_email (email),
            INDEX idx_tanggal (tanggal_transfer),
            INDEX idx_jenis (jenis_pembayaran)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->exec($sql);
        $success[] = "✅ Table 'payment_confirmations' created successfully";
        
        // Create manifest_data table for travel manifests
        $sql = "CREATE TABLE manifest_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pak_id INT,
            jamaah_id INT,
            manifest_type ENUM('umroh', 'haji') NOT NULL,
            group_number VARCHAR(50),
            seat_number VARCHAR(10),
            room_number VARCHAR(20),
            flight_number VARCHAR(20),
            departure_date DATE,
            arrival_date DATE,
            visa_number VARCHAR(100),
            mahram_id INT NULL,
            special_requests TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (pak_id) REFERENCES data_paket(pak_id) ON DELETE CASCADE,
            FOREIGN KEY (jamaah_id) REFERENCES data_jamaah(id) ON DELETE CASCADE,
            FOREIGN KEY (mahram_id) REFERENCES data_jamaah(id) ON DELETE SET NULL,
            INDEX idx_manifest_type (manifest_type),
            INDEX idx_group (group_number),
            INDEX idx_departure (departure_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->exec($sql);
        $success[] = "✅ Table 'manifest_data' created successfully";
        
        // Insert sample travel packages
        $sql = "INSERT INTO data_paket (jenis_paket, program_pilihan, tanggal_keberangkatan, base_price_quad, base_price_triple, base_price_double, durasi_hari, deskripsi, fasilitas, hotel_makkah, hotel_madinah, maskapai, kuota_tersisa) VALUES
            ('Umroh', 'Umroh Plus Turki 14 Hari', '2025-03-15', 28000000, 32000000, 38000000, 14, 'Paket Umroh Plus mengunjungi Turki dengan fasilitas lengkap dan bimbingan ibadah yang komprehensif', 'Hotel bintang 4, Makan 3x sehari, Tour Turki, Bimbingan manasik', 'Dar Al Eiman Royal', 'Anwar Al Madinah Mövenpick', 'Turkish Airlines', 40),
            
            ('Umroh', 'Umroh Reguler 9 Hari', '2025-04-20', 22000000, 25000000, 30000000, 9, 'Paket Umroh reguler dengan pelayanan terbaik dan fasilitas lengkap', 'Hotel bintang 4, Makan 3x sehari, Transportasi AC, Bimbingan manasik', 'Pullman ZamZam Makkah', 'Pullman Madinah', 'Saudia Airlines', 35),
            
            ('Haji', 'Haji Reguler 2025', '2025-06-10', 65000000, 70000000, 80000000, 35, 'Paket Haji reguler tahun 2025 dengan fasilitas standar pemerintah dan layanan premium', 'Hotel bintang 4, Makan 3x sehari, Tenda Arafah ber-AC, Transportasi', 'Conrad Makkah', 'Anwar Al Madinah Mövenpick', 'Garuda Indonesia', 25),
            
            ('Umroh', 'Umroh Ramadhan 12 Hari', '2025-03-01', 35000000, 40000000, 45000000, 12, 'Paket khusus Umroh bulan Ramadhan dengan pengalaman berbuka puasa di Masjidil Haram', 'Hotel bintang 5, Makan 3x sehari, Sahur dan buka di hotel, City tour Makkah-Madinah', 'Raffles Makkah Palace', 'Dar Al Iman InterContinental', 'Emirates', 30),
            
            ('Umroh', 'Umroh Ekonomis 7 Hari', '2025-05-10', 18000000, 21000000, 25000000, 7, 'Paket Umroh ekonomis dengan fasilitas lengkap dan harga terjangkau', 'Hotel bintang 3, Makan 3x sehari, Transportasi AC, Bimbingan manasik', 'Al Kiswah Towers', 'Taiba Madinah Hotel', 'Lion Air', 50),
            
            ('Haji', 'Haji Plus 2025', '2025-06-15', 85000000, 95000000, 110000000, 40, 'Paket Haji Plus dengan fasilitas mewah dan pelayanan VIP', 'Hotel bintang 5, Makan 4x sehari, Tenda Arafah VIP, Bus VIP, City tour', 'Fairmont Makkah Clock Royal Tower', 'Ritz Carlton Madinah', 'Qatar Airways', 15)";
        $conn->exec($sql);
        $success[] = "✅ Sample travel packages inserted";
        
        // Insert default admin users
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $operatorPassword = password_hash('operator123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO admin_users (username, password_hash, email, full_name, role, permissions) VALUES
            ('admin', '$adminPassword', 'admin@miwtravel.com', 'Administrator', 'admin', '{\"all\": true}'),
            ('operator', '$operatorPassword', 'operator@miwtravel.com', 'Operator', 'operator', '{\"view\": true, \"edit\": true}'),
            ('viewer', '$operatorPassword', 'viewer@miwtravel.com', 'Viewer', 'viewer', '{\"view\": true}')";
        $conn->exec($sql);
        $success[] = "✅ Default admin users created";
        $success[] = "🔑 Admin login: admin / admin123";
        $success[] = "🔑 Operator login: operator / operator123";
        
        // Create views for backward compatibility
        $sql = "CREATE VIEW registrations AS SELECT * FROM data_jamaah";
        $conn->exec($sql);
        $success[] = "✅ Compatibility view 'registrations' created";
        
        // Commit transaction
        $conn->commit();
        $success[] = "🎉 Database migration completed successfully!";
        $success[] = "📊 Data MIW schema recreated on Railway MySQL";
        
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "❌ Migration Error: " . $e->getMessage();
    }
}

// Get current database info
$dbInfo = [];
try {
    $stmt = $conn->query("SELECT DATABASE() as current_db");
    $dbInfo['current_database'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SHOW TABLES");
    $dbInfo['tables'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $conn->query("SELECT VERSION() as version");
    $dbInfo['mysql_version'] = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $dbInfo['error'] = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data MIW Schema Migration - Railway MySQL</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f5f7fa; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .success { color: #27ae60; font-weight: bold; margin: 5px 0; }
        .error { color: #e74c3c; font-weight: bold; margin: 5px 0; }
        .warning { color: #f39c12; font-weight: bold; }
        .info { color: #3498db; font-weight: bold; }
        .btn { padding: 12px 20px; margin: 10px 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn-primary { background-color: #667eea; color: white; }
        .btn-success { background-color: #27ae60; color: white; }
        .btn-danger { background-color: #e74c3c; color: white; }
        .btn:hover { opacity: 0.9; }
        .checkbox { margin: 10px 0; }
        .schema-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .table-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea; }
        .table-name { font-weight: bold; color: #333; margin-bottom: 8px; }
        .table-desc { font-size: 0.9em; color: #666; }
        .db-info { background: #e8f4fd; padding: 15px; border-radius: 5px; font-family: monospace; }
        .credentials { background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Data MIW Schema Migration</h1>
            <p>Migrate complete data_miw database schema to Railway MySQL</p>
            <div class="info">Environment: Railway Production MySQL</div>
        </div>

        <div class="section">
            <h3>📊 Current Database Status</h3>
            <div class="db-info">
                <strong>MySQL Version:</strong> <?= $dbInfo['mysql_version'] ?? 'Unknown' ?><br>
                <strong>Database:</strong> <?= $dbInfo['current_database'] ?? 'Unknown' ?><br>
                <strong>Tables Count:</strong> <?= count($dbInfo['tables'] ?? []) ?><br>
                <strong>Connection:</strong> <?= $conn ? '✅ Connected' : '❌ Failed' ?>
            </div>
            
            <?php if (!empty($dbInfo['tables'])): ?>
            <h4>Existing Tables:</h4>
            <div style="columns: 3; column-gap: 20px;">
                <?php foreach ($dbInfo['tables'] as $table): ?>
                <div>📋 <?= htmlspecialchars($table) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($success) || !empty($errors)): ?>
        <div class="section">
            <h3>📋 Migration Results</h3>
            <?php foreach ($success as $msg): ?>
                <div class="success"><?= $msg ?></div>
            <?php endforeach; ?>
            <?php foreach ($errors as $msg): ?>
                <div class="error"><?= $msg ?></div>
            <?php endforeach; ?>
            
            <?php if (!empty($success)): ?>
                <div style="margin-top: 20px;">
                    <a href="admin_dashboard.php" class="btn btn-success">🚀 Go to Admin Dashboard</a>
                    <a href="form_umroh.php" class="btn btn-primary">📝 Test Umroh Form</a>
                    <a href="form_haji.php" class="btn btn-primary">📝 Test Haji Form</a>
                    <a href="index.php" class="btn btn-primary">🏠 Homepage</a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="section">
            <h3>🏗️ Database Schema</h3>
            <p>This migration will create a complete data_miw database schema with the following tables:</p>
            
            <div class="schema-grid">
                <div class="table-card">
                    <div class="table-name">📦 data_paket</div>
                    <div class="table-desc">Travel packages (Umroh/Haji) with pricing and details</div>
                </div>
                <div class="table-card">
                    <div class="table-name">👥 data_jamaah</div>
                    <div class="table-desc">Customer registrations and personal data</div>
                </div>
                <div class="table-card">
                    <div class="table-name">📄 file_metadata</div>
                    <div class="table-desc">Document uploads and file management</div>
                </div>
                <div class="table-card">
                    <div class="table-name">👑 admin_users</div>
                    <div class="table-desc">Admin accounts and role management</div>
                </div>
                <div class="table-card">
                    <div class="table-name">❌ pembatalan</div>
                    <div class="table-desc">Cancellation requests and refunds</div>
                </div>
                <div class="table-card">
                    <div class="table-name">💳 payment_confirmations</div>
                    <div class="table-desc">Payment confirmations and verification</div>
                </div>
                <div class="table-card">
                    <div class="table-name">📋 manifest_data</div>
                    <div class="table-desc">Travel manifests and group assignments</div>
                </div>
                <div class="table-card">
                    <div class="table-name">🔗 registrations (view)</div>
                    <div class="table-desc">Compatibility view for data_jamaah</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>🔑 Admin Credentials</h3>
            <div class="credentials">
                <strong>Default Admin Accounts:</strong><br>
                • Administrator: <code>admin</code> / <code>admin123</code><br>
                • Operator: <code>operator</code> / <code>operator123</code><br>
                • Viewer: <code>viewer</code> / <code>operator123</code>
            </div>
        </div>

        <?php if (empty($success)): ?>
        <div class="section">
            <h3>🚀 Start Migration</h3>
            <div class="warning">⚠️ This will DROP existing tables and recreate the complete schema!</div>
            <form method="POST">
                <input type="hidden" name="action" value="migrate">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="confirm" required>
                        I understand this will drop and recreate all database tables
                    </label>
                </div>
                <button type="submit" class="btn btn-danger">Migrate Database Schema</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="section">
            <h3>🔗 Quick Links</h3>
            <a href="health.php" class="btn btn-primary">Health Check</a>
            <a href="railway_testing_framework.php" class="btn btn-primary">Run Tests</a>
            <a href="deployment_status.php" class="btn btn-primary">Status Dashboard</a>
            <a href="railway_analytics.php" class="btn btn-primary">Analytics</a>
        </div>
    </div>
</body>
</html>
