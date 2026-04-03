<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupon_helpers.php';
require_once __DIR__ . '/includes/order_pricing_helper.php';
require_once __DIR__ . '/includes/order_summary_component.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch cart total
$cartItems = fetch_order_context_items($pdo, $userId, true);
if (empty($cartItems)) {
    header("Location: cart.php");
    exit;
}

$appliedCoupon = getAppliedCoupon();
$pricing = calculate_order_pricing($pdo, $userId, $cartItems, $appliedCoupon);
$appliedCoupon = $pricing['coupon']['data'] ?? null;
$cartTotal = (float)($pricing['base_subtotal'] ?? 0);
$cartItemsCount = (int)($pricing['line_item_count'] ?? count($cartItems));
$discountAmount = (float)($pricing['applied_discount_amount'] ?? 0);
$deliveryCharge = (float)($pricing['delivery_charge'] ?? 0);
$finalTotal = (float)($pricing['final_total'] ?? $cartTotal);
$discountLabel = (string)($pricing['discount_label'] ?? 'Discount');
$couponSavedNotApplied = !empty($pricing['coupon']['saved_not_applied']);

$summaryProductsTotal = round(max(0, $cartTotal), 2);
$summaryShipping = round(max(0, $deliveryCharge), 2);
$summaryDiscount = round(max(0, $discountAmount), 2);
$summaryGrandTotal = round(max(0, $finalTotal), 2);

$summaryGstRate = 0.0;
try {
    $summaryGstRate = order_summary_max_gst_rate($pdo, $cartItems);
} catch (Throwable $e) {
    $summaryGstRate = 0.0;
}

