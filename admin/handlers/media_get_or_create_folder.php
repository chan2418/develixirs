<?php
// admin/handlers/media_get_or_create_folder.php
// Helper to get or create folder by path (e.g., "Products/My Product")
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

/**
 * Get or create a folder by path
 * @param string $folderPath - Path like "Products/Product Name" or "Homepage/Banners"
 * @return array - ['success' => true, 'folder_id' => '...', 'folder_name' => '...']
 */
function getOrCreateFolder($pdo, $folderPath) {
    // Split path into parts
    $parts = array_filter(explode('/', $folderPath));
    $parentId = null;
    
    foreach ($parts as $folderName) {
        $folderName = trim($folderName);
        
        // Check if folder exists
        if ($parentId) {
            $stmt = $pdo->prepare("SELECT id FROM media_folders WHERE name = ? AND parent_id = ?");
            $stmt->execute([$folderName, $parentId]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM media_folders WHERE name = ? AND (parent_id IS NULL OR parent_id = '')");
            $stmt->execute([$folderName]);
        }
        
        $folderId = $stmt->fetchColumn();
        
        // Create if doesn't exist
        if (!$folderId) {
            function uuid_v4() {
                $data = random_bytes(16);
                $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
                $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
                return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
            }
            
            $folderId = uuid_v4();
            $stmt = $pdo->prepare("INSERT INTO media_folders (id, name, parent_id) VALUES (?, ?, ?)");
            $stmt->execute([$folderId, $folderName, $parentId]);
        }
        
        $parentId = $folderId;
    }
    
    return [
        'success' => true,
        'folder_id' => $parentId,
        'folder_name' => end($parts)
    ];
}

// Only handle POST/GET if accessed directly (not included)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $folderPath = $_POST['folder_path'] ?? $_GET['folder_path'] ?? '';
        
        if (!$folderPath) {
            echo json_encode(['success' => false, 'error' => 'Folder path required']);
            exit;
        }
        
        try {
            $result = getOrCreateFolder($pdo, $folderPath);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
