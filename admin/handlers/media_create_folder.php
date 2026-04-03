<?php
// admin/handlers/media_create_folder.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$folderName = trim($_POST['folder_name'] ?? '');
$parentId = $_POST['parent_id'] ?? null;

if (!$folderName) {
    echo json_encode(['success' => false, 'error' => 'Folder name required']);
    exit;
}

try {
    // Generate UUID
    function uuid_v4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    $folderId = uuid_v4();

    $stmt = $pdo->prepare("
        INSERT INTO media_folders (id, name, parent_id, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([$folderId, $folderName, $parentId]);

    echo json_encode([
        'success' => true,
        'folder' => [
            'id' => $folderId,
            'name' => $folderName
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
