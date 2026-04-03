<?php
// admin/sync_simple.php (Debug DB Paths)
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain');

echo "=== PRODUCTS (images) ===\n";
$stmt = $pdo->query("SELECT images FROM products WHERE images IS NOT NULL AND images != '' LIMIT 1");
$p = $stmt->fetchColumn();
echo "Raw JSON: " . $p . "\n";
print_r(json_decode($p, true));

echo "\n=== PRODUCTS (product_media) ===\n";
$stmt = $pdo->query("SELECT product_media FROM products WHERE product_media IS NOT NULL AND product_media != '' LIMIT 1");
$pm = $stmt->fetchColumn();
echo "Raw JSON: " . $pm . "\n";
print_r(json_decode($pm, true));

echo "\n=== BANNERS (filename) ===\n";
$stmt = $pdo->query("SELECT filename FROM banners LIMIT 3");
$banners = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($banners);

echo "\n=== CATEGORIES (image) ===\n";
$stmt = $pdo->query("SELECT image FROM categories WHERE image IS NOT NULL AND image != '' LIMIT 3");
$cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($cats);
