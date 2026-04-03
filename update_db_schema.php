<?php
require_once __DIR__ . '/includes/db.php';

try {
    echo "Updating homepage_products table...\n";
    $pdo->exec("ALTER TABLE homepage_products MODIFY COLUMN section ENUM('best_seller','sale','top_rated','trendy','latest') NOT NULL");
    echo "Successfully updated section column to include 'latest'.\n";
} catch (PDOException $e) {
    echo "Error updating table: " . $e->getMessage() . "\n";
}
?>
