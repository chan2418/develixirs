<?php
require_once __DIR__ . '/includes/db.php';

try {
    echo "Checking 'products' table columns...\n";

    // Get current columns
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Add compare_price if missing
    if (!in_array('compare_price', $columns)) {
        echo "Adding 'compare_price' column...\n";
        $pdo->exec("ALTER TABLE products ADD COLUMN compare_price DECIMAL(10,2) DEFAULT NULL AFTER price");
    } else {
        echo "'compare_price' column already exists.\n";
    }

    // Add discount_percent if missing
    if (!in_array('discount_percent', $columns)) {
        echo "Adding 'discount_percent' column...\n";
        $pdo->exec("ALTER TABLE products ADD COLUMN discount_percent DECIMAL(5,2) DEFAULT NULL AFTER compare_price");
    } else {
        echo "'discount_percent' column already exists.\n";
    }

    echo "Database schema update complete.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
