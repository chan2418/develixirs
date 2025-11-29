<?php
// test_db_connection.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'develixirs_db');
define('DB_USER', 'develixirs_user');
define('DB_PASS', 'develixirs_pass');

echo "Testing connection to " . DB_HOST . ":" . DB_PORT . "...\n";

try {
    $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5 // 5 seconds timeout
    ]);
    echo "✅ Connection SUCCESSFUL!\n";
    echo "Server Info: " . $pdo->getAttribute(PDO::ATTR_SERVER_INFO) . "\n";
} catch (PDOException $e) {
    echo "❌ Connection FAILED.\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
