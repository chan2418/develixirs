<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix NULL cat_id for Products</h1>";

// 1. Show all categories
echo "<h2>Available Categories:</h2>";
try {
    $cats = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id, name")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
    echo "<tr><th>ID</th><th>Name</th><th>Parent ID</th></tr>";
    foreach ($cats as $c) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($c['id']) . "</td>";
        echo "<td>" . htmlspecialchars($c['name']) . "</td>";
        echo "<td>" . htmlspecialchars($c['parent_id'] ?? 'NULL (Top Level)') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error fetching categories: " . $e->getMessage() . "</p>";
}

// 2. Show products with NULL cat_id
echo "<h2>Products with NULL cat_id:</h2>";
try {
    $nullProducts = $pdo->query("SELECT id, name FROM products WHERE cat_id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($nullProducts)) {
        echo "<p style='color:green'>No products with NULL cat_id found!</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>Product ID</th><th>Product Name</th></tr>";
        foreach ($nullProducts as $p) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($p['id']) . "</td>";
            echo "<td>" . htmlspecialchars($p['name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 3. FIX: Assign all NULL cat_id products to the first category
echo "<h2>Auto-Fix: Assign to First Category</h2>";
if (!empty($cats)) {
    $firstCatId = $cats[0]['id'];
    $firstCatName = $cats[0]['name'];
    
    echo "<p>This will assign all products with NULL cat_id to category: <b>$firstCatName (ID: $firstCatId)</b></p>";
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET cat_id = ? WHERE cat_id IS NULL");
        $stmt->execute([$firstCatId]);
        $affectedRows = $stmt->rowCount();
        
        echo "<p style='color:green; font-weight:bold'>✓ Updated $affectedRows product(s)!</p>";
        
        // Show updated products
        echo "<h3>Verification:</h3>";
        $updated = $pdo->query("SELECT id, name, cat_id FROM products LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Name</th><th>cat_id</th></tr>";
        foreach ($updated as $u) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($u['id']) . "</td>";
            echo "<td>" . htmlspecialchars($u['name']) . "</td>";
            echo "<td>" . htmlspecialchars($u['cat_id'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<hr><p><b>Done! Now check your products page.</b></p>";
        echo "<p><a href='/product.php'>Visit Products Page</a> | <a href='products.php'>Admin Products</a></p>";
        
    } catch (Exception $e) {
        echo "<p style='color:red'>Error updating: " . $e->getMessage() . "</p>";
    }
}
