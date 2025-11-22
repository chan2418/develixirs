<?php
// admin/notifications_api.php
// Simple notifications API for admin panel
// GET  => returns { ok:true, unread: N, rows: [...] }
// POST => mark_read (id) or mark_all

// include auth and DB. _auth.php should start session and set admin session keys.
// If you don't want to require auth, replace `_auth.php` with `session_start()` and custom checks.
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure session is started (in case _auth.php doesn't)
if (session_status() === PHP_SESSION_NONE) session_start();

// Simple auth: require admin session (adjust key to your app)
if (empty($_SESSION['admin_id']) && empty($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthenticated']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        // list recent notifications (50)
        $stmt = $pdo->prepare("SELECT id, title, message, url, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 50");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $unread = 0;
        foreach ($rows as $r) {
            if ((int)($r['is_read'] ?? 0) === 0) $unread++;
        }

        echo json_encode(['ok' => true, 'unread' => $unread, 'rows' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        // Accept application/x-www-form-urlencoded or form-data
        $action = $_POST['action'] ?? '';

        if ($action === 'mark_read') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                echo json_encode(['ok' => false, 'error' => 'missing id']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'mark_all') {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
            $stmt->execute();
            echo json_encode(['ok' => true]);
            exit;
        }

        // allow creating a notification via POST for testing if desired (optional)
        if ($action === 'create' && !empty($_POST['title']) && !empty($_POST['message'])) {
            $title = $_POST['title'];
            $message = $_POST['message'];
            $url = $_POST['url'] ?? null;
            $stmt = $pdo->prepare("INSERT INTO notifications (title, message, url, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
            $stmt->execute([$title, $message, $url]);
            echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            exit;
        }

        echo json_encode(['ok' => false, 'error' => 'unknown action']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method not allowed']);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server error', 'details' => $e->getMessage()]);
    exit;
}