<?php
// api/create_cod_order.php
// Create Cash on Delivery order (no payment gateway needed)

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/coupon_helpers.php';
    require_once __DIR__ . '/../includes/order_pricing_helper.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $userId = (int)$_SESSION['user_id'];
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

    $stmt = $pdo->prepare('SELECT name, email, phone FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $customerName = $user['name'] ?? 'Customer';

    $customerAddress = trim((string)($_SESSION['shipping_address'] ?? ($_SESSION['address'] ?? '')));
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

    $orderNumber = 'COD-' . strtoupper(substr(md5(uniqid('', true)), 0, 10));

    $pdo->beginTransaction();

    $orderId = insert_priced_order($pdo, [
        'user_id' => $userId,
        'order_number' => $orderNumber,
        'customer_name' => $customerName,
        'customer_address' => $customerAddress,
        'payment_status' => 'pending',
        'order_status' => 'pending',
        'shipping_charge' => (float)($pricing['delivery_charge'] ?? 0),
        'coupon_code' => $couponData['code'] ?? null,
        'coupon_discount' => !empty($pricing['coupon']['applied']) ? (float)($pricing['coupon']['discount_amount'] ?? 0) : 0.0,
        'tax_amount' => (float)($pricing['tax_amount'] ?? 0),
        'total_amount' => (float)($pricing['final_total'] ?? 0),
        'total' => (float)($pricing['final_total'] ?? 0),
        'base_subtotal' => (float)($pricing['base_subtotal'] ?? 0),
        'subscription_plan_id' => $subscriptionData['plan_id'] ?? null,
        'subscription_plan_name' => $subscriptionData['plan_name'] ?? null,
        'subscription_discount' => (float)($pricing['subscription']['applied_discount'] ?? 0),
        'subscription_discount_percent' => !empty($subscriptionData) ? (float)($subscriptionData['discount_percentage'] ?? 0) : null,
        'applied_discount_type' => $pricing['applied_discount_type'] ?? 'none',
        'created_at' => '__NOW__',
    ]);

    $stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, qty, price) VALUES (?, ?, ?, ?, ?)');
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
        $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    if ($couponData && !empty($couponData['id'])) {
        recordCouponUsage((int)$couponData['id'], $userId, $orderId, (float)($pricing['coupon']['discount_amount'] ?? 0), $pdo);
    }

    unset($_SESSION['applied_coupon']);
    unset($_SESSION['shipping_address'], $_SESSION['selected_address_id'], $_SESSION['gst_number']);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'message' => 'Order placed successfully'
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $errorMsg = date('Y-m-d H:i:s') . ' - COD Order Error: ' . $e->getMessage() . "\n";
    $errorMsg .= 'File: ' . $e->getFile() . ' Line: ' . $e->getLine() . "\n";
    $errorMsg .= 'Trace: ' . $e->getTraceAsString() . "\n\n";
    @file_put_contents(__DIR__ . '/../admin/cod_errors.log', $errorMsg, FILE_APPEND);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to create order: ' . $e->getMessage()
    ]);
}
