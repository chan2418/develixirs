<?php
require_once __DIR__ . '/includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$cartItems = [];
$cartTotal = 0;

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.images
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total
    foreach ($cartItems as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    $cartItems = [];
}

// Fetch default address
$defaultAddress = null;
try {
    $stmtAddr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = :uid AND is_default = 1 LIMIT 1");
    $stmtAddr->execute([':uid' => $userId]);
    $defaultAddress = $stmtAddr->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $defaultAddress = null;
}

function get_first_image($images) {
    $default = '/assets/images/avatar-default.png';
    if (!$images) return $default;
    $maybe = @json_decode($images, true);
    if (is_array($maybe) && !empty($maybe[0])) {
        $val = $maybe[0];
    } else {
        if (strpos($images, ',') !== false) {
            $parts = array_map('trim', explode(',', $images));
            $val = $parts[0] ?? '';
        } else {
            $val = trim($images);
        }
    }
    if (!$val) return $default;
    if (preg_match('#^https?://#i', $val) || strpos($val, '/') === 0) {
        return $val;
    }
    return '/assets/uploads/products/' . ltrim($val, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>My Cart – Devilixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  <?php include __DIR__ . '/navbar.php'; ?>
  
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "Poppins", sans-serif; background: #f5f5f5; color: #333; }
    .container { max-width: 1400px; margin: 0 auto; padding: 30px 20px 60px; }
    
    h1 { 
      font-size: 32px;
      font-weight: 700;
      margin-bottom: 10px;
      color: #1a1a1a;
    }
    
    .cart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid #e0e0e0;
    }
    
    .cart-header-left h1 { margin-bottom: 5px; }
    .cart-header-left p { color: #666; font-size: 14px; }
    .continue-shopping {
      text-decoration: none;
      color: #D4AF37;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
    }
    .continue-shopping:hover { color: #B89026; }
    
    .delivery-address {
      background: #fff;
      border-radius: 12px;
      padding: 20px 24px;
      margin-bottom: 30px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
    }
    
    .address-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }
    
    .address-header i {
      font-size: 20px;
      color: #D4AF37;
    }
    
    .address-header h3 {
      font-size: 16px;
      font-weight: 600;
      color: #1a1a1a;
    }
    
    .address-content {
      flex: 1;
    }
    
    .address-details {
      color: #666;
      font-size: 14px;
      line-height: 1.6;
    }
    
    .address-name {
      font-weight: 600;
      color: #1a1a1a;
      margin-bottom: 4px;
    }
    
    .address-phone {
      color: #888;
      font-size: 13px;
      margin-bottom: 8px;
    }
    
    .change-address-btn {
      background: none;
      border: 2px solid #D4AF37;
      color: #D4AF37;
      padding: 8px 20px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      white-space: nowrap;
    }
    
    .change-address-btn:hover {
      background: #D4AF37;
      color: #fff;
    }
    
    .no-address {
      background: #fff9e6;
      border: 2px dashed #D4AF37;
      border-radius: 12px;
      padding: 20px 24px;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    
    .no-address i {
      font-size: 24px;
      color: #D4AF37;
    }
    
    .no-address-text {
      flex: 1;
    }
    
    .no-address-text p {
      color: #666;
      font-size: 14px;
      margin-bottom: 4px;
    }
    
    .add-address-btn {
      background: #D4AF37;
      color: #fff;
      border: none;
      padding: 10px 24px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-block;
    }
    
    .add-address-btn:hover {
      background: #B89026;
    }
    
    .cart-wrapper { 
      display: grid; 
      grid-template-columns: 1fr 380px; 
      gap: 30px; 
      align-items: start;
    }
    
    .cart-items { 
      background: #fff; 
      border-radius: 12px; 
      padding: 0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    
    .cart-item {
      display: grid;
      grid-template-columns: 140px 1fr auto;
      gap: 20px;
      padding: 24px;
      border-bottom: 1px solid #f0f0f0;
      transition: background 0.2s;
    }
    .cart-item:hover { background: #fafafa; }
    .cart-item:last-child { border-bottom: none; }
    
    .item-image { 
      width: 140px; 
      height: 140px; 
      border-radius: 8px;
      overflow: hidden;
      background: #f8f8f8;
    }
    .item-image img { 
      width: 100%; 
      height: 100%; 
      object-fit: cover;
    }
    
    .item-details { 
      display: flex; 
      flex-direction: column; 
      justify-content: space-between;
      min-width: 0;
    }
    
    .item-header {
      margin-bottom: 8px;
    }
    
    .item-name { 
      font-size: 18px; 
      font-weight: 600; 
      margin-bottom: 6px;
      color: #1a1a1a;
      line-height: 1.4;
    }
    
    .item-meta {
      font-size: 13px;
      color: #888;
      margin-bottom: 12px;
    }
    
    .item-price { 
      color: #D4AF37; 
      font-weight: 700; 
      font-size: 24px;
      margin-bottom: 16px;
    }
    
    .item-actions {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    
    .quantity-control { 
      display: inline-flex; 
      align-items: center;
      border: 2px solid #e0e0e0; 
      border-radius: 8px;
      overflow: hidden;
      background: #fff;
    }
    .quantity-control button {
      background: #fff;
      border: none;
      padding: 10px 16px;
      cursor: pointer;
      font-size: 18px;
      font-weight: 600;
      color: #666;
      transition: all 0.2s;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .quantity-control button:hover { 
      background: #f5f5f5;
      color: #D4AF37;
    }
    .quantity-control input {
      width: 50px;
      text-align: center;
      border: none;
      border-left: 1px solid #e0e0e0;
      border-right: 1px solid #e0e0e0;
      font-size: 16px;
      font-weight: 600;
      height: 40px;
      color: #1a1a1a;
    }
    
    .remove-btn {
      background: none;
      border: none;
      color: #999;
      cursor: pointer;
      font-size: 13px;
      font-weight: 500;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 12px;
      border-radius: 6px;
      transition: all 0.2s;
    }
    .remove-btn:hover { 
      background: #fff0f0;
      color: #ff4d4d;
    }
    
    .item-subtotal {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      justify-content: space-between;
      min-width: 120px;
    }
    
    .subtotal-label {
      font-size: 12px;
      color: #999;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .subtotal-amount {
      font-size: 22px;
      font-weight: 700;
      color: #1a1a1a;
    }
    
    .cart-summary {
      background: #fff;
      border-radius: 12px;
      padding: 28px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      position: sticky;
      top: 100px;
    }
    
    .summary-title { 
      font-size: 20px; 
      font-weight: 700; 
      margin-bottom: 24px;
      color: #1a1a1a;
    }
    
    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 16px;
      font-size: 15px;
      color: #666;
    }
    .summary-row span:last-child {
      font-weight: 600;
      color: #1a1a1a;
    }
    
    .summary-divider {
      height: 1px;
      background: #e0e0e0;
      margin: 20px 0;
    }
    
    .summary-total {
      display: flex;
      justify-content: space-between;
      padding: 20px 0;
      font-size: 20px;
      font-weight: 700;
      color: #1a1a1a;
    }
    .summary-total span:last-child {
      color: #D4AF37;
      font-size: 24px;
    }
    
    .promo-code {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #e0e0e0;
    }
    
    .promo-input-group {
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }
    
    .promo-input {
      flex: 1;
      padding: 12px 16px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      font-family: "Poppins", sans-serif;
    }
    .promo-input:focus {
      outline: none;
      border-color: #D4AF37;
    }
    
    .apply-btn {
      padding: 12px 24px;
      background: #f5f5f5;
      color: #666;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      font-family: "Poppins", sans-serif;
    }
    .apply-btn:hover {
      background: #D4AF37;
      color: #fff;
    }
    
    .checkout-btn {
      width: 100%;
      padding: 16px;
      background: #D4AF37;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 24px;
      font-family: "Poppins", sans-serif;
      box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
    }
    .checkout-btn:hover { 
      background: #B89026;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(212, 175, 55, 0.4);
    }
    
    .secure-checkout {
      text-align: center;
      margin-top: 16px;
      font-size: 13px;
      color: #999;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    .secure-checkout i {
      color: #4CAF50;
    }
    
    .empty-state { 
      text-align: center; 
      padding: 100px 20px;
      background: #fff;
      border-radius: 12px;
    }
    .empty-state i { 
      font-size: 80px; 
      color: #e0e0e0; 
      margin-bottom: 24px;
    }
    .empty-state h2 {
      font-size: 24px;
      margin-bottom: 12px;
      color: #1a1a1a;
    }
    .empty-state p { 
      color: #999; 
      margin-bottom: 30px; 
      font-size: 15px;
    }
    .btn-shop { 
      background: #D4AF37; 
      color: #fff; 
      padding: 14px 32px; 
      border-radius: 8px; 
      display: inline-block;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-shop:hover {
      background: #B89026;
      transform: translateY(-2px);
    }
    
    @media (max-width: 1024px) {
      .cart-wrapper { grid-template-columns: 1fr; }
      .cart-summary { position: static; }
    }
    
    @media (max-width: 768px) {
      .container { padding: 20px 15px; }
      h1 { font-size: 24px; }
      .cart-header { flex-direction: column; align-items: flex-start; gap: 10px; }
      .cart-item { 
        grid-template-columns: 100px 1fr;
        gap: 15px;
      }
      .item-image { width: 100px; height: 100px; }
      .item-subtotal { display: none; }
      .item-name { font-size: 16px; }
      .item-price { font-size: 20px; }
    }
  </style>
</head>
<body>

<div class="container">
  <?php if (empty($cartItems)): ?>
    <div class="empty-state">
      <i class="fa-solid fa-cart-shopping"></i>
      <h2>Your cart is empty</h2>
      <p>Looks like you haven't added anything to your cart yet.</p>
      <a href="product.php" class="btn-shop">Continue Shopping</a>
    </div>
  <?php else: ?>
    <div class="cart-header">
      <div class="cart-header-left">
        <h1>Shopping Cart</h1>
        <p><?php echo count($cartItems); ?> item<?php echo count($cartItems) > 1 ? 's' : ''; ?> in your cart</p>
      </div>
      <a href="product.php" class="continue-shopping">
        <i class="fa-solid fa-arrow-left"></i>
        Continue Shopping
      </a>
    </div>
    
    <?php if ($defaultAddress): ?>
      <div class="delivery-address">
        <div class="address-content">
          <div class="address-header">
            <i class="fa-solid fa-location-dot"></i>
            <h3>Delivery Address</h3>
          </div>
          <div class="address-details">
            <div class="address-name"><?php echo htmlspecialchars($defaultAddress['full_name']); ?></div>
            <div class="address-phone"><?php echo htmlspecialchars($defaultAddress['phone']); ?></div>
            <div>
              <?php echo htmlspecialchars($defaultAddress['address_line1']); ?>
              <?php if (!empty($defaultAddress['address_line2'])): ?>
                , <?php echo htmlspecialchars($defaultAddress['address_line2']); ?>
              <?php endif; ?><br>
              <?php echo htmlspecialchars($defaultAddress['city']); ?>, <?php echo htmlspecialchars($defaultAddress['state']); ?> - <?php echo htmlspecialchars($defaultAddress['pincode']); ?>
            </div>
          </div>
        </div>
        <button class="change-address-btn" onclick="window.location.href='my-profile.php?tab=addresses'">Change</button>
      </div>
    <?php else: ?>
      <div class="no-address">
        <i class="fa-solid fa-map-location-dot"></i>
        <div class="no-address-text">
          <p><strong>No delivery address found</strong></p>
          <p>Please add a delivery address to continue with checkout</p>
        </div>
        <a href="my-profile.php?tab=addresses" class="add-address-btn">Add Address</a>
      </div>
    <?php endif; ?>
    
    <div class="cart-wrapper">
      <div class="cart-items">
        <?php foreach ($cartItems as $item): ?>
          <?php 
            $img = get_first_image($item['images'] ?? '');
            $price = (float)$item['price'];
            $subtotal = $price * $item['quantity'];
          ?>
          <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
            <div class="item-image">
              <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
            </div>
            
            <div class="item-details">
              <div class="item-header">
                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                <div class="item-meta">Product ID: #<?php echo $item['product_id']; ?></div>
              </div>
              <div class="item-price">₹<?php echo number_format($price, 2); ?></div>
              <div class="item-actions">
                <div class="quantity-control">
                  <button class="qty-minus" data-product-id="<?php echo $item['product_id']; ?>">−</button>
                  <input type="text" class="qty-input" value="<?php echo $item['quantity']; ?>" readonly>
                  <button class="qty-plus" data-product-id="<?php echo $item['product_id']; ?>">+</button>
                </div>
                <button class="remove-btn" data-product-id="<?php echo $item['product_id']; ?>">
                  <i class="fa-regular fa-trash-can"></i>
                  Remove
                </button>
              </div>
            </div>
            
            <div class="item-subtotal">
              <span class="subtotal-label">Subtotal</span>
              <span class="subtotal-amount">₹<?php echo number_format($subtotal, 2); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      
      <div class="cart-summary">
        <div class="summary-title">Order Summary</div>
        
        <div class="summary-row">
          <span>Subtotal (<?php echo count($cartItems); ?> items)</span>
          <span>₹<?php echo number_format($cartTotal, 2); ?></span>
        </div>
        
        <div class="summary-row">
          <span>Shipping</span>
          <span>Free</span>
        </div>
        
        <div class="summary-row">
          <span>Tax</span>
          <span>Calculated at checkout</span>
        </div>
        
        <div class="summary-divider"></div>
        
        <div class="summary-total">
          <span>Total</span>
          <span>₹<?php echo number_format($cartTotal, 2); ?></span>
        </div>
        
        <button class="checkout-btn" onclick="window.location.href='checkout.php'">
          Place Order
        </button>
        
        <div class="secure-checkout">
          <i class="fa-solid fa-lock"></i>
          Secure Checkout
        </div>
        
        <div class="promo-code">
          <div style="font-size: 14px; font-weight: 600; margin-bottom: 4px; color: #1a1a1a;">Have a promo code?</div>
          <div class="promo-input-group">
            <input type="text" class="promo-input" placeholder="Enter code">
            <button class="apply-btn">Apply</button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity controls
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('qty-plus') || e.target.classList.contains('qty-minus')) {
            const productId = e.target.getAttribute('data-product-id');
            const cartItem = e.target.closest('.cart-item');
            const qtyInput = cartItem.querySelector('.qty-input');
            let currentQty = parseInt(qtyInput.value);
            
            if (e.target.classList.contains('qty-plus')) {
                currentQty++;
            } else if (e.target.classList.contains('qty-minus') && currentQty > 1) {
                currentQty--;
            }
            
            updateQuantity(productId, currentQty);
        }
        
        if (e.target.classList.contains('remove-btn')) {
            const productId = e.target.getAttribute('data-product-id');
            removeFromCart(productId);
        }
    });
});

function updateQuantity(productId, quantity) {
    fetch('ajax_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_quantity&product_id=${productId}&quantity=${quantity}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(err => console.error(err));
}

function removeFromCart(productId) {
    if (!confirm('Remove this item from cart?')) return;
    
    fetch('ajax_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove&product_id=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(err => console.error(err));
}
</script>

</body>
</html>
