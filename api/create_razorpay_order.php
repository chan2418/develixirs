<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/razorpay_config.php';
require_once __DIR__ . '/../includes/RazorpayClient.php';
require_once __DIR__ . '/../includes/coupon_helpers.php';
require_once __DIR__ . '/../includes/order_pricing_helper.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $items = fetch_order_context_items($pdo, $userId, true);
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    $pricing = calculate_order_pricing($pdo, $userId, $items, getAppliedCoupon());
    $finalAmount = (float)($pricing['final_total'] ?? 0);
    $amountInPaise = (int)round($finalAmount * 100);

    $api = new RazorpayClient(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    $orderData = [
        'receipt' => 'rcpt_' . time() . '_' . $userId,
        'amount' => $amountInPaise,
        'currency' => 'INR',
        'payment_capture' => 1,
    ];

    $razorpayOrder = $api->createOrder($orderData);

    if (!isset($razorpayOrder['id'])) {
        echo json_encode(['success' => false, 'message' => 'Failed to create Razorpay order']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'order_id' => $razorpayOrder['id'],
        'amount' => $amountInPaise,
        'key' => RAZORPAY_KEY_ID,
        'currency' => 'INR',
        'name' => 'Devilixirs',
        'description' => 'Order Payment',
        'pricing' => order_pricing_frontend_payload($pricing),
        'prefill' => [
            'name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'contact' => $_SESSION['user_phone'] ?? ''
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
