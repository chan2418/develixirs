<?php
// admin/delete_banner.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

session_start();

// basic POST + CSRF check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: banner.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    // invalid token
    header('Location: banner.php?error=csrf');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: banner.php?error=invalid');
    exit;
}

// 1) fetch banner to know filename
$stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
$stmt->execute([$id]);
$banner = $stmt->fetch(PDO::FETCH_ASSOC);

if ($banner) {
    // 2) delete file from disk if exists
    if (!empty($banner['filename'])) {
        $filePath = __DIR__ . '/../assets/uploads/banners/' . ltrim($banner['filename'], '/');
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    // 3) delete row from DB
    $del = $pdo->prepare("DELETE FROM banners WHERE id = ?");
    $del->execute([$id]);
}

// 4) redirect back
header('Location: banner.php?ok=1');
exit;