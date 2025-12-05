#!/bin/bash
# Export local database schema and create Hostinger sync script

echo "Exporting local database schema..."

# Connect to Docker MySQL and export only the structure
docker exec -i $(docker ps | grep mysql | awk '{print $1}') mysqldump \
  -u develixirs_user \
  -pdevelixirs_pass \
  --no-data \
  --skip-add-drop-table \
  --skip-comments \
  develixirs_db > local_schema.sql

echo "✓ Schema exported to local_schema.sql"

# Create a PHP script to sync the schema to Hostinger
cat > admin/sync_schema_to_hostinger.php << 'EOF'
<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Sync Local Schema to Hostinger</h1>";
echo "<p>This will add any missing columns from your local database to Hostinger.</p>";
echo "<hr>";

// Define the columns that should exist in products table (from your local DB)
$requiredColumns = [
    'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
    'name' => 'varchar(255) NOT NULL',
    'slug' => 'varchar(255) NULL UNIQUE',
    'cat_id' => 'int(11) NULL',
    'description' => 'longtext NULL',
    'short_description' => 'text NULL',
    'ingredients' => 'text NULL',
    'how_to_use' => 'text NULL',
    'price' => 'decimal(10,2) NULL DEFAULT 0.00',
    'compare_price' => 'decimal(10,2) NULL',
    'discount_percent' => 'decimal(5,2) NULL',
    'sku' => 'varchar(100) NULL UNIQUE',
    'stock' => 'int(11) NULL DEFAULT 0',
    'images' => 'text NULL',
    'rating' => 'decimal(3,2) NULL DEFAULT 0.00',
    'rating_count' => 'int(11) NULL DEFAULT 0',
    'is_featured' => 'tinyint(1) NULL DEFAULT 0',
    'is_bestseller' => 'tinyint(1) NULL DEFAULT 0',
    'is_new' => 'tinyint(1) NULL DEFAULT 0',
    'tax_rate' => 'decimal(5,2) NULL DEFAULT 0.00',
    'hsn_code' => 'varchar(20) NULL',
    'created_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
    'updated_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'is_active' => 'tinyint(1) NULL DEFAULT 1',
    'meta_title' => 'varchar(255) NULL',
    'meta_description' => 'text NULL',
    'variant_label' => 'varchar(50) NULL DEFAULT "Size"',
    'main_variant_name' => 'varchar(100) NULL',
    'category_name' => 'varchar(255) NULL',
    'parent_category_id' => 'int(11) NULL',
    'gst_rate' => 'decimal(5,2) NULL DEFAULT 0.00',
    'product_media' => 'text NULL'
];

try {
    // Get current columns
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Current Columns:</h2>";
    echo "<p>" . implode(', ', $existing) . "</p>";
    
    echo "<h2>Adding Missing Columns:</h2>";
    
    $added = [];
    foreach ($requiredColumns as $colName => $colDef) {
        if (!in_array($colName, $existing)) {
            try {
                $sql = "ALTER TABLE products ADD COLUMN $colName $colDef";
                $pdo->exec($sql);
                echo "<p style='color:green'>✓ Added: <b>$colName</b></p>";
                $added[] = $colName;
            } catch (PDOException $e) {
                echo "<p style='color:orange'>⚠ Could not add $colName: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    if (empty($added)) {
        echo "<p style='color:blue; font-weight:bold'>All columns already exist! Schema is in sync.</p>";
    } else {
        echo "<hr>";
        echo "<p style='color:green; font-weight:bold; font-size:18px'>✓ Successfully added " . count($added) . " columns!</p>";
        echo "<ul>";
        foreach ($added as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<p><a href='products.php'>Go to Products</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
EOF

echo "✓ Created admin/sync_schema_to_hostinger.php"
echo ""
echo "NEXT STEPS:"
echo "1. Upload the file: admin/sync_schema_to_hostinger.php"
echo "2. Run it: https://newv2.develixirs.com/admin/sync_schema_to_hostinger.php"
echo ""