$shippingAddressText = (string)($_SESSION['shipping_address'] ?? '');
$summaryIsIntra = (stripos($shippingAddressText, 'Tamil Nadu') !== false || stripos($shippingAddressText, 'Chennai') !== false);
$summaryGstType = $summaryIsIntra ? 'CGST+SGST' : 'IGST';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Payment – Devilixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  <?php
  $paymentCartTotal = $cartTotal;
  $paymentCartItemsCount = $cartItemsCount;
  $paymentDiscountAmount = $discountAmount;
  $paymentDeliveryCharge = $deliveryCharge;
  $paymentFinalTotal = $finalTotal;
  include __DIR__ . '/navbar.php';
  $cartTotal = $paymentCartTotal;
  $cartItemsCount = $paymentCartItemsCount;
  $discountAmount = $paymentDiscountAmount;
  $deliveryCharge = $paymentDeliveryCharge;
  $finalTotal = $paymentFinalTotal;
  ?>
  
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "Poppins", sans-serif; background: #f5f5f5; color: #333; }
    .container { max-width: 1200px; margin: 0 auto; padding: 40px 15px; }
    
    .payment-layout {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
    }
    
    .payment-methods {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      overflow: hidden;
    }
    
    .payment-header {
      padding: 20px 24px;
      background: #D4AF37;
      color: #fff;
      font-weight: 600;
      font-size: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .payment-option {
      border-bottom: 1px solid #f0f0f0;
    }
    .payment-option:last-child { border-bottom: none; }
    
    .option-header {
      padding: 20px 24px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 15px;
      transition: background 0.2s;
    }
    .option-header:hover { background: #fafafa; }
    
    .option-radio {
      accent-color: #D4AF37;
      width: 18px;
      height: 18px;
    }
    
    .option-title {
      font-weight: 600;
      font-size: 16px;
      flex: 1;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .option-content {
      padding: 0 24px 24px 57px;
      display: none;
    }
    .payment-option.active .option-content { display: block; }
    .payment-option.active .option-header { background: #fff9e6; }
    
    /* UPI Styles */
    .upi-options {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    .upi-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      cursor: pointer;
    }
    .upi-item:hover { border-color: #D4AF37; }
    .upi-logo { width: 24px; height: 24px; object-fit: contain; }
    
    /* Card Styles */
    .card-form {
      display: flex;
      flex-direction: column;
      gap: 15px;
      max-width: 400px;
    }
    .form-group input {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-family: "Poppins", sans-serif;
    }
    .form-row { display: flex; gap: 15px; }
    
    /* Price Details (Same as Checkout) */
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
    
    .pay-btn {
      width: 100%;
      padding: 16px;
      background: #D4AF37;
      color: #fff;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 20px;
      transition: background 0.2s;
    }
    .pay-btn:hover { background: #B89026; }
    
    @media (max-width: 900px) {
      .payment-layout { grid-template-columns: 1fr; }
      .price-details { position: static; }
    }
  </style>
</head>
<body>

<div class="container">
  <div class="payment-layout">
    
    <div class="payment-methods">
      <div class="payment-header">
        <i class="fa-solid fa-wallet"></i>
        Payment Options
      </div>
      
      <!-- UPI -->
      <div class="payment-option">
        <label class="option-header">
          <input type="radio" name="payment_method" value="upi" class="option-radio">
          <div class="option-title">
            <span>UPI</span>
            <span style="font-size:12px; color:#388e3c; background:#e8f5e9; padding:2px 6px; border-radius:4px;">Fastest</span>
          </div>
        </label>
        <div class="option-content">
          <div class="upi-options">
            <label class="upi-item">
              <input type="radio" name="upi_app" value="gpay">
              <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5a/Google_Pay_Logo.svg/512px-Google_Pay_Logo.svg.png" class="upi-logo" alt="GPay">
              <span>Google Pay</span>
            </label>
            <label class="upi-item">
              <input type="radio" name="upi_app" value="phonepe">
              <img src="https://download.logo.wine/logo/PhonePe/PhonePe-Logo.wine.png" class="upi-logo" alt="PhonePe">
              <span>PhonePe</span>
            </label>
            <label class="upi-item">
              <input type="radio" name="upi_app" value="paytm">
              <img src="https://download.logo.wine/logo/Paytm/Paytm-Logo.wine.png" class="upi-logo" alt="Paytm">
              <span>Paytm</span>
            </label>
            <div style="margin-top:10px;">
              <input type="text" placeholder="Enter UPI ID" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
              <button style="margin-top:10px; padding:8px 20px; background:#333; color:#fff; border:none; border-radius:4px; cursor:pointer;">Verify</button>
            </div>
          </div>
          <button class="pay-btn">Pay ₹<?php echo number_format($finalTotal, 2); ?></button>
        </div>
      </div>
      
      <!-- Credit / Debit / ATM Card -->
      <div class="payment-option">
        <label class="option-header">
          <input type="radio" name="payment_method" value="card" class="option-radio">
          <div class="option-title">Credit / Debit / ATM Card</div>
        </label>
        <div class="option-content">
          <div class="card-form">
            <div class="form-group">
              <input type="text" placeholder="Card Number">
            </div>
            <div class="form-row">
              <div class="form-group" style="flex:1;">
                <input type="text" placeholder="Valid Thru (MM/YY)">
              </div>
              <div class="form-group" style="flex:1;">
                <input type="text" placeholder="CVV">
              </div>
            </div>
            <button class="pay-btn">Pay ₹<?php echo number_format($finalTotal, 2); ?></button>
          </div>
        </div>
      </div>
      
      <!-- Net Banking -->
      <div class="payment-option">
        <label class="option-header">
          <input type="radio" name="payment_method" value="netbanking" class="option-radio">
          <div class="option-title">Net Banking</div>
        </label>
        <div class="option-content">
          <select style="width:100%; padding:12px; border:1px solid #ddd; border-radius:4px;">
            <option value="">Choose Bank</option>
            <option value="sbi">HDFC Bank</option>
            <option value="icici">ICICI Bank</option>
            <option value="sbi">State Bank of India</option>
            <option value="axis">Axis Bank</option>
          </select>
          <button class="pay-btn">Pay ₹<?php echo number_format($finalTotal, 2); ?></button>
        </div>
      </div>
      
      <!-- Cash on Delivery -->
      <div class="payment-option">
        <label class="option-header">
          <input type="radio" name="payment_method" value="cod" class="option-radio">
          <div class="option-title">Cash on Delivery</div>
        </label>
        <div class="option-content">
          <p style="color:#666; margin-bottom:15px;">Pay cash at the time of delivery. We recommend using online payments for contactless delivery.</p>
          <button class="pay-btn">Confirm Order</button>
        </div>
      </div>
      
    </div>
    
    <!-- PRICE DETAILS -->
    <div class="price-details">
      <?php
        render_order_summary_component([
            'productsTotal' => $summaryProductsTotal,
            'shipping' => $summaryShipping,
            'discount' => $summaryDiscount,
            'grandTotal' => $summaryGrandTotal,
            'gstRate' => $summaryGstRate,
            'gstType' => $summaryGstType,
            'showDiscountWhenZero' => true,
        ]);
      ?>
      <?php if ($couponSavedNotApplied && $appliedCoupon): ?>
        <div style="margin-top:12px; font-size:12px; color:#8a6d3b;">
          Coupon <strong><?php echo htmlspecialchars($appliedCoupon['code']); ?></strong> is saved, but your subscription gives better savings for this cart.
        </div>
      <?php endif; ?>
      
      <div style="margin-top:20px; font-size:12px; color:#777; display:flex; align-items:center; gap:8px;">
        <i class="fa-solid fa-shield-halved"></i>
        Safe and Secure Payments. Easy returns. 100% Authentic products.
      </div>
    </div>
    
  </div>
</div>
</div>

<?php include 'footer.php'; ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Remove active class from all options
        document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('active'));
        // Add active class to selected option
        this.closest('.payment-option').classList.add('active');
    });
});

// Handle Pay Buttons
document.querySelectorAll('.pay-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) {
            alert('Please select a payment method');
            return;
        }
        
        const methodValue = selectedMethod.value;
        
        if (methodValue === 'cod') {
            // Process COD order
            processCODOrder(this);
            return;
        }
        
        // Initiate Razorpay Payment
        startRazorpayPayment(this);
    });
});

