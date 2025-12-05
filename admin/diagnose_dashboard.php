<?php
/**
 * Dashboard Diagnostic Script
 * Tests every query used in dashboard.php to find the crash
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db.php';

echo "<h1>📊 Dashboard Diagnostic</h1>";
echo "<p>Testing dashboard queries one by one...</p>";

function testQuery($name, $pdo, $sql, $params = []) {
    echo "<div style='margin-bottom:10px; padding:10px; border:1px solid #ddd;'>";
    echo "<strong>Testing: $name</strong><br>";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<span style='color:green'>✅ OK</span> (Rows: " . count($res) . ")";
    } catch (PDOException $e) {
        echo "<span style='color:red'>❌ FAILED</span><br>";
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
    echo "</div>";
}

// 1. Check Tables
echo "<h2>1. Table Check</h2>";
$tables = ['orders', 'order_items', 'products', 'users', 'categories'];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $t");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✅ Table <code>$t</code> exists. Columns: " . implode(', ', $cols) . "<br>";
    } catch (PDOException $e) {
        echo "❌ Table <code>$t</code> MISSING or error: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>2. Query Tests</h2>";

// Query 1: Today's Revenue
testQuery("Today's Revenue", $pdo, 
    "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS revenue FROM orders WHERE DATE(created_at) = CURDATE()"
);

// Query 2: Month Revenue
testQuery("Month Revenue", $pdo, 
    "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS revenue FROM orders WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"
);

// Query 3: Totals
testQuery("Total Products", $pdo, "SELECT COUNT(*) FROM products");
testQuery("Total Orders", $pdo, "SELECT COUNT(*) FROM orders");
testQuery("Total Users", $pdo, "SELECT COUNT(*) FROM users");
testQuery("Total Categories", $pdo, "SELECT COUNT(*) FROM categories");

// Query 4: Pending Orders
testQuery("Pending Orders", $pdo, 
    "SELECT COUNT(*) FROM orders WHERE order_status='pending' OR payment_status='pending'"
);

// Query 5: Sales Last 30 Days
testQuery("Sales Chart Data", $pdo, "
    SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount),0) AS revenue
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");

// Query 6: Low Stock
testQuery("Low Stock", $pdo, 
    "SELECT id, name, sku, stock FROM products WHERE stock <= ? ORDER BY stock ASC LIMIT 8", 
    [5]
);

// Query 7: Best Sellers
testQuery("Best Sellers", $pdo, "
  SELECT p.id, p.name, p.sku, COALESCE(SUM(oi.qty),0) AS sold
  FROM order_items oi
  LEFT JOIN products p ON oi.product_id = p.id
  GROUP BY p.id
  ORDER BY sold DESC
  LIMIT 8
");

// Query 8: Recent Customers
testQuery("Recent Customers", $pdo, 
    "SELECT id, name, email, created_at, (SELECT COUNT(*) FROM orders o WHERE o.user_id = users.id) AS orders_count FROM users ORDER BY created_at DESC LIMIT 8"
);

// Query 9: Recent Orders
testQuery("Recent Orders", $pdo, 
    "SELECT id, order_number, customer_name, total_amount, payment_status, order_status, created_at FROM orders ORDER BY created_at DESC LIMIT 8"
);

echo "<hr><p>Diagnostic Complete.</p>";
?>
