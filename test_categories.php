<?php
// test_categories.php - Standalone test
require_once __DIR__ . '/includes/db.php';

echo '<h1>Category Test</h1>';
echo '<pre>';

try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE parent_id = 0 OR parent_id IS NULL ORDER BY id ASC");
    $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($cats) . " categories:\n\n";
    
    foreach ($cats as $cat) {
        echo "ID: " . $cat['id'] . "\n";
        echo "Name: " . ($cat['name'] ?? $cat['title']) . "\n";
        echo "Image: " . ($cat['image'] ?? 'NULL') . "\n";
        echo "---\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

echo '</pre>';
