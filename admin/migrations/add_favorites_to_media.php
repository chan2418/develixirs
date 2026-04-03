<?php
// admin/migrations/add_favorites_to_media.php
// Run this ONCE to add favorites column to media_files table

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM media_files LIKE 'is_favorite'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add is_favorite column to media_files
        $pdo->exec("
            ALTER TABLE media_files 
            ADD COLUMN is_favorite TINYINT(1) DEFAULT 0 AFTER description
        ");
        
        echo json_encode([
            'success' => true,
            'message' => 'Favorites column added successfully! 🎉'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Favorites column already exists ✓'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
