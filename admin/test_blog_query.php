<?php
// admin/test_blog_query.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kolkata'); // Same as blog.php

require_once __DIR__ . '/../includes/db.php';

echo "<h1>Debug Blog Frontend</h1>";
echo "Current PHP Time (Asia/Kolkata): " . date('Y-m-d H:i:s') . "<br>";

$sql = "SELECT b.id, b.title, b.is_published, b.published_at 
        FROM blogs b
        WHERE b.is_published = 1 AND (b.published_at IS NULL OR b.published_at <= :current_time)";

echo "Query: <pre>$sql</pre>";

try {
    $stmt = $pdo->prepare($sql);
    $params = [':current_time' => date('Y-m-d H:i:s')];
    echo "Params: "; print_r($params); echo "<br>";
    
    $stmt->execute($params);
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Results (" . count($blogs) . " match condition)</h2>";
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Title</th><th>Published?</th><th>Published At</th></tr>";
    
    foreach ($blogs as $b) {
        echo "<tr>";
        echo "<td>{$b['id']}</td>";
        echo "<td>{$b['title']}</td>";
        echo "<td>{$b['is_published']}</td>";
        echo "<td>{$b['published_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Also show what failed
    echo "<h2>All Posts (Debugging which ones failed)</h2>";
    $all = $pdo->query("SELECT id, title, is_published, published_at FROM blogs")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Title</th><th>Published?</th><th>Published At</th><th>Would Show?</th></tr>";
    foreach ($all as $b) {
        $p_at = $b['published_at'];
        $now = date('Y-m-d H:i:s');
        $show = ($b['is_published'] == 1 && ($p_at === null || $p_at <= $now)) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>";
        echo "<tr>";
        echo "<td>{$b['id']}</td>";
        echo "<td>{$b['title']}</td>";
        echo "<td>{$b['is_published']}</td>";
        echo "<td>{$b['published_at']}</td>";
        echo "<td>$show</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>SQL Error: " . $e->getMessage() . "</h2>";
}
?>
