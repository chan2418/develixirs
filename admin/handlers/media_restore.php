<?php
// admin/handlers/media_restore.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$mediaId = $_POST['media_id'] ?? '';

if (!$mediaId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Media ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE media_files 
        SET deleted_at = NULL 
        WHERE id = ? AND deleted_at IS NOT NULL
    ");
    $stmt->execute([$mediaId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Media restored successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Media not found in trash']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
