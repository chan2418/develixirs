<?php
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to use wishlist']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'toggle') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit;
    }

    try {
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            // Remove
            $del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = :uid AND product_id = :pid");
            $del->execute([':uid' => $userId, ':pid' => $productId]);
            $status = 'removed';
        } else {
            // Add
            $ins = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (:uid, :pid)");
            $ins->execute([':uid' => $userId, ':pid' => $productId]);
            $status = 'added';
        }

        // Get new count
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = :uid");
        $cnt->execute([':uid' => $userId]);
        $count = $cnt->fetchColumn();

        echo json_encode(['success' => true, 'status' => $status, 'wishlist_count' => $count]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($action === 'get_all') {
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['success' => true, 'ids' => $ids]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'ids' => []]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
