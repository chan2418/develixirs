<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Add product_name column
    try {
        $pdo->exec("ALTER TABLE `external_reviews` ADD COLUMN `product_name` VARCHAR(255) NULL DEFAULT NULL AFTER `is_featured`");
        echo "Column 'product_name' added successfully.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "Column 'product_name' already exists.\n";
        } else {
            echo "Error adding product_name: " . $e->getMessage() . "\n";
        }
    }

    // Add product_image column
    try {
        $pdo->exec("ALTER TABLE `external_reviews` ADD COLUMN `product_image` VARCHAR(255) NULL DEFAULT NULL AFTER `product_name`");
        echo "Column 'product_image' added successfully.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "Column 'product_image' already exists.\n";
        } else {
            echo "Error adding product_image: " . $e->getMessage() . "\n";
        }
    }

} catch (PDOException $e) {
    echo "General Error: " . $e->getMessage();
}
?>
