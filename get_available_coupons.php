<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupon_helpers.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login to view offers');
    }
    
    $userId = $_SESSION['user_id'];
    
    // Get cart items to validate product/category specific offers
    $stmt = $pdo->prepare("
        SELECT c.product_id, c.quantity, p.price, p.category_id
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate cart total
    $cartTotal = 0;
    foreach ($cartItems as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
    }
    
    // Fetch all active coupons (let validation logic handle dates)
    $stmt = $pdo->prepare("
        SELECT * FROM coupons 
        WHERE status = 'active' 
        ORDER BY discount_value DESC
    ");
    $stmt->execute();
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $displayCoupons = [];
    foreach ($coupons as $c) {
        $validation = validateCoupon($c['code'], $userId, $cartTotal, $cartItems, $pdo);
        
        // Skip if already used
        if (!$validation['valid'] && isset($validation['reason_code']) && $validation['reason_code'] === 'ALREADY_USED') {
            continue;
        }
        
        $displayCoupons[] = [
            'code' => $c['code'],
            'title' => $c['title'],
            'description' => $c['description'],
            'discount_text' => $c['discount_type'] === 'percentage' 
                ? floatval($c['discount_value']) . '% OFF' 
                : '₹' . floatval($c['discount_value']) . ' OFF',
            'min_purchase_text' => $c['min_purchase'] > 0 
                ? 'Min purchase: ₹' . floatval($c['min_purchase']) 
                : 'No min purchase',
            'is_eligible' => $validation['valid'],
            'ineligibility_reason' => $validation['valid'] ? '' : $validation['message']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'coupons' => $displayCoupons,
        'count' => count($displayCoupons)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'coupons' => []
    ]);
}
