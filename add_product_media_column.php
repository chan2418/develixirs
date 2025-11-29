<?php
// add_product_media_column.php - Migration to add product_media column

require_once __DIR__ . '/includes/db.php';

try {
    echo "Adding product_media column to products table...\n";
    
    $sql = "ALTER TABLE products 
            ADD COLUMN product_media JSON DEFAULT NULL
            COMMENT 'Stores array of media files (images/videos) with type and path'";
    
    $pdo->exec($sql);
    
    echo "✅ Successfully added product_media column!\n";
    
    // Verify
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'product_media'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "\nColumn details:\n";
        echo "Field: " . $result['Field'] . "\n";
        echo "Type: " . $result['Type'] . "\n";
        echo "Null: " . $result['Null'] . "\n";
        echo "Default: " . ($result['Default'] ?? 'NULL') . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
