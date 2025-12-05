<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupon_helpers.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$cartItems = [];
$cartTotal = 0;
$appliedCoupon = getAppliedCoupon();
$discountAmount = 0;

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
    
    // Re-validate applied coupon
    if ($appliedCoupon) {
        $validation = validateCoupon($appliedCoupon['code'], $userId, $cartTotal, $cartItems, $pdo);
        
        if (!$validation['valid']) {
            // Coupon no longer valid
            removeCouponFromSession();
            $appliedCoupon = null;
            $discountAmount = 0;
            $couponRemovedMessage = "Coupon removed: " . $validation['message'];
        } else {
            // Update discount amount (in case total changed)
            $discountAmount = calculateDiscount($validation['coupon'], $cartTotal);
            applyCouponToSession($validation['coupon'], $discountAmount);
            $appliedCoupon = getAppliedCoupon(); // Refresh variable
        }
    }
    
    // Apply discount if coupon is applied
    if ($appliedCoupon) {
        $discountAmount = $appliedCoupon['discount_amount'];
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
    .apply-btn:hover:not(:disabled) {
      background: #D4AF37;
      color: #fff;
    }
    .apply-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .promo-input:disabled {
      background: #f5f5f5;
      cursor: not-allowed;
    }
    
    .remove-coupon-btn {
      background: none;
      border: none;
      color: #dc3545;
      cursor: pointer;
      padding: 2px 6px;
      margin-left: 8px;
      font-size: 12px;
      transition: all 0.2s;
    }
    .remove-coupon-btn:hover {
      color: #c82333;
      transform: scale(1.1);
    }
    
    #couponMessage {
      font-size: 12px;
      margin-top: 8px;
    }
    #couponMessage.success {
      color: #28a745;
    }
    #couponMessage.error {
      color: #dc3545;
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
    
    <?php if (isset($couponRemovedMessage)): ?>
    <div style="background: #ffebee; color: #c62828; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ef9a9a; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-exclamation"></i> 
        <div>
            <strong>Coupon Removed</strong><br>
            <span style="font-size: 13px;"><?php echo htmlspecialchars(str_replace('Coupon removed: ', '', $couponRemovedMessage)); ?></span>
        </div>
    </div>
    <?php endif; ?>
    
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
          <span>Subtotal (<span id="cart-count"><?php echo count($cartItems); ?></span> items)</span>
          <span id="cart-subtotal">₹<?php echo number_format($cartTotal, 2); ?></span>
        </div>
        
        <?php if ($appliedCoupon): ?>
          <div class="summary-row" style="color: #28a745;" id="cart-discount-row">
            <span>
              <i class="fa fa-tag"></i>
              Coupon Discount (<?php echo htmlspecialchars($appliedCoupon['code']); ?>)
              <button class="remove-coupon-btn" onclick="removeCoupon()" title="Remove coupon">
                <i class="fa fa-times"></i>
              </button>
            </span>
            <span id="cart-discount">-₹<?php echo number_format($discountAmount, 2); ?></span>
          </div>
        <?php else: ?>
          <div class="summary-row" style="color: #28a745; display:none;" id="cart-discount-row">
             <span>
              <i class="fa fa-tag"></i>
              Coupon Discount (<span id="cart-coupon-code"></span>)
              <button class="remove-coupon-btn" onclick="removeCoupon()" title="Remove coupon">
                <i class="fa fa-times"></i>
              </button>
            </span>
            <span id="cart-discount"></span>
          </div>
        <?php endif; ?>
        
        <div class="summary-row">
          <span>Shipping</span>
          <?php 
            $deliveryCharge = ($cartTotal < 1000) ? 80 : 0;
          ?>
          <span id="cart-shipping"><?php echo ($deliveryCharge > 0) ? '₹' . number_format($deliveryCharge, 2) : 'Free'; ?></span>
        </div>
        
        <div class="summary-row">
          <span>Tax</span>
          <span>Calculated at checkout</span>
        </div>
        
        <div class="summary-divider"></div>
        
        <div class="summary-total">
          <span>Total</span>
          <span id="cart-total">₹<?php echo number_format($cartTotal - $discountAmount + $deliveryCharge, 2); ?></span>
        </div>
        
        <div id="cart-savings-msg" style="background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; font-size: 13px; text-align: center; margin-top: 10px; <?php echo ($discountAmount > 0) ? '' : 'display:none;'; ?>">
            <i class="fa fa-check-circle"></i>
            You saved <span id="cart-savings-amount">₹<?php echo number_format($discountAmount, 2); ?></span>!
        </div>
        
        <button class="checkout-btn" onclick="window.location.href='checkout.php'">
          Place Order
        </button>
        
        <div class="secure-checkout">
          <i class="fa-solid fa-lock"></i>
          Secure Checkout
        </div>
        
        <div class="promo-code">
          <div style="font-size: 14px; font-weight: 600; margin-bottom: 4px; color: #1a1a1a;">
            Have a promo code?
            <a class="view-offers-link" onclick="openOffersModal()">View Available Offers</a>
          </div>
          <div class="promo-input-group">
            <input type="text" class="promo-input" id="couponCodeInput" placeholder="Enter code" <?php echo $appliedCoupon ? 'disabled' : ''; ?>>
            <button class="apply-btn" id="applyCouponBtn" onclick="applyCoupon()" <?php echo $appliedCoupon ? 'disabled' : ''; ?>>Apply</button>
          </div>
          <div id="couponMessage" style="margin-top: 8px; font-size: 12px;"></div>
        </div>
      </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<!-- Offers Modal -->
