<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Add custom_title column
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN custom_title VARCHAR(255) DEFAULT NULL AFTER variant_name");
    echo "✓ Added custom_title column\n";
    
    // Add custom_description column
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN custom_description TEXT DEFAULT NULL AFTER custom_title");
    echo "✓ Added custom_description column\n";
    
    // Add images column (JSON array)
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN images TEXT DEFAULT NULL AFTER custom_description");
    echo "✓ Added images column\n";
    
    echo "\n✅ Successfully added all variant override columns!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
