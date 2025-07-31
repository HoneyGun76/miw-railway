<?php
/**
 * SUBMIT_UMROH.PHP DIAGNOSTIC SCRIPT
 * Identifies the exact cause of HTTP 500 error
 */

echo "🔍 SUBMIT UMROH HTTP 500 ERROR DIAGNOSIS\n";
echo "======================================\n\n";

// Check file existence and syntax
$files = [
    'safe_config.php',
    'csrf_protection.php', 
    'input_validator.php',
    'config.php',
    'email_functions.php',
    'upload_handler.php'
];

echo "📁 FILE DEPENDENCY CHECK:\n";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
        
        // Check for syntax errors
        $syntaxCheck = exec("php -l $file 2>&1", $output, $return);
        if ($return === 0) {
            echo "  ✅ Syntax: OK\n";
        } else {
            echo "  ❌ Syntax Error: " . implode(' ', $output) . "\n";
        }
        unset($output);
    } else {
        echo "❌ $file missing\n";
    }
}

echo "\n🔍 SUBMIT_UMROH.PHP ANALYSIS:\n";

// Check submit_umroh.php syntax
if (file_exists('submit_umroh.php')) {
    echo "✅ submit_umroh.php exists\n";
    
    $content = file_get_contents('submit_umroh.php');
    
    // Check for common issues
    echo "\n🔎 COMMON ISSUE DETECTION:\n";
    
    // 1. Duplicate require_once
    $requirePattern = '/require_once\s+[\'"]([^\'"]+)[\'"]/';
    preg_match_all($requirePattern, $content, $matches);
    $requires = $matches[1];
    $duplicates = array_count_values($requires);
    
    foreach ($duplicates as $file => $count) {
        if ($count > 1) {
            echo "⚠️ Duplicate require_once: $file (found $count times)\n";
        }
    }
    
    // 2. Check for header issues
    $headerPattern = '/header\s*\(/';
    $headerCount = preg_match_all($headerPattern, $content);
    echo "📤 Header calls: $headerCount\n";
    
    // 3. Check for output before headers
    $lines = explode("\n", $content);
    $foundPhpOpen = false;
    $outputBeforeHeaders = false;
    
    foreach ($lines as $lineNum => $line) {
        $trimmed = trim($line);
        if (strpos($trimmed, '<?php') === 0) {
            $foundPhpOpen = true;
            continue;
        }
        
        if ($foundPhpOpen && !empty($trimmed) && 
            !preg_match('/^(\/\/|\/\*|\*|require_once|include_once)/', $trimmed) &&
            strpos($trimmed, 'header(') === false) {
            
            if (strpos($trimmed, 'echo') === 0 || 
                strpos($trimmed, 'print') === 0 ||
                (strpos($trimmed, '?>') === false && !preg_match('/^\$|^if|^try|^catch|^function/', $trimmed))) {
                // Potential output before headers
                if (preg_match('/header\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                    $headerPos = $matches[0][1];
                    $linePos = strpos($content, $line);
                    if ($linePos < $headerPos) {
                        echo "⚠️ Potential output before headers at line " . ($lineNum + 1) . ": " . substr($trimmed, 0, 50) . "\n";
                        $outputBeforeHeaders = true;
                    }
                }
            }
        }
    }
    
    if (!$outputBeforeHeaders) {
        echo "✅ No obvious output before headers detected\n";
    }
    
    // 4. Check for undefined functions
    echo "\n🔧 FUNCTION USAGE CHECK:\n";
    $functionCalls = [
        'logError' => '/logError\s*\(/',
        'CSRFProtection::validateToken' => '/CSRFProtection::validateToken/',
        'InputValidator::sanitizeArray' => '/InputValidator::sanitizeArray/',
        'UploadHandler' => '/new\s+UploadHandler/'
    ];
    
    foreach ($functionCalls as $func => $pattern) {
        if (preg_match($pattern, $content)) {
            echo "📞 Using: $func\n";
        }
    }
    
    // 5. Database connection check
    if (strpos($content, '$conn') !== false) {
        echo "💾 Uses database connection: \$conn\n";
    }
    
} else {
    echo "❌ submit_umroh.php not found\n";
}

echo "\n🔧 SPECIFIC ISSUE ANALYSIS:\n";

// Check the three lines in question
echo "📍 ANALYZING LINES 2-4:\n";
$targetLines = [
    "require_once 'safe_config.php';",
    "require_once 'csrf_protection.php';", 
    "require_once 'input_validator.php';"
];

foreach ($targetLines as $i => $line) {
    $lineNum = $i + 2;
    echo "Line $lineNum: $line\n";
    
    $filename = str_replace(["require_once '", "';"], '', $line);
    
    if (file_exists($filename)) {
        echo "  ✅ File exists\n";
        
        // Check if file has any immediate output
        $fileContent = file_get_contents($filename);
        $firstLine = strtok($fileContent, "\n");
        
        if (strpos($firstLine, '<?php') !== 0) {
            echo "  ⚠️ File doesn't start with <?php\n";
        }
        
        // Check for immediate echo/print statements
        if (preg_match('/^\s*(echo|print|printf)/', $fileContent)) {
            echo "  ⚠️ File has immediate output\n";
        }
        
        // Check for session_start calls
        if (strpos($fileContent, 'session_start()') !== false) {
            echo "  📋 File calls session_start()\n";
        }
        
    } else {
        echo "  ❌ File not found\n";
    }
}

echo "\n💡 LIKELY CAUSES OF HTTP 500:\n";
echo "1. Duplicate require_once causing redefinition errors\n";
echo "2. Headers already sent before header() calls\n";
echo "3. Session conflicts or multiple session_start() calls\n";
echo "4. Missing required files or classes\n";
echo "5. PHP syntax errors in included files\n";

echo "\n🔧 NEXT STEPS:\n";
echo "1. Check web server error logs\n";
echo "2. Enable PHP error display temporarily\n";
echo "3. Test file includes individually\n";
echo "4. Remove duplicate require_once statements\n";

echo "\n📊 DIAGNOSTIC COMPLETE\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
?>
