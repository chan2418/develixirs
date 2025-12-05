<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupon_helpers.php';

session_start();
$userId = $_SESSION['user_id'] ?? 1; 

echo "User ID: $userId\n";

// Fetch all active coupons
$stmt = $pdo->prepare("SELECT * FROM coupons WHERE status = 'active'");
$stmt->execute();
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cartTotal = 100; // Dummy total
$cartItems = []; // Dummy items

// Insert dummy order to simulate existing user
$pdo->exec("INSERT INTO orders (user_id, status, total_amount) VALUES ($userId, 'completed', 500)");
echo "Inserted dummy order for User $userId\n";

foreach ($coupons as $c) {
    echo "\n--------------------------------\n";
    echo "Checking Coupon: {$c['code']} (Type: {$c['offer_type']})\n";
    
    $validation = validateCoupon($c['code'], $userId, $cartTotal, $cartItems, $pdo);
    
    echo "Valid: " . ($validation['valid'] ? 'Yes' : 'No') . "\n";
    echo "Message: {$validation['message']}\n";
    echo "Reason Code: " . ($validation['reason_code'] ?? 'N/A') . "\n";
    
    // Also explicitly check usage count
    $stmt = $pdo->prepare("SELECT COUNT(*) as usage_count FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
    $stmt->execute([$c['id'], $userId]);
    $usage = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Actual DB Usage Count: {$usage['usage_count']}\n";
    
    // Check first user status
    if ($c['offer_type'] === 'first_user') {
        echo "Is First Time User? " . (isFirstTimeUser($userId, $pdo) ? 'Yes' : 'No') . "\n";
    }
}
?>
