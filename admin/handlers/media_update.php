<?php
// admin/handlers/media_update.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$mediaId = $_POST['media_id'] ?? '';
$altText = $_POST['alt_text'] ?? null;
$title = $_POST['title'] ?? null;
$description = $_POST['description'] ?? null;
$tags = $_POST['tags'] ?? '';

if (!$mediaId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Media ID required']);
    exit;
}

try {
    // Update media metadata
    $stmt = $pdo->prepare("
        UPDATE media_files 
        SET alt_text = ?, title = ?, description = ?
        WHERE id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$altText, $title, $description, $mediaId]);

    // Handle tags
    if ($tags !== '') {
        // Delete existing tags
        $pdo->prepare("DELETE FROM media_file_tags WHERE media_id = ?")->execute([$mediaId]);

        // Parse and insert new tags
        $tagArray = array_filter(array_map('trim', explode(',', $tags)));
        
        foreach ($tagArray as $tagName) {
            // Get or create tag
            $stmt = $pdo->prepare("SELECT id FROM media_tags WHERE name = ?");
            $stmt->execute([$tagName]);
            $tagId = $stmt->fetchColumn();

            if (!$tagId) {
                $tagId = substr(str_replace('-', '', uuid_v4()), 0, 36);
                $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $tagName));
                $pdo->prepare("INSERT INTO media_tags (id, name, slug) VALUES (?, ?, ?)")
                    ->execute([$tagId, $tagName, $slug]);
            }

            // Link tag to media
            try {
                $pdo->prepare("INSERT INTO media_file_tags (media_id, tag_id) VALUES (?, ?)")
                    ->execute([$mediaId, $tagId]);
            } catch (PDOException $e) {
                // Ignore duplicate key errors
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Media updated successfully']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function uuid_v4() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
