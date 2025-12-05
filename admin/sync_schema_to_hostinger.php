<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Sync Local Schema to Hostinger</h1>";
echo "<p>This will add any missing columns from your local database to Hostinger.</p>";
echo "<hr>";

// Define ALL the columns that should exist in products table (from your complete local DB)
$requiredColumns = [
    'meta_title' => ['type' => 'VARCHAR(255)', 'null' => 'NULL', 'default' => null, 'after' => 'description'],
    'meta_description' => ['type' => 'TEXT', 'null' => 'NULL', 'default' => null, 'after' => 'meta_title'],
    'variant_label' => ['type' => 'VARCHAR(50)', 'null' => 'NULL', 'default' => "'Size'", 'after' => 'is_active'],
    'main_variant_name' => ['type' => 'VARCHAR(100)', 'null' => 'NULL', 'default' => null, 'after' => 'variant_label'],
    'category_name' => ['type' => 'VARCHAR(255)', 'null' => 'NULL', 'default' => null, 'after' => 'cat_id'],
    'parent_category_id' => ['type' => 'INT(11)', 'null' => 'NULL', 'default' => null, 'after' => 'cat_id'],
    'gst_rate' => ['type' => 'DECIMAL(5,2)', 'null' => 'NULL', 'default' => '0.00', 'after' => 'discount_percent'],
    'product_media' => ['type' => 'TEXT', 'null' => 'NULL', 'default' => null, 'after' => 'images'],
    'hsn' => ['type' => 'VARCHAR(20)', 'null' => 'NULL', 'default' => null, 'after' => 'sku']
];

try {
    // Get current columns
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Current Columns in Hostinger DB:</h2>";
    echo "<p><code>" . implode(', ', $existing) . "</code></p>";
    
    echo "<h2>Adding Missing Columns:</h2>";
    
    $added = [];
    $errors = [];
    
    foreach ($requiredColumns as $colName => $config) {
        if (!in_array($colName, $existing)) {
            try {
                $sql = "ALTER TABLE products ADD COLUMN `{$colName}` {$config['type']} {$config['null']}";
                
                if ($config['default'] !== null) {
                    $sql .= " DEFAULT {$config['default']}";
                }
                
                if (isset($config['after'])) {
                    $sql .= " AFTER `{$config['after']}`";
                }
                
                echo "<p><small><code>$sql</code></small></p>";
                $pdo->exec($sql);
                echo "<p style='color:green'>✓ Added: <b>$colName</b></p>";
                $added[] = $colName;
            } catch (PDOException $e) {
                echo "<p style='color:orange'>⚠ Could not add $colName: " . htmlspecialchars($e->getMessage()) . "</p>";
                $errors[] = $colName;
            }
        } else {
            echo "<p style='color:gray'>○ Already exists: $colName</p>";
        }
    }
    
    echo "<hr>";
    
    if (empty($added) && empty($errors)) {
        echo "<p style='color:blue; font-weight:bold; font-size:18px'>✓ All columns already exist! Schema is in sync.</p>";
    } else {
        if (!empty($added)) {
            echo "<p style='color:green; font-weight:bold; font-size:18px'>✓ Successfully added " . count($added) . " column(s)!</p>";
            echo "<ul>";
            foreach ($added as $col) {
                echo "<li><b>$col</b></li>";
            }
            echo "</ul>";
        }
        
        if (!empty($errors)) {
            echo "<p style='color:red; font-weight:bold'>⚠ " . count($errors) . " column(s) had errors</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2>Verification:</h2>";
    $finalCols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Total columns now: <b>" . count($finalCols) . "</b></p>";
    
    echo "<hr>";
    echo "<p><a href='products.php'>Go to Products</a> | <a href='add_product.php'>Add New Product</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
