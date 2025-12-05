<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/razorpay_config.php';
require_once __DIR__ . '/../includes/RazorpayClient.php';
require_once __DIR__ . '/../includes/coupon_helpers.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // 1. Calculate Cart Total
    $stmt = $pdo->prepare("
        SELECT c.quantity, p.price
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    $cartTotal = 0;
    $cartCount = 0;
    foreach ($items as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
        $cartCount += $item['quantity'];
    }

    // 2. Calculate Delivery Charge
    $deliveryCharge = ($cartTotal < 1000 && $cartCount > 0) ? 80 : 0;

    // 3. Apply Coupon Discount
    $discountAmount = 0;
    if (isset($_SESSION['applied_coupon'])) {
        // Re-validate coupon logic could go here, but for now we use the session value
        // Ideally we should re-verify validity
        $coupon = $_SESSION['applied_coupon'];
        $discountAmount = calculateDiscount($coupon, $cartTotal);
    }

    $finalAmount = $cartTotal + $deliveryCharge - $discountAmount;
    
    // Razorpay expects amount in paise (multiply by 100)
    $amountInPaise = round($finalAmount * 100);

    // 4. Create Order in Razorpay
    $api = new RazorpayClient(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    
    $orderData = [
        'receipt'         => 'rcpt_' . time() . '_' . $userId,
        'amount'          => $amountInPaise,
        'currency'        => 'INR',
        'payment_capture' => 1 // Auto capture
    ];

    $razorpayOrder = $api->createOrder($orderData);

    if (isset($razorpayOrder['id'])) {
        echo json_encode([
            'success' => true,
            'order_id' => $razorpayOrder['id'],
            'amount' => $amountInPaise,
            'key' => RAZORPAY_KEY_ID,
            'currency' => 'INR',
            'name' => 'Devilixirs',
            'description' => 'Order Payment',
            'prefill' => [
                'name' => $_SESSION['user_name'] ?? '',
                'email' => $_SESSION['user_email'] ?? '',
                'contact' => $_SESSION['user_phone'] ?? ''
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create Razorpay order']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
