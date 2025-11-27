<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my-profile.php#section-addresses");
    exit;
}

$userId    = $_SESSION['user_id'];
$addressId = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;

if ($addressId <= 0) {
    header("Location: my-profile.php#section-addresses");
    exit;
}

try {
    // Only delete address that belongs to this logged-in user
    $stmt = $pdo->prepare("
        DELETE FROM user_addresses
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$addressId, $userId]);

} catch (Exception $e) {
    error_log('Delete address error: ' . $e->getMessage());
}

// Always go back to profile addresses section
header("Location: my-profile.php#section-addresses");
exit;
