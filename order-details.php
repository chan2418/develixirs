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
    // Order not found or doesn't belong to user
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

// Decode shipping address if it's JSON
$shippingAddress = $order['shipping_address'] ?? '';
$shipping = [];

if (empty($shippingAddress)) {
    // Fallback: Fetch default address
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order #<?php echo htmlspecialchars($order['order_number']); ?> – Devilixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">

  <style>
    :root{
      --primary: #D4AF37;
      --primary-dark: #b5952f;
      --bg: #f5f5f5; /* Match other pages */
      --card-bg: #ffffff;
      --text-main: #2d3748;
      --text-muted: #718096;
      --border: #e2e8f0;
      --success: #48bb78;
      --danger: #f56565;
      --warning: #ed8936;
      --info: #4299e1;
    }
    body{
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text-main);
      /* padding-top removed to fix spacing issue */
      line-height: 1.6;
    }
    .container{
      max-width: 1000px;
      margin: 0 auto 60px;
      padding: 0 20px;
    }
    
    /* Header */
    .page-header{
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 30px;
    }
    .page-title{
      font-size: 28px;
      font-weight: 700;
      color: #1a202c;
      letter-spacing: -0.5px;
    }
    .back-link{
      font-size: 14px;
      color: var(--text-muted);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .back-link:hover{
      color: var(--primary);
    }

    /* Cards */
    .card{
      background: var(--card-bg);
      border-radius: 12px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
      padding: 30px;
      margin-bottom: 30px;
      border: 1px solid rgba(0,0,0,0.02);
    }

    /* Order Header Grid */
    .order-header{
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 24px;
      padding-bottom: 24px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 24px;
    }
    .order-meta-group{
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .meta-label{
      font-size: 11px;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      font-weight: 600;
    }
    .meta-value{
      font-size: 15px;
      font-weight: 600;
      color: var(--text-main);
    }
    
    /* Status Badges */
    .status-badge{
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 600;
      text-transform: capitalize;
      line-height: 1;
    }
    .status-badge::before {
        content: '';
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        margin-right: 6px;
        background-color: currentColor;
    }
    .status-pending{ background: #fffaf0; color: var(--warning); }
    .status-processing{ background: #ebf8ff; color: var(--info); }
    .status-shipped{ background: #e6fffa; color: #38b2ac; }
    .status-delivered{ background: #f0fff4; color: var(--success); }
    .status-cancelled{ background: #fff5f5; color: var(--danger); }

    /* Items Table */
    .items-table{
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 12px;
      margin-top: -12px;
    }
    .items-table th{
      text-align: left;
      padding: 0 10px 10px;
      font-size: 12px;
      color: var(--text-muted);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 1px solid var(--border);
    }
    .items-table td{
      padding: 16px 10px;
      vertical-align: middle;
      background: #fff;
      border-bottom: 1px solid var(--border);
    }
    .items-table tr:last-child td {
        border-bottom: none;
    }
    
    .item-product{
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .item-img{
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: #f7fafc;
    }
    .item-info{
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .item-name{
      font-size: 15px;
      font-weight: 600;
      color: var(--text-main);
    }
    .item-meta{
      font-size: 13px;
      color: var(--text-muted);
    }

    /* Summary Section */
    .summary-section{
      display: flex;
      justify-content: flex-end;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
    }
    .summary-box{
      width: 100%;
      max-width: 350px;
    }
    .summary-row{
      display: flex;
      justify-content: space-between;
      margin-bottom: 12px;
      font-size: 14px;
      color: var(--text-muted);
    }
    .summary-row span:last-child {
        color: var(--text-main);
        font-weight: 500;
    }
    .summary-row.total{
      margin-top: 16px;
      padding-top: 16px;
      border-top: 2px dashed var(--border);
      font-weight: 700;
      font-size: 18px;
      color: var(--text-main);
      align-items: center;
    }
    .summary-row.total span:last-child {
        color: var(--primary);
        font-size: 20px;
    }

    /* Address Section */
    .address-section{
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 40px;
    }
    .addr-box h3{
      font-size: 16px;
      margin-bottom: 16px;
      font-weight: 600;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .addr-box h3 i {
        color: var(--primary);
    }
    .addr-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid var(--border);
        height: 100%;
    }
    .addr-text{
      font-size: 14px;
      line-height: 1.7;
      color: #4a5568;
    }
    .addr-text strong {
        color: var(--text-main);
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
        font-size: 15px;
    }

    @media(max-width: 768px){
      .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
      .items-table th:nth-child(2), .items-table td:nth-child(2){ display: none; }
      .order-header { grid-template-columns: 1fr 1fr; gap: 16px; }
      .card { padding: 20px; }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<div class="container">
  <div class="page-header">
    <h1 class="page-title">Order Details</h1>
    <a href="my-profile.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to My Orders</a>
  </div>

  <div class="card">
    <div class="order-header">
      <div class="order-meta-group">
        <span class="meta-label">Order ID</span>
        <span class="meta-value">#<?php echo htmlspecialchars($order['order_number']); ?></span>
      </div>
      <div class="order-meta-group">
        <span class="meta-label">Date Placed</span>
        <span class="meta-value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
      </div>
      <div class="order-meta-group">
        <span class="meta-label">Total Amount</span>
        <span class="meta-value">₹<?php echo number_format($order['total_amount'], 2); ?></span>
      </div>
      <div class="order-meta-group">
        <span class="meta-label">Status</span>
        <span class="meta-value">
          <?php 
            $status = strtolower($order['order_status'] ?? 'pending');
            $class = 'status-' . $status;
          ?>
          <span class="status-badge <?php echo $class; ?>"><?php echo ucfirst($status); ?></span>
        </span>
      </div>
    </div>

    <table class="items-table">
      <thead>
        <tr>
          <th>Product</th>
          <th>Price</th>
          <th>Quantity</th>
          <th style="text-align:right;">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $productTotal = 0; // Init subtotal
        foreach ($items as $item): 
            $img = 'https://via.placeholder.com/60';
            if (!empty($item['product_images'])) {
                $imgs = json_decode($item['product_images'], true);
                if (is_array($imgs) && !empty($imgs[0])) {
                    $img = '/assets/uploads/products/' . $imgs[0];
                }
            }
            $lineTotal = $item['price'] * $item['qty'];
            $productTotal += $lineTotal; // Accumulate subtotal
        ?>
        <tr>
          <td>
            <div class="item-product">
              <img src="<?php echo htmlspecialchars($img); ?>" alt="" class="item-img">
              <div class="item-info">
                <div class="item-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></div>
                <!-- Optional: Variant info if available -->
              </div>
            </div>
          </td>
          <td>₹<?php echo number_format($item['price'], 2); ?></td>
          <td><?php echo (int)$item['qty']; ?></td>
          <td style="text-align:right;">₹<?php echo number_format($lineTotal, 2); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="summary-section">
      <div class="summary-box">
        <div class="summary-row">
          <span>Subtotal</span>
          <span>₹<?php echo number_format($productTotal, 2); ?></span>
        </div>
        <?php if (!empty($order['coupon_discount']) && $order['coupon_discount'] > 0): ?>
        <div class="summary-row" style="color: #28a745;">
          <span>Discount <?php echo !empty($order['coupon_code']) ? '(' . htmlspecialchars($order['coupon_code']) . ')' : ''; ?></span>
          <span>-₹<?php echo number_format($order['coupon_discount'], 2); ?></span>
        </div>
        <?php endif; ?>
        <div class="summary-row">
          <span>Shipping</span>
          <span><?php 
            $shippingDb = (float)($order['shipping_charge'] ?? 0);
            $discount   = (float)($order['coupon_discount'] ?? 0);
            $total      = (float)$order['total_amount'];
            
            // If DB has shipping, use it. Else infer: Total - Products + Discount
            $shippingVal = $shippingDb;
            if ($shippingVal <= 0) {
                $inferred = $total - $productTotal + $discount;
                // Allow small float margin or just round
                if ($inferred > 1) { 
                    $shippingVal = $inferred;
                }
            }
            
            echo ($shippingVal > 0) ? '₹' . number_format($shippingVal, 2) : 'Free'; 
          ?></span>
        </div>
        <div class="summary-row total">
          <span>Grand Total</span>
          <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="address-section">
      <div class="addr-box">
        <h3><i class="fa-solid fa-truck-fast"></i> Shipping Address</h3>
        <div class="addr-card">
            <div class="addr-text">
              <?php if (!empty($shipping)): ?>
                <strong><?php echo htmlspecialchars($shipping['name'] ?? ''); ?></strong>
                <?php echo htmlspecialchars($shipping['address'] ?? ''); ?><br>
                <?php echo htmlspecialchars($shipping['city'] ?? ''); ?>, <?php echo htmlspecialchars($shipping['state'] ?? ''); ?> - <?php echo htmlspecialchars($shipping['postal'] ?? ''); ?><br>
                Phone: <?php echo htmlspecialchars($shipping['phone'] ?? ''); ?>
              <?php else: ?>
                <?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? '')); ?>
              <?php endif; ?>
            </div>
        </div>
      </div>
      <div class="addr-box">
        <h3><i class="fa-solid fa-credit-card"></i> Payment Info</h3>
        <div class="addr-card">
            <div class="addr-text">
              Status: <strong><?php echo ucfirst($order['payment_status']); ?></strong><br>
              Method: Online / UPI <!-- Replace if method is stored -->
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>
