<?php
// admin/save_banner.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: banner.php'); exit;
}
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header('Location: banner.php?err=csrf'); exit;
}

$alt = trim($_POST['alt_text'] ?? '');
$link = trim($_POST['link'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

// prepare upload folder
$uploadDir = __DIR__ . '/../assets/uploads/banners/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// handle upload (optional)
$filename = '';
if (!empty($_FILES['banner']) && $_FILES['banner']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['banner'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        header('Location: banner.php?err=upload'); exit;
    }
    // validate size (5MB) and type
    if ($f['size'] > 5 * 1024 * 1024) {
        header('Location: banner.php?err=size'); exit;
    }
    $allowed = ['image/jpeg','image/png','image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) {
        header('Location: banner.php?err=type'); exit;
    }
    // generate safe filename
    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
    $filename = time() . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $uploadDir . $filename;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        header('Location: banner.php?err=save'); exit;
    }
    // optional: you can run image resize here (gd/imagic) if needed
}

// Insert or update banner row (simple approach: insert new row)
try {
    if ($filename) {
        $stmt = $pdo->prepare("INSERT INTO banners (name, filename, alt_text, link, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Homepage banner', $filename, $alt, $link ?: null, $is_active]);
    } else {
        // if no file uploaded, update last banner
        $stmt = $pdo->prepare("UPDATE banners SET alt_text = ?, link = ?, is_active = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$alt, $link ?: null, $is_active]);
    }
} catch (Exception $e) {
    // log $e->getMessage() in production
    header('Location: banner.php?err=db'); exit;
}

header('Location: banner.php?ok=1'); exit;