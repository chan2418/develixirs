<?php
// admin/handlers/media_restore_from_trash.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$mediaIds = json_decode($_POST['media_ids'] ?? '[]', true);

if (empty($mediaIds) || !is_array($mediaIds)) {
    echo json_encode(['success' => false, 'error' => 'No media IDs provided']);
    exit;
}

try {
    $restored = 0;
    
    foreach ($mediaIds as $mediaId) {
        $stmt = $pdo->prepare("UPDATE media_files SET deleted_at = NULL WHERE id = ?");
        $stmt->execute([$mediaId]);
        if ($stmt->rowCount() > 0) $restored++;
    }

    echo json_encode([
        'success' => true,
        'message' => "Restored $restored item(s)",
        'restored' => $restored
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
