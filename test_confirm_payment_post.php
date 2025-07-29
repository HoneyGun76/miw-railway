<?php
/**
 * Test POST Request to confirm_payment.php
 * Simulates a form submission to identify specific issues
 */

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Confirm Payment POST</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .result { background: white; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #007cba; }
        .error { border-left-color: red; }
        .success { border-left-color: green; }
        input, select, textarea { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a9e; }
    </style>
</head>
<body>

<h1>🧪 Test Confirm Payment POST Request</h1>
<p>This form will test the confirm_payment.php endpoint with realistic data</p>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='result'>";
    echo "<h3>📤 Form Submitted - Testing confirm_payment.php</h3>";
    
    // Display submitted data
    echo "<h4>Submitted Data:</h4>";
    echo "<pre>" . htmlspecialchars(print_r($_POST, true)) . "</pre>";
    
    if (!empty($_FILES)) {
        echo "<h4>File Upload Data:</h4>";
        echo "<pre>" . htmlspecialchars(print_r($_FILES, true)) . "</pre>";
    }
    
    // Attempt to process through confirm_payment.php logic
    try {
        require_once 'config.php';
        require_once 'email_functions.php';
        
        echo "<h4>Processing Results:</h4>";
        
        // Check required fields
        $requiredFields = ['nik', 'transfer_account_name', 'nama', 'program_pilihan'];
        $missing = [];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            echo "<div class='error'>❌ Missing required fields: " . implode(', ', $missing) . "</div>";
        } else {
            echo "<div class='success'>✅ All required fields present</div>";
        }
        
        // Check if NIK exists in database
        if (!empty($_POST['nik'])) {
            $stmt = $conn->prepare("SELECT * FROM data_jamaah WHERE nik = ?");
            $stmt->execute([$_POST['nik']]);
            $jamaah = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($jamaah) {
                echo "<div class='success'>✅ NIK found in database: " . htmlspecialchars($jamaah['nama']) . "</div>";
            } else {
                echo "<div class='error'>❌ NIK not found in database</div>";
            }
        }
        
        // Check file upload
        if (isset($_FILES['payment_path'])) {
            $uploadError = $_FILES['payment_path']['error'];
            if ($uploadError === UPLOAD_ERR_OK) {
                echo "<div class='success'>✅ File upload: OK (" . $_FILES['payment_path']['size'] . " bytes)</div>";
            } else {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File too large (exceeds upload_max_filesize)',
                    UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds form MAX_FILE_SIZE)',
                    UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                ];
                $errorMsg = $errorMessages[$uploadError] ?? "Unknown error ($uploadError)";
                echo "<div class='error'>❌ File upload error: $errorMsg</div>";
            }
        } else {
            echo "<div class='error'>❌ No file upload field found</div>";
        }
        
        // Test email function availability
        if (function_exists('sendPaymentConfirmationEmail')) {
            echo "<div class='success'>✅ Email function available</div>";
        } else {
            echo "<div class='error'>❌ Email function not available</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Processing error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "</div>";
}
?>

<div class='form-container'>
<h3>Test Form (simulates invoice.php submission)</h3>

<form method="POST" enctype="multipart/form-data">
    <label>NIK (16 digits):</label>
    <input type="text" name="nik" value="9999999999999998" required>
    
    <label>Full Name:</label>
    <input type="text" name="nama" value="Test User Haji" required>
    
    <label>Phone Number:</label>
    <input type="text" name="no_telp" value="081234567890" required>
    
    <label>Program:</label>
    <input type="text" name="program_pilihan" value="Haji Test Program" required>
    
    <label>Payment Total:</label>
    <input type="number" name="payment_total" value="50000000">
    
    <label>Payment Method:</label>
    <select name="payment_method">
        <option value="BSI">BSI</option>
        <option value="BNI">BNI</option>
        <option value="Mandiri">Mandiri</option>
    </select>
    
    <label>Payment Type:</label>
    <select name="payment_type">
        <option value="DP">DP (Down Payment)</option>
        <option value="Lunas">Lunas (Full Payment)</option>
    </select>
    
    <label>Email:</label>
    <input type="email" name="email" value="test@example.com">
    
    <label>Room Type:</label>
    <select name="type_room_pilihan">
        <option value="Quad">Quad</option>
        <option value="Triple">Triple</option>
        <option value="Double">Double</option>
    </select>
    
    <label>Departure Date:</label>
    <input type="date" name="tanggal_keberangkatan" value="2026-01-01">
    
    <label>Transfer Account Name:</label>
    <input type="text" name="transfer_account_name" value="Test Transfer Account" required>
    
    <label>Payment Proof (Upload a small image/PDF):</label>
    <input type="file" name="payment_path" accept=".jpg,.jpeg,.png,.pdf" required>
    
    <p><small>Note: This form tests the validation logic. It won't actually submit to confirm_payment.php to avoid modifying real data.</small></p>
    
    <button type="submit">🧪 Test Validation Logic</button>
</form>

<div style="margin-top: 20px;">
    <a href="confirm_payment_diagnostic_haji.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
        🩺 Run Full Diagnostic
    </a>
    <a href="error_logger.php" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-left: 10px;">
        📋 Check Error Logs
    </a>
</div>
</div>

</body>
</html>
