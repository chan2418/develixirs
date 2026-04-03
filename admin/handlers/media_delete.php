<?php
// admin/handlers/media_delete.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$mediaId = $_POST['media_id'] ?? '';
$permanent = isset($_POST['permanent']) && $_POST['permanent'] === 'true';

if (!$mediaId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Media ID required']);
    exit;
}

try {
    if ($permanent) {
        // Permanent delete - remove file and DB record
        $stmt = $pdo->prepare("SELECT storage_path, thumb_url FROM media_files WHERE id = ?");
        $stmt->execute([$mediaId]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($media) {
            // Delete files
            $filePath = __DIR__ . '/../../' . $media['storage_path'];
            if (file_exists($filePath)) unlink($filePath);

            if ($media['thumb_url']) {
                $thumbPath = __DIR__ . '/../../' . ltrim($media['thumb_url'], '/');
                if (file_exists($thumbPath)) unlink($thumbPath);
            }

            // Delete DB record (cascades to tags, variants, etc)
            $pdo->prepare("DELETE FROM media_files WHERE id = ?")->execute([$mediaId]);
        }

        echo json_encode(['success' => true, 'message' => 'Media permanently deleted']);

    } else {
        // Soft delete - move to trash
        $stmt = $pdo->prepare("
            UPDATE media_files 
            SET deleted_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$mediaId]);

        echo json_encode(['success' => true, 'message' => 'Media moved to trash']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
