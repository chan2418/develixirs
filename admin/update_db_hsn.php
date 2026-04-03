<?php
// admin/update_db_hsn.php
require_once __DIR__ . '/../includes/db.php';

echo "<h1>Database Update: Add HSN Column</h1>";

try {
    // Check if column exists first
    $stmt = $pdo->query("SHOW COLUMNS FROM product_variants LIKE 'hsn'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "<h2 style='color:green'>✅ Column 'hsn' already exists!</h2>";
    } else {
        // Add the column
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN hsn VARCHAR(50) NULL AFTER sku");
        echo "<h2 style='color:green'>✅ Successfully added 'hsn' column!</h2>";
    }

    echo "<p><a href='products.php'>Go back to Products</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
