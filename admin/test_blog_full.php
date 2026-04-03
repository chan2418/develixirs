<?php
// admin/test_blog_full.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../includes/db.php';

echo "<h1>Strong Debug: Blog Frontend Logic</h1>";
echo "Timezone: " . date_default_timezone_get() . "<br>";
echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";

$categoryId = isset($_GET['cat']) ? (int)$_GET['cat'] : null;
echo "Category Filter: " . ($categoryId ? $categoryId : "None") . "<br><br>";

// 1. TEST MAIN QUERY WITH JOIN
echo "<h2>1. Testing Main Blog Query (with JOIN)</h2>";
$sql = "SELECT b.id, b.title, b.slug, b.is_published, b.published_at, c.title as category_name 
        FROM blogs b
        LEFT JOIN blog_categories c ON b.blog_category_id = c.id
        WHERE b.is_published = 1 AND (b.published_at IS NULL OR b.published_at <= :current_time)";

$params = [':current_time' => date('Y-m-d H:i:s')];

if ($categoryId) {
    $sql .= " AND b.blog_category_id = :cat_id";
    $params[':cat_id'] = $categoryId;
}

$sql .= " ORDER BY b.created_at DESC LIMIT 9";

echo "<strong>SQL:</strong> <pre>$sql</pre>";
echo "<strong>Params:</strong> "; print_r($params); echo "<br>";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Result: " . count($blogs) . " rows found</h3>";
    if (count($blogs) > 0) {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Title</th><th>Category</th><th>Published At</th></tr>";
        foreach ($blogs as $b) {
            echo "<tr>";
            echo "<td>{$b['id']}</td>";
            echo "<td>{$b['title']}</td>";
            echo "<td>{$b['category_name']} (If empty, JOIN failed)</td>";
            echo "<td>{$b['published_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>No rows returned. This is why you see 'No stories found'.</p>";
    }

} catch (PDOException $e) {
    echo "<h3 style='color:red; background: #ffe6e6; padding: 10px; border: 1px solid red;'>SQL ERROR: " . $e->getMessage() . "</h3>";
    echo "<p><strong>Possible Cause:</strong> 'blog_categories' table missing or 'blog_category_id' column missing in blogs table.</p>";
}

// 2. TEST BLOG CATEGORIES TABLE
echo "<h2>2. Testing 'blog_categories' Table Access</h2>";
try {
    $stmtCats = $pdo->query("SELECT id, title FROM blog_categories LIMIT 5");
    $cats = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
    echo "Access OK. Found " . count($cats) . " categories.<br>";
    print_r($cats);
} catch (PDOException $e) {
    echo "<h3 style='color:red'>Error accessing blog_categories: " . $e->getMessage() . "</h3>";
}

// 3. CHECK RAW BLOGS DATA
echo "<h2>3. Raw Blogs Data (First 5)</h2>";
try {
    // Check if column exists
    $stmt = $pdo->query("SELECT id, title, blog_category_id FROM blogs LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>ID</th><th>Title</th><th>blog_category_id</th></tr>";
    foreach($rows as $r) {
        echo "<tr><td>{$r['id']}</td><td>{$r['title']}</td><td>{$r['blog_category_id']}</td></tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<h3 style='color:red'>Error reading raw blogs: " . $e->getMessage() . "</h3>";
}
?>
