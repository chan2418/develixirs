<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER sku");
    echo "Successfully added 'image' column to product_variants table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
