<?php
// admin/order_view.php
// Professional order details + status update page

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// validate id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: orders.php');
    exit;
}

// Allowed statuses & payment options
$statuses = ['pending','processing','packed','shipped','delivered','cancelled'];
$payment_options = ['pending','paid','refunded'];

// Handle POST status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_status'])) {
    $newStatus = in_array($_POST['order_status'], $statuses, true) ? $_POST['order_status'] : null;
    $newPayment = in_array($_POST['payment_status'] ?? '', $payment_options, true) ? $_POST['payment_status'] : null;

    if ($newStatus !== null && $newPayment !== null) {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $newPayment, $id]);
    }
    header("Location: order_view.php?id={$id}");
    exit;
}

$page_title = 'Order #' . $id;
include __DIR__ . '/layout/header.php';

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<div class="max-w-[1200px] mx-auto p-6"><div class="bg-white p-6 rounded-lg shadow text-center">Order not found.</div></div>';
    include __DIR__ . '/layout/footer.php';
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("SELECT oi.*, COALESCE(p.name, oi.product_name) AS product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Decode shipping address if JSON
$shipping = [];
if (!empty($order['shipping_address'])) {
    $maybe = @json_decode($order['shipping_address'], true);
    if (is_array($maybe)) $shipping = $maybe;
}

// Helper for status badge
function status_badge($s) {
    $map = [
        'pending' => ['bg'=>'bg-amber-50','text'=>'text-amber-700','label'=>'Pending'],
        'processing' => ['bg'=>'bg-indigo-50','text'=>'text-indigo-700','label'=>'Processing'],
        'packed' => ['bg'=>'bg-green-50','text'=>'text-green-700','label'=>'Packed'],
        'shipped' => ['bg'=>'bg-teal-50','text'=>'text-teal-700','label'=>'Shipped'],
        'delivered' => ['bg'=>'bg-green-50','text'=>'text-green-700','label'=>'Delivered'],
        'cancelled' => ['bg'=>'bg-red-50','text'=>'text-red-700','label'=>'Cancelled'],
    ];
    $s = strtolower((string)$s);
    $info = $map[$s] ?? ['bg'=>'bg-slate-50','text'=>'text-slate-700','label'=>ucfirst($s)];
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $info['bg'] . ' ' . $info['text'] . '">' . htmlspecialchars($info['label']) . '</span>';
}
?>

<div class="max-w-[1200px] mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-800">Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></h1>
                <?php echo status_badge($order['order_status']); ?>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Placed on <?php echo date('M d, Y \a\t H:i', strtotime($order['created_at'])); ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                Print
            </button>
            <a href="order_invoice.php?id=<?php echo $id; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M8 12h8M8 8h8M8 4h8" /></svg>
                Download Invoice
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content (Left 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Items Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="font-semibold text-slate-800">Order Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-slate-500 font-medium border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3">Product</th>
                                <th class="px-6 py-3 text-right">Unit Price</th>
                                <th class="px-6 py-3 text-center">Qty</th>
                                <th class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($items as $it): 
                                $lineTotal = ((float)$it['price']) * ((int)$it['qty']);
                            ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900"><?php echo htmlspecialchars($it['product_name'] ?? ''); ?></div>
                                    <?php if (!empty($it['variant'])): ?>
                                        <div class="text-xs text-slate-500 mt-0.5">Variant: <?php echo htmlspecialchars($it['variant']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right text-slate-600">₹ <?php echo number_format($it['price'], 2); ?></td>
                                <td class="px-6 py-4 text-center text-slate-900 font-medium"><?php echo (int)$it['qty']; ?></td>
                                <td class="px-6 py-4 text-right text-slate-900 font-semibold">₹ <?php echo number_format($lineTotal, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Totals -->
                <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100">
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

                        $summarySubtotal = !empty($order['base_subtotal'])
                            ? max(0, (float)$order['base_subtotal'] - (float)($order['tax_amount'] ?? 0))
                            : (float)$order['total_amount'] - (float)($order['shipping_charge'] ?? 0) + $displayDiscount - (float)($order['tax_amount'] ?? 0);
                    ?>
                    <div class="max-w-xs ml-auto space-y-2">
                        <div class="flex justify-between text-sm text-slate-600">
                            <span>Subtotal</span>
                            <span>₹ <?php echo number_format($summarySubtotal, 2); ?></span>
                        </div>
                        <div class="flex justify-between text-sm text-slate-600">
                            <span>Tax (18% GST)</span>
                            <span>₹ <?php echo number_format($order['tax_amount'], 2); ?></span>
                        </div>
                        <div class="flex justify-between text-sm text-slate-600">
                            <span>Shipping</span>
                            <span>₹ <?php echo number_format($order['shipping_charge'] ?? 0, 2); ?></span>
                        </div>
                        <?php if ($displayDiscount > 0): ?>
                        <div class="flex justify-between text-sm text-green-600">
                            <span><?php echo htmlspecialchars($displayDiscountLabel); ?></span>
                            <span>- ₹ <?php echo number_format($displayDiscount, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t border-gray-200">
                            <span>Total</span>
                            <span>₹ <?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer & Shipping Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="font-semibold text-slate-800">Customer & Shipping Details</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Customer Information</h4>
                        <div class="flex items-start gap-3">
                            <div class="bg-indigo-50 text-indigo-600 rounded-full p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div>
                                <div class="font-medium text-slate-900"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></div>
                                <?php if (!empty($order['customer_email'])): ?>
                                    <div class="text-sm text-slate-500"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($order['customer_phone'])): ?>
                                    <div class="text-sm text-slate-500"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                                <?php endif; ?>
                                <div class="text-xs text-slate-400 mt-2 font-medium uppercase tracking-wider">Payment Method</div>
                                <div class="font-medium text-slate-700">
                                    <?php echo (str_starts_with($order['order_number'] ?? '', 'COD-')) ? 'Cash on Delivery' : 'Online / UPI'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Shipping Address</h4>
                        <div class="flex items-start gap-3">
                            <div class="bg-emerald-50 text-emerald-600 rounded-full p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div class="text-sm text-slate-600 leading-relaxed">
                                <?php if (!empty($shipping)): ?>
                                    <div class="font-medium text-slate-900 mb-1"><?php echo htmlspecialchars($shipping['name'] ?? ''); ?></div>
                                    <?php echo htmlspecialchars($shipping['address'] ?? ''); ?><br>
                                    <?php echo htmlspecialchars(implode(', ', array_filter([$shipping['city'] ?? '', $shipping['state'] ?? '', $shipping['postal'] ?? '']))); ?><br>
                                    <?php if (!empty($shipping['phone'])): ?>Phone: <?php echo htmlspecialchars($shipping['phone']); ?><?php endif; ?>
                                <?php else: ?>
                                    <?php 
                                        $addrToShow = $order['shipping_address'] ?? '';
                                        if (empty($addrToShow) || trim($addrToShow) === '') {
                                            $addrToShow = $order['customer_address'] ?? '';
                                        }
                                        echo nl2br(htmlspecialchars($addrToShow ?: 'No address provided')); 
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sidebar (Right col) -->
        <div class="space-y-6">
            
            <!-- Status Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-slate-800 mb-4">Update Status</h3>
                <form method="post" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Order Status</label>
                        <select name="order_status" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>" <?php if ($order['order_status'] === $s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 uppercase mb-1">Payment Status</label>
                        <select name="payment_status" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            <?php foreach ($payment_options as $popt): ?>
                                <option value="<?php echo htmlspecialchars($popt); ?>" <?php if ($order['payment_status'] === $popt) echo 'selected'; ?>><?php echo ucfirst($popt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg text-sm transition-colors">
                        Update Order
                    </button>
                </form>
            </div>

            <!-- Actions Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-slate-800 mb-4">Actions</h3>
                <div class="space-y-3">
                    <a href="orders.php" class="block w-full py-2 px-4 bg-white border border-gray-200 text-slate-600 font-medium rounded-lg text-sm text-center hover:bg-gray-50 transition-colors">
                        &larr; Back to Orders
                    </a>
                    
                    <?php if ($order['order_status'] !== 'cancelled'): ?>
                    <form method="post" onsubmit="return confirm('Are you sure you want to CANCEL this order? This action cannot be undone.');">
                        <input type="hidden" name="order_status" value="cancelled">
                        <input type="hidden" name="payment_status" value="<?php echo htmlspecialchars($order['payment_status']); ?>">
                        <button type="submit" class="w-full py-2 px-4 bg-red-50 text-red-600 border border-red-100 font-medium rounded-lg text-sm hover:bg-red-100 transition-colors">
                            Cancel Order
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
