<?php
// add_product_relations_table.php
// Migration script to create product_relations table for related products feature

require_once __DIR__ . '/includes/db.php';

try {
    // Check if table already exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'product_relations'");
    if ($checkTable->rowCount() > 0) {
        echo "Table 'product_relations' already exists.\n";
        exit;
    }

    // Create the product_relations table
    $sql = "
        CREATE TABLE product_relations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            related_product_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_relation (product_id, related_product_id),
            INDEX idx_product_id (product_id),
            INDEX idx_related_product_id (related_product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $pdo->exec($sql);
    
    echo "✓ Successfully created 'product_relations' table.\n";
    echo "✓ Added foreign key constraints for data integrity.\n";
    echo "✓ Added unique constraint to prevent duplicate relations.\n";
    echo "✓ Added indexes for better query performance.\n";

} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage() . "\n");
}
