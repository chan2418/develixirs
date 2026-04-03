<?php

require_once __DIR__ . '/coupon_helpers.php';
require_once __DIR__ . '/subscription_plan_helper.php';

if (!function_exists('order_pricing_table_exists')) {
    function order_pricing_table_exists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
        ");
        $stmt->execute([subscription_db_name($pdo), $table]);

        return ((int)$stmt->fetchColumn()) > 0;
    }
}

if (!function_exists('order_pricing_column_exists')) {
    function order_pricing_column_exists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([subscription_db_name($pdo), $table, $column]);

        return ((int)$stmt->fetchColumn()) > 0;
    }
}

if (!function_exists('order_pricing_list_columns')) {
    function order_pricing_list_columns(PDO $pdo, string $table): array
    {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
        $columns = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            if (!empty($column['Field'])) {
                $columns[] = (string)$column['Field'];
            }
        }
        return $columns;
    }
}

if (!function_exists('ensure_order_pricing_schema')) {
    function ensure_order_pricing_schema(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        ensure_subscription_schema($pdo);

        if (!order_pricing_table_exists($pdo, 'orders')) {
            return;
        }

        $columns = [
            'base_subtotal' => "ALTER TABLE orders ADD COLUMN base_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount",
            'subscription_plan_id' => "ALTER TABLE orders ADD COLUMN subscription_plan_id INT(11) DEFAULT NULL AFTER base_subtotal",
            'subscription_plan_name' => "ALTER TABLE orders ADD COLUMN subscription_plan_name VARCHAR(100) DEFAULT NULL AFTER subscription_plan_id",
            'subscription_discount' => "ALTER TABLE orders ADD COLUMN subscription_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER subscription_plan_name",
            'subscription_discount_percent' => "ALTER TABLE orders ADD COLUMN subscription_discount_percent DECIMAL(5,2) DEFAULT NULL AFTER subscription_discount",
            'applied_discount_type' => "ALTER TABLE orders ADD COLUMN applied_discount_type VARCHAR(20) NOT NULL DEFAULT 'none' AFTER subscription_discount_percent",
        ];

        foreach ($columns as $column => $sql) {
            if (!order_pricing_column_exists($pdo, 'orders', $column)) {
                $pdo->exec($sql);
            }
        }
    }
}

if (!function_exists('fetch_active_user_subscription')) {
    function fetch_active_user_subscription(PDO $pdo, int $userId): ?array
    {
        ensure_order_pricing_schema($pdo);

        if (!order_pricing_table_exists($pdo, 'user_subscriptions')) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT
                us.id AS user_subscription_id,
                us.plan_id,
                COALESCE(NULLIF(us.plan_name, ''), sp.name) AS plan_name,
                COALESCE(us.discount_percentage_snapshot, sp.discount_percentage, 0) AS discount_percentage,
                COALESCE(us.free_shipping_snapshot, sp.free_shipping, 0) AS free_shipping,
                COALESCE(NULLIF(us.badge_text_snapshot, ''), sp.badge_text) AS badge_text,
                COALESCE(NULLIF(us.billing_cycle_snapshot, ''), sp.billing_cycle) AS billing_cycle,
                us.start_date,
                us.end_date
            FROM user_subscriptions us
            LEFT JOIN subscription_plans sp ON sp.id = us.plan_id
            WHERE us.user_id = ?
              AND us.status = 'active'
              AND us.start_date <= CURDATE()
              AND us.end_date >= CURDATE()
            ORDER BY us.end_date DESC, us.id DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$subscription) {
            return null;
        }

        $subscription['plan_id'] = isset($subscription['plan_id']) ? (int)$subscription['plan_id'] : null;
        $subscription['user_subscription_id'] = (int)$subscription['user_subscription_id'];
        $subscription['plan_name'] = trim((string)($subscription['plan_name'] ?? ''));
        $subscription['discount_percentage'] = round((float)($subscription['discount_percentage'] ?? 0), 2);
        $subscription['free_shipping'] = !empty($subscription['free_shipping']) ? 1 : 0;

        return $subscription;
    }
}

