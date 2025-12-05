<?php
require_once __DIR__ . '/includes/db.php';

echo "Checking products with discount...\n\n";

try {
    $stmt = $pdo->query("
        SELECT id, name, price, compare_price, discount_percent 
        FROM products 
        WHERE is_active = 1 
        ORDER BY id DESC 
        LIMIT 10
    ");
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "No products found!\n";
    } else {
        foreach ($products as $p) {
            echo "ID: {$p['id']}\n";
            echo "Name: {$p['name']}\n";
            echo "Price: ₹{$p['price']}\n";
            echo "Compare Price: " . ($p['compare_price'] ?? 'NULL') . "\n";
            echo "Discount %: " . ($p['discount_percent'] ?? 'NULL') . "\n";
            echo "---\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
