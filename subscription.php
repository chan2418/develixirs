<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/subscription_plan_helper.php';

ensure_subscription_schema($pdo);

$plans = [];
try {
    $plans = subscription_fetch_active_plans($pdo);
} catch (Throwable $e) {
    error_log('Subscription plans fetch error: ' . $e->getMessage());
    $plans = [];
}

if (!$plans) {
    header('Location: index.php');
    exit;
}

$featuredPlan = $plans[0];
$pageTitle = "Subscribe & Save - Unlock Premium Benefits";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | Develixirs</title>
    <meta name="description" content="Choose a Develixirs membership plan and unlock product discounts, early access, exclusive offers, and premium perks.">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/navbar.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --ink: #122033;
            --muted: #5e6775;
            --line: #e5e7eb;
            --card: #ffffff;
            --canvas: #f6f7fb;
            --accent: #d65f50;
            --accent-deep: #8a3d34;
            --sand: #f6ecdf;
            --mint: #dce9df;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at top left, rgba(220, 233, 223, 0.9), transparent 30%),
                        radial-gradient(circle at top right, rgba(246, 236, 223, 0.9), transparent 28%),
                        var(--canvas);
            color: var(--ink);
            line-height: 1.6;
        }

        .hero {
            padding: 110px 20px 70px;
        }

        .hero-wrap {
            max-width: 1240px;
            margin: 0 auto;
            display: grid;
            gap: 28px;
            grid-template-columns: 1.2fr 0.8fr;
            align-items: stretch;
        }

        .hero-copy,
        .hero-featured {
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(18, 32, 51, 0.08);
            backdrop-filter: blur(14px);
            box-shadow: 0 20px 70px rgba(18, 32, 51, 0.08);
        }

        .hero-copy {
            padding: 42px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: var(--sand);
            color: var(--accent-deep);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .hero-title {
            margin-top: 18px;
            font-size: clamp(34px, 5vw, 58px);
            line-height: 1.04;
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .hero-text {
            margin-top: 18px;
            max-width: 680px;
            font-size: 17px;
            color: var(--muted);
        }

        .hero-points {
            margin-top: 24px;
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .hero-point {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 16px 18px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(18, 32, 51, 0.06);
        }

        .hero-point i {
            color: var(--accent);
            font-size: 18px;
        }

        .hero-featured {
            padding: 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                linear-gradient(135deg, rgba(214, 95, 80, 0.08), transparent 45%),
                rgba(255, 255, 255, 0.94);
        }

        .plan-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: var(--mint);
            color: #274b3a;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .featured-name {
            margin-top: 20px;
            font-size: 30px;
            font-weight: 800;
        }

        .featured-price {
            margin-top: 16px;
            font-size: 54px;
            line-height: 1;
            font-weight: 800;
            color: var(--accent-deep);
        }

        .featured-price small {
            font-size: 18px;
            color: var(--muted);
            font-weight: 600;
        }

        .featured-copy {
            margin-top: 14px;
            color: var(--muted);
            font-size: 15px;
        }

        .featured-list {
            margin-top: 22px;
            display: grid;
            gap: 10px;
        }

        .featured-list div {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            color: var(--ink);
            font-size: 14px;
        }

        .featured-list i {
            color: var(--accent);
            margin-top: 3px;
        }

        .featured-button {
            margin-top: 26px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 16px 22px;
            border-radius: 18px;
            background: var(--ink);
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            font-weight: 700;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 16px 28px rgba(18, 32, 51, 0.18);
        }

        .featured-button:hover {
            transform: translateY(-2px);
        }

        .plans {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 20px 80px;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 20px;
            margin-bottom: 28px;
        }

        .section-head h2 {
            font-size: 34px;
            line-height: 1.05;
            letter-spacing: -0.03em;
        }

        .section-head p {
            max-width: 520px;
            color: var(--muted);
            font-size: 15px;
        }

        .plan-grid {
            display: grid;
            gap: 22px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .plan-card {
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 28px;
            border-radius: 26px;
            background: var(--card);
            border: 1px solid rgba(18, 32, 51, 0.08);
            box-shadow: 0 18px 46px rgba(18, 32, 51, 0.06);
        }

        .plan-card.featured {
            border-color: rgba(214, 95, 80, 0.35);
            transform: translateY(-6px);
            box-shadow: 0 24px 54px rgba(214, 95, 80, 0.14);
        }

        .plan-card-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: start;
        }

        .plan-card h3 {
            font-size: 26px;
            line-height: 1.08;
            letter-spacing: -0.03em;
        }

        .price-line {
            margin-top: 18px;
            display: flex;
            align-items: end;
            gap: 10px;
        }

        .price-line strong {
            font-size: 48px;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .price-line span {
            color: var(--muted);
            font-size: 16px;
            font-weight: 600;
        }

        .compare-price {
            margin-top: 8px;
            color: #98a1ae;
            text-decoration: line-through;
            font-size: 15px;
        }

        .plan-desc {
            margin-top: 14px;
            font-size: 14px;
            color: var(--muted);
        }

        .meta-row {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #f8f6f3;
            color: #5d4d44;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .benefits {
            margin-top: 22px;
            display: grid;
            gap: 12px;
            flex: 1;
        }

        .benefits div {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            font-size: 14px;
            color: #314055;
        }

        .benefits i {
            color: var(--accent);
            margin-top: 3px;
        }

        .plan-action {
            margin-top: 26px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 15px 20px;
            border-radius: 18px;
            border: 1px solid var(--ink);
            color: var(--ink);
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .plan-action:hover {
            background: var(--ink);
            color: #fff;
        }

        .trust-strip {
            max-width: 1240px;
            margin: 0 auto 80px;
            padding: 0 20px;
        }

        .trust-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .trust-item {
            padding: 20px;
            border-radius: 22px;
            background: #fff;
            border: 1px solid rgba(18, 32, 51, 0.08);
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .trust-item i {
            font-size: 20px;
            color: var(--accent);
        }

        .trust-item strong {
            display: block;
            font-size: 15px;
        }

        .trust-item span {
            display: block;
            font-size: 13px;
            color: var(--muted);
        }

        @media (max-width: 1080px) {
            .hero-wrap,
            .plan-grid,
            .trust-grid {
                grid-template-columns: 1fr;
            }

            .plan-card.featured {
                transform: none;
            }
        }

        @media (max-width: 640px) {
            .hero {
                padding-top: 96px;
            }

            .hero-copy,
            .hero-featured,
            .plan-card {
                padding: 24px;
            }

            .hero-points {
                grid-template-columns: 1fr;
            }

            .section-head {
                flex-direction: column;
                align-items: start;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <section class="hero">
        <div class="hero-wrap">
            <div class="hero-copy">
                <div class="eyebrow">
                    <i class="fa-solid fa-crown"></i>
                    Premium Memberships
                </div>
                <h1 class="hero-title">Choose the subscription plan that matches how your customers buy.</h1>
                <p class="hero-text">
                    Monthly, quarterly, and yearly memberships are now supported. Use the plan that fits your reorder cycle and start saving on every order with one clean membership flow.
                </p>

                <div class="hero-points">
                    <div class="hero-point">
                        <i class="fa-solid fa-percent"></i>
                        <div>
                            <strong>Product savings</strong><br>
                            Up to <?php echo number_format((float)$featuredPlan['discount_percentage'], 0); ?>% off on subscriber orders
                        </div>
                    </div>
                    <div class="hero-point">
                        <i class="fa-solid fa-bolt"></i>
                        <div>
                            <strong>Fast checkout</strong><br>
                            Plan-specific checkout and direct Razorpay payment flow
                        </div>
                    </div>
                    <div class="hero-point">
                        <i class="fa-solid fa-box-open"></i>
                        <div>
                            <strong>Early access</strong><br>
                            Priority access to new launches and exclusive drops
                        </div>
                    </div>
                    <div class="hero-point">
                        <i class="fa-solid fa-headset"></i>
                        <div>
                            <strong>Support priority</strong><br>
                            Members move faster through customer support
                        </div>
                    </div>
                </div>
            </div>

            <aside class="hero-featured">
                <div>
                    <div class="plan-badge">
                        <i class="fa-solid fa-star"></i>
                        <?php echo htmlspecialchars(trim((string)($featuredPlan['badge_text'] ?? 'Featured Plan')) ?: 'Featured Plan'); ?>
                    </div>
                    <div class="featured-name"><?php echo htmlspecialchars($featuredPlan['name']); ?></div>
                    <div class="featured-price">
                        ₹<?php echo number_format((float)$featuredPlan['price'], 0); ?>
                        <small><?php echo htmlspecialchars(subscription_cycle_suffix($featuredPlan['billing_cycle'] ?? 'monthly')); ?></small>
                    </div>
                    <div class="featured-copy">
                        <?php echo htmlspecialchars($featuredPlan['short_description'] ?: 'Balanced savings and retention for regular customers.'); ?>
                    </div>
                    <div class="featured-list">
                        <?php foreach (array_slice($featuredPlan['benefits_list'] ?? [], 0, 4) as $benefit): ?>
                            <div>
                                <i class="fa-solid fa-check"></i>
                                <span><?php echo htmlspecialchars($benefit); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <a class="featured-button" href="subscription_checkout.php?plan_id=<?php echo (int)$featuredPlan['id']; ?>">
                    Choose <?php echo htmlspecialchars($featuredPlan['name']); ?>
                </a>
            </aside>
        </div>
    </section>

    <section class="plans">
        <div class="section-head">
            <div>
                <h2>Active Plans</h2>
                <p>Keep multiple plans active at the same time. The featured plan is highlighted first, but customers can choose any active membership.</p>
            </div>
        </div>

        <div class="plan-grid">
            <?php foreach ($plans as $plan): ?>
                <?php
                    $isFeatured = !empty($plan['is_featured']);
                    $badgeText = trim((string)($plan['badge_text'] ?? ''));
                    $benefits = $plan['benefits_list'] ?? [];
                ?>
                <article class="plan-card <?php echo $isFeatured ? 'featured' : ''; ?>">
                    <div class="plan-card-top">
                        <div>
                            <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <div class="price-line">
                                <strong>₹<?php echo number_format((float)$plan['price'], 0); ?></strong>
                                <span><?php echo htmlspecialchars(subscription_cycle_suffix($plan['billing_cycle'] ?? 'monthly')); ?></span>
                            </div>
                            <?php if (!empty($plan['compare_price']) && (float)$plan['compare_price'] > (float)$plan['price']): ?>
                                <div class="compare-price">₹<?php echo number_format((float)$plan['compare_price'], 0); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ($badgeText !== ''): ?>
                            <div class="plan-badge"><?php echo htmlspecialchars($badgeText); ?></div>
                        <?php endif; ?>
                    </div>

                    <p class="plan-desc">
                        <?php echo htmlspecialchars($plan['short_description'] ?: 'Subscriber-only access to product savings and exclusive perks.'); ?>
                    </p>

                    <div class="meta-row">
                        <div class="meta-pill">
                            <i class="fa-solid fa-percent"></i>
                            <?php echo number_format((float)$plan['discount_percentage'], 0); ?>% Off
                        </div>
                        <div class="meta-pill">
                            <i class="fa-solid fa-calendar"></i>
                            <?php echo htmlspecialchars(subscription_cycle_label($plan['billing_cycle'] ?? 'monthly')); ?>
                        </div>
                        <div class="meta-pill">
                            <i class="fa-solid fa-clock"></i>
                            <?php echo (int)$plan['validity_days']; ?> Days
                        </div>
                        <?php if (!empty($plan['free_shipping'])): ?>
                            <div class="meta-pill">
                                <i class="fa-solid fa-truck-fast"></i>
                                Free Shipping
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="benefits">
                        <?php foreach ($benefits as $benefit): ?>
                            <div>
                                <i class="fa-solid fa-check-circle"></i>
                                <span><?php echo htmlspecialchars($benefit); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <a class="plan-action" href="subscription_checkout.php?plan_id=<?php echo (int)$plan['id']; ?>">
                        Choose This Plan
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="trust-strip">
        <div class="trust-grid">
            <div class="trust-item">
                <i class="fa-solid fa-shield-check"></i>
                <div>
                    <strong>Secure Payment</strong>
                    <span>Razorpay-secured plan checkout</span>
                </div>
            </div>
            <div class="trust-item">
                <i class="fa-solid fa-repeat"></i>
                <div>
                    <strong>Flexible Renewal</strong>
                    <span>Monthly, quarterly, and yearly plan options</span>
                </div>
            </div>
            <div class="trust-item">
                <i class="fa-solid fa-user-check"></i>
                <div>
                    <strong>Subscriber Perks</strong>
                    <span>Priority offers and better repeat-purchase economics</span>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
