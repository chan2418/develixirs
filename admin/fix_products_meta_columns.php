<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Products Table - Add Meta Columns</h1>";

try {
    // Check current columns
    echo "<h2>Current Table Structure:</h2>";
    $cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_ASSOC);
    
    $hasMetaTitle = false;
    $hasMetaDescription = false;
    
    foreach ($cols as $c) {
        if ($c['Field'] === 'meta_title') $hasMetaTitle = true;
        if ($c['Field'] === 'meta_description') $hasMetaDescription = true;
    }
    
    echo "<p>Column <b>meta_title</b>: " . ($hasMetaTitle ? "<span style='color:green'>EXISTS</span>" : "<span style='color:red'>MISSING</span>") . "</p>";
    echo "<p>Column <b>meta_description</b>: " . ($hasMetaDescription ? "<span style='color:green'>EXISTS</span>" : "<span style='color:red'>MISSING</span>") . "</p>";
    
    echo "<hr>";
    
    // Add missing columns
    $changes = [];
    
    if (!$hasMetaTitle) {
        echo "<p>Adding column <b>meta_title</b>...</p>";
        $pdo->exec("ALTER TABLE products ADD COLUMN meta_title VARCHAR(255) NULL AFTER description");
        $changes[] = "meta_title";
        echo "<p style='color:green'>✓ Added meta_title</p>";
    }
    
    if (!$hasMetaDescription) {
        echo "<p>Adding column <b>meta_description</b>...</p>";
        $pdo->exec("ALTER TABLE products ADD COLUMN meta_description TEXT NULL AFTER meta_title");
        $changes[] = "meta_description";
        echo "<p style='color:green'>✓ Added meta_description</p>";
    }
    
    if (empty($changes)) {
        echo "<p style='color:blue; font-weight:bold'>No changes needed. All columns already exist!</p>";
    } else {
        echo "<hr>";
        echo "<p style='color:green; font-weight:bold; font-size:18px'>✓ Successfully added: " . implode(', ', $changes) . "</p>";
        echo "<p>You can now add/edit products without errors.</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='add_product.php'>Try Adding a Product</a> | <a href='products.php'>View Products</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
