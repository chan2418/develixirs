<?php
// Add short_description column to product_variants table
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN short_description TEXT DEFAULT NULL AFTER custom_description");
    echo "✅ Successfully added 'short_description' column to product_variants table!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
