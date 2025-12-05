<?php
// Admin Debug Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin Dashboard Debug</h1>";

// 1. Check admin files exist
echo "<h2>1. Admin Files Check</h2>";
$adminFiles = [
    'admin/dashboard.php',
    'admin/layout/header.php',
    'admin/layout/sidebar.php'
];

foreach ($adminFiles as $file) {
    if (file_exists('../' . $file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file NOT FOUND<br>";
    }
}

// 2. Test database
echo "<h2>2. Database Connection</h2>";
try {
    require_once '../includes/db.php';
    echo "✅ Database connected<br>";
    
    // Check admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user found: " . htmlspecialchars($admin['email']) . "<br>";
    } else {
        echo "❌ No admin user found! Create one first.<br>";
    }
    
    // Check required tables
    echo "<h3>Database Tables:</h3>";
    $tables = ['users', 'products', 'orders', 'categories'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ $table ($count rows)<br>";
        } catch (Exception $e) {
            echo "❌ $table - Error: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
}

// 3. Session check
echo "<h2>3. Session Test</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✅ Session active - User ID: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "⚠️ No active session. You need to login first.<br>";
}

echo "<hr>";
echo "<p><strong>Solution:</strong> Go to <a href='../login.php'>Login Page</a> and login with admin credentials.</p>";
echo "<p>Admin Email: admin@develixirs.com<br>Password: admin@123</p>";
?>
