<?php
session_start();
require_once __DIR__ . '/includes/coupon_helpers.php';

header('Content-Type: application/json');

try {
    removeCouponFromSession();
    
    echo json_encode([
        'success' => true,
        'message' => 'Coupon removed successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
