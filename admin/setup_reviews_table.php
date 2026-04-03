<?php
// admin/setup_reviews_table.php
// REPAIR SCRIPT: Adds missing columns to existing table
require_once __DIR__ . '/../includes/db.php';

echo "<h2>Fixing Product Reviews Table...</h2>";

try {
    // Ensure table exists first
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 1. Add 'title' column if missing
    try {
        $pdo->query("SELECT title FROM product_reviews LIMIT 1");
        echo "✅ Column 'title' already exists.<br>";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE product_reviews ADD COLUMN title VARCHAR(255) NULL AFTER rating");
        echo "🛠️ Added column 'title'.<br>";
    }

    // 2. Add 'is_featured' column if missing
    try {
        $pdo->query("SELECT is_featured FROM product_reviews LIMIT 1");
        echo "✅ Column 'is_featured' already exists.<br>";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE product_reviews ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER status");
        echo "🛠️ Added column 'is_featured'.<br>";
    }

    // 3. Add 'admin_note' column if missing
    try {
        $pdo->query("SELECT admin_note FROM product_reviews LIMIT 1");
        echo "✅ Column 'admin_note' already exists.<br>";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE product_reviews ADD COLUMN admin_note TEXT NULL AFTER is_featured");
        echo "🛠️ Added column 'admin_note'.<br>";
    }

    // 4. Ensure 'comment' column exists (renaming from review_text if needed, or adding)
    try {
        $pdo->query("SELECT comment FROM product_reviews LIMIT 1");
        echo "✅ Column 'comment' already exists.<br>";
    } catch (Exception $e) {
        // Check if review_text exists to rename it
        try {
            $pdo->query("SELECT review_text FROM product_reviews LIMIT 1");
            $pdo->exec("ALTER TABLE product_reviews CHANGE COLUMN review_text comment TEXT NULL");
            echo "🛠️ Renamed 'review_text' to 'comment'.<br>";
        } catch (Exception $e2) {
            $pdo->exec("ALTER TABLE product_reviews ADD COLUMN comment TEXT NULL AFTER title");
            echo "🛠️ Added column 'comment'.<br>";
        }
    }
    
    // 5. Ensure 'rating' exists
    try {
        $pdo->query("SELECT rating FROM product_reviews LIMIT 1");
        echo "✅ Column 'rating' already exists.<br>";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE product_reviews ADD COLUMN rating INT NOT NULL DEFAULT 5");
        echo "🛠️ Added column 'rating'.<br>";
    }

    // 6. Ensure 'status' exists
    try {
        $pdo->query("SELECT status FROM product_reviews LIMIT 1");
       echo "✅ Column 'status' already exists.<br>";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE product_reviews ADD COLUMN status ENUM('pending','approved','hidden','spam') DEFAULT 'pending'");
        echo "🛠️ Added column 'status'.<br>";
    }

    // 7. Ensure 'reviewer_name' exists
    try {
        $pdo->query("SELECT reviewer_name FROM product_reviews LIMIT 1");
       echo "✅ Column 'reviewer_name' already exists.<br>";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE product_reviews ADD COLUMN reviewer_name VARCHAR(255) NULL");
        echo "🛠️ Added column 'reviewer_name'.<br>";
    }


    echo "<hr><h3>🎉 Database Schema Repaired!</h3>";
    echo "<p>Please delete this file (<code>admin/setup_reviews_table.php</code>) from your server after you see this message.</p>";
    echo "<p><a href='product_reviews.php'>Go back to Product Reviews</a></p>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
?>
