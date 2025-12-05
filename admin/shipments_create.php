<?php
// admin/shipments_create.php
// Create a new shipment for an order

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

$page_title = 'Create Shipment';
include __DIR__ . '/layout/header.php';

// Fetch eligible orders (pending, processing, packed) that are not cancelled
// You might want to filter out orders that are already fully shipped if you track that,
// but for now we'll just show active orders.
try {
    $stmt = $pdo->query("SELECT id, order_number, customer_name, created_at, order_status FROM orders WHERE order_status NOT IN ('cancelled', 'delivered', 'returned') ORDER BY created_at DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $orders = [];
    error_log("Error fetching orders for shipment: " . $e->getMessage());
}

// Pre-select order if passed in URL
$selected_order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
?>

<div class="max-w-[800px] mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Create New Shipment</h1>
        <a href="shipments.php" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-gray-50">Cancel</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="shipments_action.php" method="post" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="action" value="create">

            <!-- Order Selection -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Select Order <span class="text-red-500">*</span></label>
                <select name="order_id" required class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <option value="">-- Choose an Order --</option>
                    <?php foreach ($orders as $o): ?>
                        <option value="<?php echo $o['id']; ?>" <?php if ($selected_order_id === $o['id']) echo 'selected'; ?>>
                            #<?php echo htmlspecialchars($o['order_number']); ?> - <?php echo htmlspecialchars($o['customer_name']); ?> (<?php echo ucfirst($o['order_status']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-slate-500 mt-1">Only active orders (not cancelled/delivered) are shown.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Carrier -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Carrier</label>
                    <input type="text" name="carrier" placeholder="e.g. FedEx, DHL, USPS" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>

                <!-- Tracking Number -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tracking Number</label>
                    <input type="text" name="tracking_number" placeholder="e.g. 1234567890" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Shipping Method -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Shipping Method</label>
                    <input type="text" name="shipping_method" placeholder="e.g. Standard, Express" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>

                <!-- Shipping Cost -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Shipping Cost</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-slate-400">₹</span>
                        <input type="number" step="0.01" name="shipping_cost" placeholder="0.00" class="w-full pl-7 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                </div>

                <!-- Weight -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Weight (kg)</label>
                    <input type="text" name="weight" placeholder="e.g. 1.5" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
            </div>

            <!-- Label File -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Upload Label (PDF)</label>
                <input type="file" name="label_pdf" accept="application/pdf" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="text-xs text-slate-500 mt-1">Optional. Upload the shipping label PDF provided by the carrier.</p>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Internal Notes</label>
                <textarea name="notes" rows="3" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" placeholder="Any internal notes about this shipment..."></textarea>
            </div>

            <!-- Options -->
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" id="mark_shipped" name="mark_order_shipped" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <label for="mark_shipped" class="text-sm text-slate-700">Update Order Status to "Shipped"</label>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                <a href="shipments.php" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-sm">Create Shipment</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
