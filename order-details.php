<?php
require_once __DIR__ . '/includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

if ($orderId <= 0) {
    header("Location: my-profile.php");
    exit;
}

// Fetch Order
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ? 
    LIMIT 1
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: my-profile.php");
    exit;
}

// Fetch Order Items
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.name AS product_name, p.images AS product_images
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Decode shipping address
$shippingAddress = $order['shipping_address'] ?? '';
$shipping = [];

if (empty($shippingAddress)) {
    // Fallback
    $stmtAddr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC LIMIT 1");
    $stmtAddr->execute([$userId]);
    $fallbackAddr = $stmtAddr->fetch(PDO::FETCH_ASSOC);
    
    if ($fallbackAddr) {
        $shipping = [
            'name' => $fallbackAddr['full_name'],
            'address' => $fallbackAddr['address_line1'] . ($fallbackAddr['address_line2'] ? ', ' . $fallbackAddr['address_line2'] : ''),
            'city' => $fallbackAddr['city'],
            'state' => $fallbackAddr['state'],
            'postal' => $fallbackAddr['pincode'],
            'phone' => $fallbackAddr['phone']
        ];
    }
} else if (is_string($shippingAddress) && (str_starts_with($shippingAddress, '{') || str_starts_with($shippingAddress, '['))) {
    $decoded = json_decode($shippingAddress, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $shipping = $decoded;
    }
}

// Return Logic
$canReturn = false;
$returnStatus = null;
$returnRequest = null;

$created = strtotime($order['created_at']);
$diffDays = (time() - $created) / (60 * 60 * 24);

// Fetch Return Request if exists
try {
    $stmtRet = $pdo->prepare("SELECT * FROM order_returns WHERE order_id = ? LIMIT 1");
    $stmtRet->execute([$orderId]);
    $returnRequest = $stmtRet->fetch(PDO::FETCH_ASSOC);
    if ($returnRequest) {
        $returnStatus = $returnRequest['status'];
    }
} catch(Exception $e) {}

