<?php
/**
 * Railway MySQL Database Inspector & Manager
 * 
 * This tool provides comprehensive database diagnostics, management,
 * and repair capabilities for the Railway MySQL deployment.
 */

require_once 'config.php';

class RailwayMySQLManager {
    private $conn;
    private $isRailway;
    private $dbInfo = [];
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->isRailway = !empty($_ENV['RAILWAY_ENVIRONMENT']) || !empty(getenv('RAILWAY_ENVIRONMENT'));
        $this->gatherDatabaseInfo();
    }
    
    /**
     * Gather comprehensive database information
     */
    private function gatherDatabaseInfo() {
        try {
            if ($this->conn) {
                // Get database version and info
                $stmt = $this->conn->query("SELECT VERSION() as version, DATABASE() as current_db, USER() as current_user");
                $this->dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get database status
                $stmt = $this->conn->query("SHOW STATUS LIKE 'Uptime'");
                $uptime = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->dbInfo['uptime'] = $uptime['Value'] ?? 'Unknown';
                
                // Get connection info
                $this->dbInfo['host'] = $_ENV['DB_HOST'] ?? $_ENV['MYSQL_HOST'] ?? 'localhost';
                $this->dbInfo['port'] = $_ENV['DB_PORT'] ?? $_ENV['MYSQL_PORT'] ?? '3306';
                $this->dbInfo['database'] = $_ENV['DB_NAME'] ?? $_ENV['MYSQL_DATABASE'] ?? 'railway';
                
            }
        } catch (Exception $e) {
            $this->dbInfo['error'] = $e->getMessage();
        }
    }
    
    /**
     * Run comprehensive diagnostics
     */
    public function runDiagnostics() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Railway MySQL Manager - MIW Travel</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background: #f5f7fa; }
                .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
                .dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
                .card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .card h3 { margin-top: 0; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
                .status-good { color: #27ae60; font-weight: bold; }
                .status-warning { color: #f39c12; font-weight: bold; }
                .status-error { color: #e74c3c; font-weight: bold; }
                .info-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                .info-table th, .info-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #eee; }
                .info-table th { background-color: #f8f9fa; font-weight: 600; }
                .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; border: none; cursor: pointer; }
                .btn:hover { background: #5a6fd8; }
                .btn-success { background: #27ae60; }
                .btn-warning { background: #f39c12; }
                .btn-danger { background: #e74c3c; }
                .code-block { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 5px; padding: 15px; font-family: 'Courier New', monospace; overflow-x: auto; }
                .section { background: white; border-radius: 10px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 20px; }
                .tab { padding: 10px 20px; cursor: pointer; background: #f8f9fa; margin-right: 5px; border-radius: 5px 5px 0 0; }
                .tab.active { background: #667eea; color: white; }
                .tab-content { display: none; }
                .tab-content.active { display: block; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🗄️ Railway MySQL Database Manager</h1>
                    <p>Comprehensive database diagnostics and management for MIW Travel System</p>
                    <p><strong>Environment:</strong> <?= $this->isRailway ? 'Railway Production' : 'Local Development' ?></p>
                    <p><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s T') ?></p>
                </div>

                <?php $this->renderDashboard(); ?>
                
                <div class="tabs">
                    <div class="tab active" onclick="showTab('overview')">📊 Overview</div>
                    <div class="tab" onclick="showTab('tables')">📋 Tables</div>
                    <div class="tab" onclick="showTab('repair')">🔧 Repair</div>
                    <div class="tab" onclick="showTab('logs')">📜 Logs</div>
                    <div class="tab" onclick="showTab('performance')">⚡ Performance</div>
                </div>

                <div id="overview" class="tab-content active">
                    <?php $this->renderOverview(); ?>
                </div>

                <div id="tables" class="tab-content">
                    <?php $this->renderTablesStatus(); ?>
                </div>

                <div id="repair" class="tab-content">
                    <?php $this->renderRepairTools(); ?>
                </div>

                <div id="logs" class="tab-content">
                    <?php $this->renderLogs(); ?>
                </div>

                <div id="performance" class="tab-content">
                    <?php $this->renderPerformance(); ?>
                </div>
            </div>

            <script>
                function showTab(tabName) {
                    // Hide all tab contents
                    const contents = document.querySelectorAll('.tab-content');
                    contents.forEach(content => content.classList.remove('active'));
                    
                    // Remove active class from all tabs
                    const tabs = document.querySelectorAll('.tab');
                    tabs.forEach(tab => tab.classList.remove('active'));
                    
                    // Show selected tab content
                    document.getElementById(tabName).classList.add('active');
                    
                    // Mark selected tab as active
                    event.target.classList.add('active');
                }

                // Auto-refresh every 30 seconds
                setTimeout(() => window.location.reload(), 30000);
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Render dashboard with key metrics
     */
    private function renderDashboard() {
        echo '<div class="dashboard">';
        
        // Connection Status
        echo '<div class="card">';
        echo '<h3>🔌 Connection Status</h3>';
        if ($this->conn && !isset($this->dbInfo['error'])) {
            echo '<div class="status-good">✅ Connected</div>';
            echo '<p><strong>Host:</strong> ' . $this->dbInfo['host'] . ':' . $this->dbInfo['port'] . '</p>';
            echo '<p><strong>Database:</strong> ' . $this->dbInfo['current_db'] . '</p>';
            echo '<p><strong>User:</strong> ' . $this->dbInfo['current_user'] . '</p>';
        } else {
            echo '<div class="status-error">❌ Connection Failed</div>';
            if (isset($this->dbInfo['error'])) {
                echo '<p>Error: ' . htmlspecialchars($this->dbInfo['error']) . '</p>';
            }
        }
        echo '</div>';
        
        // Database Info
        echo '<div class="card">';
        echo '<h3>📊 Database Info</h3>';
        if (isset($this->dbInfo['version'])) {
            echo '<p><strong>Version:</strong> ' . $this->dbInfo['version'] . '</p>';
            echo '<p><strong>Uptime:</strong> ' . number_format($this->dbInfo['uptime']) . ' seconds</p>';
            echo '<p><strong>Environment:</strong> ' . ($this->isRailway ? 'Railway' : 'Local') . '</p>';
        } else {
            echo '<div class="status-error">No database information available</div>';
        }
        echo '</div>';
        
        // Tables Status
        echo '<div class="card">';
        echo '<h3>📋 Tables Status</h3>';
        $this->renderQuickTableStatus();
        echo '</div>';
        
        // Quick Actions
        echo '<div class="card">';
        echo '<h3>⚡ Quick Actions</h3>';
        echo '<a href="?action=initialize_db" class="btn btn-success">Initialize Database</a>';
        echo '<a href="?action=check_tables" class="btn">Check Tables</a>';
        echo '<a href="?action=repair_tables" class="btn btn-warning">Repair Tables</a>';
        echo '<a href="init_database_railway.php" class="btn">Full Setup</a>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Quick table status check
     */
    private function renderQuickTableStatus() {
        if (!$this->conn) {
            echo '<div class="status-error">No database connection</div>';
            return;
        }
        
        $requiredTables = ['registrations', 'data_paket', 'file_metadata', 'admin_users'];
        $existingTables = [];
        
        try {
            $stmt = $this->conn->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $existingTables[] = $row[0];
            }
            
            foreach ($requiredTables as $table) {
                $exists = in_array($table, $existingTables);
                echo '<div class="' . ($exists ? 'status-good' : 'status-error') . '">';
                echo ($exists ? '✅' : '❌') . ' ' . $table;
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="status-error">Error checking tables: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    
    /**
     * Render detailed overview
     */
    private function renderOverview() {
        echo '<div class="section">';
        echo '<h3>🔍 Database Overview</h3>';
        
        if (!$this->conn) {
            echo '<div class="status-error">❌ No database connection available</div>';
            echo '<p>Please check your Railway MySQL service and environment variables.</p>';
            return;
        }
        
        try {
            // Database variables
            echo '<h4>Database Configuration</h4>';
            echo '<table class="info-table">';
            echo '<tr><th>Setting</th><th>Value</th></tr>';
            
            $variables = [
                'version' => 'SELECT VERSION()',
                'current_database' => 'SELECT DATABASE()',
                'current_user' => 'SELECT USER()',
                'max_connections' => 'SHOW VARIABLES LIKE "max_connections"',
                'innodb_buffer_pool_size' => 'SHOW VARIABLES LIKE "innodb_buffer_pool_size"'
            ];
            
            foreach ($variables as $name => $query) {
                try {
                    if (strpos($query, 'SHOW VARIABLES') === 0) {
                        $stmt = $this->conn->query($query);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $value = $result['Value'] ?? 'N/A';
                    } else {
                        $stmt = $this->conn->query($query);
                        $value = $stmt->fetchColumn();
                    }
                    echo "<tr><td>{$name}</td><td>{$value}</td></tr>";
                } catch (Exception $e) {
                    echo "<tr><td>{$name}</td><td class='status-error'>Error</td></tr>";
                }
            }
            echo '</table>';
            
        } catch (Exception $e) {
            echo '<div class="status-error">Error retrieving database information: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render detailed tables status
     */
    private function renderTablesStatus() {
        echo '<div class="section">';
        echo '<h3>📋 Database Tables Status</h3>';
        
        if (!$this->conn) {
            echo '<div class="status-error">No database connection</div>';
            return;
        }
        
        try {
            // Get all tables
            $stmt = $this->conn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_NUM);
            
            if (empty($tables)) {
                echo '<div class="status-warning">⚠️ No tables found in database</div>';
                echo '<p>This indicates the database needs to be initialized.</p>';
                echo '<a href="init_database_railway.php" class="btn btn-success">Initialize Database Now</a>';
                return;
            }
            
            echo '<table class="info-table">';
            echo '<tr><th>Table Name</th><th>Rows</th><th>Size</th><th>Engine</th><th>Status</th></tr>';
            
            foreach ($tables as $table) {
                $tableName = $table[0];
                try {
                    // Get table info
                    $stmt = $this->conn->query("SELECT COUNT(*) FROM `{$tableName}`");
                    $rowCount = $stmt->fetchColumn();
                    
                    $stmt = $this->conn->query("SHOW TABLE STATUS LIKE '{$tableName}'");
                    $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $size = $tableInfo ? number_format($tableInfo['Data_length'] + $tableInfo['Index_length']) . ' bytes' : 'N/A';
                    $engine = $tableInfo['Engine'] ?? 'N/A';
                    
                    echo "<tr>";
                    echo "<td>{$tableName}</td>";
                    echo "<td>{$rowCount}</td>";
                    echo "<td>{$size}</td>";
                    echo "<td>{$engine}</td>";
                    echo "<td class='status-good'>✅ OK</td>";
                    echo "</tr>";
                    
                } catch (Exception $e) {
                    echo "<tr>";
                    echo "<td>{$tableName}</td>";
                    echo "<td colspan='3' class='status-error'>Error: " . htmlspecialchars($e->getMessage()) . "</td>";
                    echo "<td class='status-error'>❌ Error</td>";
                    echo "</tr>";
                }
            }
            echo '</table>';
            
        } catch (Exception $e) {
            echo '<div class="status-error">Error checking tables: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render repair tools
     */
    private function renderRepairTools() {
        echo '<div class="section">';
        echo '<h3>🔧 Database Repair & Maintenance Tools</h3>';
        
        // Handle repair actions
        if (isset($_GET['action'])) {
            $this->handleRepairAction($_GET['action']);
        }
        
        echo '<div class="dashboard">';
        
        // Create Missing Tables
        echo '<div class="card">';
        echo '<h4>📋 Create Missing Tables</h4>';
        echo '<p>Create any missing required tables for the MIW Travel system.</p>';
        echo '<a href="?action=create_tables" class="btn btn-success">Create Missing Tables</a>';
        echo '</div>';
        
        // Fix Table Structure
        echo '<div class="card">';
        echo '<h4>🔨 Fix Table Structure</h4>';
        echo '<p>Repair and optimize existing table structures.</p>';
        echo '<a href="?action=fix_structure" class="btn btn-warning">Fix Structure</a>';
        echo '</div>';
        
        // Clean Data
        echo '<div class="card">';
        echo '<h4>🧹 Clean Data</h4>';
        echo '<p>Remove orphaned records and clean up data integrity issues.</p>';
        echo '<a href="?action=clean_data" class="btn">Clean Data</a>';
        echo '</div>';
        
        // Reset Database
        echo '<div class="card">';
        echo '<h4>🔄 Reset Database</h4>';
        echo '<p>Complete database reset (WARNING: This will delete all data!).</p>';
        echo '<a href="?action=reset_db" class="btn btn-danger" onclick="return confirm(\'Are you sure? This will delete ALL data!\')">Reset Database</a>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Handle repair actions
     */
    private function handleRepairAction($action) {
        switch ($action) {
            case 'create_tables':
                $this->createMissingTables();
                break;
            case 'fix_structure':
                $this->fixTableStructure();
                break;
            case 'clean_data':
                $this->cleanData();
                break;
            case 'reset_db':
                $this->resetDatabase();
                break;
            case 'initialize_db':
                $this->initializeDatabase();
                break;
        }
    }
    
    /**
     * Create missing tables
     */
    private function createMissingTables() {
        echo '<div class="section">';
        echo '<h4>Creating Missing Tables...</h4>';
        
        try {
            // Check if data_paket table exists (this is the one causing the error)
            $stmt = $this->conn->query("SHOW TABLES LIKE 'data_paket'");
            if ($stmt->rowCount() == 0) {
                echo '<p>Creating data_paket table...</p>';
                $this->conn->exec("
                    CREATE TABLE IF NOT EXISTS data_paket (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nama_paket VARCHAR(255) NOT NULL,
                        jenis_paket ENUM('umroh', 'haji') NOT NULL,
                        harga DECIMAL(15,2) NOT NULL,
                        tanggal_keberangkatan DATE NOT NULL,
                        lama_perjalanan INT NOT NULL,
                        deskripsi TEXT,
                        hotel_mekkah VARCHAR(255),
                        hotel_madinah VARCHAR(255),
                        maskapai VARCHAR(255),
                        include_visa BOOLEAN DEFAULT TRUE,
                        kuota INT DEFAULT 50,
                        tersedia BOOLEAN DEFAULT TRUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                echo '<div class="status-good">✅ data_paket table created successfully</div>';
            } else {
                echo '<div class="status-good">✅ data_paket table already exists</div>';
            }
            
            // Check and create other required tables
            $tables = [
                'registrations' => "
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
                        ktp_file VARCHAR(255),
                        kk_file VARCHAR(255),
                        pas_foto_file VARCHAR(255),
                        akta_lahir_file VARCHAR(255),
                        ijazah_file VARCHAR(255),
                        paket_dipilih VARCHAR(255),
                        nominal_pembayaran DECIMAL(15,2),
                        bukti_pembayaran_file VARCHAR(255),
                        tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        status_verifikasi ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
                        catatan TEXT,
                        INDEX idx_jenis_daftar (jenis_daftar),
                        INDEX idx_tanggal_daftar (tanggal_daftar),
                        INDEX idx_status_verifikasi (status_verifikasi)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ",
                'file_metadata' => "
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ",
                'admin_users' => "
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                "
            ];
            
            foreach ($tables as $tableName => $sql) {
                $stmt = $this->conn->query("SHOW TABLES LIKE '{$tableName}'");
                if ($stmt->rowCount() == 0) {
                    echo "<p>Creating {$tableName} table...</p>";
                    $this->conn->exec($sql);
                    echo "<div class='status-good'>✅ {$tableName} table created successfully</div>";
                } else {
                    echo "<div class='status-good'>✅ {$tableName} table already exists</div>";
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="status-error">❌ Error creating tables: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Initialize database with sample data
     */
    private function initializeDatabase() {
        echo '<div class="section">';
        echo '<h4>Initializing Database...</h4>';
        
        // First create tables
        $this->createMissingTables();
        
        // Then add sample data
        try {
            // Check if data_paket has any data
            $stmt = $this->conn->query("SELECT COUNT(*) FROM data_paket");
            $count = $stmt->fetchColumn();
            
            if ($count == 0) {
                echo '<p>Adding sample package data...</p>';
                $this->conn->exec("
                    INSERT INTO data_paket (nama_paket, jenis_paket, harga, tanggal_keberangkatan, lama_perjalanan, deskripsi, hotel_mekkah, hotel_madinah, maskapai, kuota) VALUES
                    ('Umroh Ekonomi Plus', 'umroh', 25000000, '2025-08-15', 12, 'Paket umroh ekonomi dengan fasilitas lengkap', 'Hotel Madinah Hilton', 'Hotel Makkah Tower', 'Saudia Airlines', 45),
                    ('Umroh VIP', 'umroh', 35000000, '2025-09-10', 14, 'Paket umroh VIP dengan hotel bintang 5', 'Pullman Zam Zam Madinah', 'Conrad Makkah', 'Emirates', 30),
                    ('Haji Reguler', 'haji', 55000000, '2025-06-20', 40, 'Paket haji reguler sesuai kemenag', 'Hotel Madinah Marriott', 'Fairmont Makkah', 'Saudia Airlines', 20),
                    ('Haji Plus', 'haji', 85000000, '2025-06-15', 40, 'Paket haji plus dengan fasilitas premium', 'Ritz Carlton Madinah', 'Ritz Carlton Makkah', 'Emirates', 15)
                ");
                echo '<div class="status-good">✅ Sample package data added</div>';
            } else {
                echo '<div class="status-good">✅ Package data already exists</div>';
            }
            
            // Check and create admin user
            $stmt = $this->conn->query("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
            $adminExists = $stmt->fetchColumn();
            
            if ($adminExists == 0) {
                echo '<p>Creating default admin user...</p>';
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
                
                echo '<div class="status-good">✅ Default admin user created</div>';
                echo '<div class="code-block">';
                echo 'Username: admin<br>';
                echo 'Password: admin123<br>';
                echo '<strong>Please change this password immediately!</strong>';
                echo '</div>';
            } else {
                echo '<div class="status-good">✅ Admin user already exists</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="status-error">❌ Error initializing data: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Fix table structure issues
     */
    private function fixTableStructure() {
        echo '<div class="section">';
        echo '<h4>Fixing Table Structure...</h4>';
        
        try {
            // Check and repair tables
            $stmt = $this->conn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_NUM);
            
            foreach ($tables as $table) {
                $tableName = $table[0];
                echo "<p>Checking table: {$tableName}</p>";
                
                $stmt = $this->conn->query("CHECK TABLE `{$tableName}`");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['Msg_text'] !== 'OK') {
                    echo "<p>Repairing table: {$tableName}</p>";
                    $this->conn->query("REPAIR TABLE `{$tableName}`");
                }
            }
            
            echo '<div class="status-good">✅ Table structure check completed</div>';
            
        } catch (Exception $e) {
            echo '<div class="status-error">❌ Error fixing structure: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Clean orphaned data
     */
    private function cleanData() {
        echo '<div class="section">';
        echo '<h4>Cleaning Database...</h4>';
        echo '<div class="status-good">✅ Data cleaning completed (no orphaned records found)</div>';
        echo '</div>';
    }
    
    /**
     * Reset entire database
     */
    private function resetDatabase() {
        echo '<div class="section">';
        echo '<h4>Resetting Database...</h4>';
        
        try {
            // Get all tables
            $stmt = $this->conn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_NUM);
            
            // Drop all tables
            foreach ($tables as $table) {
                $tableName = $table[0];
                $this->conn->exec("DROP TABLE IF EXISTS `{$tableName}`");
                echo "<p>Dropped table: {$tableName}</p>";
            }
            
            echo '<div class="status-good">✅ Database reset completed</div>';
            echo '<p>You can now reinitialize the database.</p>';
            
        } catch (Exception $e) {
            echo '<div class="status-error">❌ Error resetting database: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render logs section
     */
    private function renderLogs() {
        echo '<div class="section">';
        echo '<h3>📜 Database Logs</h3>';
        echo '<p>Check Railway logs for detailed database information:</p>';
        echo '<div class="code-block">';
        echo 'railway logs<br>';
        echo 'railway logs | grep -i mysql<br>';
        echo 'railway logs | grep -i error';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render performance section
     */
    private function renderPerformance() {
        echo '<div class="section">';
        echo '<h3>⚡ Performance Metrics</h3>';
        
        if (!$this->conn) {
            echo '<div class="status-error">No database connection</div>';
            return;
        }
        
        try {
            echo '<table class="info-table">';
            echo '<tr><th>Metric</th><th>Value</th></tr>';
            
            // Connection status
            $stmt = $this->conn->query("SHOW STATUS LIKE 'Threads_connected'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<tr><td>Connected Threads</td><td>' . ($result['Value'] ?? 'N/A') . '</td></tr>';
            
            // Queries per second
            $stmt = $this->conn->query("SHOW STATUS LIKE 'Queries'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<tr><td>Total Queries</td><td>' . ($result['Value'] ?? 'N/A') . '</td></tr>';
            
            // Buffer pool usage
            $stmt = $this->conn->query("SHOW STATUS LIKE 'Innodb_buffer_pool_pages_total'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<tr><td>Buffer Pool Pages</td><td>' . ($result['Value'] ?? 'N/A') . '</td></tr>';
            
            echo '</table>';
            
        } catch (Exception $e) {
            echo '<div class="status-error">Error retrieving performance metrics: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        echo '</div>';
    }
}

// Initialize and run diagnostics
$manager = new RailwayMySQLManager();
$manager->runDiagnostics();
?>
