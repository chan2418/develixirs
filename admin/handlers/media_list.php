<?php
// admin/handlers/media_list.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

// Get query parameters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$folderId = $_GET['folder_id'] ?? '';
$view = $_GET['view'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(1, (int)($_GET['per_page'] ?? 48)));

// Build WHERE clause
$where = [];
$params = [];

// Filter by view (all, trash, recent, favorites)
if ($view === 'trash') {
    $where[] = "deleted_at IS NOT NULL";
} else {
    $where[] = "deleted_at IS NULL";
    
    if ($view === 'recent') {
        // Recent: uploaded in last 7 days
        $where[] = "uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($view === 'favorites') {
        // Favorites only
        $where[] = "is_favorite = 1";
    }
}

// Filter by folder
if ($folderId) {
    $where[] = "folder_id = ?";
    $params[] = $folderId;
} else {
    $where[] = "(folder_id IS NULL OR folder_id = '')";
}

if ($search) {
    $where[] = "(filename LIKE ? OR alt_text LIKE ? OR title LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($type === 'image') {
    $where[] = "mime_type LIKE 'image/%'";
} elseif ($type === 'video') {
    $where[] = "mime_type LIKE 'video/%'";
} elseif ($type === 'document') {
    $where[] = "mime_type LIKE 'application/%'";
}

$whereClause = implode(' AND ', $where);

// Build ORDER BY clause
$orderBy = match($sort) {
    'oldest' => 'uploaded_at ASC',
    'name' => 'filename ASC',
    'size' => 'size DESC',
    default => 'uploaded_at DESC'
};

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM media_files WHERE $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Get paginated results
$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("
    SELECT 
        id, filename, original_filename, mime_type, size,
        width, height, cdn_url, thumb_url, alt_text, title,
        uploaded_at, uploaded_by
    FROM media_files 
    WHERE $whereClause
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
");

$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$media = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format response
$formattedMedia = array_map(function($item) {
    return [
        'id' => $item['id'],
        'filename' => $item['filename'],
        'original_filename' => $item['original_filename'],
        'mime_type' => $item['mime_type'],
        'size' => (int)$item['size'],
        'width' => $item['width'] ? (int)$item['width'] : null,
        'height' => $item['height'] ? (int)$item['height'] : null,
        'cdn_url' => $item['cdn_url'],
        'thumb_url' => $item['thumb_url'],
        'alt_text' => $item['alt_text'],
        'title' => $item['title'],
        'uploaded_at' => $item['uploaded_at'],
        'uploaded_by' => $item['uploaded_by']
    ];
}, $media);

echo json_encode([
    'success' => true,
    'data' => $formattedMedia,
    'meta' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => (int)$total,
        'total_pages' => (int)ceil($total / $perPage)
    ]
]);
