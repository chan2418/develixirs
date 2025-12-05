<?php
require_once __DIR__ . '/includes/db.php';
session_start();

$order = null;
$error = '';
$orderId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = trim($_POST['order_id'] ?? '');
    
    if (empty($orderId)) {
        $error = 'Please enter an Order ID.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM orders 
                WHERE order_number = :ord_num 
                LIMIT 1
            ");
            $stmt->execute([':ord_num' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                $error = 'Order not found. Please check your Order ID and try again.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred while searching. Please try again later.';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Track Order – Devilixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  
  <style>
    :root{
      --primary:#D4AF37;
      --primary-dark:#B89026;
      --text:#1a1a1a;
      --text-light:#666;
      --border:#e0e0e0;
      --bg-light:#f5f5f5;
    }
    *{margin:0;padding:0;box-sizing:border-box;}
    body{
      font-family:'Poppins', sans-serif;
      color:var(--text);
      background:#fff;
      line-height:1.6;
      padding-top: 140px;
    }
    
    .track-container {
        max-width: 800px;
        margin: 0 auto 60px;
        padding: 0 20px;
    }
    
    .track-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .track-header h1 {
        font-size: 32px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .track-header p {
        color: var(--text-light);
    }
    
    .track-form-box {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 40px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        text-align: center;
    }
    
    .track-input-group {
        max-width: 400px;
        margin: 0 auto 20px;
        text-align: left;
    }
    .track-input-group label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 8px;
    }
    .track-input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.3s;
    }
    .track-input:focus {
        border-color: var(--primary);
    }
    
    .track-btn {
        background: var(--primary);
        color: #fff;
        border: none;
        padding: 12px 30px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .track-btn:hover {
        background: var(--primary-dark);
    }
    
    .error-msg {
        color: #d32f2f;
        background: #ffebee;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 20px;
        font-size: 14px;
    }
    
    /* Order Result Styles */
    .order-result {
        margin-top: 40px;
        border: 1px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
    }
    .order-result-header {
        background: #f9f9f9;
        padding: 20px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .order-id-display {
        font-size: 18px;
        font-weight: 600;
    }
    .order-date-display {
        font-size: 14px;
        color: var(--text-light);
    }
    
    .order-status-bar {
        padding: 40px 20px;
        display: flex;
        justify-content: space-between;
        position: relative;
        max-width: 600px;
        margin: 0 auto;
    }
    .order-status-bar::before {
        content: '';
        position: absolute;
        top: 55px;
        left: 40px;
        right: 40px;
        height: 4px;
        background: #eee;
        z-index: 0;
    }
    
    .status-step {
        position: relative;
        z-index: 1;
        text-align: center;
        width: 80px;
    }
    .step-icon {
        width: 34px;
        height: 34px;
        background: #fff;
        border: 2px solid #ddd;
        border-radius: 50%;
        margin: 0 auto 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ddd;
        font-size: 14px;
        transition: all 0.3s;
    }
    .step-label {
        font-size: 12px;
        color: #999;
        font-weight: 500;
    }
    
    /* Active State Logic */
    .status-step.active .step-icon {
        border-color: var(--primary);
        background: var(--primary);
        color: #fff;
    }
    .status-step.active .step-label {
        color: var(--text);
        font-weight: 600;
    }
    .status-step.completed .step-icon {
        border-color: var(--primary);
        background: var(--primary);
        color: #fff;
    }
    .status-step.completed .step-label {
        color: var(--primary);
    }
    
    /* Progress Line Fill */
    .progress-fill {
        position: absolute;
        top: 55px;
        left: 40px;
        height: 4px;
        background: var(--primary);
        z-index: 0;
        width: 0%; /* Dynamic */
        transition: width 0.5s ease;
    }
    
    .order-details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        padding: 20px;
        border-top: 1px solid var(--border);
    }
    .detail-box h4 {
        font-size: 14px;
        text-transform: uppercase;
        color: var(--text-light);
        margin-bottom: 8px;
    }
    .detail-box p {
        font-size: 15px;
        font-weight: 500;
    }
    
    @media (max-width: 600px) {
        .order-status-bar {
            flex-direction: column;
            gap: 30px;
            align-items: flex-start;
            padding-left: 40px;
        }
        .order-status-bar::before {
            top: 40px;
            left: 56px;
            right: auto;
            width: 4px;
            height: calc(100% - 80px);
        }
        .progress-fill {
            top: 40px;
            left: 56px;
            width: 4px;
            height: 0%; /* Dynamic height for mobile */
        }
        .status-step {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
            text-align: left;
        }
        .step-icon {
            margin: 0;
        }
        .order-details-grid {
            grid-template-columns: 1fr;
        }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<div class="track-container">
    <div class="track-header">
        <h1>Track Your Order</h1>
        <p>Enter your Order ID to see the current status.</p>
    </div>

    <div class="track-form-box">
        <?php if ($error): ?>
            <div class="error-msg">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="track-input-group">
                <label for="order_id">Order ID</label>
                <input type="text" id="order_id" name="order_id" class="track-input" placeholder="e.g. ORD-1733076543" value="<?php echo htmlspecialchars($orderId); ?>" required>
            </div>
            <button type="submit" class="track-btn">Track Order</button>
        </form>
    </div>

    <?php if ($order): ?>
        <?php 
            $status = strtolower($order['status'] ?? 'pending');
            $progress = 0;
            
            // Determine progress
            switch($status) {
                case 'pending': $progress = 10; break;
                case 'processing': $progress = 35; break;
                case 'shipped': $progress = 65; break;
                case 'delivered': $progress = 100; break;
                case 'cancelled': $progress = 0; break;
                default: $progress = 10;
            }
        ?>
        <div class="order-result">
            <div class="order-result-header">
                <div class="order-id-display">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                <div class="order-date-display">Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
            </div>
            
            <?php if ($status === 'cancelled'): ?>
                <div style="padding: 40px; text-align: center; color: #d32f2f;">
                    <i class="fa-solid fa-ban" style="font-size: 40px; margin-bottom: 15px;"></i>
                    <h3>Order Cancelled</h3>
                    <p>This order has been cancelled.</p>
                </div>
            <?php else: ?>
                <div class="order-status-bar">
                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                    
                    <div class="status-step <?php echo ($progress >= 10) ? 'active' : ''; ?>">
                        <div class="step-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                        <div class="step-label">Order Placed</div>
                    </div>
                    
                    <div class="status-step <?php echo ($progress >= 35) ? 'active' : ''; ?>">
                        <div class="step-icon"><i class="fa-solid fa-box-open"></i></div>
                        <div class="step-label">Processing</div>
                    </div>
                    
                    <div class="status-step <?php echo ($progress >= 65) ? 'active' : ''; ?>">
                        <div class="step-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="step-label">Shipped</div>
                    </div>
                    
                    <div class="status-step <?php echo ($progress >= 100) ? 'active' : ''; ?>">
                        <div class="step-icon"><i class="fa-solid fa-house-chimney"></i></div>
                        <div class="step-label">Delivered</div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="order-details-grid">
                <div class="detail-box">
                    <h4>Current Status</h4>
                    <p style="text-transform: capitalize; color: var(--primary-dark);"><?php echo htmlspecialchars($order['status']); ?></p>
                </div>
                <div class="detail-box">
                    <h4>Total Amount</h4>
                    <p>₹<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>
                <div class="detail-box">
                    <h4>Payment Status</h4>
                    <p style="text-transform: capitalize;"><?php echo htmlspecialchars($order['payment_status']); ?></p>
                </div>
                <div class="detail-box">
                    <h4>Customer Name</h4>
                    <p><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

</body>
</html>
