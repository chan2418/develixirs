<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database config
define('DB_HOST', 'localhost');
define('DB_NAME', 'u295126515_develixirs_26');
define('DB_USER', 'u295126515_admin');
define('DB_PASS', 'n@C7SoV4B|');

echo "<!DOCTYPE html><html><head><title>Wide Banner Cleanup</title></head><body>";
echo "<h1>Wide Banner Cleanup Script</h1>";

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch all home_center banners
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE page_slot = 'home_center'");
    $stmt->execute();
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Found " . count($banners) . " banner(s) in 'home_center' slot:</h2>";
    echo "<ul>";
    foreach ($banners as $b) {
        echo "<li>ID: {$b['id']}, Filename: {$b['filename']}, Active: {$b['is_active']}</li>";
    }
    echo "</ul>";
    
    if (count($banners) > 0) {
        // Delete all
        $deleteStmt = $pdo->prepare("DELETE FROM banners WHERE page_slot = 'home_center'");
        $deleteStmt->execute();
        
        echo "<h2 style='color:green;'>✓ Successfully deleted all " . count($banners) . " banner(s) from database.</h2>";
        
        // Try to delete physical files too
        echo "<h3>File deletion status:</h3>";
        echo "<ul>";
        foreach ($banners as $b) {
            if (!empty($b['filename'])) {
                $filePath = __DIR__ . '/assets/uploads/banners/' . ltrim($b['filename'], '/');
                if (file_exists($filePath)) {
                    if (unlink($filePath)) {
                        echo "<li style='color:green;'>✓ Deleted file: {$b['filename']}</li>";
                    } else {
                        echo "<li style='color:orange;'>⚠ Could not delete file: {$b['filename']}</li>";
                    }
                } else {
                    echo "<li style='color:gray;'>File not found (already deleted): {$b['filename']}</li>";
                }
            }
        }
        echo "</ul>";
        
        echo "<hr>";
        echo "<h2 style='color:blue;'>Next Steps:</h2>";
        echo "<ol>";
        echo "<li>Go to <strong>Admin Panel > Appearance > Banner</strong></li>";
        echo "<li>Make sure you're on the <strong>Home Page</strong> tab</li>";
        echo "<li>Click on the <strong>Center Carousel</strong> sub-tab</li>";
        echo "<li>Upload your <strong>one correct Wide Banner image</strong></li>";
        echo "<li>Check the homepage - you should now see only one banner without any empty slides</li>";
        echo "</ol>";
        
        echo "<p><a href='index.php' style='padding:10px 20px; background:#2563eb; color:white; text-decoration:none; border-radius:6px; display:inline-block; margin-top:20px;'>Check Homepage</a></p>";
    } else {
        echo "<p>No banners to delete.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}

echo "</body></html>";
