<?php
// admin/invoice_create.php
// List orders eligible for invoicing (no invoice yet) and allow generation.

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_number_helper.php';

$page_title = 'Create Invoice';
include __DIR__ . '/layout/header.php';

// Handle generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    
    // Check if invoice already exists
    $stmt = $pdo->prepare("SELECT id FROM invoices WHERE order_id = ?");
    $stmt->execute([$orderId]);
    if ($stmt->fetch()) {
        $_SESSION['flash_error'] = 'Invoice already exists for this order.';
        header('Location: invoice_create.php');
        exit;
    }

    // Fetch order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Generate Invoice Number with shared format
        $invoiceNum = build_invoice_number($pdo, (int)$orderId, $order['created_at'] ?? null);
        
        // Create Invoice
        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                order_id, invoice_number, amount, tax_amount, status, created_at
            ) VALUES (
                ?, ?, ?, ?, 'issued', NOW()
            )
        ");
        $stmt->execute([
            $orderId,
            $invoiceNum,
            $order['total_amount'],
            $order['tax_amount'] ?? 0
        ]);
        $invoiceId = $pdo->lastInsertId();

        // Copy items to invoice_items (optional but good for snapshot)
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$orderId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $stmtInsItem = $pdo->prepare("
            INSERT INTO invoice_items (
                invoice_id, description, qty, unit_price, amount
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $desc = $item['product_name'];
            if (!empty($item['variant'])) $desc .= ' (' . $item['variant'] . ')';
            
            $lineTotal = $item['price'] * $item['qty'];
            
            $stmtInsItem->execute([
                $invoiceId,
                $desc,
                $item['qty'],
                $item['price'],
                $lineTotal
            ]);
        }

        $_SESSION['flash_success'] = 'Invoice generated successfully.';
        header('Location: invoices.php');
        exit;
    }
}

// Fetch orders without invoices
$sql = "
    SELECT o.* 
    FROM orders o 
    LEFT JOIN invoices i ON o.id = i.order_id 
    WHERE i.id IS NULL 
    ORDER BY o.created_at DESC 
    LIMIT 50
";
$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="max-w-[1000px] mx-auto mt-8 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Create Invoice</h1>
            <p class="text-slate-500 text-sm">Select an order to generate an invoice.</p>
        </div>
        <a href="invoices.php" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-slate-700 hover:bg-gray-50">Back to Invoices</a>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="mb-4 p-4 bg-red-50 text-red-700 border border-red-200 rounded-lg">
            <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Order</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Customer</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Date</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Total</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                            All orders have invoices!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900"><?= htmlspecialchars($order['order_number']) ?></div>
                                <div class="text-xs text-slate-500">ID: <?= $order['id'] ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900"><?= htmlspecialchars($order['customer_name']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-sm">
                                <?= date('M j, Y', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                ₹<?= number_format($order['total_amount'], 2) ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="post">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                        Generate
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