<div id="offersModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Available Offers</h3>
      <span class="close-modal" onclick="closeOffersModal()">&times;</span>
    </div>
    <div id="offersList" class="offers-list">
      <div style="text-align: center; padding: 20px; color: #666;">Loading offers...</div>
    </div>
  </div>
</div>

<style>
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
.modal-content { background-color: #fefefe; margin: 10% auto; padding: 0; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 12px; overflow: hidden; animation: slideIn 0.3s; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
.modal-header { padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 18px; color: #333; font-weight: 600; }
.close-modal { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; }
.close-modal:hover { color: #000; }
.offers-list { padding: 20px; max-height: 400px; overflow-y: auto; }
.offer-card { border: 1px dashed #D4AF37; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: #fff9e6; position: relative; transition: transform 0.2s; }
.offer-card:hover { transform: translateY(-2px); box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.offer-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.offer-code { font-weight: 700; color: #D4AF37; font-size: 16px; letter-spacing: 0.5px; background: #fff; padding: 2px 8px; border-radius: 4px; border: 1px solid #D4AF37; }
.offer-desc { font-size: 13px; color: #555; margin-bottom: 10px; line-height: 1.4; }
.offer-meta { font-size: 12px; color: #888; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(212, 175, 55, 0.2); padding-top: 8px; }
.apply-offer-btn { background: #D4AF37; border: none; color: #fff; padding: 6px 16px; border-radius: 20px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s; }
.apply-offer-btn:hover { background: #B89026; transform: scale(1.05); }
.offer-card.ineligible { background: #f8f9fa; border-color: #e0e0e0; opacity: 0.9; }
.offer-card.ineligible .offer-code { border-color: #ccc; color: #888; }
.offer-card.ineligible .apply-offer-btn { background: #e0e0e0; color: #888; cursor: not-allowed; transform: none; }
.ineligible-reason { font-size: 11px; color: #dc3545; margin-top: 8px; font-weight: 500; display: flex; align-items: center; gap: 4px; }
.view-offers-link { color: #D4AF37; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; float: right; margin-top: 2px; }
.view-offers-link:hover { text-decoration: underline; }
.no-offers { text-align: center; padding: 30px 0; color: #666; }
.no-offers i { font-size: 40px; color: #ddd; margin-bottom: 15px; display: block; }
@keyframes slideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

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
            if (data.coupon_status && data.coupon_status.status === 'removed') {
                alert(data.coupon_status.message);
            }
            
            // Update Item Subtotal
            const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
            if (cartItem) {
                const priceEl = cartItem.querySelector('.item-price');
                const subtotalEl = cartItem.querySelector('.subtotal-amount');
                const qtyInput = cartItem.querySelector('.qty-input');
                
                // Update input value
                qtyInput.value = quantity;
                
                // Calculate new item subtotal
                const price = parseFloat(priceEl.textContent.replace(/[^\d.]/g, ''));
                const newSubtotal = price * quantity;
                subtotalEl.textContent = formatCurrency(newSubtotal);
            }
            
            updateCartSummary(data);
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
            if (data.coupon_status && data.coupon_status.status === 'removed') {
                alert(data.coupon_status.message);
            }
            
            // Remove item from DOM
            const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
            if (cartItem) {
                cartItem.remove();
            }
            
            // Check if cart is empty
            if (data.count === 0) {
                location.reload(); // Reload to show empty state easily
                return;
            }
            
            updateCartSummary(data);
        }
    })
    .catch(err => console.error(err));
}

function updateCartSummary(data) {
    // Update Counts and Totals
    document.getElementById('cart-count').textContent = data.count;
    document.getElementById('cart-subtotal').textContent = formatCurrency(data.total);
    
    // Update Shipping
    const shippingEl = document.getElementById('cart-shipping');
    if (data.delivery_charge > 0) {
        shippingEl.textContent = formatCurrency(data.delivery_charge);
    } else {
        shippingEl.textContent = 'Free';
    }
    
    // Update Grand Total
    document.getElementById('cart-total').textContent = formatCurrency(data.grand_total);
    
    // Update Discount if exists
    const discountRow = document.getElementById('cart-discount-row');
    const discountEl = document.getElementById('cart-discount');
    const savingsMsg = document.getElementById('cart-savings-msg');
    const savingsAmount = document.getElementById('cart-savings-amount');
    
    let discount = 0;
    if (data.coupon_status && data.coupon_status.status === 'updated') {
        discount = data.coupon_status.discount;
    }
    
    if (discount > 0) {
        if (discountRow) {
            discountRow.style.display = 'flex';
            discountEl.textContent = '-' + formatCurrency(discount);
        }
        if (savingsMsg) {
            savingsMsg.style.display = 'block';
            savingsAmount.textContent = formatCurrency(discount);
        }
    } else {
        if (discountRow) discountRow.style.display = 'none';
        if (savingsMsg) savingsMsg.style.display = 'none';
    }
}

function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Remove coupon
function removeCoupon() {
    if (!confirm('Remove applied coupon?')) return;
    
    fetch('remove_coupon.php', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Simpler to reload for coupon removal as it affects state significantly
        }
    })
    .catch(err => console.error(err));
}

// Allow Enter key to apply coupon
document.addEventListener('DOMContentLoaded', function() {
    const couponInput = document.getElementById('couponCodeInput');
    if (couponInput) {
        couponInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyCoupon();
            }
        });
    }
});

// Offers Modal Functions
function openOffersModal() {
    document.getElementById('offersModal').style.display = 'block';
    fetchOffers();
}

function closeOffersModal() {
    document.getElementById('offersModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('offersModal')) {
        closeOffersModal();
    }
}

function fetchOffers() {
    const list = document.getElementById('offersList');
    list.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">Loading offers...</div>';
    
    fetch('get_available_coupons.php')
    .then(r => r.json())
    .then(data => {
        if (data.success && data.count > 0) {
            let html = '';
            data.coupons.forEach(c => {
                const isEligible = c.is_eligible;
                const cardClass = isEligible ? 'offer-card' : 'offer-card ineligible';
                const btnAttr = isEligible ? `onclick="applyCouponCode('${c.code}')"` : 'disabled';
                const btnText = isEligible ? 'APPLY' : 'LOCKED';
                const reasonHtml = isEligible ? '' : `<div class="ineligible-reason"><i class="fa-solid fa-lock"></i> ${c.ineligibility_reason}</div>`;
                
                html += `
                    <div class="${cardClass}">
                        <div class="offer-header">
                            <span class="offer-code">${c.code}</span>
                            <button class="apply-offer-btn" ${btnAttr}>${btnText}</button>
                        </div>
                        <div class="offer-desc">${c.description || c.title}</div>
                        <div class="offer-meta">
                            <span>${c.discount_text}</span>
                            <span>${c.min_purchase_text}</span>
                        </div>
                        ${reasonHtml}
                    </div>
                `;
            });
            list.innerHTML = html;
        } else {
            list.innerHTML = `
                <div class="no-offers">
                    <i class="fa fa-ticket"></i>
                    <p>No offers available for you right now.</p>
                </div>
            `;
        }
    })
    .catch(err => {
        console.error(err);
        list.innerHTML = '<div style="text-align: center; color: red;">Failed to load offers.</div>';
    });
}

function applyCoupon() {
    const codeInput = document.getElementById('couponCodeInput');
    const code = codeInput.value.trim();
    const msgEl = document.getElementById('couponMessage');
    const btn = document.getElementById('applyCouponBtn');
    
    if (!code) {
        msgEl.textContent = 'Please enter a coupon code';
        msgEl.className = 'error';
        return;
    }
    
    btn.disabled = true;
    msgEl.textContent = 'Applying...';
    msgEl.className = '';
    
    fetch('apply_coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'code=' + encodeURIComponent(code)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            msgEl.textContent = data.message;
            msgEl.className = 'success';
            setTimeout(() => location.reload(), 1000);
        } else {
            msgEl.textContent = data.message || 'Invalid coupon';
            msgEl.className = 'error';
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        msgEl.textContent = 'Error applying coupon';
        msgEl.className = 'error';
        btn.disabled = false;
    });
}

function applyCouponCode(code) {
    document.getElementById('couponCodeInput').value = code;
    closeOffersModal();
    applyCoupon();
}
</script>

</body>
</html>
