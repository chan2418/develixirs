<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupon_helpers.php';
require_once __DIR__ . '/includes/order_pricing_helper.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login to apply coupons');
    }
    
    if (!isset($_POST['code']) || trim($_POST['code']) === '') {
        throw new Exception('Please enter a coupon code');
    }
    
    $userId = $_SESSION['user_id'];
    $code = trim($_POST['code']);
    
    // Get cart items with category info
    $stmt = $pdo->prepare("
        SELECT c.product_id, c.quantity, p.price, p.category_id
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cartItems)) {
        throw new Exception('Your cart is empty');
    }
    
    // Calculate cart total
    $cartTotal = 0;
    foreach ($cartItems as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
    }
    
    // Validate coupon
    $validation = validateCoupon($code, $userId, $cartTotal, $cartItems, $pdo);
    
    if (!$validation['valid']) {
        throw new Exception($validation['message']);
    }
    
    $coupon = $validation['coupon'];
    
    // Calculate discount
    $discountAmount = calculateDiscount($coupon, $cartTotal);
    
    // Store in session
    applyCouponToSession($coupon, $discountAmount);
    
    $pricing = calculate_order_pricing($pdo, $userId, $cartItems, getAppliedCoupon());
    $newTotal = (float)($pricing['final_total'] ?? ($cartTotal - $discountAmount));
    $message = 'Coupon applied successfully!';
    if (!empty($pricing['coupon']['saved_not_applied'])) {
        $message = 'Coupon saved, but your subscription discount is better for this cart.';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'coupon' => [
            'code' => $coupon['code'],
            'title' => $coupon['title'],
            'discount_amount' => $discountAmount,
            'discount_type' => $coupon['discount_type'],
            'discount_value' => $coupon['discount_value']
        ],
        'cart_total' => $cartTotal,
        'discount_amount' => $discountAmount,
        'new_total' => $newTotal,
        'pricing' => order_pricing_frontend_payload($pricing)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
