<?php
// CRITICAL: Prevent ANY non-JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start(); 

// Helper to reliably send JSON and exit
function sendJson($data) {
    ob_end_clean(); // Discard any prior output/warnings
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

try {
    // 1. Load Dependencies
    try {
        require_once __DIR__ . '/includes/db.php';
        require_once __DIR__ . '/includes/coupon_helpers.php';
        require_once __DIR__ . '/includes/order_pricing_helper.php';
        
        if (!isset($pdo)) {
            throw new Exception("Database connection failed");
        }
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
    }

    // 2. Start Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        sendJson(['success' => false, 'message' => 'Please login to use cart']);
    }

    $userId = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';

    // 3. Define Helper Functions (must be before usage or defined globally)
    function getCartPricingState(PDO $pdo, int $userId): array {
        $items = fetch_order_context_items($pdo, $userId, false);
        $pricing = calculate_order_pricing($pdo, $userId, $items, getAppliedCoupon());
        $couponStatus = null;

        if (!empty($pricing['coupon']['removed_message'])) {
            $couponStatus = [
                'status' => 'removed',
                'message' => $pricing['coupon']['removed_message'],
            ];
        }

        return [
            'count' => (int)($pricing['line_item_count'] ?? count($items)),
            'total' => (float)($pricing['base_subtotal'] ?? 0),
            'delivery_charge' => (float)($pricing['delivery_charge'] ?? 0),
            'grand_total' => (float)($pricing['final_total'] ?? 0),
            'pricing' => order_pricing_frontend_payload($pricing),
            'coupon_status' => $couponStatus,
        ];
    }

    // 4. Handle Actions
    if ($action === 'direct_buy') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if ($productId > 0 && $quantity > 0) {
            $_SESSION['direct_buy_item'] = [
                'product_id' => $productId,
                'quantity' => $quantity
            ];
            session_write_close(); 
            sendJson(['success' => true]);
        } else {
            sendJson(['success' => false, 'message' => 'Invalid product']);
        }
    }
    
    elseif ($action === 'add') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if ($productId <= 0 || $quantity <= 0) {
            sendJson(['success' => false, 'message' => 'Invalid product or quantity']);
        }

        // Check if product already in cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            $upd = $pdo->prepare("UPDATE cart SET quantity = :qty WHERE id = :id");
            $upd->execute([':qty' => $newQty, ':id' => $existing['id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:uid, :pid, :qty)");
            $ins->execute([':uid' => $userId, ':pid' => $productId, ':qty' => $quantity]);
        }

        $stats = getCartPricingState($pdo, $userId);
        
        sendJson([
            'success' => true, 
            'message' => 'Added to cart', 
            'count' => $stats['count'], 
            'total' => $stats['total'],
            'delivery_charge' => $stats['delivery_charge'],
            'grand_total' => $stats['grand_total'],
            'pricing' => $stats['pricing'],
            'coupon_status' => $stats['coupon_status']
        ]);
    } 
    
    elseif ($action === 'remove') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($productId <= 0) sendJson(['success' => false, 'message' => 'Invalid product']);

        $del = $pdo->prepare("DELETE FROM cart WHERE user_id = :uid AND product_id = :pid");
        $del->execute([':uid' => $userId, ':pid' => $productId]);

        $stats = getCartPricingState($pdo, $userId);
        
        sendJson([
            'success' => true, 
            'message' => 'Removed from cart', 
            'count' => $stats['count'], 
            'total' => $stats['total'],
            'delivery_charge' => $stats['delivery_charge'],
            'grand_total' => $stats['grand_total'],
            'pricing' => $stats['pricing'],
            'coupon_status' => $stats['coupon_status']
        ]);
    } 
    
    elseif ($action === 'update_quantity') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        if ($productId <= 0) sendJson(['success' => false, 'message' => 'Invalid product']);

        if ($quantity <= 0) {
            $del = $pdo->prepare("DELETE FROM cart WHERE user_id = :uid AND product_id = :pid");
            $del->execute([':uid' => $userId, ':pid' => $productId]);
        } else {
            $upd = $pdo->prepare("UPDATE cart SET quantity = :qty WHERE user_id = :uid AND product_id = :pid");
            $upd->execute([':qty' => $quantity, ':uid' => $userId, ':pid' => $productId]);
        }

        $stats = getCartPricingState($pdo, $userId);
        
        sendJson([
            'success' => true, 
            'count' => $stats['count'], 
            'total' => $stats['total'],
            'delivery_charge' => $stats['delivery_charge'],
            'grand_total' => $stats['grand_total'],
            'pricing' => $stats['pricing'],
            'coupon_status' => $stats['coupon_status']
        ]);
    } 
    
    elseif ($action === 'get_all') {
        $stmt = $pdo->prepare("SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.images FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = getCartPricingState($pdo, $userId);
        sendJson([
            'success' => true,
            'items' => $items,
            'count' => $stats['count'],
            'total' => $stats['total'],
            'delivery_charge' => $stats['delivery_charge'],
            'grand_total' => $stats['grand_total'],
            'pricing' => $stats['pricing'],
        ]);
    } 
    
    else {
        sendJson(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Throwable $e) {
    sendJson(['success' => false, 'message' => 'Unexpected Error: ' . $e->getMessage()]);
}
