<?php
// subscription_checkout.php - Subscription Payment Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$selectedPlanId = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'subscription_checkout.php' . ($selectedPlanId > 0 ? ('?plan_id=' . $selectedPlanId) : '');
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/subscription_plan_helper.php';
require_once __DIR__ . '/includes/subscription_lifecycle_helper.php';

ensure_subscription_schema($pdo);
subscription_sync_statuses($pdo, (int)$_SESSION['user_id']);

// Fetch user info
$user_id = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$userPhone = $_SESSION['user_phone'] ?? '';

$currentSubscription = null;
$upcomingSubscription = null;
try {
    $currentSubscription = subscription_fetch_current_active($pdo, $user_id);
    $upcomingSubscription = subscription_fetch_upcoming_active($pdo, $user_id);
} catch (PDOException $e) {
    error_log("Error checking subscription: " . $e->getMessage());
}

// Fetch active subscription plan
$allPlans = [];
$plan = null;
try {
    $allPlans = subscription_fetch_active_plans($pdo);
    $plan = subscription_fetch_primary_plan($pdo, $selectedPlanId, true);
} catch (PDOException $e) {
    error_log("Error fetching subscription plan: " . $e->getMessage());
}

if (!$plan) {
    header('Location: subscription.php?error=no_plan');
    exit;
}

