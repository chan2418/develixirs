<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing 'tags' table (adding is_active, created_at)...</h2>";

try {
    // Check if is_active exists
    $stmt = $pdo->query("SHOW COLUMNS FROM tags LIKE 'is_active'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>Column 'is_active' already exists.</p>";
    } else {
        $pdo->exec("ALTER TABLE tags ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        echo "<p style='color:green'>Success: Column 'is_active' added.</p>";
    }

    // Check if created_at exists
    $stmt = $pdo->query("SHOW COLUMNS FROM tags LIKE 'created_at'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>Column 'created_at' already exists.</p>";
    } else {
        $pdo->exec("ALTER TABLE tags ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "<p style='color:green'>Success: Column 'created_at' added.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='tags.php'>Go back to Tags Page</a></p>";
