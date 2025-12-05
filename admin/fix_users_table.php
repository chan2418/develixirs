<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing 'users' table (adding gender, phone)...</h2>";

// Helper to add column if missing
function addColumnIfNeeded($pdo, $table, $col, $def) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $col $def");
            echo "<p style='color:green'>Added column '$col' to '$table'.</p>";
        } else {
            echo "<p style='color:gray'>Column '$col' already exists in '$table'.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error checking/adding '$col' to '$table': " . $e->getMessage() . "</p>";
    }
}

addColumnIfNeeded($pdo, 'users', 'gender', "ENUM('male','female','other') DEFAULT NULL");
addColumnIfNeeded($pdo, 'users', 'phone', "VARCHAR(20) DEFAULT NULL");

echo "<p><a href='/my-profile.php'>Go to My Profile</a></p>";
