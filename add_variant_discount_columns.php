<?php
require_once __DIR__ . '/includes/db.php';

try {
    echo "Checking 'product_variants' table columns...\n";

    // Get current columns
    $stmt = $pdo->query("SHOW COLUMNS FROM product_variants");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Add compare_price if missing
    if (!in_array('compare_price', $columns)) {
        echo "Adding 'compare_price' column to product_variants...\n";
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN compare_price DECIMAL(10,2) DEFAULT NULL AFTER price");
    } else {
        echo "'compare_price' column already exists in product_variants.\n";
    }

    // Add discount_percent if missing
    if (!in_array('discount_percent', $columns)) {
        echo "Adding 'discount_percent' column to product_variants...\n";
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN discount_percent DECIMAL(5,2) DEFAULT NULL AFTER compare_price");
    } else {
        echo "'discount_percent' column already exists in product_variants.\n";
    }

    echo "Database schema update complete.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
