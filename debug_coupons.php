<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupon_helpers.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Coupon Debugger</h2>";
echo "Current Time (Server): " . date('Y-m-d H:i:s') . "<br>";
echo "Timezone: " . date_default_timezone_get() . "<br>";

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$userId = $_SESSION['user_id'];
echo "User ID: $userId<br>";

// Check first user status
$isFirst = isFirstTimeUser($userId, $pdo);
echo "Is First Time User? " . ($isFirst ? "YES" : "NO") . "<br>";

// Get cart info
$stmt = $pdo->prepare("
    SELECT c.product_id, c.quantity, p.price, p.cat_id
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
echo "Cart Total: ₹$cartTotal<br>";
echo "Cart Items: " . count($cartItems) . "<br><hr>";

// Fetch ALL coupons
$stmt = $pdo->query("SELECT * FROM coupons");
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Analyzing " . count($coupons) . " Coupons:</h3>";

foreach ($coupons as $c) {
    echo "<div style='border:1px solid #ccc; padding:10px; margin-bottom:10px;'>";
    echo "<strong>Code: " . htmlspecialchars($c['code']) . "</strong><br>";
    echo "Status: " . $c['status'] . "<br>";
    echo "Start Date: " . $c['start_date'] . "<br>";
    echo "End Date: " . $c['end_date'] . "<br>";
    echo "Min Purchase: " . $c['min_purchase'] . "<br>";
    echo "Offer Type: " . $c['offer_type'] . "<br>";
    
    // Run validation
    $validation = validateCoupon($c['code'], $userId, $cartTotal, $cartItems, $pdo);
    
    if ($validation['valid']) {
        echo "<span style='color:green; font-weight:bold;'>PASSED VALIDATION</span>";
    } else {
        echo "<span style='color:red; font-weight:bold;'>FAILED: " . $validation['message'] . "</span>";
    }
    echo "</div>";
}
