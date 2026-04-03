<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

try {
    // Validate required fields
    $required = ['coupon_id', 'title', 'code', 'discount_type', 'discount_value', 'offer_type', 'usage_limit_per_user', 'start_date', 'end_date'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $couponId = intval($_POST['coupon_id']);
    $title = trim($_POST['title']);
    $code = strtoupper(trim($_POST['code']));
    $description = trim($_POST['description'] ?? '');
    $discountType = $_POST['discount_type'];
    $discountValue = floatval($_POST['discount_value']);
    $maxDiscountLimit = !empty($_POST['max_discount_limit']) ? floatval($_POST['max_discount_limit']) : null;
    $minPurchase = !empty($_POST['min_purchase']) ? floatval($_POST['min_purchase']) : null;
    $offerType = $_POST['offer_type'];
    $usageLimitPerUser = $_POST['usage_limit_per_user'];
    $canBeClubbed = isset($_POST['can_be_clubbed']) ? 1 : 0;
    $showOnMarquee = isset($_POST['show_on_marquee']) ? 1 : 0;
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $status = isset($_POST['status']) ? 'active' : 'inactive';
    
    // Validate discount value
    if ($discountValue <= 0) {
        throw new Exception('Discount value must be greater than 0');
    }
    
    if ($discountType === 'percentage' && $discountValue > 100) {
        throw new Exception('Percentage discount cannot exceed 100%');
    }
    
    // Validate dates
    if (strtotime($endDate) <= strtotime($startDate)) {
        throw new Exception('End date must be after start date');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update coupon
    $stmt = $pdo->prepare("
        UPDATE coupons SET
            title = :title,
            description = :description,
            discount_type = :discount_type,
            discount_value = :discount_value,
            max_discount_limit = :max_discount_limit,
            min_purchase = :min_purchase,
            offer_type = :offer_type,
            usage_limit_per_user = :usage_limit_per_user,
            can_be_clubbed = :can_be_clubbed,
            start_date = :start_date,
            end_date = :end_date,
            status = :status,
            show_on_marquee = :show_on_marquee
        WHERE id = :id
    ");
    
    $stmt->execute([
        ':id' => $couponId,
        ':title' => $title,
        ':description' => $description,
        ':discount_type' => $discountType,
        ':discount_value' => $discountValue,
        ':max_discount_limit' => $maxDiscountLimit,
        ':min_purchase' => $minPurchase,
        ':offer_type' => $offerType,
        ':usage_limit_per_user' => $usageLimitPerUser,
        ':can_be_clubbed' => $canBeClubbed,
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':status' => $status,
        ':show_on_marquee' => $showOnMarquee
    ]);
    
    // Delete existing product associations
    $pdo->prepare("DELETE FROM coupon_products WHERE coupon_id = :id")->execute([':id' => $couponId]);
    
    // Delete existing category associations
    $pdo->prepare("DELETE FROM coupon_categories WHERE coupon_id = :id")->execute([':id' => $couponId]);
    
    // Add new product associations
    if ($offerType === 'product_specific' && !empty($_POST['products'])) {
        $stmt = $pdo->prepare("INSERT INTO coupon_products (coupon_id, product_id) VALUES (:coupon_id, :product_id)");
        foreach ($_POST['products'] as $productId) {
            $stmt->execute([
                ':coupon_id' => $couponId,
                ':product_id' => intval($productId)
            ]);
        }
    }
    
    // Add new category associations
    if ($offerType === 'category_specific' && !empty($_POST['categories'])) {
        $stmt = $pdo->prepare("INSERT INTO coupon_categories (coupon_id, category_id) VALUES (:coupon_id, :category_id)");
        foreach ($_POST['categories'] as $categoryId) {
            $stmt->execute([
                ':coupon_id' => $couponId,
                ':category_id' => intval($categoryId)
            ]);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect to coupons page
    header('Location: coupons.php?success=updated');
    exit;
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Redirect back with error
    header('Location: coupons_edit.php?id=' . $couponId . '&error=' . urlencode($e->getMessage()));
    exit;
}
