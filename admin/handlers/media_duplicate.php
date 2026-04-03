<?php
// admin/handlers/media_duplicate.php
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
    echo json_encode(['success' => false, 'error' => 'Media ID required']);
    exit;
}

try {
    // Get original media
    $stmt = $pdo->prepare("SELECT * FROM media_files WHERE id = ?");
    $stmt->execute([$mediaId]);
    $original = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$original) {
        throw new Exception('Media not found');
    }

    // Generate new UUID
    function uuid_v4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    $newId = uuid_v4();
    $ext = pathinfo($original['filename'], PATHINFO_EXTENSION);
    $newFilename = $newId . '.' . $ext;
    
    // Copy file
    $uploadDir = __DIR__ . '/../../assets/uploads/media/';
    $originalPath = $uploadDir . $original['filename'];
    $newPath = $uploadDir . $newFilename;

    if (!copy($originalPath, $newPath)) {
        throw new Exception('Failed to copy file');
    }

    // Insert new record
    $stmt = $pdo->prepare("
        INSERT INTO media_files (
            id, filename, original_filename, mime_type, size, 
            width, height, storage_path, cdn_url, thumb_url, 
            alt_text, title, description, uploaded_by, folder_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $newStoragePath = 'assets/uploads/media/' . $newFilename;
    $newCdnUrl = '/' . $newStoragePath;
    $uploadedBy = $_SESSION['admin_id'] ?? 1;

    $stmt->execute([
        $newId,
        $newFilename,
        'Copy of ' . $original['original_filename'],
        $original['mime_type'],
        $original['size'],
        $original['width'],
        $original['height'],
        $newStoragePath,
        $newCdnUrl,
        $original['thumb_url'],
        $original['alt_text'],
        $original['title'],
        $original['description'],
        $uploadedBy,
        $original['folder_id']
    ]);

    echo json_encode([
        'success' => true,
        'media' => [
            'id' => $newId,
            'filename' => $newFilename
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
