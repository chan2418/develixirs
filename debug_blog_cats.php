<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Debug: Blogs and Categories</h2>";

try {
    // 1. Show all Categories
    echo "<h3>Categories:</h3>";
    $stmt = $pdo->query("SELECT id, title FROM categories");
    $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($cats, true) . "</pre>";

    // 2. Show all Blogs
    echo "<h3>Blogs:</h3>";
    $stmt = $pdo->query("SELECT id, title, category_id, is_published FROM blogs");
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($blogs, true) . "</pre>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
