<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupon_helpers.php';

session_start();
$userId = $_SESSION['user_id'] ?? 1; // Default to 1 if not logged in for testing

echo "User ID: $userId\n";

// 1. Check Orders
echo "\n--- Orders ---\n";
$stmt = $pdo->prepare("SELECT id, status, order_status FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($orders);

// 2. Check isFirstTimeUser result
echo "\nisFirstTimeUser($userId): " . (isFirstTimeUser($userId, $pdo) ? 'TRUE' : 'FALSE') . "\n";

// 3. Check Coupon Usage
echo "\n--- Coupon Usage ---\n";
$stmt = $pdo->prepare("SELECT * FROM coupon_usage WHERE user_id = ?");
$stmt->execute([$userId]);
$usage = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($usage);

// 4. Check Coupon Details (assuming 'FIRST50' or similar)
echo "\n--- Coupons ---\n";
$stmt = $pdo->query("SELECT * FROM coupons");
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($coupons as $c) {
    echo "Code: {$c['code']}, Type: {$c['offer_type']}, Limit: {$c['usage_limit_per_user']}\n";
}
?>
