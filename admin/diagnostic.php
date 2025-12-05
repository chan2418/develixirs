<?php
/**
 * Hostinger Diagnostic Script
 * Checks DB connection and file integrity
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 System Diagnostic</h1>";

// 1. Check PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// 2. Check Database Connection (Standalone)
echo "<h2>2. Database Connection (Standalone)</h2>";
$host = 'localhost';
$dbname = 'u295126515_develixirs';
$user = 'u295126515_develixirsuser';
$pass = '?Je1fu#9';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div style='color:green'>✅ Database connection successful!</div>";
    
    // Check users table
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ 'users' table exists.<br>";
        
        // Check columns in users table
        $stmt = $pdo->query("SHOW COLUMNS FROM users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Columns in users table: " . implode(', ', $columns) . "<br>";
        
        if (in_array('updated_at', $columns)) {
            echo "⚠️ 'updated_at' column EXISTS.<br>";
        } else {
            echo "ℹ️ 'updated_at' column DOES NOT exist.<br>";
        }
    } else {
        echo "❌ 'users' table NOT found.<br>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color:red'>❌ Database connection failed: " . $e->getMessage() . "</div>";
}

// 3. Check includes/db.php
echo "<h2>3. Checking includes/db.php</h2>";
$db_file = __DIR__ . '/../includes/db.php';

if (file_exists($db_file)) {
    echo "✅ File exists: $db_file<br>";
    $content = file_get_contents($db_file);
    
    if (strpos($content, 'DB_PORT') !== false) {
        echo "<div style='color:red; font-weight:bold; background:#ffebee; padding:10px;'>
            ❌ CRITICAL: includes/db.php still contains 'DB_PORT'!<br>
            You have NOT successfully uploaded the fixed file.<br>
            Please re-upload includes/db.php
            </div>";
    } else {
        echo "<div style='color:green'>✅ includes/db.php looks updated (no DB_PORT found).</div>";
    }
} else {
    echo "❌ File not found: $db_file<br>";
}

echo "<hr><p>End of diagnostic.</p>";
?>
