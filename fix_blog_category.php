<?php
// fix_blog_category.php
// Run this to fix the specific "Men Care" blog post
require_once __DIR__ . '/includes/db.php';

try {
    // 1. Get "Men Care" category ID
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE title LIKE :title LIMIT 1");
    $stmt->execute([':title' => '%Men Care%']);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cat) {
        die("❌ Error: Could not find 'Men Care' category.");
    }

    $catId = $cat['id'];
    echo "✓ Found 'Men Care' category ID: $catId<br>";

    // 2. Update the blog post
    // We'll update ALL posts with "Men" in the title to this category for now, or just the one with ID 3
    $stmt = $pdo->prepare("UPDATE blogs SET category_id = :cat_id WHERE title LIKE :title OR id = 3");
    $stmt->execute([
        ':cat_id' => $catId,
        ':title' => '%Men care%'
    ]);

    echo "✅ SUCCESS: Updated blog posts to have category 'Men Care'.<br>";
    echo "Go to <a href='blog.php'>blog.php</a> and click 'Men Care' in the sidebar to test.";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
