<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing 'orders' table (adding total_amount)...</h2>";

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

// Add total_amount (DECIMAL 10,2)
addColumnIfNeeded($pdo, 'orders', 'total_amount', "DECIMAL(10,2) DEFAULT 0.00");

// Also check for other common columns that might be missing based on typical schema
addColumnIfNeeded($pdo, 'orders', 'subtotal', "DECIMAL(10,2) DEFAULT 0.00");
addColumnIfNeeded($pdo, 'orders', 'tax_amount', "DECIMAL(10,2) DEFAULT 0.00");
addColumnIfNeeded($pdo, 'orders', 'shipping_cost', "DECIMAL(10,2) DEFAULT 0.00");
addColumnIfNeeded($pdo, 'orders', 'discount_amount', "DECIMAL(10,2) DEFAULT 0.00");

echo "<p><a href='/my-profile.php'>Go to My Profile</a></p>";
