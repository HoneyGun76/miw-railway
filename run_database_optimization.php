<?php
/**
 * Database initialization and optimization runner
 */

require_once 'safe_config.php';

function runDatabaseOptimization($conn) {
    echo "🔧 Running Database Optimization...\n";
    
    try {
        // Read and execute SQL optimization
        $sql = file_get_contents('database_schema_optimization.sql');
        $statements = explode(';', $sql);
        
        $executed = 0;
        $errors = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $conn->exec($statement);
                $executed++;
                echo "✅ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                $errors++;
                echo "⚠️ Warning: " . substr($statement, 0, 30) . "... - " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n📊 Optimization Results:\n";
        echo "- Statements executed: $executed\n";
        echo "- Warnings/Errors: $errors\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Optimization failed: " . $e->getMessage() . "\n";
        return false;
    }
}

function validateOptimization($conn) {
    echo "\n🔍 Validating Optimization...\n";
    
    $checks = [
        'Foreign Keys' => "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_SCHEMA = DATABASE()",
        'Indexes' => "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND INDEX_NAME != 'PRIMARY'",
        'Views' => "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = DATABASE()",
        'Procedures' => "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE'"
    ];
    
    foreach ($checks as $check => $query) {
        try {
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "✅ $check: " . $result['count'] . "\n";
        } catch (Exception $e) {
            echo "❌ $check check failed: " . $e->getMessage() . "\n";
        }
    }
}

// Main execution
if (isset($conn) && $conn) {
    echo "🗄️ DATABASE OPTIMIZATION RUNNER\n";
    echo "==============================\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (runDatabaseOptimization($conn)) {
        validateOptimization($conn);
        echo "\n🎉 Database optimization completed successfully!\n";
    } else {
        echo "\n❌ Database optimization failed!\n";
    }
} else {
    echo "❌ Database connection not available\n";
    echo "💡 Start MySQL service first: net start MySQL80\n";
}
?>