$planPrice = (float)$plan['price'];
$planName = $plan['name'];
$discountPercentage = $plan['discount_percentage'];
$billingCycle = $plan['billing_cycle'];
$planBenefits = $plan['benefits_list'] ?? subscription_default_benefits((float)$discountPercentage, !empty($plan['free_shipping']));
$planShortDescription = trim((string)($plan['short_description'] ?? ''));
$billingCycleLabel = subscription_cycle_label($billingCycle);
$isRenewalCheckout = $currentSubscription !== null;
$paymentButtonLabel = $isRenewalCheckout ? 'Renew Subscription' : 'Pay';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe & Save - Checkout | Develixirs</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    
    <!-- Razorpay Checkout -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    
    <!-- Navbar -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            padding-top: 80px;
        }
        
        .checkout-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .checkout-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        }
        
        .checkout-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .checkout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: white;
        }
        
        .checkout-title {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        
        .checkout-subtitle {
            font-size: 16px;
            color: #666;
        }
        
        .plan-details {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .plan-name {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .plan-price {
            font-size: 36px;
            font-weight: 800;
            color: #667eea;
        }
        
        .plan-benefits {
            display: grid;
            gap: 12px;
            margin-top: 20px;
        }
        
        .plan-benefit {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            color: #555;
        }
        
        .plan-benefit i {
            color: #667eea;
            font-size: 16px;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .user-info-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1a1a1a;
        }
        
        .user-info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .user-info-item:last-child {
            border-bottom: none;
        }
        
        .user-info-label {
            color: #666;
            font-weight: 500;
        }
        
        .user-info-value {
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .payment-button {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .payment-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }
        
        .payment-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .secure-badge i {
            color: #4caf50;
            font-size: 18px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }

        .plan-switcher {
            display: grid;
            gap: 10px;
            margin-bottom: 24px;
        }

        .plan-switcher a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            text-decoration: none;
            color: #1a1a1a;
            background: #fff;
            transition: all 0.2s ease;
        }

        .plan-switcher a:hover {
            border-color: #667eea;
            transform: translateY(-1px);
        }

        .plan-switcher a.active {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.08);
        }

        .status-note {
            background: #eef6ff;
            border: 1px solid #cfe0ff;
            color: #234;
            padding: 16px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        .status-note strong {
            color: #1a1a1a;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>
    
    <div class="checkout-container">
        <div class="checkout-card">
            <div class="checkout-header">
                <div class="checkout-icon">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <h1 class="checkout-title">Complete Your Subscription</h1>
                <p class="checkout-subtitle">You're one step away from exclusive benefits!</p>
            </div>

            <?php if ($currentSubscription): ?>
                <div class="status-note">
                    <strong>Current membership:</strong>
                    <?php echo htmlspecialchars($currentSubscription['display_plan_name']); ?>
                    is active until
                    <strong><?php echo date('d M Y', strtotime($currentSubscription['end_date'])); ?></strong>.
                    This purchase will start after your current plan ends.
                </div>
            <?php endif; ?>

            <?php if ($upcomingSubscription): ?>
                <div class="status-note" style="background:#fff7ed;border-color:#fed7aa;">
                    <strong>Renewal already scheduled:</strong>
                    <?php echo htmlspecialchars($upcomingSubscription['display_plan_name']); ?>
                    starts on
                    <strong><?php echo date('d M Y', strtotime($upcomingSubscription['start_date'])); ?></strong>
                    and runs until
                    <strong><?php echo date('d M Y', strtotime($upcomingSubscription['end_date'])); ?></strong>.
                </div>
            <?php endif; ?>

            <?php if (count($allPlans) > 1): ?>
                <div class="plan-switcher">
                    <?php foreach ($allPlans as $switchPlan): ?>
                        <a href="subscription_checkout.php?plan_id=<?php echo (int)$switchPlan['id']; ?>" class="<?php echo ((int)$switchPlan['id'] === (int)$plan['id']) ? 'active' : ''; ?>">
                            <span>
                                <strong><?php echo htmlspecialchars($switchPlan['name']); ?></strong><br>
                                <span style="font-size:13px;color:#666;">
                                    <?php echo htmlspecialchars(subscription_cycle_label($switchPlan['billing_cycle'] ?? 'monthly')); ?>
                                    · <?php echo number_format((float)$switchPlan['discount_percentage'], 0); ?>% off
                                </span>
                            </span>
                            <strong>₹<?php echo number_format((float)$switchPlan['price'], 0); ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="plan-details">
                <div class="plan-name">
                    <?php echo htmlspecialchars($planName); ?>
                    <div>
                        <?php if ($plan['compare_price'] && $plan['compare_price'] > $planPrice): ?>
                            <span style="text-decoration: line-through; opacity: 0.6; font-size: 20px; margin-right: 8px;">
                                ₹<?php echo number_format($plan['compare_price'], 0); ?>
                            </span>
                        <?php endif; ?>
                        <span class="plan-price">₹<?php echo number_format($planPrice, 0); ?></span>
                    </div>
                </div>

                <?php if ($planShortDescription !== ''): ?>
                    <div style="color:#4b5563; font-size:15px; margin-bottom:16px;">
                        <?php echo htmlspecialchars($planShortDescription); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($plan['compare_price'] && $plan['compare_price'] > $planPrice): 
                    $savings = $plan['compare_price'] - $planPrice;
                ?>
                    <div style="background: rgba(102, 126, 234, 0.15); padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; color: #667eea; font-weight: 600;">
                        💰 You save ₹<?php echo number_format($savings, 0); ?> with this subscription!
                    </div>
                <?php endif; ?>
                
                <div class="plan-benefits">
                    <?php foreach ($planBenefits as $benefit): ?>
                        <div class="plan-benefit">
                            <i class="fa-solid fa-check-circle"></i>
                            <?php echo htmlspecialchars($benefit); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="user-info">
                <div class="user-info-title">Billing Information</div>
                <div class="user-info-item">
                    <span class="user-info-label">Name</span>
                    <span class="user-info-value"><?php echo htmlspecialchars($userName); ?></span>
                </div>
                <div class="user-info-item">
                    <span class="user-info-label">Email</span>
                    <span class="user-info-value"><?php echo htmlspecialchars($userEmail); ?></span>
                </div>
                <div class="user-info-item">
                    <span class="user-info-label">Phone</span>
                    <span class="user-info-value"><?php echo htmlspecialchars($userPhone ?: 'Not provided'); ?></span>
                </div>
                <div class="user-info-item">
                    <span class="user-info-label">Billing Cycle</span>
                    <span class="user-info-value"><?php echo htmlspecialchars($billingCycleLabel); ?></span>
                </div>
                <div class="user-info-item">
                    <span class="user-info-label">Validity</span>
                    <span class="user-info-value"><?php echo (int)$plan['validity_days']; ?> days</span>
                </div>
            </div>
            
            <button id="payButton" class="payment-button">
                <i class="fa-solid fa-lock"></i> <?php echo htmlspecialchars($paymentButtonLabel); ?> ₹<?php echo number_format($planPrice, 0); ?> Securely
            </button>
            
            <div class="secure-badge">
                <i class="fa-solid fa-shield-check"></i>
                <span>Secured by Razorpay</span>
            </div>
            
            <center>
                <a href="subscription.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Back to Subscription Info
                </a>
            </center>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script>
        const payButton = document.getElementById('payButton');
        const defaultButtonHtml = '<i class="fa-solid fa-lock"></i> <?php echo htmlspecialchars($paymentButtonLabel); ?> ₹<?php echo number_format($planPrice, 0); ?> Securely';
        
        payButton.addEventListener('click', function() {
            payButton.disabled = true;
            payButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            
            // Create Razorpay order
            fetch('api/create_subscription_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    plan_id: <?php echo (int)$plan['id']; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    openRazorpay(data);
                } else {
                    alert('Error: ' + (data.message || 'Failed to create order'));
                    payButton.disabled = false;
                    payButton.innerHTML = defaultButtonHtml;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Something went wrong. Please try again.');
                payButton.disabled = false;
                payButton.innerHTML = defaultButtonHtml;
            });
        });
        
        function openRazorpay(orderData) {
            const options = {
                key: orderData.key,
                amount: orderData.amount,
                currency: 'INR',
                name: 'Develixirs Premium',
                description: '<?php echo htmlspecialchars($planName); ?>',
                order_id: orderData.order_id,
                handler: function(response) {
                    // Payment successful
                    verifyPayment(response);
                },
                prefill: {
                    name: '<?php echo htmlspecialchars($userName); ?>',
                    email: '<?php echo htmlspecialchars($userEmail); ?>',
                    contact: '<?php echo htmlspecialchars($userPhone); ?>'
                },
                theme: {
                    color: '#667eea'
                },
                modal: {
                    ondismiss: function() {
                        payButton.disabled = false;
                        payButton.innerHTML = defaultButtonHtml;
                    }
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        }
        
        function verifyPayment(response) {
            fetch('subscription_callback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature,
                    plan_id: <?php echo (int)$plan['id']; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const targetState = data.activation_type === 'renewal' ? 'renewed' : 'activated';
                    window.location.href = 'my-profile.php?tab=subscription&subscription=' + encodeURIComponent(targetState);
                } else {
                    alert('Payment verification failed: ' + (data.message || 'Unknown error'));
                    payButton.disabled = false;
                    payButton.innerHTML = defaultButtonHtml;
                }
            })
            .catch(error => {
                console.error('Verification error:', error);
                alert('Payment verification failed. Please contact support.');
                payButton.disabled = false;
                payButton.innerHTML = defaultButtonHtml;
            });
        }
    </script>
</body>
</html>
