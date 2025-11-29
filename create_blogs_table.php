<?php
// create_blogs_table.php
// Run this file ONCE in your browser: http://localhost/create_blogs_table.php
// After success, you can delete this file

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Blogs Table...</h2>";

require_once __DIR__ . '/includes/db.php';

try {
    // Check connection
    echo "✓ Database connection successful<br>";
    echo "→ Connected to: " . DB_NAME . " at " . DB_HOST . ":" . DB_PORT . "<br><br>";
    
    // Drop table if exists (clean start)
    $pdo->exec("DROP TABLE IF EXISTS `blogs`");
    echo "✓ Cleaned up old table (if any)<br>";
    
    // Create table
    $sql = "
    CREATE TABLE `blogs` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `title` VARCHAR(255) NOT NULL,
      `slug` VARCHAR(255) NOT NULL,
      `meta_title` VARCHAR(255) DEFAULT NULL,
      `meta_description` VARCHAR(500) DEFAULT NULL,
      `content` MEDIUMTEXT,
      `author` VARCHAR(150) DEFAULT NULL,
      `featured_image` VARCHAR(255) DEFAULT NULL,
      `is_published` TINYINT(1) DEFAULT 1,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      INDEX (`slug`),
      INDEX (`is_published`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
    
    echo "✓ Blogs table created successfully<br><br>";
    
    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'blogs'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;border-radius:5px;margin:20px 0;'>";
        echo "<strong>✅ SUCCESS!</strong><br><br>";
        echo "The blogs table has been created in database: <strong>" . DB_NAME . "</strong><br><br>";
        echo "<strong>Next steps:</strong><br>";
        echo "1. Go to <a href='/admin/blogs.php' style='color:#0056b3;font-weight:bold;'>/admin/blogs.php</a> to manage blog posts<br>";
        echo "2. View published blogs at <a href='/blog.php' style='color:#0056b3;font-weight:bold;'>/blog.php</a><br><br>";
        echo "<em style='color:#856404;'>You can now delete this file (create_blogs_table.php)</em>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<strong>❌ ERROR:</strong><br><br>";
    echo "Message: " . $e->getMessage() . "<br><br>";
    echo "Check your database configuration in: <code>includes/db.php</code>";
    echo "</div>";
}
?>
