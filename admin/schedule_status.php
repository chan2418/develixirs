<?php
// schedule_status.php - Upload to admin/ folder and run
require_once __DIR__ . '/../includes/db.php';
date_default_timezone_set('Asia/Kolkata');

echo "<h1>Debugging Blog Scheduling</h1>";

// 1. Check Timezone
echo "<h2>1. Timezone Settings</h2>";
echo "Server Time (PHP time()): " . date('Y-m-d H:i:s') . "<br>";
echo "Default Timezone: " . date_default_timezone_get() . "<br>";

// 2. Check Database Column
echo "<h2>2. Database Structure</h2>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM blogs LIKE 'published_at'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($column) {
        echo "<span style='color:green'>✅ Column 'published_at' EXISTS.</span><br>";
        echo "Type: " . $column['Type'] . "<br>";
    } else {
        echo "<span style='color:red'>❌ Column 'published_at' MISSING!</span><br>";
        echo "<strong>Fix:</strong> Please run the `admin/migrations/add_published_at_to_blogs.sql` script.<br>";
    }
} catch (PDOException $e) {
    echo "Error checking column: " . $e->getMessage();
}

// 3. Check Recent Posts
echo "<h2>3. Recent 5 Posts Status</h2>";
try {
    // Try to select published_at. If it fails, we know why.
    $stmt = $pdo->query("SELECT id, title, is_published, published_at FROM blogs ORDER BY id DESC LIMIT 5");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Title</th><th>Published?</th><th>Published At</th><th>Server Time</th><th>Status Logic</th></tr>";
    
    foreach ($posts as $p) {
        $isPub = $p['is_published'];
        $pubAt = $p['published_at'];
        $serverTime = time();
        $pubTime = $pubAt ? strtotime($pubAt) : 0;
        
        $status = "Draft";
        if ($isPub) {
            if ($pubAt && $pubTime > $serverTime) {
                $status = "Scheduled (Future)";
            } elseif ($pubAt) {
                $status = "Published (Past Date)";
            } else {
                $status = "Published (No Date)";
            }
        }
        
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['title']}</td>";
        echo "<td>{$isPub}</td>";
        echo "<td>{$pubAt}</td>";
        echo "<td>" . date('Y-m-d H:i:s') . "</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<span style='color:red'>Error fetching posts (likely missing column): " . $e->getMessage() . "</span>";
}
?>
