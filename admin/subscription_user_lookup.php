<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/subscription_plan_helper.php';

header('Content-Type: application/json');

try {
    ensure_subscription_schema($pdo);

    $query = trim((string)($_GET['q'] ?? ''));
    if ($query === '') {
        echo json_encode(['success' => true, 'items' => []]);
        exit;
    }

    $isNumeric = ctype_digit($query);
    if (!$isNumeric && strlen($query) < 2) {
        echo json_encode(['success' => true, 'items' => []]);
        exit;
    }

    $like = '%' . $query . '%';
    $exactId = $isNumeric ? (int)$query : 0;

    $stmt = $pdo->prepare(" 
        SELECT
            id,
            name,
            email,
            phone,
            COALESCE(is_subscriber, 0) AS is_subscriber,
            subscription_expires_at
        FROM users
        WHERE id = ?
           OR name LIKE ?
           OR email LIKE ?
           OR phone LIKE ?
        ORDER BY
            CASE WHEN id = ? THEN 0 ELSE 1 END,
            CASE WHEN email = ? THEN 0 ELSE 1 END,
            CASE WHEN phone = ? THEN 0 ELSE 1 END,
            name ASC,
            id ASC
        LIMIT 8
    ");
    $stmt->execute([
        $exactId,
        $like,
        $like,
        $like,
        $exactId,
        $query,
        $query,
    ]);

    $items = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $items[] = [
            'id' => (int)$row['id'],
            'name' => (string)($row['name'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'phone' => (string)($row['phone'] ?? ''),
            'is_subscriber' => !empty($row['is_subscriber']),
            'subscription_expires_at' => $row['subscription_expires_at'] ?? null,
        ];
    }

    echo json_encode([
        'success' => true,
        'items' => $items,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
