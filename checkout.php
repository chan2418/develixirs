<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupon_helpers.php';
require_once __DIR__ . '/includes/order_pricing_helper.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch addresses
$addresses = [];
try {
    $stmtAddr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $stmtAddr->execute([$userId]);
    $addresses = $stmtAddr->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $addresses = [];
}

$selectedAddressId = (int)($_SESSION['selected_address_id'] ?? 0);
$sessionGstNumber = strtoupper(trim((string)($_SESSION['gst_number'] ?? '')));

// Fetch cart items
$cartItems = [];
$cartTotal = 0;
$appliedCoupon = getAppliedCoupon();
$discountAmount = 0;
$deliveryCharge = 0;
$finalTotal = 0;
$discountLabel = 'Discount';
$couponSavedNotApplied = false;

try {

    
    // HANDLE DIRECT BUY FROM URL (Bypassing AJAX to avoid race conditions)
    if (isset($_GET['source']) && $_GET['source'] === 'direct_buy' && isset($_GET['product_id'])) {
        $_SESSION['direct_buy_item'] = [
            'product_id' => (int)$_GET['product_id'],
            'quantity' => isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1
        ];

    } else {

    }

    // Check for "Direct Buy" session
    $directBuyItem = $_SESSION['direct_buy_item'] ?? null;

    $isDirectSource = (isset($_GET['source']) && $_GET['source'] === 'direct_buy');
    
    // IMPORTANT: Clear direct buy session if NOT coming from Buy Now button
    // This prevents old Buy Now session from interfering with normal cart checkout
    if (!$isDirectSource && isset($_SESSION['direct_buy_item'])) {
        unset($_SESSION['direct_buy_item']);
        $directBuyItem = null;

    }
    
    // If direct buy exists, use THAT instead of DB cart
    if ($directBuyItem && isset($directBuyItem['product_id'])) {
        $cartItems = [];
        // Fetch product details for this single item
        $stmtP = $pdo->prepare("SELECT id, name, price, images, category_id FROM products WHERE id = ?");
        $stmtP->execute([$directBuyItem['product_id']]);
        $prod = $stmtP->fetch(PDO::FETCH_ASSOC);
        

        
        if ($prod) {
             $prod['quantity'] = $directBuyItem['quantity']; // Override with buy now qty
             $prod['product_id'] = $prod['id']; // Map to expected key
             $cartItems[] = $prod;
             $cartTotal = $prod['price'] * $prod['quantity'];

        }
    } else {
        // FALLBACK: Normal DB Cart

        // CRITICAL DEBUG: If user came from "Buy Now" button but session is empty, STOP and show error.
        if ($isDirectSource) {
            echo '<div style="background:#f8d7da; color:#721c24; padding:20px; text-align:center; margin:20px; border:1px solid #f5c6cb; border-radius:5px;">
                    <h3><i class="fa fa-exclamation-circle"></i> Direct Buy Error</h3>
                    <p>Session data for the product was lost during redirect.</p>
                    <p>Debug Info: Session ID: '.session_id().' | Direct Item: '.(isset($_SESSION['direct_buy_item']) ? 'SET' : 'MISSING').'</p>
                    <a href="index.php" style="color:#721c24; font-weight:bold; text-decoration:underline;">Return to Shop</a>
                  </div>';
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.images, p.category_id
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = :uid
        ");
        $stmt->execute([':uid' => $userId]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        

        
        foreach ($cartItems as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }
    }
    
    $pricing = calculate_order_pricing($pdo, $userId, $cartItems, $appliedCoupon);
    $appliedCoupon = $pricing['coupon']['data'] ?? null;
    $discountAmount = (float)($pricing['applied_discount_amount'] ?? 0);
    $deliveryCharge = (float)($pricing['delivery_charge'] ?? 0);
    $finalTotal = (float)($pricing['final_total'] ?? $cartTotal);
    $discountLabel = (string)($pricing['discount_label'] ?? 'Discount');
    $couponSavedNotApplied = !empty($pricing['coupon']['saved_not_applied']);
} catch (PDOException $e) {
    $cartItems = [];
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
  <title>Checkout – Devilixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  <?php 
  // CRITICAL: Preserve checkout's cartTotal before navbar overwrites it
  $checkoutCartTotal = $cartTotal;
  $checkoutCartItems = $cartItems;
  include __DIR__ . '/navbar.php'; 
  // Restore checkout values after navbar
  $cartTotal = $checkoutCartTotal;
  $cartItems = $checkoutCartItems;
  ?>
  
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "Poppins", sans-serif; background: #f5f5f5; color: #333; }
    .container { max-width: 1200px; margin: 0 auto; padding: 40px 15px; }
    
    .checkout-layout {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
    }
    
    .checkout-steps {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      overflow: hidden;
    }
    
    .step-header {
      padding: 20px 24px;
      background: #f9f9f9;
      border-bottom: 1px solid #eee;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
    }
    .step-header.active {
      background: #D4AF37;
      color: #fff;
    }
    .step-header.completed {
      background: #fff;
      color: #333;
    }
    .step-header.completed .step-number {
      background: #D4AF37;
      color: #fff;
    }
    
    .step-title {
      display: flex;
      align-items: center;
      gap: 15px;
      font-weight: 600;
      font-size: 16px;
    }
    
    .step-number {
      width: 28px;
      height: 28px;
      background: #eee;
      color: #777;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      font-weight: 600;
    }
    .active .step-number {
      background: #fff;
      color: #D4AF37;
    }
    
    .step-content {
      padding: 24px;
      display: none;
    }
    .step-content.active {
      display: block;
    }
    
    /* Address Styles */
    .address-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    
    .address-card {
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      padding: 16px;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: flex-start;
      gap: 15px;
    }
    .address-card:hover { border-color: #D4AF37; }
    .address-card.selected {
      border-color: #D4AF37;
      background: #fff9e6;
    }
    
    .address-radio {
      margin-top: 4px;
      accent-color: #D4AF37;
    }
    
    .address-details { font-size: 14px; line-height: 1.5; color: #555; }
    .address-name { font-weight: 600; color: #333; margin-bottom: 4px; display: block; }
    .address-phone { font-weight: 500; color: #333; margin-top: 4px; display: block; }
    
    .deliver-btn {
      background: #D4AF37;
      color: #fff;
      border: none;
      padding: 14px 30px;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 20px;
      text-transform: uppercase;
    }
    .deliver-btn:hover { background: #B89026; }
    
    /* Order Summary Styles */
    .cart-item {
      display: flex;
      gap: 15px;
      padding: 15px 0;
      border-bottom: 1px solid #f0f0f0;
    }
    .cart-item:last-child { border-bottom: none; }
    
    .item-img { width: 80px; height: 80px; border-radius: 4px; overflow: hidden; flex-shrink: 0; }
    .item-img img { width: 100%; height: 100%; object-fit: cover; }
    
    .item-info { flex: 1; }
    .item-name { font-weight: 500; margin-bottom: 5px; font-size: 15px; }
    .item-meta { font-size: 13px; color: #777; margin-bottom: 8px; }
    .item-price { font-weight: 600; color: #333; }
    
    .remove-link {
      color: #999;
      font-size: 12px;
      text-decoration: none;
      text-transform: uppercase;
      font-weight: 600;
      margin-top: 8px;
      display: inline-block;
    }
    .remove-link:hover { color: #ff4d4d; }
    
    /* Price Details */
    .price-details {
      background: #fff;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      height: fit-content;
      position: sticky;
      top: 20px;
    }
    
    .price-header {
      font-size: 16px;
      font-weight: 600;
      color: #878787;
      border-bottom: 1px solid #f0f0f0;
      padding-bottom: 15px;
      margin-bottom: 15px;
      text-transform: uppercase;
    }
    
    .price-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 15px;
      font-size: 15px;
    }
    
    .total-row {
      border-top: 1px dashed #e0e0e0;
      padding-top: 15px;
      margin-top: 15px;
      font-weight: 700;
      font-size: 18px;
      display: flex;
      justify-content: space-between;
    }
    
    .add-address-link {
      display: inline-block;
      margin-top: 15px;
      color: #D4AF37;
      font-weight: 600;
      text-decoration: none;
      font-size: 14px;
    }

    .gst-field-wrap {
      margin-top: 18px;
      padding: 14px;
      border: 1px solid #eee;
      border-radius: 8px;
      background: #fafafa;
    }

    .gst-field-wrap label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }

    .gst-input {
      width: 100%;
      padding: 11px 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-family: "Poppins", sans-serif;
      font-size: 14px;
      text-transform: uppercase;
    }

    .gst-input:focus {
      outline: none;
      border-color: #D4AF37;
      box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.12);
    }

    .gst-help {
      display: block;
      margin-top: 6px;
      color: #777;
      font-size: 12px;
    }
    
    @media (max-width: 900px) {
      .checkout-layout { grid-template-columns: 1fr; }
      .price-details { position: static; }
    }
  </style>
</head>
<body>

<div class="container">
  <div class="checkout-layout">
    <div class="checkout-steps">
      
      <!-- STEP 1: ADDRESS -->
      <div class="step-header active" id="header-address">
        <div class="step-title">
          <span class="step-number">1</span>
          DELIVERY ADDRESS
        </div>
        <i class="fa-solid fa-check" style="display:none;"></i>
      </div>
      <div class="step-content active" id="content-address">
        <?php if (empty($addresses)): ?>
          <p>No addresses found.</p>
          <a href="my-profile.php?tab=addresses" class="add-address-link">+ Add New Address</a>
        <?php else: ?>
          <div class="address-list">
            <?php foreach ($addresses as $index => $addr): ?>
              <?php
                $isSelected = $selectedAddressId > 0
                    ? ((int)$addr['id'] === $selectedAddressId)
                    : !empty($addr['is_default']);
              ?>
              <label class="address-card <?php echo $isSelected ? 'selected' : ''; ?>">
                <input type="radio" name="delivery_address" value="<?php echo $addr['id']; ?>" 
                       class="address-radio" <?php echo $isSelected ? 'checked' : ''; ?>>
                <div class="address-details">
                  <span class="address-name"><?php echo htmlspecialchars($addr['full_name']); ?></span>
                  <div><?php echo htmlspecialchars($addr['address_line1']); ?>, <?php echo htmlspecialchars($addr['city']); ?></div>
                  <div><?php echo htmlspecialchars($addr['state']); ?> - <?php echo htmlspecialchars($addr['pincode']); ?></div>
                  <span class="address-phone">Phone: <?php echo htmlspecialchars($addr['phone']); ?></span>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="gst-field-wrap">
            <label for="gst_number">GST Number (Optional)</label>
            <input
              type="text"
              id="gst_number"
              class="gst-input"
              maxlength="15"
              placeholder="Ex: 29ABCDE1234F2Z5"
              value="<?php echo htmlspecialchars($sessionGstNumber); ?>"
            >
            <small class="gst-help">Used for business invoice if provided.</small>
          </div>
          <button class="deliver-btn" onclick="goToStep2(this)">Deliver Here</button>
        <?php endif; ?>
      </div>
      
      <!-- STEP 2: ORDER SUMMARY -->
      <div class="step-header" id="header-summary">
        <div class="step-title">
          <span class="step-number">2</span>
          ORDER SUMMARY
        </div>
      </div>
      <div class="step-content" id="content-summary">
        <?php foreach ($cartItems as $item): ?>
          <?php $img = get_first_image($item['images'] ?? ''); ?>
          <div class="cart-item">
            <div class="item-img">
              <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
            </div>
            <div class="item-info">
              <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
              <div class="item-meta">Qty: <?php echo $item['quantity']; ?></div>
              <div class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
              <a href="#" class="remove-link" onclick="removeFromCart(<?php echo $item['product_id']; ?>); return false;">Remove</a>
            </div>
          </div>
        <?php endforeach; ?>
        
        <button class="deliver-btn" onclick="window.location.href='payment.php'" style="width:100%; margin-top:30px;">
          CONTINUE
        </button>
      </div>
      
    </div>
    
    <!-- PRICE DETAILS -->

    <div class="price-details">
      <div class="price-header">Price Details</div>
      <div class="price-row">
        <span>Price (<?php echo count($cartItems); ?> items)</span>
        <span>₹<?php echo number_format($cartTotal, 2); ?></span>
      </div>
      <?php if ($discountAmount > 0): ?>
        <div class="price-row" style="color: #28a745;">
          <span><i class="fa fa-tag"></i> <?php echo htmlspecialchars($discountLabel); ?></span>
          <span>-₹<?php echo number_format($discountAmount, 2); ?></span>
        </div>
      <?php endif; ?>
      <?php if ($couponSavedNotApplied && $appliedCoupon): ?>
        <div style="margin-bottom: 12px; font-size: 12px; color: #8a6d3b;">
          Coupon <strong><?php echo htmlspecialchars($appliedCoupon['code']); ?></strong> is saved, but your subscription gives better savings for this cart.
        </div>
      <?php endif; ?>
      <div class="price-row">
        <span>Delivery Charges</span>
        <span style="color:<?php echo ($deliveryCharge > 0) ? '#333' : '#388e3c'; ?>;">
          <?php echo ($deliveryCharge > 0) ? '₹' . number_format($deliveryCharge, 2) : 'FREE'; ?>
        </span>
      </div>
      <div class="total-row">
        <span>Total Payable</span>
        <span>₹<?php echo number_format($finalTotal, 2); ?></span>
      </div>
      <?php if ($discountAmount > 0): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; font-size: 13px; text-align: center; margin-top: 15px;">
          <i class="fa fa-check-circle"></i>
          You saved ₹<?php echo number_format($discountAmount, 2); ?>!
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Navigation
document.getElementById('header-address').addEventListener('click', function() {
    // Always allow going back to address step
    showStep(1);
});

document.getElementById('header-summary').addEventListener('click', function() {
    // Only allow going to summary if address is selected
    const selected = document.querySelector('input[name="delivery_address"]:checked');
    if (selected) {
        showStep(2);
    }
});

function showStep(stepNumber) {
    const headerAddr = document.getElementById('header-address');
    const contentAddr = document.getElementById('content-address');
    const headerSumm = document.getElementById('header-summary');
    const contentSumm = document.getElementById('content-summary');
    
    if (stepNumber === 1) {
        headerAddr.classList.add('active');
        headerAddr.classList.remove('completed');
        contentAddr.classList.add('active');
        
        headerSumm.classList.remove('active');
        contentSumm.classList.remove('active');
    } else if (stepNumber === 2) {
        headerAddr.classList.remove('active');
        headerAddr.classList.add('completed');
        contentAddr.classList.remove('active');
        
        headerSumm.classList.add('active');
        contentSumm.classList.add('active');
    }
}

function isValidGstNumber(gstNumber) {
    return /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/.test(gstNumber);
}

async function goToStep2(btn) {
    const selected = document.querySelector('input[name="delivery_address"]:checked');
    if (!selected) {
        alert('Please select a delivery address');
        return;
    }

    const gstInput = document.getElementById('gst_number');
    const gstNumber = gstInput ? gstInput.value.trim().toUpperCase().replace(/\s+/g, '') : '';

    if (gstInput) {
        gstInput.value = gstNumber;
    }

    if (gstNumber && !isValidGstNumber(gstNumber)) {
        alert('Please enter a valid GST number or leave it empty.');
        if (gstInput) gstInput.focus();
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.dataset.originalText = btn.dataset.originalText || btn.innerText;
        btn.innerText = 'Saving...';
    }

    try {
        const response = await fetch('api/set_checkout_details.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `address_id=${encodeURIComponent(selected.value)}&gst_number=${encodeURIComponent(gstNumber)}`
        });
        const data = await response.json();

        if (!data.success) {
            alert(data.message || 'Failed to save delivery details');
            return;
        }

        showStep(2);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        console.error(error);
        alert('Unable to save delivery details. Please try again.');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerText = btn.dataset.originalText || 'Deliver Here';
        }
    }
}

function removeFromCart(productId) {
    if (!confirm('Remove this item?')) return;
    
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
    });
}

// Address selection styling
document.querySelectorAll('input[name="delivery_address"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
        this.closest('.address-card').classList.add('selected');
    });
});

const gstInput = document.getElementById('gst_number');
if (gstInput) {
    gstInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^0-9A-Z]/g, '').slice(0, 15);
    });
}
</script>

<?php include 'footer.php'; ?>

</body>
</html>
