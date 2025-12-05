<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing 'products' table (adding is_active)...</h2>";

try {
    // Check if is_active exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_active'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>Column 'is_active' already exists in 'products'.</p>";
    } else {
        // Add the column
        $pdo->exec("ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        echo "<p style='color:green'>Success: Column 'is_active' added to 'products' table.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='product_inventory.php'>Go back to Product Inventory</a></p>";
