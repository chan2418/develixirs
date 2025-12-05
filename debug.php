<?php
// Debug script to identify HTTP 500 error
// Upload this to your Hostinger root and visit: newv2.develixirs.com/debug.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>DevElixir Debug Information</h1>";

// 1. Check PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "✅ PHP is working!<br>";

// 2. Check required extensions
echo "<h2>2. PHP Extensions</h2>";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl', 'gd'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext is loaded<br>";
    } else {
        echo "❌ $ext is NOT loaded (Required!)<br>";
    }
}

// 3. Test database connection
echo "<h2>3. Database Connection Test</h2>";
if (file_exists('includes/db.php')) {
    echo "✅ includes/db.php exists<br>";
    
    try {
        require_once 'includes/db.php';
        echo "✅ Database file loaded successfully<br>";
        
        if (isset($pdo)) {
            echo "✅ PDO connection established!<br>";
            
            // Test query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            echo "✅ Database query successful! User count: " . $result['count'] . "<br>";
        } else {
            echo "❌ PDO object not created<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ includes/db.php NOT FOUND!<br>";
}

// 4. Check critical directories
echo "<h2>4. Directory Checks</h2>";
$dirs = [
    'includes',
    'admin',
    'assets',
    'assets/uploads',
    'assets/uploads/products'
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ $dir exists<br>";
        if (is_writable($dir)) {
            echo "&nbsp;&nbsp;✅ Writable<br>";
        } else {
            echo "&nbsp;&nbsp;⚠️ NOT writable<br>";
        }
    } else {
        echo "❌ $dir NOT FOUND<br>";
    }
}

// 5. Check index.php
echo "<h2>5. Index File Check</h2>";
if (file_exists('index.php')) {
    echo "✅ index.php exists<br>";
    
    // Try to include it and catch errors
    ob_start();
    try {
        include 'index.php';
        $output = ob_get_clean();
        echo "✅ index.php loads without fatal errors<br>";
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Error loading index.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ index.php NOT FOUND!<br>";
}

// 6. Server info
echo "<h2>6. Server Information</h2>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";

echo "<hr><p><strong>If all checks pass, the issue might be in a specific page. Delete this file after debugging!</strong></p>";
?>