function processCODOrder(btn) {
    const originalText = btn.innerText;
    btn.innerText = 'Processing...';
    btn.disabled = true;

    fetch('api/create_cod_order.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'my-profile.php?order_success=true&id=' + data.order_id;
        } else {
            alert('Error: ' + data.message);
            btn.innerText = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Something went wrong. Please try again.');
        btn.innerText = originalText;
        btn.disabled = false;
    });
}

function startRazorpayPayment(btn) {
    const originalText = btn.innerText;
    btn.innerText = 'Processing...';
    btn.disabled = true;

    fetch('api/create_razorpay_order.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const options = {
                "key": data.key,
                "amount": data.amount,
                "currency": data.currency,
                "name": data.name,
                "description": data.description,
                "order_id": data.order_id,
                "handler": function (response) {
                    verifyPayment(response, btn);
                },
                "prefill": data.prefill,
                "theme": {
                    "color": "#D4AF37"
                },
                "modal": {
                    "ondismiss": function() {
                        btn.innerText = originalText;
                        btn.disabled = false;
                    }
                }
            };
            const rzp1 = new Razorpay(options);
            rzp1.open();
        } else {
            alert('Error: ' + data.message);
            btn.innerText = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Something went wrong. Please try again.');
        btn.innerText = originalText;
        btn.disabled = false;
    });
}

function verifyPayment(response, btn) {
    fetch('api/verify_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_order_id: response.razorpay_order_id,
            razorpay_signature: response.razorpay_signature
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'my-profile.php?order_success=true&id=' + data.order_id;
        } else {
            alert('Payment verification failed: ' + data.message);
            btn.innerText = 'Pay Now';
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Payment verification error.');
        btn.innerText = 'Pay Now';
        btn.disabled = false;
    });
}
</script>

</body>
</html>
