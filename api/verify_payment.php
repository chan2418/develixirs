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
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['razorpay_payment_id'], $input['razorpay_order_id'], $input['razorpay_signature'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
    exit;
}

try {
    $api = new RazorpayClient(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    $attributes = [
        'razorpay_order_id' => $input['razorpay_order_id'],
        'razorpay_payment_id' => $input['razorpay_payment_id'],
        'razorpay_signature' => $input['razorpay_signature'],
    ];

    if (!$api->verifyPaymentSignature($attributes)) {
        throw new Exception('Invalid payment signature');
    }

    $isDirectBuy = !empty($_SESSION['direct_buy_item']['product_id']);
    $cartItems = fetch_order_context_items($pdo, $userId, true);
    if (empty($cartItems)) {
        throw new Exception('Cart is empty');
    }

    $pricing = calculate_order_pricing($pdo, $userId, $cartItems, getAppliedCoupon());
    $cartItems = $pricing['items'] ?? [];
    if (empty($cartItems)) {
        throw new Exception('Cart is empty');
    }

    $couponData = (!empty($pricing['coupon']['applied']) && !empty($pricing['coupon']['data']))
        ? $pricing['coupon']['data']
        : null;
    $subscriptionData = !empty($pricing['subscription']['active']) ? $pricing['subscription'] : [];
    $finalAmount = (float)($pricing['final_total'] ?? 0);

    $stmtUser = $pdo->prepare('SELECT name, email, phone FROM users WHERE id = ?');
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: [];
    $customerName = $user['name'] ?? 'Guest';

    $customerAddress = trim((string)($_SESSION['shipping_address'] ?? ''));
    if ($customerAddress === '') {
        $stmtAddr = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1');
        $stmtAddr->execute([$userId]);
        $addr = $stmtAddr->fetch(PDO::FETCH_ASSOC);

        if ($addr) {
            $customerAddress = $addr['full_name'] . "\n" .
                               $addr['address_line1'] . "\n" .
                               ($addr['address_line2'] ? $addr['address_line2'] . "\n" : '') .
                               $addr['city'] . ', ' . $addr['state'] . ' - ' . $addr['pincode'] . "\n" .
                               'Phone: ' . $addr['phone'];
        }
    }

    $gstNumber = strtoupper(trim((string)($_SESSION['gst_number'] ?? '')));
    if ($gstNumber !== '' && stripos($customerAddress, 'GSTIN:') === false) {
        $customerAddress = trim($customerAddress . "\n" . 'GSTIN: ' . $gstNumber);
    }
    if ($customerAddress === '') {
        $customerAddress = 'Not provided';
    }

    $pdo->beginTransaction();

    $orderId = insert_priced_order($pdo, [
        'user_id' => $userId,
        'order_number' => $input['razorpay_order_id'],
        'customer_name' => $customerName,
        'customer_address' => $customerAddress,
        'total_amount' => $finalAmount,
        'shipping_charge' => (float)($pricing['delivery_charge'] ?? 0),
        'total' => $finalAmount,
        'coupon_code' => $couponData['code'] ?? null,
        'coupon_discount' => !empty($pricing['coupon']['applied']) ? (float)($pricing['coupon']['discount_amount'] ?? 0) : 0.0,
        'tax_amount' => (float)($pricing['tax_amount'] ?? 0),
        'base_subtotal' => (float)($pricing['base_subtotal'] ?? 0),
        'subscription_plan_id' => $subscriptionData['plan_id'] ?? null,
        'subscription_plan_name' => $subscriptionData['plan_name'] ?? null,
        'subscription_discount' => (float)($pricing['subscription']['applied_discount'] ?? 0),
        'subscription_discount_percent' => !empty($subscriptionData) ? (float)($subscriptionData['discount_percentage'] ?? 0) : null,
        'applied_discount_type' => $pricing['applied_discount_type'] ?? 'none',
        'status' => 'processing',
        'order_status' => 'processing',
        'payment_status' => 'paid',
        'created_at' => '__NOW__',
    ]);

    $stmtItem = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, qty, price, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
    );

    foreach ($cartItems as $item) {
        $stmtItem->execute([
            $orderId,
            (int)$item['product_id'],
            $item['name'],
            (int)$item['quantity'],
            (float)$item['price'],
        ]);
    }

    if ($isDirectBuy) {
        unset($_SESSION['direct_buy_item']);
    } else {
        $stmtClear = $pdo->prepare('DELETE FROM cart WHERE user_id = ?');
        $stmtClear->execute([$userId]);
    }

    if ($couponData && !empty($couponData['id'])) {
        recordCouponUsage((int)$couponData['id'], $userId, $orderId, (float)($pricing['coupon']['discount_amount'] ?? 0), $pdo);
        unset($_SESSION['applied_coupon']);
    } else {
        unset($_SESSION['applied_coupon']);
    }

    unset($_SESSION['shipping_address'], $_SESSION['selected_address_id'], $_SESSION['gst_number']);

    try {
        $notifTitle = 'New Order #' . $input['razorpay_order_id'];
        $notifMsg = 'Customer ' . $customerName . ' placed a new order of ₹' . number_format($finalAmount, 2);
        $notifUrl = 'order_view.php?id=' . $orderId;

        $stmtNotif = $pdo->prepare('INSERT INTO notifications (title, message, url, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
        $stmtNotif->execute([$notifTitle, $notifMsg, $notifUrl]);
    } catch (Exception $e) {
        error_log('Notification creation failed: ' . $e->getMessage());
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'order_id' => $orderId]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
