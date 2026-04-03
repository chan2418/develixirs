<?php
// admin/handlers/media_rename.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$mediaId = $_POST['media_id'] ?? '';
$newFilename = $_POST['filename'] ?? '';

if (!$mediaId || !$newFilename) {
    echo json_encode(['success' => false, 'error' => 'Media ID and filename required']);
    exit;
}

try {
    // Get current media info
    $stmt = $pdo->prepare("SELECT * FROM media_files WHERE id = ?");
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$media) {
        throw new Exception('Media not found');
    }

    // Clean filename
    $newFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $newFilename);
    
    // Keep the same extension
    $oldExt = pathinfo($media['filename'], PATHINFO_EXTENSION);
    $newNameWithoutExt = pathinfo($newFilename, PATHINFO_FILENAME);
    $finalFilename = $newNameWithoutExt . '.' . $oldExt;

    // Update database
    $stmt = $pdo->prepare("
        UPDATE media_files 
        SET original_filename = ? 
        WHERE id = ?
    ");
    $stmt->execute([$finalFilename, $mediaId]);

    echo json_encode([
        'success' => true,
        'message' => 'File renamed successfully',
        'filename' => $finalFilename
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
