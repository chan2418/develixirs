<?php
// admin/search_products.php - Search products for variant linking
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, price, stock 
        FROM products 
        WHERE name LIKE :query 
        AND is_active = 1
        ORDER BY name ASC 
        LIMIT 20
    ");
    
    $stmt->execute([':query' => '%' . $query . '%']);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($products);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