if (!function_exists('fetch_order_context_items')) {
    function fetch_order_context_items(PDO $pdo, int $userId, bool $preferDirectBuy = true): array
    {
        $items = [];
        $directBuyItem = $_SESSION['direct_buy_item'] ?? null;

        if (
            $preferDirectBuy &&
            is_array($directBuyItem) &&
            !empty($directBuyItem['product_id']) &&
            !empty($directBuyItem['quantity'])
        ) {
            $stmt = $pdo->prepare("
                SELECT id AS product_id, name, price, images, category_id
                FROM products
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([(int)$directBuyItem['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $product['quantity'] = max(1, (int)$directBuyItem['quantity']);
                $items[] = $product;
            }
        }

        if (!empty($items)) {
            return $items;
        }

        $stmt = $pdo->prepare("
            SELECT
                c.id,
                c.product_id,
                c.quantity,
                p.name,
                p.price,
                p.images,
                p.category_id
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = :uid
            ORDER BY c.id ASC
        ");
        $stmt->execute([':uid' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('normalize_pricing_cart_items')) {
    function normalize_pricing_cart_items(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $qty = max(0, (int)($item['quantity'] ?? $item['qty'] ?? 0));
            $price = round((float)($item['price'] ?? $item['unit_price'] ?? 0), 2);

            if ($qty <= 0) {
                continue;
            }

            $normalized[] = [
                'product_id' => isset($item['product_id']) ? (int)$item['product_id'] : (isset($item['id']) ? (int)$item['id'] : 0),
                'name' => (string)($item['name'] ?? $item['product_name'] ?? ''),
                'price' => $price,
                'quantity' => $qty,
                'images' => (string)($item['images'] ?? ''),
                'category_id' => isset($item['category_id']) ? (int)$item['category_id'] : 0,
            ];
        }

        return $normalized;
    }
}

if (!function_exists('resolve_pricing_coupon')) {
    function resolve_pricing_coupon(PDO $pdo, int $userId, array $cartItems, ?array $sessionCoupon = null): array
    {
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $subtotal += ((float)$item['price'] * (int)$item['quantity']);
        }
        $subtotal = round($subtotal, 2);

        $response = [
            'coupon' => null,
            'discount_amount' => 0.0,
            'removed_message' => null,
        ];

        if (!$sessionCoupon || empty($sessionCoupon['code'])) {
            return $response;
        }

        $validation = validateCoupon((string)$sessionCoupon['code'], $userId, $subtotal, $cartItems, $pdo);
        if (!$validation['valid']) {
            removeCouponFromSession();
            $response['removed_message'] = 'Coupon removed: ' . $validation['message'];
            return $response;
        }

        $coupon = $validation['coupon'];
        $discount = round((float)calculateDiscount($coupon, $subtotal), 2);
        applyCouponToSession($coupon, $discount);

        $coupon['discount_amount'] = $discount;
        $response['coupon'] = $coupon;
        $response['discount_amount'] = $discount;

        return $response;
    }
}

if (!function_exists('calculate_order_pricing')) {
    function calculate_order_pricing(PDO $pdo, int $userId, array $items, ?array $sessionCoupon = null): array
    {
        ensure_order_pricing_schema($pdo);

        $cartItems = normalize_pricing_cart_items($items);
        $lineItemCount = count($cartItems);
        $quantityTotal = 0;
        $subtotal = 0.0;

        foreach ($cartItems as $item) {
            $quantityTotal += (int)$item['quantity'];
            $subtotal += ((float)$item['price'] * (int)$item['quantity']);
        }

        $subtotal = round($subtotal, 2);
        $couponState = resolve_pricing_coupon($pdo, $userId, $cartItems, $sessionCoupon);
        $subscription = fetch_active_user_subscription($pdo, $userId);

        $couponDiscount = round((float)$couponState['discount_amount'], 2);
        $subscriptionPotentialDiscount = 0.0;

        if ($subscription && $subtotal > 0 && (float)$subscription['discount_percentage'] > 0) {
            $subscriptionPotentialDiscount = round(min($subtotal, ($subtotal * (float)$subscription['discount_percentage']) / 100), 2);
        }

        $appliedDiscountType = 'none';
        $appliedDiscountAmount = 0.0;
        $couponAppliedDiscount = 0.0;
        $subscriptionAppliedDiscount = 0.0;

        if ($subscriptionPotentialDiscount > 0 && $couponDiscount > 0) {
            if ($subscriptionPotentialDiscount >= $couponDiscount) {
                $appliedDiscountType = 'subscription';
                $appliedDiscountAmount = $subscriptionPotentialDiscount;
                $subscriptionAppliedDiscount = $subscriptionPotentialDiscount;
            } else {
                $appliedDiscountType = 'coupon';
                $appliedDiscountAmount = $couponDiscount;
                $couponAppliedDiscount = $couponDiscount;
            }
        } elseif ($subscriptionPotentialDiscount > 0) {
            $appliedDiscountType = 'subscription';
            $appliedDiscountAmount = $subscriptionPotentialDiscount;
            $subscriptionAppliedDiscount = $subscriptionPotentialDiscount;
        } elseif ($couponDiscount > 0) {
            $appliedDiscountType = 'coupon';
            $appliedDiscountAmount = $couponDiscount;
            $couponAppliedDiscount = $couponDiscount;
        }

        $productTotalAfterDiscount = round(max(0, $subtotal - $appliedDiscountAmount), 2);
        $deliveryCharge = 0.0;
        $shippingReason = 'free_threshold';

        if ($quantityTotal > 0) {
            if ($subscription && !empty($subscription['free_shipping'])) {
                $deliveryCharge = 0.0;
                $shippingReason = 'subscription';
            } else {
                $deliveryCharge = ($subtotal < 1000) ? 80.0 : 0.0;
                $shippingReason = $deliveryCharge > 0 ? 'standard' : 'free_threshold';
            }
        }

        $taxAmount = 0.0;
        $taxableSubtotal = 0.0;
        if ($productTotalAfterDiscount > 0) {
            $taxAmount = round($productTotalAfterDiscount - ($productTotalAfterDiscount / 1.18), 2);
            $taxableSubtotal = round(max(0, $productTotalAfterDiscount - $taxAmount), 2);
        }

        $finalTotal = round($productTotalAfterDiscount + $deliveryCharge, 2);

        $discountLabel = '';
        if ($appliedDiscountType === 'coupon' && !empty($couponState['coupon']['code'])) {
            $discountLabel = 'Coupon Discount (' . strtoupper((string)$couponState['coupon']['code']) . ')';
        } elseif ($appliedDiscountType === 'subscription' && $subscription) {
            $planName = trim((string)($subscription['plan_name'] ?? 'Subscription'));
            $discountLabel = 'Subscription Discount (' . $planName . ')';
        }

        return [
            'items' => $cartItems,
            'line_item_count' => $lineItemCount,
            'quantity_total' => $quantityTotal,
            'base_subtotal' => $subtotal,
            'product_total_after_discount' => $productTotalAfterDiscount,
            'delivery_charge' => round($deliveryCharge, 2),
            'shipping_reason' => $shippingReason,
            'tax_amount' => $taxAmount,
            'taxable_subtotal' => $taxableSubtotal,
            'final_total' => $finalTotal,
            'applied_discount_type' => $appliedDiscountType,
            'applied_discount_amount' => round($appliedDiscountAmount, 2),
            'discount_label' => $discountLabel,
            'coupon' => [
                'data' => $couponState['coupon'],
                'discount_amount' => $couponDiscount,
                'applied' => $appliedDiscountType === 'coupon' && $couponDiscount > 0,
                'saved_not_applied' => $couponDiscount > 0 && $appliedDiscountType !== 'coupon',
                'removed_message' => $couponState['removed_message'],
            ],
            'subscription' => [
                'active' => (bool)$subscription,
                'plan_id' => $subscription['plan_id'] ?? null,
                'user_subscription_id' => $subscription['user_subscription_id'] ?? null,
                'plan_name' => $subscription['plan_name'] ?? '',
                'discount_percentage' => $subscription ? (float)$subscription['discount_percentage'] : 0.0,
                'potential_discount' => $subscriptionPotentialDiscount,
                'applied_discount' => $subscriptionAppliedDiscount,
                'free_shipping' => $subscription ? !empty($subscription['free_shipping']) : false,
                'badge_text' => $subscription['badge_text'] ?? '',
            ],
        ];
    }
}

if (!function_exists('order_pricing_frontend_payload')) {
    function order_pricing_frontend_payload(array $pricing): array
    {
        return [
            'line_item_count' => (int)($pricing['line_item_count'] ?? 0),
            'quantity_total' => (int)($pricing['quantity_total'] ?? 0),
            'base_subtotal' => round((float)($pricing['base_subtotal'] ?? 0), 2),
            'delivery_charge' => round((float)($pricing['delivery_charge'] ?? 0), 2),
            'final_total' => round((float)($pricing['final_total'] ?? 0), 2),
            'applied_discount_type' => (string)($pricing['applied_discount_type'] ?? 'none'),
            'applied_discount_amount' => round((float)($pricing['applied_discount_amount'] ?? 0), 2),
            'discount_label' => (string)($pricing['discount_label'] ?? ''),
            'coupon_code' => (string)($pricing['coupon']['data']['code'] ?? ''),
            'coupon_saved_not_applied' => !empty($pricing['coupon']['saved_not_applied']),
            'subscription_plan_name' => (string)($pricing['subscription']['plan_name'] ?? ''),
            'subscription_active' => !empty($pricing['subscription']['active']),
            'subscription_free_shipping' => !empty($pricing['subscription']['free_shipping']),
            'coupon_removed_message' => (string)($pricing['coupon']['removed_message'] ?? ''),
        ];
    }
}

if (!function_exists('insert_priced_order')) {
    function insert_priced_order(PDO $pdo, array $data): int
    {
        ensure_order_pricing_schema($pdo);
        $availableColumns = order_pricing_list_columns($pdo, 'orders');
        $columnLookup = array_fill_keys($availableColumns, true);
        $columns = [];
        $placeholders = [];
        $values = [];

        foreach ($data as $column => $value) {
            if (!isset($columnLookup[$column])) {
                continue;
            }

            $columns[] = $column;
            if ($value === '__NOW__') {
                $placeholders[] = 'NOW()';
                continue;
            }

            $placeholders[] = '?';
            $values[] = $value;
        }

        if (empty($columns)) {
            throw new RuntimeException('No valid order columns available for insert.');
        }

        $sql = 'INSERT INTO orders (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        return (int)$pdo->lastInsertId();
    }
}
