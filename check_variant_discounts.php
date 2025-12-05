<?php
require_once __DIR__ . '/includes/db.php';

$productId = 29; // The product ID we found earlier

echo "Checking variants for Product ID: $productId\n\n";

try {
    $stmt = $pdo->prepare("
        SELECT id, variant_name, price, compare_price, discount_percent 
        FROM product_variants 
        WHERE product_id = ? 
    ");
    $stmt->execute([$productId]);
    
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($variants)) {
        echo "No variants found for this product.\n";
    } else {
        foreach ($variants as $v) {
            echo "Variant ID: {$v['id']}\n";
            echo "Name: {$v['variant_name']}\n";
            echo "Price: ₹{$v['price']}\n";
            echo "Compare Price: " . ($v['compare_price'] ?? 'NULL') . "\n";
            echo "Discount %: " . ($v['discount_percent'] ?? 'NULL') . "\n";
            echo "---\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
