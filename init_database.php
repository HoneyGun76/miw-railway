<?php
/**
 * Database Initialization Script for Railway
 * This script initializes the MySQL database schema
 */

// Load configuration
require_once 'config.php';

try {
    echo "Initializing MIW Travel database schema...\n";
    
    // Read the SQL file
    $sql_file = __DIR__ . '/init_database_postgresql.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    if ($sql === false) {
        throw new Exception("Failed to read SQL file");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        try {
            $conn->exec($statement);
            $success_count++;
            echo ".";
        } catch (PDOException $e) {
            $error_count++;
            echo "Error executing statement: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n\nDatabase initialization completed!\n";
    echo "Successful statements: $success_count\n";
    echo "Failed statements: $error_count\n";
    
    // Test the database connection and tables
    echo "\nTesting database tables...\n";
    
    $tables = ['data_paket', 'data_jamaah', 'data_invoice', 'data_pembatalan'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "Table $table: $count records\n";
        } catch (PDOException $e) {
            echo "Error checking table $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nDatabase initialization successful!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
