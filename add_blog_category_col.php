<?php
// add_blog_category_col.php
require_once __DIR__ . '/includes/db.php';

try {
    // Add category_id column
    $pdo->exec("
        ALTER TABLE blogs 
        ADD COLUMN category_id INT UNSIGNED DEFAULT NULL AFTER author,
        ADD INDEX (category_id)
    ");
    echo "✅ Added category_id column to blogs table";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "ℹ️ Column category_id already exists";
    } else {
        echo "❌ Error: " . $e->getMessage();
    }
}
