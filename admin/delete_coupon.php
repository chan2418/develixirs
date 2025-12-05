<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception('Coupon ID is required');
    }
    
    $couponId = intval($_POST['id']);
    
    // Check if coupon exists
    $stmt = $pdo->prepare("SELECT id FROM coupons WHERE id = :id");
    $stmt->execute([':id' => $couponId]);
    if (!$stmt->fetch()) {
        throw new Exception('Coupon not found');
    }
    
    // Delete coupon (cascade will handle related records)
    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = :id");
    $stmt->execute([':id' => $couponId]);
    
    echo json_encode(['success' => true, 'message' => 'Coupon deleted successfully']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
