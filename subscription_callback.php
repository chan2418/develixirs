<?php
// subscription_callback.php - Payment Verification & Subscription Activation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/razorpay_config.php';
require_once __DIR__ . '/includes/RazorpayClient.php';
require_once __DIR__ . '/includes/subscription_lifecycle_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$paymentId = trim((string)($input['razorpay_payment_id'] ?? ''));
$orderId = trim((string)($input['razorpay_order_id'] ?? ''));
$signature = trim((string)($input['razorpay_signature'] ?? ''));
$userId = (int)$_SESSION['user_id'];

if ($paymentId === '' || $orderId === '' || $signature === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
    exit;
}

try {
    $api = new RazorpayClient(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    $attributes = [
        'razorpay_order_id' => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature' => $signature,
    ];

    if (!$api->verifyPaymentSignature($attributes)) {
        throw new Exception('Payment signature verification failed');
    }

    $pdo->beginTransaction();
    $result = subscription_activate_paid_subscription($pdo, $userId, $orderId);
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => ($result['activation_type'] ?? 'instant') === 'renewal'
            ? 'Subscription renewal scheduled successfully'
            : 'Subscription activated successfully',
        'subscription_id' => $result['subscription_id'] ?? null,
        'activation_type' => $result['activation_type'] ?? 'instant',
        'effective_start_date' => $result['effective_start_date'] ?? null,
        'effective_end_date' => $result['effective_end_date'] ?? null,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Subscription callback error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
