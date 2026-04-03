<?php
// admin/handlers/media_bulk_delete.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$mediaIds = json_decode($_POST['media_ids'] ?? '[]', true);
$permanent = isset($_POST['permanent']) && $_POST['permanent'] === 'true';

if (empty($mediaIds) || !is_array($mediaIds)) {
    echo json_encode(['success' => false, 'error' => 'No media IDs provided']);
    exit;
}

try {
    $deleted = 0;
    
    foreach ($mediaIds as $mediaId) {
        if ($permanent) {
            // Permanent delete
            $stmt = $pdo->prepare("SELECT storage_path, thumb_url FROM media_files WHERE id = ?");
            $stmt->execute([$mediaId]);
            $media = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($media) {
                // Delete files
                $filePath = __DIR__ . '/../../' . $media['storage_path'];
                if (file_exists($filePath)) @unlink($filePath);

                if ($media['thumb_url']) {
                    $thumbPath = __DIR__ . '/../../' . ltrim($media['thumb_url'], '/');
                    if (file_exists($thumbPath)) @unlink($thumbPath);
                }

                // Delete DB record
                $pdo->prepare("DELETE FROM media_files WHERE id = ?")->execute([$mediaId]);
                $deleted++;
            }
        } else {
            // Soft delete
            $stmt = $pdo->prepare("UPDATE media_files SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$mediaId]);
            if ($stmt->rowCount() > 0) $deleted++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Deleted $deleted item(s)",
        'deleted' => $deleted
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
