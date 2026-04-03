<?php
// admin/test_sync.php - Quick test version
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    $results = [
        'test' => 'working',
        'products_count' => 0,
        'db_connected' => true
    ];
    
    // Test database
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $results['products_count'] = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
