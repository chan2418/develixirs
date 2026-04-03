<?php
// admin/invoice_cut.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_number_helper.php';
include __DIR__ . '/header.php';

// ensure admin session
if (session_status() === PHP_SESSION_NONE) session_start();

// id of order to invoice
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    echo '<div class="page-wrap"><div class="card">Missing order id.</div></div>';
    include __DIR__ . '/footer.php';
    exit;
}

// fetch order + items
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) throw new Exception("Order not found");

    // fetch items (assuming order_items with qty, price, name)
    $stmt = $pdo->prepare("SELECT oi.*, COALESCE(p.name, oi.product_name) AS product_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo '<div class="page-wrap"><div class="card">Error: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    include __DIR__ . '/footer.php';
    exit;
}

// If "issue" action posted -> create invoice record (idempotent: if invoice exists, reuse)
$createdInvoice = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'issue') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['flash_error'] = 'Invalid CSRF token';
    } else {
        try {
            // check if invoice already exists
            $stmt = $pdo->prepare("SELECT * FROM invoices WHERE order_id = ? LIMIT 1");
            $stmt->execute([$order_id]);
            $inv = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($inv) {
                $createdInvoice = $inv;
            } else {
                // create invoice number with shared format
                $base = build_invoice_number($pdo, (int)$order_id, $order['created_at'] ?? null);
                // compute amount from order.total_amount (or sum items)
                $amount = (float)$order['total_amount'];

                $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, order_id, amount, status, created_by) VALUES (?, ?, ?, 'issued', ?)");
                $stmt->execute([$base, $order_id, $amount, $_SESSION['admin_id'] ?? null]);
                $newId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
                $stmt->execute([$newId]);
                $createdInvoice = $stmt->fetch(PDO::FETCH_ASSOC);

                // optional: update orders table to flag invoice_issued or order_status; keep simple
                $u = $pdo->prepare("UPDATE orders SET order_status = COALESCE(order_status, 'processing') WHERE id = ?");
                $u->execute([$order_id]);
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Failed to create invoice: ' . $e->getMessage();
        }
    }
}

// CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];
?>

<link rel="stylesheet" href="/assets/css/admin.css">
<style>
.invoice-card { background:#fff; padding:20px; border-radius:12px; border:1px solid #eef2f7; }
.invoice-top { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
.invoice-company { font-weight:800; font-size:18px; color:#0f172a; }
.invoice-meta { text-align:right; }
.table { width:100%; border-collapse:collapse; }
.table th, .table td { padding:10px; border-bottom:1px solid #f1f5f9; text-align:left; }
.print-btn { background:#0b76ff; color:#fff; padding:10px 12px; border-radius:8px; font-weight:700; text-decoration:none; }
.small { color:#64748b; font-size:13px; }
</style>

<div class="page-wrap">
  <div class="card invoice-card">
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div style="color:#b91c1c; font-weight:700; margin-bottom:10px;"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <div class="invoice-top">
      <div>
        <div class="invoice-company">Your Company Name</div>
        <div class="small">Address line 1<br>City, State ZIP<br>Phone: +91 xxxxx</div>
      </div>

      <div class="invoice-meta">
        <?php if ($createdInvoice): ?>
          <div><strong>Invoice:</strong> <?php echo htmlspecialchars($createdInvoice['invoice_number']); ?></div>
          <div class="small">Issued: <?php echo htmlspecialchars(date('d M Y', strtotime($createdInvoice['created_at']))); ?></div>
          <div class="small">Status: <?php echo htmlspecialchars(ucfirst($createdInvoice['status'])); ?></div>
        <?php else: ?>
          <div class="small">No invoice issued yet</div>
        <?php endif; ?>
        <div style="margin-top:8px;">
          <a href="#" class="print-btn" onclick="window.print();return false;">Print / Save PDF</a>
        </div>
      </div>
    </div>

    <hr style="margin:14px 0 18px; border:none; border-top:1px solid #f1f5f9;">

    <div style="display:flex; gap:18px; margin-bottom:14px;">
      <div style="flex:1;">
        <div class="small">Bill To</div>
        <div style="font-weight:700; margin-top:6px;"><?php echo htmlspecialchars($order['customer_name'] ?? $order['email'] ?? '-'); ?></div>
        <div class="small"><?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? '')); ?></div>
      </div>

      <div style="width:220px;">
        <div class="small">Order #</div>
        <div style="font-weight:700; margin-top:6px;"><?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></div>
        <div class="small">Placed: <?php echo htmlspecialchars(date('d M Y', strtotime($order['created_at']))); ?></div>
      </div>
    </div>

    <table class="table" aria-describedby="invoice-items">
      <thead>
        <tr>
          <th style="width:56%;">Product</th>
          <th style="width:12%;">Qty</th>
          <th style="width:16%;">Unit</th>
          <th style="width:16%;">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $sub = 0.0;
          if (!empty($items)):
            foreach ($items as $it):
              $lineTotal = ((float)$it['price']) * ((int)$it['qty']);
              $sub += $lineTotal;
        ?>
          <tr>
            <td><?php echo htmlspecialchars($it['product_name']); ?></td>
            <td><?php echo (int)$it['qty']; ?></td>
            <td>₹ <?php echo number_format((float)$it['price'],2); ?></td>
            <td>₹ <?php echo number_format($lineTotal,2); ?></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="4" class="small">No items found for this order.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div style="display:flex; justify-content:flex-end; margin-top:12px; gap:20px; align-items:center;">
      <div style="width:320px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><div class="small">Subtotal</div><div class="small">₹ <?php echo number_format($sub,2); ?></div></div>
        <?php
          // taxes/shipping placeholders (adjust as needed)
          $shipping = (float)($order['shipping_amount'] ?? 0.00);
          $tax = (float)($order['tax_amount'] ?? 0.00);
        ?>
        <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><div class="small">Shipping</div><div class="small">₹ <?php echo number_format($shipping,2); ?></div></div>
        <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><div class="small">Tax</div><div class="small">₹ <?php echo number_format($tax,2); ?></div></div>
        <hr style="border:none;border-top:1px solid #f1f5f9;margin:8px 0;">
        <div style="display:flex; justify-content:space-between; font-weight:800; font-size:18px;"> <div>Total</div><div>₹ <?php echo number_format((float)$order['total_amount'],2); ?></div></div>
      </div>
    </div>

    <div style="margin-top:18px; display:flex; gap:12px;">
      <?php if (!$createdInvoice): ?>
        <form method="post" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="action" value="issue">
          <button class="btn" style="padding:10px 12px; background:#0b76ff;color:#fff;border-radius:8px;font-weight:700;border:0;">Issue Invoice</button>
        </form>
      <?php else: ?>
        <a href="invoice_clear.php?id=<?php echo (int)$createdInvoice['id']; ?>" class="btn" style="padding:10px 12px; background:#059669;color:#fff;border-radius:8px;font-weight:700;text-decoration:none;">Mark as Cleared</a>
        <a href="invoice_cut.php?order_id=<?php echo $order_id; ?>&print=1" class="btn" style="padding:10px 12px; background:#111827;color:#fff;border-radius:8px;font-weight:700;text-decoration:none;" onclick="window.print();return false;">Print</a>
      <?php endif; ?>

      <a href="orders.php" class="btn" style="padding:10px 12px; background:transparent;border:1px solid #eef2f7;border-radius:8px;color:#0b1220;font-weight:700;text-decoration:none;">Back to Orders</a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
