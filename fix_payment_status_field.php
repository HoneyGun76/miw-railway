<?php
/**
 * Fix Payment Status Field Length
 * Addresses the VARCHAR(10) truncation error for payment_status
 */

require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Fix Payment Status Field</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.result { background: white; margin: 10px 0; padding: 15px; border-radius: 8px; }
.success { border-left: 4px solid green; }
.error { border-left: 4px solid red; }
.info { border-left: 4px solid blue; }
h1 { color: #333; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔧 Fix Payment Status Field Length</h1>";

try {
    echo "<div class='result info'>";
    echo "<h3>Current Environment: " . getCurrentEnvironment() . "</h3>";
    echo "<h3>Database Type: " . getDatabaseType() . "</h3>";
    echo "</div>";

    // Check current payment_status column definition
    echo "<div class='result info'>";
    echo "<h3>📊 Checking Current Schema</h3>";
    
    if (getDatabaseType() === 'postgresql') {
        $stmt = $conn->query("
            SELECT column_name, data_type, character_maximum_length, is_nullable
            FROM information_schema.columns 
            WHERE table_name = 'data_jamaah' AND column_name = 'payment_status'
        ");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($column) {
            echo "<p><strong>Current payment_status field:</strong></p>";
            echo "<pre>";
            echo "Type: {$column['data_type']}\n";
            echo "Max Length: {$column['character_maximum_length']}\n";
            echo "Nullable: {$column['is_nullable']}\n";
            echo "</pre>";
        } else {
            echo "<p>❌ payment_status column not found</p>";
        }
    } else {
        $stmt = $conn->query("DESCRIBE data_jamaah payment_status");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($column) {
            echo "<p><strong>Current payment_status field:</strong></p>";
            echo "<pre>" . print_r($column, true) . "</pre>";
        } else {
            echo "<p>❌ payment_status column not found</p>";
        }
    }
    echo "</div>";

    // Apply the fix
    echo "<div class='result info'>";
    echo "<h3>🔧 Applying Fix</h3>";
    
    if (getDatabaseType() === 'postgresql') {
        // For PostgreSQL, we need to drop the constraint first, then alter the column
        echo "<p>Step 1: Dropping existing CHECK constraint...</p>";
        try {
            $conn->exec("ALTER TABLE data_jamaah DROP CONSTRAINT IF EXISTS data_jamaah_payment_status_check");
            echo "<p>✅ Constraint dropped (if existed)</p>";
        } catch (Exception $e) {
            echo "<p>ℹ️ No constraint to drop or error: " . $e->getMessage() . "</p>";
        }
        
        echo "<p>Step 2: Altering column length...</p>";
        $conn->exec("ALTER TABLE data_jamaah ALTER COLUMN payment_status TYPE VARCHAR(25)");
        echo "<p>✅ Column altered to VARCHAR(25)</p>";
        
        echo "<p>Step 3: Adding new CHECK constraint...</p>";
        $conn->exec("ALTER TABLE data_jamaah ADD CONSTRAINT data_jamaah_payment_status_check 
                     CHECK (payment_status IN ('pending', 'verified', 'rejected', 'confirmation_submitted'))");
        echo "<p>✅ New constraint added with 'confirmation_submitted'</p>";
        
    } else {
        // For MySQL
        echo "<p>Altering MySQL column...</p>";
        $conn->exec("ALTER TABLE data_jamaah MODIFY payment_status VARCHAR(25) 
                     CHECK (payment_status IN ('pending', 'verified', 'rejected', 'confirmation_submitted'))");
        echo "<p>✅ MySQL column altered</p>";
    }
    echo "</div>";

    // Verify the fix
    echo "<div class='result success'>";
    echo "<h3>✅ Verification</h3>";
    
    if (getDatabaseType() === 'postgresql') {
        $stmt = $conn->query("
            SELECT column_name, data_type, character_maximum_length
            FROM information_schema.columns 
            WHERE table_name = 'data_jamaah' AND column_name = 'payment_status'
        ");
        $newColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Updated payment_status field:</strong></p>";
        echo "<pre>";
        echo "Type: {$newColumn['data_type']}\n";
        echo "Max Length: {$newColumn['character_maximum_length']}\n";
        echo "</pre>";
        
        // Test the new value
        echo "<p>Testing 'confirmation_submitted' value...</p>";
        $testResult = $conn->query("SELECT 'confirmation_submitted'::VARCHAR(25) as test_value")->fetchColumn();
        echo "<p>✅ Test successful: " . $testResult . "</p>";
        
    } else {
        $stmt = $conn->query("DESCRIBE data_jamaah payment_status");
        $newColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Updated payment_status field:</strong></p>";
        echo "<pre>" . print_r($newColumn, true) . "</pre>";
    }
    echo "</div>";

    echo "<div class='result success'>";
    echo "<h3>🎉 Fix Applied Successfully!</h3>";
    echo "<p>The payment_status field can now accept 'confirmation_submitted' (20 characters)</p>";
    echo "<p>Allowed values: 'pending', 'verified', 'rejected', 'confirmation_submitted'</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='result error'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Failed to apply fix: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<div class='result info'>";
echo "<h3>🔗 Next Steps</h3>";
echo "<p><a href='test_confirm_payment_post.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Test Confirm Payment POST</a></p>";
echo "<p><a href='confirm_payment_diagnostic_haji.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-left: 10px;'>Run Haji Diagnostic</a></p>";
echo "</div>";

echo "</body></html>";
?>
