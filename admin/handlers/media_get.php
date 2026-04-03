<?php
// admin/handlers/media_get.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$mediaId = $_GET['id'] ?? '';

if (!$mediaId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Media ID required']);
    exit;
}

try {
    // Get media details
    $stmt = $pdo->prepare("
        SELECT * FROM media_files WHERE id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$media) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Media not found']);
        exit;
    }

    // Get tags
    $tagsStmt = $pdo->prepare("
        SELECT t.name 
        FROM media_tags t
        JOIN media_file_tags ft ON ft.tag_id = t.id
        WHERE ft.media_id = ?
    ");
    $tagsStmt->execute([$mediaId]);
    $tags = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);

    // Get usage
    $usageStmt = $pdo->prepare("
        SELECT entity_type, entity_id, field_name, last_used_at
        FROM media_usage
        WHERE media_id = ?
        ORDER BY last_used_at DESC
        LIMIT 20
    ");
    $usageStmt->execute([$mediaId]);
    $usages = $usageStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format usage
    $formattedUsages = array_map(function($u) {
        $title = '';
        $link = '';
        
        if ($u['entity_type'] === 'product') {
            global $pdo;
            $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$u['entity_id']]);
            $title = $stmt->fetchColumn() ?: "Product #{$u['entity_id']}";
            $link = "/admin/edit_product.php?id={$u['entity_id']}";
        }
        
        return [
            'type' => $u['entity_type'],
            'id' => $u['entity_id'],
            'title' => $title,
            'link' => $link,
            'field' => $u['field_name'],
            'last_used' => $u['last_used_at']
        ];
    }, $usages);

    // Get variants
    $variantsStmt = $pdo->prepare("
        SELECT variant_name, format, url, width, height, size
        FROM media_variants
        WHERE media_id = ?
    ");
    $variantsStmt->execute([$mediaId]);
    $variants = $variantsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $media['id'],
            'filename' => $media['filename'],
            'original_filename' => $media['original_filename'],
            'mime_type' => $media['mime_type'],
            'size' => (int)$media['size'],
            'width' => $media['width'] ? (int)$media['width'] : null,
            'height' => $media['height'] ? (int)$media['height'] : null,
            'cdn_url' => $media['cdn_url'],
            'thumb_url' => $media['thumb_url'],
            'alt_text' => $media['alt_text'],
            'title' => $media['title'],
            'description' => $media['description'],
            'tags' => $tags,
            'colors' => $media['colors'] ? json_decode($media['colors'], true) : null,
            'exif' => $media['exif'] ? json_decode($media['exif'], true) : null,
            'uploaded_by' => $media['uploaded_by'],
            'uploaded_at' => $media['uploaded_at'],
            'last_used_at' => $media['last_used_at'],
            'used_in' => $formattedUsages,
            'variants' => $variants
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
