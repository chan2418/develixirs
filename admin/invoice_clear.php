<?php
// admin/invoice_clear.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: orders.php');
    exit;
}

try {
    // fetch invoice
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$inv) {
        $_SESSION['flash_error'] = 'Invoice not found';
        header('Location: orders.php'); exit;
    }

    // mark invoice cleared
    $pdo->beginTransaction();
    $u = $pdo->prepare("UPDATE invoices SET status = 'cleared', cleared_at = NOW() WHERE id = ?");
    $u->execute([$id]);

    // also update order payment_status to 'paid' (adjust to your scheme)
    $ou = $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'completed' WHERE id = ?");
    $ou->execute([$inv['order_id']]);

    $pdo->commit();
    $_SESSION['flash_success'] = 'Invoice marked as cleared and order payment updated.';
    header('Location: invoice_cut.php?order_id=' . (int)$inv['order_id']);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_error'] = 'Failed to clear invoice: ' . $e->getMessage();
    header('Location: invoice_cut.php?order_id=' . (int)$inv['order_id']);
    exit;
}