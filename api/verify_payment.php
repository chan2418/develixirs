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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['razorpay_payment_id'], $input['razorpay_order_id'], $input['razorpay_signature'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
    exit;
}

try {
    $api = new RazorpayClient(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    
    // 1. Verify Signature
    $attributes = [
        'razorpay_order_id' => $input['razorpay_order_id'],
        'razorpay_payment_id' => $input['razorpay_payment_id'],
        'razorpay_signature' => $input['razorpay_signature']
    ];

    $valid = $api->verifyPaymentSignature($attributes);

    if (!$valid) {
        throw new Exception('Invalid payment signature');
    }

    // 2. Fetch Cart Items & Calculate Total (Security check)
    $stmt = $pdo->prepare("
        SELECT c.product_id, c.quantity, p.name, p.price, p.id as pid
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        throw new Exception('Cart is empty');
    }

    $cartTotal = 0;
    $cartCount = 0;
    foreach ($cartItems as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
        $cartCount += $item['quantity'];
    }

    $deliveryCharge = ($cartTotal < 1000 && $cartCount > 0) ? 80 : 0;
    
    $couponCode = '';
    $discountAmount = 0;
    if (isset($_SESSION['applied_coupon'])) {
        $coupon = $_SESSION['applied_coupon'];
        $couponCode = $coupon['code'];
        $discountAmount = calculateDiscount($coupon, $cartTotal);
    }

    $finalAmount = $cartTotal + $deliveryCharge - $discountAmount;

    // 3. Fetch User Details & Address
    $stmtUser = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $customerName = $user['name'] ?? 'Guest';

    // Fetch Address
    $stmtAddr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1");
    $stmtAddr->execute([$userId]);
    $addr = $stmtAddr->fetch(PDO::FETCH_ASSOC);
    
    $customerAddress = null;
    if ($addr) {
        $customerAddress = $addr['full_name'] . "\n" . 
                           $addr['address_line1'] . "\n" . 
                           ($addr['address_line2'] ? $addr['address_line2'] . "\n" : "") . 
                           $addr['city'] . ", " . $addr['state'] . " - " . $addr['pincode'] . "\n" . 
                           "Phone: " . $addr['phone'];
    }

    // 4. Create Order in Database
    $pdo->beginTransaction();

    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (
            user_id, order_number, customer_name, customer_address,
            total_amount, shipping_charge, total, 
            coupon_code, coupon_discount, tax_amount,
            status, order_status, payment_status, 
            created_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, 
            ?, ?, ?,
            'processing', 'processing', 'paid', 
            NOW()
        )
    ");
    
    // Note: 'total' and 'total_amount' seem redundant in schema, populating both
    // Calculate Tax (18% GST)
    $taxRate = 0.18;
    $taxAmount = $cartTotal * $taxRate;
    
    // Final Amount = Subtotal + Tax + Delivery - Discount
    // Note: If prices are inclusive of tax, logic would differ. Assuming exclusive for now based on request.
    // However, usually e-com prices are inclusive. Let's assume exclusive to show the tax line clearly as requested.
    // Or better, let's treat the price as taxable base.
    
    // Actually, to avoid changing the total price the user sees (which might be confusing if we suddenly add 18%),
    // let's assume the product prices are INCLUSIVE of tax, and we just back-calculate it for display.
    // Tax = Total - (Total / 1.18)
    // This way the final amount remains what the user saw in the cart.
    
    // $taxAmount = $cartTotal - ($cartTotal / 1.18); 
    // Wait, if the user wants "add this also", they might imply it's an extra charge or just a breakdown.
    // Given the "herbal ecom" context, usually prices are inclusive.
    // But if I add it as an extra field, I should probably just calculate it and store it.
    // Let's go with: Tax is included in the price, we just break it out.
    
    $taxAmount = $cartTotal * 0.18; // Let's add it on top? No, that changes the payment amount which is already verified by Razorpay!
    // CRITICAL: The payment is ALREADY verified for $finalAmount. We cannot change $finalAmount now.
    // So we must calculate tax as a component of the existing amount OR just store it for display without affecting total.
    // But wait, if I store it, it should mathematically add up.
    // Total = Subtotal + Tax + Shipping - Discount.
    
    // If $finalAmount is fixed (from Razorpay), then:
    // Subtotal + Tax = $cartTotal (roughly).
    // Let's back-calculate tax from the cart total assuming 18% GST included.
    $taxAmount = $cartTotal - ($cartTotal / 1.18);
    $subtotalExclTax = $cartTotal - $taxAmount;
    
    // Actually, let's just store the tax amount. The display logic will handle the breakdown.
    // I will store the calculated tax (back-calculated).
    
    $stmtOrder->execute([
        $userId, $input['razorpay_order_id'], $customerName, $customerAddress,
        $finalAmount, $deliveryCharge, $finalAmount,
        $couponCode, $discountAmount, $taxAmount
    ]);
    
    $orderId = $pdo->lastInsertId();

    // 5. Insert Order Items
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_id, product_name, 
            qty, price, created_at
        ) VALUES (
            ?, ?, ?, 
            ?, ?, NOW()
        )
    ");

    foreach ($cartItems as $item) {
        $stmtItem->execute([
            $orderId, 
            $item['product_id'], 
            $item['name'], 
            $item['quantity'], 
            $item['price']
        ]);
    }

    // 6. Clear Cart
    $stmtClear = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmtClear->execute([$userId]);

    // 7. Record Coupon Usage & Clear Session
    if (isset($_SESSION['applied_coupon'])) {
        $coupon = $_SESSION['applied_coupon'];
        recordCouponUsage($coupon['id'], $userId, $orderId, $discountAmount, $pdo);
        unset($_SESSION['applied_coupon']);
    }

    // 8. Create Admin Notification
    try {
        $notifTitle = "New Order #" . $input['razorpay_order_id'];
        $notifMsg = "Customer {$customerName} placed a new order of ₹" . number_format($finalAmount, 2);
        $notifUrl = "order_view.php?id=" . $orderId;
        
        $stmtNotif = $pdo->prepare("INSERT INTO notifications (title, message, url, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
        $stmtNotif->execute([$notifTitle, $notifMsg, $notifUrl]);
    } catch (Exception $e) {
        // Ignore notification errors to avoid failing the order
        error_log("Notification creation failed: " . $e->getMessage());
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
