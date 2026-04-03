<?php
// admin/products_delete.php
header('Content-Type: application/json; charset=utf-8');

// hide direct warnings from output (they'll be logged)
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/db.php';
// require admin auth (ensure admin session)
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'error'=>'Invalid request method']); exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'Invalid id']); exit; }

try {
    // fetch images for cleanup (best-effort)
    $stmt = $pdo->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchColumn();

    if ($images) {
        $files = [];
        $maybe = @json_decode($images, true);
        if (is_array($maybe)) {
            $files = $maybe;
        } elseif (strpos($images, ',') !== false) {
            $files = array_map('trim', explode(',', $images));
        } else {
            $files = [$images];
        }

        foreach ($files as $f) {
            if (!$f) continue;
            if (preg_match('#^https?://#i', $f)) continue;
            $path = __DIR__ . '/../assets/uploads/products/' . ltrim($f, '/');
            if (file_exists($path) && is_file($path)) @unlink($path);
            $alt = __DIR__ . '/..' . (strpos($f, '/') === 0 ? $f : '/'.$f);
            if (file_exists($alt) && is_file($alt)) @unlink($alt);
        }
    }

    // NUCLEAR OPTION: Disable keys to force delete
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    try {
        // 1. Delete filtering values
        $stmt = $pdo->prepare("DELETE FROM product_filter_values WHERE product_id = ?");
        $stmt->execute([$id]);

        // 2. Delete variants
        $stmt = $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?");
        $stmt->execute([$id]);
        
        // 3. Delete order items (CAUTION) - unlink them to prevent order history loss
        $stmt = $pdo->prepare("UPDATE order_items SET product_id = NULL WHERE product_id = ?");
        $stmt->execute([$id]);

        // 4. Finally delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        
    } catch (Exception $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        throw $e;
    }

    echo json_encode(['ok'=>true]); exit;

} catch (Exception $e) {
    @file_put_contents(__DIR__ . '/../storage/delete_product_error.log', date('c') . " - {$e->getMessage()}\n", FILE_APPEND);
    echo json_encode(['ok'=>false,'error'=>'Server error: ' . $e->getMessage()]); exit;
}