<?php
// admin/search_products_api.php
// AJAX endpoint for searching products in related products dropdown

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in (add your auth check here if needed)
// if (!isset($_SESSION['admin_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$currentProductId = isset($_GET['current']) ? (int)$_GET['current'] : 0;

try {
    // Search products by name or SKU
    $sql = "SELECT id, name, sku, price, images 
            FROM products 
            WHERE is_active = 1 
            AND id != :current_id
            AND (name LIKE :search_name OR sku LIKE :search_sku)
            ORDER BY name ASC 
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':current_id' => $currentProductId,
        ':search_name' => '%' . $searchTerm . '%',
        ':search_sku' => '%' . $searchTerm . '%'
    ]);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results for Select2
    $results = [];
    foreach ($products as $product) {
        $imagePath = 'assets/images/avatar-default.png';
        if (!empty($product['images'])) {
            $decoded = json_decode($product['images'], true);
            if (is_array($decoded) && !empty($decoded)) {
                $imagePath = 'assets/uploads/products/' . $decoded[0];
            }
        }
        
        $results[] = [
            'id' => $product['id'],
            'text' => $product['name'] . ' (SKU: ' . ($product['sku'] ?: 'N/A') . ')',
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $imagePath
        ];
    }
    
    echo json_encode(['results' => $results]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
