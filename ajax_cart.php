<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupon_helpers.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to use cart']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Helper to re-validate coupon
function checkCouponValidity($pdo, $userId) {
    if (!isset($_SESSION['applied_coupon'])) return null;

    $couponCode = $_SESSION['applied_coupon']['code'];
    
    // Get cart items for validation
    $stmt = $pdo->prepare("
        SELECT c.product_id, c.quantity, p.price, p.category_id
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cartTotal = 0;
    foreach ($cartItems as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
    }
    
    $result = validateCoupon($couponCode, $userId, $cartTotal, $cartItems, $pdo);
    
    if (!$result['valid']) {
        removeCouponFromSession();
        return [
            'status' => 'removed',
            'message' => "Coupon removed: " . $result['message']
        ];
    } else {
        // Update discount amount in session as total might have changed
        $coupon = $result['coupon'];
        $discount = calculateDiscount($coupon, $cartTotal);
        applyCouponToSession($coupon, $discount);
        return [
            'status' => 'updated',
            'discount' => $discount,
            'final_total' => $cartTotal - $discount
        ];
    }
}

if ($action === 'add') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($productId <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
        exit;
    }

    try {
        // Check if product already in cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update quantity
            $newQty = $existing['quantity'] + $quantity;
            $upd = $pdo->prepare("UPDATE cart SET quantity = :qty WHERE id = :id");
            $upd->execute([':qty' => $newQty, ':id' => $existing['id']]);
        } else {
            // Insert new
            $ins = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:uid, :pid, :qty)");
            $ins->execute([':uid' => $userId, ':pid' => $productId, ':qty' => $quantity]);
        }

        // Get updated cart stats
        $stats = getCartStats($pdo, $userId);
        $couponStatus = checkCouponValidity($pdo, $userId);
        
        $discount = 0;
        if ($couponStatus && $couponStatus['status'] === 'updated') {
            $discount = $couponStatus['discount'];
        }
        
        $grandTotal = $stats['total'] - $discount + $stats['delivery_charge'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Added to cart', 
            'count' => $stats['count'], 
            'total' => $stats['total'],
            'delivery_charge' => $stats['delivery_charge'],
            'grand_total' => $grandTotal,
            'coupon_status' => $couponStatus
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($action === 'remove') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit;
    }

    try {
        $del = $pdo->prepare("DELETE FROM cart WHERE user_id = :uid AND product_id = :pid");
        $del->execute([':uid' => $userId, ':pid' => $productId]);

        $stats = getCartStats($pdo, $userId);
        $couponStatus = checkCouponValidity($pdo, $userId);
        
        $discount = 0;
        if ($couponStatus && $couponStatus['status'] === 'updated') {
            $discount = $couponStatus['discount'];
        }
        
        $grandTotal = $stats['total'] - $discount + $stats['delivery_charge'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Removed from cart', 
            'count' => $stats['count'], 
            'total' => $stats['total'],
            'delivery_charge' => $stats['delivery_charge'],
            'grand_total' => $grandTotal,
            'coupon_status' => $couponStatus
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($action === 'update_quantity') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit;
    }

    try {
        if ($quantity <= 0) {
            // Remove if quantity is 0 or less
            $del = $pdo->prepare("DELETE FROM cart WHERE user_id = :uid AND product_id = :pid");
            $del->execute([':uid' => $userId, ':pid' => $productId]);
        } else {
            // Update quantity
            $upd = $pdo->prepare("UPDATE cart SET quantity = :qty WHERE user_id = :uid AND product_id = :pid");
            $upd->execute([':qty' => $quantity, ':uid' => $userId, ':pid' => $productId]);
        }

        $stats = getCartStats($pdo, $userId);
        $couponStatus = checkCouponValidity($pdo, $userId);
        
        $discount = 0;
        if ($couponStatus && $couponStatus['status'] === 'updated') {
            $discount = $couponStatus['discount'];
        }
        
        $grandTotal = $stats['total'] - $discount + $stats['delivery_charge'];
        
        echo json_encode([
            'success' => true, 
            'count' => $stats['count'], 
            'total' => $stats['total'],
            'delivery_charge' => $stats['delivery_charge'],
            'grand_total' => $grandTotal,
            'coupon_status' => $couponStatus
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($action === 'get_all') {
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.images
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = :uid
        ");
        $stmt->execute([':uid' => $userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = getCartStats($pdo, $userId);
        echo json_encode(['success' => true, 'items' => $items, 'count' => $stats['count'], 'total' => $stats['total']]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'items' => []]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getCartStats($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(p.price * c.quantity), 0) as total
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $count = (int)$result['count'];
    $total = (float)$result['total'];
    
    // Calculate Delivery Charge
    $deliveryCharge = ($total < 1000 && $count > 0) ? 80 : 0;
    
    // Calculate Discount if coupon applied
    $discount = 0;
    if (isset($_SESSION['applied_coupon'])) {
        $coupon = $_SESSION['applied_coupon'];
        // Re-calculate discount based on new total
        // Note: This is a simplified check. Ideally, we should re-validate the coupon fully.
        // For now, we'll just re-apply the percentage or fixed amount logic if possible, 
        // or rely on the client to refresh if complex validation is needed.
        // But since we want to avoid refresh, let's do a basic calc here.
        
        // However, checkCouponValidity() is called in the main flow which updates the session.
        // So we can just read the updated session value if checkCouponValidity was called before this.
        // But getCartStats is called inside the action blocks.
        // Let's rely on the fact that checkCouponValidity returns the updated discount.
        // We will pass the discount *into* this function or merge it outside.
        // Actually, let's just return the raw total and delivery here, and let the main block merge coupon data.
    }
    
    return [
        'count' => $count, 
        'total' => $total, 
        'delivery_charge' => $deliveryCharge
    ];
}