if ($diffDays <= 7 && strtolower($order['order_status']) !== 'cancelled' && !$returnStatus) {
    $canReturn = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order #<?php echo htmlspecialchars($order['order_number']); ?> – Devilixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    body { font-family: 'Outfit', sans-serif; background: #fdfbf7; color: #1f2937; }
    .gold-text { color: #D4AF37; }
    .bg-soft-gold { background-color: #fdfbf7; }
  </style>
</head>
<body class="flex flex-col min-h-screen">

<?php include __DIR__ . '/navbar.php'; ?>

<main class="flex-grow container mx-auto px-4 py-8 max-w-5xl">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="my-profile.php" class="text-gray-400 hover:text-gray-600 transition text-sm">
                    <i class="fa-solid fa-arrow-left"></i> Back to Orders
                </a>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">
                Order <span class="text-gray-500">#<?php echo htmlspecialchars($order['order_number']); ?></span>
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                Placed on <?php echo date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?>
            </p>
        </div>
        
         <div class="flex items-center gap-3">
             <a href="https://wa.me/919500650454?text=Help%20with%20Order%20%23<?php echo $order['order_number']; ?>" target="_blank" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-50 hover:border-gray-300 transition shadow-sm flex items-center gap-2">
               <i class="fa-brands fa-whatsapp text-green-500 text-lg"></i> Need Help?
             </a>
             
             <?php if ($returnStatus): ?>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-50 text-amber-700 rounded-xl font-medium border border-amber-200 shadow-sm">
                   <i class="fa-solid fa-clock-rotate-left"></i>
                   Return: <?php echo ucfirst($returnStatus); ?>
                </span>
             <?php elseif ($canReturn): ?>
                <a href="order-return.php?id=<?php echo $orderId; ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-red-600 rounded-xl font-medium border border-red-200 shadow-sm hover:bg-red-50 hover:border-red-300 transition-all transform hover:-translate-y-0.5">
                   <i class="fa-solid fa-box-open"></i>
                   Return Items
                </a>
             <?php endif; ?>
         </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Col: Items & Return Info -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Items Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h2 class="font-semibold text-gray-800">Items Ordered</h2>
                    <span class="text-sm px-3 py-1 rounded-full 
                        <?php 
                        $st = strtolower($order['order_status']);
                        echo match($st) {
                            'delivered' => 'bg-green-100 text-green-700',
                            'shipped' => 'bg-blue-50 text-blue-700',
                            'cancelled' => 'bg-red-50 text-red-700',
                             default => 'bg-yellow-50 text-amber-700'
                        };
                        ?> font-medium">
                        <?php echo ucfirst($st); ?>
                    </span>
                </div>
                
                <div class="divide-y divide-gray-100">
                    <?php 
                    $productTotal = 0;
                    foreach ($items as $item): 
                        $img = 'https://via.placeholder.com/60';
                        if (!empty($item['product_images'])) {
                            $imgs = json_decode($item['product_images'], true);
                            if (is_array($imgs) && !empty($imgs[0])) {
                                $img = '/assets/uploads/products/' . $imgs[0];
                            }
                        }
                        $lineTotal = $item['price'] * $item['qty'];
                        $productTotal += $lineTotal;
                    ?>
                    <div class="p-6 flex gap-4 hover:bg-gray-50/50 transition-colors">
                        <div class="w-20 h-20 flex-shrink-0 rounded-lg border border-gray-200 overflow-hidden bg-white">
                            <img src="<?php echo htmlspecialchars($img); ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-grow">
                            <h3 class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></h3>
                            <div class="text-sm text-gray-500 mb-2">Quantity: <?php echo (int)$item['qty']; ?></div>
                            <div class="font-semibold text-gray-900">₹<?php echo number_format($item['price'], 2); ?></div>
                        </div>
                        <div class="text-right font-bold text-gray-900 self-center">
                            ₹<?php echo number_format($lineTotal, 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Return Details Card (If Exists) -->
            <?php if ($returnRequest): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-amber-50/30 flex items-center gap-2">
                    <i class="fa-solid fa-rotate-left text-amber-600"></i>
                    <h2 class="font-semibold text-gray-800">Return Request</h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="flex-grow">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1 block">Reason</label>
                            <p class="text-gray-700 text-sm leading-relaxed bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <?php echo nl2br(htmlspecialchars($returnRequest['reason'])); ?>
                            </p>
                        </div>
                        
                        <?php 
                        $retImages = json_decode($returnRequest['images'] ?? '[]', true);
                        if (!empty($retImages)): 
                        ?>
                        <div class="min-w-[150px]">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 block">Proof</label>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($retImages as $rImg): ?>
                                    <a href="assets/uploads/returns/<?php echo htmlspecialchars($rImg); ?>" target="_blank" class="w-12 h-12 rounded-lg border border-gray-200 overflow-hidden hover:border-amber-400 transition block">
                                        <img src="assets/uploads/returns/<?php echo htmlspecialchars($rImg); ?>" class="w-full h-full object-cover">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
                        <span>Requested Request ID: #RET-<?php echo $returnRequest['id']; ?></span>
                        <span class="px-2 py-1 rounded bg-amber-100 text-amber-700 font-semibold">
                            <?php echo ucfirst($returnRequest['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Right Col: Summary & Addresses -->
        <div class="space-y-6">
            
            <!-- Summary Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 border-b border-gray-100 pb-3 mb-4">Order Summary</h3>
                
                <?php
                    $couponDiscount = (float)($order['coupon_discount'] ?? 0);
                    $subscriptionDiscount = (float)($order['subscription_discount'] ?? 0);
                    $appliedDiscountType = (string)($order['applied_discount_type'] ?? '');
                    $displayDiscount = 0.0;
                    $displayDiscountLabel = 'Discount';

                    if ($appliedDiscountType === 'subscription' && $subscriptionDiscount > 0) {
                        $displayDiscount = $subscriptionDiscount;
                        $displayDiscountLabel = 'Subscription Discount';
                        if (!empty($order['subscription_plan_name'])) {
                            $displayDiscountLabel .= ' (' . $order['subscription_plan_name'] . ')';
                        }
                    } elseif ($couponDiscount > 0) {
                        $displayDiscount = $couponDiscount;
                        $displayDiscountLabel = 'Discount';
                        if (!empty($order['coupon_code'])) {
                            $displayDiscountLabel .= ' (' . $order['coupon_code'] . ')';
                        }
                    } elseif ($subscriptionDiscount > 0) {
                        $displayDiscount = $subscriptionDiscount;
                        $displayDiscountLabel = 'Subscription Discount';
                    }
                ?>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-500">
                        <span>Subtotal</span>
                        <span class="font-medium text-gray-900">₹<?php echo number_format($productTotal, 2); ?></span>
                    </div>
                    
                    <?php if ($displayDiscount > 0): ?>
                    <div class="flex justify-between text-green-600">
                        <span><?php echo htmlspecialchars($displayDiscountLabel); ?></span>
                        <span>-₹<?php echo number_format($displayDiscount, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-between text-gray-500">
                        <span>Shipping</span>
                        <span class="font-medium text-gray-900">
                        <?php 
                            $shippingDb = (float)($order['shipping_charge'] ?? 0);
                            $discount   = $displayDiscount;
                            $total      = (float)$order['total_amount'];
                            
                            $shippingVal = $shippingDb;
                            if ($shippingVal <= 0) {
                                $inferred = $total - $productTotal + $discount;
                                if ($inferred > 1) { 
                                    $shippingVal = $inferred;
                                }
                            }
                            echo ($shippingVal > 0) ? '₹' . number_format($shippingVal, 2) : 'Free'; 
                        ?>
                        </span>
                    </div>
                    
                    <div class="pt-3 mt-3 border-t border-dashed border-gray-200 flex justify-between items-center">
                        <span class="font-bold text-gray-900">Total</span>
                        <span class="font-bold text-xl text-yellow-600">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 border-b border-gray-100 pb-3 mb-4">Payment Details</h3>
                <div class="space-y-3 text-sm">
                     <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="font-medium px-2 py-0.5 rounded bg-green-50 text-green-700 text-xs uppercase tracking-wide">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Method</span>
                        <span class="font-medium text-gray-900">
                            <?php 
                                if (str_starts_with($order['order_number'], 'COD-')) {
                                    echo 'Cash on Delivery';
                                } else {
                                    echo 'Online / UPI';
                                }
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 border-b border-gray-100 pb-3 mb-4">Shipping Address</h3>
                <div class="text-sm text-gray-600 leading-relaxed">
                  <?php if (!empty($shipping)): ?>
                    <p class="font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($shipping['name'] ?? ''); ?></p>
                    <p><?php echo htmlspecialchars($shipping['address'] ?? ''); ?></p>
                    <p class="mb-2"><?php echo htmlspecialchars($shipping['city'] ?? ''); ?>, <?php echo htmlspecialchars($shipping['state'] ?? ''); ?> - <?php echo htmlspecialchars($shipping['postal'] ?? ''); ?></p>
                    <div class="flex items-center gap-2 text-gray-500 mt-2 pt-2 border-t border-gray-50">
                        <i class="fa-solid fa-phone text-xs"></i> 
                        <?php echo htmlspecialchars($shipping['phone'] ?? ''); ?>
                    </div>
                  <?php else: ?>
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? '')); ?>
                  <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
