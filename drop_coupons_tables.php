<?php
/**
 * Drop Coupons Tables (Use this to start fresh if needed)
 * WARNING: This will delete all coupon data!
 */

require_once __DIR__ . '/includes/db.php';

echo "<h2>Dropping Coupon Tables...</h2>";
echo "<p style='color: red;'><strong>WARNING:</strong> This will delete all coupon data!</p>";

try {
    // Drop tables in reverse order (to handle foreign keys)
    $tables = ['coupon_usage', 'coupon_products', 'coupon_categories', 'coupons'];
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "<p>✓ Dropped table: $table</p>";
    }
    
    // Remove coupon columns from orders table
    try {
        $pdo->exec("ALTER TABLE orders DROP COLUMN IF EXISTS coupon_code");
        $pdo->exec("ALTER TABLE orders DROP COLUMN IF EXISTS coupon_discount");
        echo "<p>✓ Removed coupon columns from orders table</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠ Could not remove columns from orders: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h3 style='color: green;'>✓ All coupon tables dropped successfully!</h3>";
    echo "<p><a href='create_coupons_schema.php'>Click here to recreate tables</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
}
