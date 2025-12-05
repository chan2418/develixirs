<?php
/**
 * Database Schema Fixer
 * Adds missing 'updated_at' column to users table
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db.php';

echo "<h1>🛠️ Database Schema Fixer</h1>";

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'updated_at'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<div style='color:green; background:#ecfdf5; padding:15px; border-radius:8px;'>
            ✅ The <code>updated_at</code> column already exists. No changes needed.
        </div>";
    } else {
        echo "<div>Attempting to add <code>updated_at</code> column...</div>";
        
        // Add the column
        $sql = "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL AFTER created_at";
        $pdo->exec($sql);
        
        echo "<div style='color:green; background:#ecfdf5; padding:15px; border-radius:8px; margin-top:10px;'>
            ✅ SUCCESS: Added <code>updated_at</code> column to users table!
        </div>";
    }
    
    echo "<div style='margin-top:20px;'>
        <h3>Next Steps:</h3>
        <ol>
            <li><a href='reset_admin_password.php'>Run Password Reset Script</a></li>
            <li><a href='login.php'>Login to Admin Panel</a></li>
        </ol>
    </div>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; background:#fff1f2; padding:15px; border-radius:8px;'>
        ❌ Error: " . htmlspecialchars($e->getMessage()) . "
    </div>";
}
?>
