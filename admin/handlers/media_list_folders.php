<?php
// admin/handlers/media_list_folders.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$parentId = $_GET['parent_id'] ?? '';

try {
    if ($parentId) {
        $whereClause = "parent_id = ?";
        $params = [$parentId];
    } else {
        $whereClause = "(parent_id IS NULL OR parent_id = '')";
        $params = [];
    }
    
    $stmt = $pdo->prepare("
        SELECT id, name, created_at,
               (SELECT COUNT(*) FROM media_files WHERE folder_id = media_folders.id) as file_count
        FROM media_folders 
        WHERE $whereClause
        ORDER BY name ASC
    ");
    
    $stmt->execute($params);
    
    $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'folders' => $folders
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
