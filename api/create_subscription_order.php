<?php
// api/create_subscription_order.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/subscription_plan_helper.php';
require_once __DIR__ . '/../includes/razorpay_config.php';
require_once __DIR__ . '/../includes/RazorpayClient.php';

header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

ensure_subscription_schema($pdo);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$plan_id = (int)($input['plan_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($plan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Fetch plan
    $plan = subscription_fetch_plan_by_id($pdo, $plan_id, true);
    
    if (!$plan) {
        echo json_encode(['success' => false, 'message' => 'Plan not found']);
        exit;
    }

    $amount = (float)$plan['price'];
    
    // Create pending subscription record
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+{$plan['validity_days']} days"));
    
    $stmt = $pdo->prepare("
        INSERT INTO user_subscriptions (
            user_id, plan_id, status, start_date, end_date, auto_renew,
            plan_name, price_paid, compare_price_snapshot, discount_percentage_snapshot,
            billing_cycle_snapshot, validity_days_snapshot, free_shipping_snapshot,
            badge_text_snapshot, benefits_snapshot
        )
        VALUES (
            ?, ?, 'pending', ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?
        )
    ");
    $stmt->execute([
        $user_id,
        $plan_id,
        $startDate,
        $endDate,
        !empty($plan['auto_renew_enabled']) ? 1 : 0,
        $plan['name'],
        $amount,
        $plan['compare_price'] !== null ? (float)$plan['compare_price'] : null,
        (float)$plan['discount_percentage'],
        $plan['billing_cycle'],
        (int)$plan['validity_days'],
        !empty($plan['free_shipping']) ? 1 : 0,
        $plan['badge_text'] ?? null,
        json_encode($plan['benefits_list'] ?? [], JSON_UNESCAPED_UNICODE),
    ]);
    $subscription_id = $pdo->lastInsertId();
    
    // Create transaction record
    $stmt = $pdo->prepare("
        INSERT INTO subscription_transactions (user_subscription_id, user_id, plan_id, amount, payment_status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$subscription_id, $user_id, $plan_id, $amount]);
    $transaction_id = $pdo->lastInsertId();
    
    // Create Razorpay order
    $api = new RazorpayClient(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    
    $orderData = [
        'amount' => $amount * 100, // paise
        'currency' => 'INR',
        'receipt' => 'subscription_' . $transaction_id,
        'notes' => [
            'user_id' => $user_id,
            'subscription_id' => $subscription_id,
            'transaction_id' => $transaction_id,
            'plan_id' => $plan_id
        ]
    ];
    
    $razorpayOrder = $api->createOrder($orderData);
    
    if (isset($razorpayOrder['id'])) {
        // Update transaction with order ID
        $stmt = $pdo->prepare("UPDATE subscription_transactions SET payment_id = ? WHERE id = ?");
        $stmt->execute([$razorpayOrder['id'], $transaction_id]);
        
        echo json_encode([
            'success' => true,
            'order_id' => $razorpayOrder['id'],
            'amount' => $amount * 100,
            'key' => RAZORPAY_KEY_ID,
            'subscription_id' => $subscription_id,
            'transaction_id' => $transaction_id
        ]);
    } else {
        throw new Exception('Failed to create Razorpay order');
    }
    
} catch (Exception $e) {
    error_log("Subscription order creation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
