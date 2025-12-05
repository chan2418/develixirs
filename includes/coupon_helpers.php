<?php
/**
 * Coupon Helper Functions
 * Reusable functions for coupon validation and management
 */

// Set timezone to India Standard Time to ensure accurate date comparisons
date_default_timezone_set('Asia/Kolkata');

if (!function_exists('isFirstTimeUser')) {
    /**
     * Check if user is a first-time buyer (no completed orders)
     */
    function isFirstTimeUser($userId, $pdo) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as order_count 
            FROM orders 
            WHERE user_id = :user_id AND status != 'cancelled'
        ");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['order_count'] == 0;
    }
}

if (!function_exists('validateCoupon')) {
    /**
     * Validate coupon and return validation result
     * Returns: ['valid' => bool, 'message' => string, 'coupon' => array|null]
     */
    function validateCoupon($code, $userId, $cartTotal, $cartItems, $pdo) {
        // 1. Check if coupon exists
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE UPPER(code) = UPPER(:code)");
        $stmt->execute([':code' => $code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code', 'coupon' => null];
        }
        
        // 2. Check if coupon is active
        if ($coupon['status'] !== 'active') {
            return ['valid' => false, 'message' => 'This coupon is currently inactive', 'coupon' => null];
        }
        
        // 3. Check validity dates
        $now = date('Y-m-d H:i:s');
        if ($now < $coupon['start_date']) {
            return ['valid' => false, 'message' => 'This coupon is not yet active', 'coupon' => null];
        }
        if ($now > $coupon['end_date']) {
            return ['valid' => false, 'message' => 'This coupon has expired', 'coupon' => null];
        }
        
        // 4. Check minimum purchase requirement
        if ($coupon['min_purchase'] && $cartTotal < $coupon['min_purchase']) {
            return [
                'valid' => false, 
                'message' => 'This coupon is only valid for orders above ₹' . number_format($coupon['min_purchase'], 2),
                'coupon' => null
            ];
        }
        
        // 5. Check offer type specific conditions
        switch ($coupon['offer_type']) {
            case 'first_user':
                if (!isFirstTimeUser($userId, $pdo)) {
                    return [
                        'valid' => false, 
                        'message' => 'This coupon is only for first-time users', 
                        'coupon' => null,
                        'reason_code' => 'ALREADY_USED'
                    ];
                }
                break;
                
            case 'product_specific':
                // Check if cart contains eligible products
                $stmt = $pdo->prepare("SELECT product_id FROM coupon_products WHERE coupon_id = :coupon_id");
                $stmt->execute([':coupon_id' => $coupon['id']]);
                $eligibleProducts = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $hasEligibleProduct = false;
                foreach ($cartItems as $item) {
                    if (in_array($item['product_id'], $eligibleProducts)) {
                        $hasEligibleProduct = true;
                        break;
                    }
                }
                
                if (!$hasEligibleProduct) {
                    return ['valid' => false, 'message' => 'This coupon does not apply to the products in your cart', 'coupon' => null];
                }
                break;
                
            case 'category_specific':
                // Check if cart contains products from eligible categories
                $stmt = $pdo->prepare("SELECT category_id FROM coupon_categories WHERE coupon_id = :coupon_id");
                $stmt->execute([':coupon_id' => $coupon['id']]);
                $eligibleCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $hasEligibleCategory = false;
                foreach ($cartItems as $item) {
                    if (in_array($item['category_id'], $eligibleCategories)) {
                        $hasEligibleCategory = true;
                        break;
                    }
                }
                
                if (!$hasEligibleCategory) {
                    return ['valid' => false, 'message' => 'This coupon does not apply to the products in your cart', 'coupon' => null];
                }
                break;
        }
        
        // 6. Check usage limit per user
        if ($coupon['usage_limit_per_user'] === 'once') {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as usage_count 
                FROM coupon_usage 
                WHERE coupon_id = :coupon_id AND user_id = :user_id
            ");
            $stmt->execute([':coupon_id' => $coupon['id'], ':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['usage_count'] > 0) {
                return [
                    'valid' => false, 
                    'message' => 'You have already used this coupon', 
                    'coupon' => null,
                    'reason_code' => 'ALREADY_USED'
                ];
            }
        }
        
        // 7. Check clubbing rules (if another coupon is already applied)
        if (isset($_SESSION['applied_coupon'])) {
            // If checking the same coupon that is already applied, skip clubbing check
            if (strtoupper($_SESSION['applied_coupon']['code']) !== strtoupper($code)) {
                if (!$coupon['can_be_clubbed']) {
                    return ['valid' => false, 'message' => 'This coupon cannot be combined with other offers', 'coupon' => null];
                }
            }
        }
        
        // All validations passed
        return ['valid' => true, 'message' => 'Coupon applied successfully!', 'coupon' => $coupon];
    }
}

if (!function_exists('calculateDiscount')) {
    /**
     * Calculate discount amount based on coupon type
     */
    function calculateDiscount($coupon, $cartTotal) {
        if ($coupon['discount_type'] === 'percentage') {
            $discount = ($cartTotal * $coupon['discount_value']) / 100;
            
            // Apply max discount limit if set
            if ($coupon['max_discount_limit'] && $discount > $coupon['max_discount_limit']) {
                $discount = $coupon['max_discount_limit'];
            }
        } else {
            // Flat discount
            $discount = $coupon['discount_value'];
            
            // Discount cannot exceed cart total
            if ($discount > $cartTotal) {
                $discount = $cartTotal;
            }
        }
        
        return round($discount, 2);
    }
}

if (!function_exists('getEligibleCoupons')) {
    /**
     * Get list of coupons that user can potentially use
     */
    function getEligibleCoupons($userId, $cartTotal, $cartItems, $pdo) {
        $now = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            SELECT * FROM coupons 
            WHERE status = 'active'
            ORDER BY discount_value DESC
        ");
        $stmt->execute();
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $eligible = [];
        foreach ($coupons as $coupon) {
            $validation = validateCoupon($coupon['code'], $userId, $cartTotal, $cartItems, $pdo);
            if ($validation['valid']) {
                $eligible[] = $coupon;
            }
        }
        
        return $eligible;
    }
}

if (!function_exists('applyCouponToSession')) {
    /**
     * Store applied coupon in session
     */
    function applyCouponToSession($couponData, $discountAmount) {
        $_SESSION['applied_coupon'] = [
            'id' => $couponData['id'],
            'code' => $couponData['code'],
            'title' => $couponData['title'],
            'discount_type' => $couponData['discount_type'],
            'discount_value' => $couponData['discount_value'],
            'discount_amount' => $discountAmount,
            'can_be_clubbed' => $couponData['can_be_clubbed']
        ];
    }
}

if (!function_exists('getAppliedCoupon')) {
    /**
     * Get applied coupon from session
     */
    function getAppliedCoupon() {
        return $_SESSION['applied_coupon'] ?? null;
    }
}

if (!function_exists('removeCouponFromSession')) {
    /**
     * Remove applied coupon from session
     */
    function removeCouponFromSession() {
        unset($_SESSION['applied_coupon']);
    }
}

if (!function_exists('recordCouponUsage')) {
    /**
     * Record coupon usage in database (called after order is placed)
     */
    function recordCouponUsage($couponId, $userId, $orderId, $discountAmount, $pdo) {
        $stmt = $pdo->prepare("
            INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount)
            VALUES (:coupon_id, :user_id, :order_id, :discount_amount)
        ");
        return $stmt->execute([
            ':coupon_id' => $couponId,
            ':user_id' => $userId,
            ':order_id' => $orderId,
            ':discount_amount' => $discountAmount
        ]);
    }
}
