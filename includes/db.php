<?php
// Hostinger Production Database Configuration

define('DB_HOST', 'localhost');
define('DB_NAME', 'u295126515_develixirs');
define('DB_USER', 'u295126515_develixirsuser');
define('DB_PASS', '?Je1fu#9');

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5, // Add connection timeout of 5 seconds
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check if the database server is running at " . DB_HOST . ". Error: " . $e->getMessage());
}