<?php
// admin/pages/save_page.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/db.php';
// Use _auth.php if available for consistent session/auth
if (file_exists(__DIR__ . '/../_auth.php')) {
    require_once __DIR__ . '/../_auth.php';
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$id = $_POST['id'] ?? '';
$title = $_POST['title'] ?? 'Untitled Page';
$slug = $_POST['slug'] ?? '';
$status = $_POST['status'] ?? 'draft';
$type = $_POST['type'] ?? 'custom';
$content = $_POST['content'] ?? '[]'; // JSON string
$metaTitle = $_POST['meta_title'] ?? '';
$metaDesc = $_POST['meta_description'] ?? '';

// Validate Slug
if (empty($slug)) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
} else {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug)));
}

// Check for slug uniqueness (simple check)
// Note: In production you might want to allow same slug if it's the SAME page id
$sqlCheck = "SELECT id FROM pages WHERE slug = ?";
$paramsCheck = [$slug];
if (!empty($id)) {
    $sqlCheck .= " AND id != ?";
    $paramsCheck[] = $id;
}
$stmt = $pdo->prepare($sqlCheck);
$stmt->execute($paramsCheck);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Slug already exists. Please choose another URL.']);
    exit;
}

try {
    if (!empty($id)) {
        // Update
        $sql = "UPDATE pages SET 
                title = ?, slug = ?, type = ?, status = ?, content = ?, 
                meta_title = ?, meta_description = ?, updated_at = NOW() 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $slug, $type, $status, $content, $metaTitle, $metaDesc, $id]);
        $newId = $id;
    } else {
        // Insert
        $sql = "INSERT INTO pages (title, slug, type, status, content, meta_title, meta_description, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $slug, $type, $status, $content, $metaTitle, $metaDesc]);
        $newId = $pdo->lastInsertId();
    }

    echo json_encode(['success' => true, 'id' => $newId]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
