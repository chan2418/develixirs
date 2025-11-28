<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN main_variant_name VARCHAR(255) DEFAULT NULL AFTER variant_label");
    echo "✅ Successfully added 'main_variant_name' column to products table!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
