<?php
// admin/products_delete.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php'; // adjust path if needed
session_start(); // make sure authentication is consistent with your app
// optionally require admin authentication, e.g. require_once __DIR__ . '/_auth.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid id']); exit;
}

try {
    // (optional) Fetch images for cleanup
    $stmt = $pdo->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchColumn();
    if ($images) {
        $maybe = @json_decode($images, true);
        if (is_array($maybe)) $files = $maybe;
        elseif (strpos($images, ',') !== false) $files = array_map('trim', explode(',', $images));
        else $files = [$images];
        foreach ($files as $f) {
            if (!$f) continue;
            if (preg_match('#^https?://#i', $f)) continue;
            $candidate = __DIR__ . '/../assets/uploads/products/' . ltrim($f, '/');
            if (file_exists($candidate) && is_file($candidate)) @unlink($candidate);
        }
    }

    // Delete product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['ok' => true]); exit;

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}