<?php
require_once __DIR__ . '/includes/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS variant_faqs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        variant_id INT NOT NULL,
        question VARCHAR(500) NOT NULL,
        answer TEXT NOT NULL,
        display_order INT DEFAULT 0,
        FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
        INDEX idx_variant (variant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "✅ Successfully created variant_faqs table!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
