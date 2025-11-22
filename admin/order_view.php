<?php
// admin/order_view.php
// Professional order details + status update page

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/header.php';

// ensure session (header/_auth might already start it)
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

// Handle POST status update (redirect after POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_status'])) {
    $newStatus = in_array($_POST['order_status'], $statuses, true) ? $_POST['order_status'] : null;
    $newPayment = in_array($_POST['payment_status'] ?? '', $payment_options, true) ? $_POST['payment_status'] : null;

    if ($newStatus !== null && $newPayment !== null) {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $newPayment, $id]);
        // optional: add order history/audit table here
    }
    // redirect to avoid double-post
    header("Location: order_view.php?id={$id}");
    exit;
}

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo '<div class="page-wrap"><div class="card">Order not found.</div></div>';
    include __DIR__ . '/footer.php';
    exit;
}

// Fetch order items (join product name)
$stmt = $pdo->prepare("SELECT oi.*, COALESCE(p.name, oi.product_name) AS product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// (Optional) decode JSON fields such as shipping address if stored as JSON
$shipping = [];
if (!empty($order['shipping_address'])) {
    $maybe = @json_decode($order['shipping_address'], true);
    if (is_array($maybe)) $shipping = $maybe;
}

// Helper for status badge (same style as orders page)
function status_badge($s) {
    $map = [
        'pending' => ['bg'=>'#fff7ed','color'=>'#c2410c','label'=>'Pending'],
        'processing' => ['bg'=>'#eef2ff','color'=>'#3730a3','label'=>'Processing'],
        'packed' => ['bg'=>'#f0fdf4','color'=>'#065f46','label'=>'Packed'],
        'shipped' => ['bg'=>'#ecfeff','color'=>'#0f766e','label'=>'Shipped'],
        'delivered' => ['bg'=>'#ecfdf5','color'=>'#065f46','label'=>'Delivered'],
        'cancelled' => ['bg'=>'#fff1f2','color'=>'#991b1b','label'=>'Cancelled'],
    ];
    $s = strtolower((string)$s);
    $info = $map[$s] ?? ['bg'=>'#f8fafc','color'=>'#334155','label'=>ucfirst($s)];
    return '<span style="display:inline-block;padding:6px 10px;border-radius:999px;background:'.htmlspecialchars($info['bg']).';color:'.htmlspecialchars($info['color']).';font-weight:700;font-size:13px;">'.htmlspecialchars($info['label']).'</span>';
}

// small helper for money
function money($amt) {
    return '₹ ' . number_format((float)$amt, 2);
}
?>
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
.page-wrap { max-width:1200px; margin:28px auto; padding:0 18px 60px; font-family:Inter,system-ui,Arial; color:#0f172a; }
.header { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:18px; }
.h-title { font-size:20px; font-weight:800; margin:0; }
.h-sub { color:#64748b; margin:0; font-size:13px; }
.grid-2 { display:grid; grid-template-columns: 1fr 360px; gap:18px; align-items:start; }
.card { background:#fff; padding:18px; border-radius:12px; border:1px solid #e6eef7; box-shadow:0 8px 30px rgba(2,6,23,0.04); }
.meta { display:flex; gap:12px; align-items:center; flex-wrap:wrap; color:#475569; font-size:13px; }
.table { width:100%; border-collapse:collapse; font-size:14px; margin-top:10px; }
.table th, .table td { padding:10px 12px; border-bottom:1px solid #f1f5f9; text-align:left; vertical-align:middle; }
.table th { background:#fbfcfe; color:#475569; font-weight:700; font-size:13px; }
.item-qty { font-weight:700; color:#111827; }
.summary-row { display:flex; justify-content:space-between; gap:12px; margin-top:8px; }
.small { font-size:13px; color:#64748b; }
.form-row { margin-top:8px; display:flex; gap:8px; align-items:center; }
.input-field, select { padding:8px 10px; border-radius:8px; border:1px solid #e6eef7; background:#fbfdff; font-size:14px; width:100%; box-sizing:border-box;}
.btn { padding:10px 12px; border-radius:8px; font-weight:700; cursor:pointer; border:0; }
.btn.primary { background:#0b76ff; color:#fff; }
.btn.ghost { background:#fff; border:1px solid #e6eef7; color:#0f172a; }
.badge { display:inline-block;padding:6px 10px;border-radius:999px;background:#f3f4f6;color:#111827;font-weight:700;font-size:13px; }
@media(max-width:980px){ .grid-2 { grid-template-columns: 1fr; } .header { flex-direction:column; align-items:flex-start; } }
</style>

<div class="page-wrap">
  <div class="header">
    <div>
      <h1 class="h-title">Order Details</h1>
      <p class="h-sub">Order overview, items and status. Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></p>
      <div class="meta" style="margin-top:8px;">
        <div class="small">Placed: <?php echo htmlspecialchars(date('d M Y H:i', strtotime($order['created_at']))); ?></div>
        <div class="small">Payment: <?php echo htmlspecialchars(ucfirst($order['payment_status'] ?? '')); ?></div>
        <div class="small">Customer: <?php echo htmlspecialchars($order['customer_name'] ?? '-'); ?></div>
      </div>
    </div>

    <div style="display:flex;gap:8px;align-items:center;">
      <a href="order_invoice.php?id=<?php echo (int)$order['id']; ?>" class="btn ghost">Download Invoice</a>
      <button onclick="window.print();" class="btn ghost">Print</button>
      <?php echo status_badge($order['order_status']); ?>
    </div>
  </div>

  <div class="grid-2">
    <!-- LEFT: items & customer -->
    <div class="card">
      <h3 style="margin:0 0 8px 0;">Items</h3>

      <table class="table" aria-describedby="order-items">
        <thead>
          <tr>
            <th style="width:48%;">Product</th>
            <th style="width:18%;">Unit Price</th>
            <th style="width:12%;">Qty</th>
            <th style="width:18%;">Line Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): 
              $lineTotal = ((float)$it['price']) * ((int)$it['qty']);
          ?>
            <tr>
              <td>
                <div style="font-weight:700;"><?php echo htmlspecialchars($it['product_name'] ?? ''); ?></div>
                <?php if (!empty($it['variant'])): ?>
                  <div class="small">Variant: <?php echo htmlspecialchars($it['variant']); ?></div>
                <?php endif; ?>
                <?php if (!empty($it['meta'])): ?>
                  <div class="small"><?php echo htmlspecialchars($it['meta']); ?></div>
                <?php endif; ?>
              </td>
              <td><?php echo money($it['price']); ?></td>
              <td><span class="item-qty"><?php echo (int)$it['qty']; ?></span></td>
              <td style="font-weight:700;"><?php echo money($lineTotal); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div style="margin-top:12px; display:flex; gap:12px; justify-content:flex-end;">
        <div style="width:360px;">
          <div class="summary-row"><div class="small">Subtotal</div><div><?php echo money($order['sub_total'] ?? $order['total_amount']); ?></div></div>
          <div class="summary-row"><div class="small">Shipping</div><div><?php echo money($order['shipping_amount'] ?? 0); ?></div></div>
          <div class="summary-row"><div class="small">Discount</div><div>- <?php echo money($order['discount_amount'] ?? 0); ?></div></div>
          <hr style="margin:10px 0;border:none;border-top:1px solid #eef2f7;">
          <div class="summary-row" style="font-size:18px;font-weight:800;"><div>Total</div><div><?php echo money($order['total_amount']); ?></div></div>
        </div>
      </div>

      <!-- Customer / Shipping info -->
      <h3 style="margin-top:18px;">Customer & Shipping</h3>
      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <div style="flex:1;min-width:220px;">
          <div class="small">Name</div>
          <div style="font-weight:700;"><?php echo htmlspecialchars($order['customer_name'] ?? '-'); ?></div>
          <?php if (!empty($order['customer_email'])): ?><div class="small" style="margin-top:6px;"><?php echo htmlspecialchars($order['customer_email']); ?></div><?php endif; ?>
          <?php if (!empty($order['customer_phone'])): ?><div class="small"><?php echo htmlspecialchars($order['customer_phone']); ?></div><?php endif; ?>
        </div>

        <div style="flex:1;min-width:220px;">
          <div class="small">Shipping Address</div>
          <?php if (!empty($shipping)): ?>
            <div style="font-weight:700;"><?php echo htmlspecialchars(implode(', ', array_filter([$shipping['name'] ?? '', $shipping['address'] ?? '', $shipping['city'] ?? '', $shipping['state'] ?? '', $shipping['postal'] ?? '']))); ?></div>
            <?php if (!empty($shipping['phone'])): ?><div class="small"><?php echo htmlspecialchars($shipping['phone']); ?></div><?php endif; ?>
          <?php else: ?>
            <div style="font-weight:700;"><?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? '—')); ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RIGHT: summary & actions -->
    <div class="card">
      <h3 style="margin:0 0 8px 0;">Order Summary</h3>
      <div style="display:grid;grid-template-columns:1fr auto;gap:8px 12px;margin-top:8px;">
        <div class="small">Order #</div><div style="font-weight:700;"><?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></div>
        <div class="small">Placed</div><div class="small"><?php echo htmlspecialchars(date('d M Y H:i', strtotime($order['created_at']))); ?></div>
        <div class="small">Payment</div><div class="small"><?php echo htmlspecialchars(ucfirst($order['payment_status'] ?? '')); ?></div>
        <div class="small">Status</div><div><?php echo status_badge($order['order_status']); ?></div>
        <div class="small">Items</div><div class="small"><?php echo count($items); ?></div>
        <div class="small">Total</div><div style="font-weight:800;"><?php echo money($order['total_amount']); ?></div>
      </div>

      <hr style="margin:12px 0;border:none;border-top:1px solid #eef2f7;">

      <h4 style="margin:0 0 8px 0;">Update Status</h4>
      <form method="post" style="margin-top:8px;">
        <label class="small" style="display:block;margin-bottom:6px;">Order Status</label>
        <select name="order_status" class="input-field" required>
          <?php foreach ($statuses as $s): ?>
            <option value="<?php echo htmlspecialchars($s); ?>" <?php if ($order['order_status'] === $s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
          <?php endforeach; ?>
        </select>

        <label class="small" style="display:block;margin:10px 0 6px 0;">Payment Status</label>
        <select name="payment_status" class="input-field" required>
          <?php foreach ($payment_options as $popt): ?>
            <option value="<?php echo htmlspecialchars($popt); ?>" <?php if ($order['payment_status'] === $popt) echo 'selected'; ?>><?php echo ucfirst($popt); ?></option>
          <?php endforeach; ?>
        </select>

        <div style="display:flex;gap:8px;margin-top:12px;">
          <button class="btn primary" type="submit">Save</button>
          <a class="btn ghost" href="orders.php">Back to Orders</a>
        </div>
      </form>

      <hr style="margin:12px 0;border:none;border-top:1px solid #eef2f7;">

      <h4 style="margin:0 0 8px 0;">Activity</h4>
      <div class="small" style="color:#64748b;">
        <?php
          // Placeholder: show updated_at or created_at and last status change
          echo 'Last updated: ' . htmlspecialchars(date('d M Y H:i', strtotime($order['updated_at'] ?? $order['created_at'])));
        ?>
      </div>

      <div style="margin-top:12px;">
        <a href="order_invoice.php?id=<?php echo (int)$order['id']; ?>" class="btn ghost" style="width:100%;">Download Invoice</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>