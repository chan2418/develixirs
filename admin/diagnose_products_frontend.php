<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Product Visibility Diagnostic</h1>";

// 1. Check Table Structure
echo "<h2>1. Table Structure: 'products'</h2>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasIsActive = false;
    $hasCatId = false;
    $hasCategoryId = false;
    
    foreach ($cols as $c) {
        if ($c['Field'] === 'is_active') $hasIsActive = true;
        if ($c['Field'] === 'cat_id') $hasCatId = true;
        if ($c['Field'] === 'category_id') $hasCategoryId = true;
        
        echo "<tr>";
        foreach ($c as $v) echo "<td>" . htmlspecialchars($v) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Analysis:</h3>";
    echo "<ul>";
    echo "<li>Column <b>is_active</b>: " . ($hasIsActive ? "<span style='color:green'>EXISTS</span>" : "<span style='color:red'>MISSING</span>") . "</li>";
    echo "<li>Column <b>cat_id</b>: " . ($hasCatId ? "<span style='color:green'>EXISTS</span>" : "<span style='color:red'>MISSING</span>") . "</li>";
    echo "<li>Column <b>category_id</b>: " . ($hasCategoryId ? "<span style='color:orange'>EXISTS (Old Schema?)</span>" : "<span style='color:gray'>Not Present</span>") . "</li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color:red'>Error reading structure: " . $e->getMessage() . "</p>";
}

// 2. Check Data Content
echo "<h2>2. Data Content (First 10 Rows)</h2>";
try {
    $stmt = $pdo->query("SELECT id, name, price, stock, is_active, cat_id FROM products LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "<p style='color:red'>Table is EMPTY.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>is_active</th><th>cat_id</th></tr>";
        foreach ($rows as $r) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['id']) . "</td>";
            echo "<td>" . htmlspecialchars($r['name']) . "</td>";
            echo "<td>" . htmlspecialchars($r['price']) . "</td>";
            echo "<td>" . htmlspecialchars($r['stock']) . "</td>";
            echo "<td>" . htmlspecialchars($r['is_active'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($r['cat_id'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error reading data: " . $e->getMessage() . "</p>";
}

// 3. Test Frontend Query Logic
echo "<h2>3. Frontend Query Simulation</h2>";
try {
    // Simulate the exact WHERE clause from product.php
    $whereSql = "WHERE (is_active = 1 OR is_active IS NULL)";
    
    // Check count
    $sql = "SELECT COUNT(*) FROM products $whereSql";
    echo "<p><b>Query:</b> $sql</p>";
    
    $count = $pdo->query($sql)->fetchColumn();
    echo "<p><b>Result Count:</b> " . $count . "</p>";
    
    if ($count == 0) {
        echo "<p style='color:red'><b>PROBLEM FOUND:</b> The query returns 0 products. This is why the frontend is empty.</p>";
        
        // Check if it's because of is_active
        $total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        echo "<p>Total products in table (ignoring filters): <b>$total</b></p>";
        
        if ($total > 0) {
            echo "<p style='color:orange'>It seems products exist but are filtered out. Likely 'is_active' is 0 or something else.</p>";
        }
    } else {
        echo "<p style='color:green'>Query returns results. Frontend should work if code matches.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>Query Error: " . $e->getMessage() . "</p>";
}
