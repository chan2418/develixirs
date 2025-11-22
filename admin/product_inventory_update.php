<?php
// admin/product_inventory_update.php
// AJAX endpoint to increase product stock (returns JSON)

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// JSON response helper
function json_out($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// require session + POST body
if (session_status() === PHP_SESSION_NONE) session_start();

// only allow POST with JSON
$raw = file_get_contents('php://input');
if (!$raw) json_out(['ok' => false, 'error' => 'Empty request']);

$data = json_decode($raw, true);
if (!is_array($data)) json_out(['ok' => false, 'error' => 'Invalid request format']);

$id = isset($data['id']) ? (int)$data['id'] : 0;
$qty = isset($data['qty']) ? (int)$data['qty'] : 0;
$csrf = isset($data['csrf_token']) ? $data['csrf_token'] : '';

if ($id <= 0) json_out(['ok' => false, 'error' => 'Invalid product id']);
if ($qty <= 0) json_out(['ok' => false, 'error' => 'Quantity must be > 0']);

// CSRF check
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    json_out(['ok' => false, 'error' => 'Invalid CSRF token']);
}

// fetch current stock
try {
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) json_out(['ok' => false, 'error' => 'Product not found']);
    $current = (int)$row['stock'];

    $new = $current + $qty;

    $u = $pdo->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?");
    $u->execute([$new, $id]);

    json_out(['ok' => true, 'stock' => $new, 'message' => 'Stock updated']);
} catch (Exception $e) {
    json_out(['ok' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}