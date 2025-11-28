<?php
// Add extra fields to product_variants table
require_once __DIR__ . '/includes/db.php';

try {
    // Add ingredients column
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN ingredients TEXT DEFAULT NULL AFTER short_description");
    echo "✅ Added 'ingredients' column\n";
} catch (Exception $e) {
    echo "⚠️  ingredients: " . $e->getMessage() . "\n";
}

try {
    // Add how_to_use column
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN how_to_use TEXT DEFAULT NULL AFTER ingredients");
    echo "✅ Added 'how_to_use' column\n";
} catch (Exception $e) {
    echo "⚠️  how_to_use: " . $e->getMessage() . "\n";
}

try {
    // Add meta_title column
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER how_to_use");
    echo "✅ Added 'meta_title' column\n";
} catch (Exception $e) {
    echo "⚠️  meta_title: " . $e->getMessage() . "\n";
}

try {
    // Add meta_description column
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN meta_description TEXT DEFAULT NULL AFTER meta_title");
    echo "✅ Added 'meta_description' column\n";
} catch (Exception $e) {
    echo "⚠️  meta_description: " . $e->getMessage() . "\n";
}

echo "\n✅ Migration complete!\n";
