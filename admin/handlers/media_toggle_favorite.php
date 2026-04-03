<?php
// admin/handlers/media_toggle_favorite.php
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
    // Get current favorite status of first item to determine action
    $stmt = $pdo->prepare("SELECT is_favorite FROM media_files WHERE id = ?");
    $stmt->execute([$mediaIds[0]]);
    $currentStatus = $stmt->fetchColumn();
    
    // Toggle: if currently favorite (1), set to 0; if not (0), set to 1
    $newStatus = $currentStatus ? 0 : 1;
    
    $updated = 0;
    foreach ($mediaIds as $mediaId) {
        $stmt = $pdo->prepare("UPDATE media_files SET is_favorite = ? WHERE id = ?");
        $stmt->execute([$newStatus, $mediaId]);
        if ($stmt->rowCount() > 0) $updated++;
    }

    $action = $newStatus ? 'added to' : 'removed from';
    
    echo json_encode([
        'success' => true,
        'message' => "$updated item(s) $action favorites",
        'is_favorite' => $newStatus,
        'updated' => $updated
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
