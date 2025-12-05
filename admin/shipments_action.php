<?php
// admin/shipments_action.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_POST['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$id && $action !== 'create') { header('Location: shipments.php'); exit; }

try {
    if ($action === 'create') {
        $order_id = (int)($_POST['order_id'] ?? 0);
        $carrier = $_POST['carrier'] ?? null;
        $tracking = $_POST['tracking_number'] ?? null;
        $method = $_POST['shipping_method'] ?? null;
        $cost = (float)($_POST['shipping_cost'] ?? 0);
        $weight = $_POST['weight'] ?? null;
        $notes = $_POST['notes'] ?? null;
        $mark_shipped = !empty($_POST['mark_order_shipped']);

        if (!$order_id) {
            header('Location: shipments_create.php?error=missing_order');
            exit;
        }

        // Generate shipment number
        $shipment_number = 'SHP-' . strtoupper(uniqid());

        // Handle file upload
        $fileName = null;
        if (!empty($_FILES['label_pdf']['tmp_name'])) {
            $up = $_FILES['label_pdf'];
            if ($up['error'] === UPLOAD_ERR_OK && strtolower(pathinfo($up['name'], PATHINFO_EXTENSION)) === 'pdf') {
                $targetDir = __DIR__ . '/../uploads/labels/'; // Ensure this matches your view logic
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                $fileName = uniqid('label_') . '_' . basename($up['name']);
                $dest = $targetDir . $fileName;
                move_uploaded_file($up['tmp_name'], $dest);
            }
        }

        // Insert shipment
        $stmt = $pdo->prepare("INSERT INTO shipments (order_id, shipment_number, carrier, tracking_number, shipping_method, shipping_cost, weight, notes, label_file, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'label_created', NOW())");
        $stmt->execute([$order_id, $shipment_number, $carrier, $tracking, $method, $cost, $weight, $notes, $fileName]);
        $shipment_id = $pdo->lastInsertId();

        // Update order status if requested
        if ($mark_shipped) {
            $stmt = $pdo->prepare("UPDATE orders SET order_status = 'shipped' WHERE id = ?");
            $stmt->execute([$order_id]);
        }

        header("Location: shipment_view.php?id={$shipment_id}");
        exit;

    } elseif ($action === 'mark_shipped' || $action === 'mark_shipped_alt') {
        $stmt = $pdo->prepare("UPDATE shipments SET status='in_transit', shipped_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: shipment_view.php?id={$id}");
        exit;
    } elseif ($action === 'update') {
        $carrier = $_POST['carrier'] ?? null;
        $tracking = $_POST['tracking_number'] ?? null;
        $method = $_POST['shipping_method'] ?? null;
        $cost = (float)($_POST['shipping_cost'] ?? 0);

        // handle file upload for label_pdf
        if (!empty($_FILES['label_pdf']['tmp_name'])) {
            $up = $_FILES['label_pdf'];
            if ($up['error'] === UPLOAD_ERR_OK && strtolower(pathinfo($up['name'], PATHINFO_EXTENSION)) === 'pdf') {
                $targetDir = __DIR__ . '/../uploads/labels/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                $fileName = uniqid('label_') . '_' . basename($up['name']);
                $dest = $targetDir . $fileName;
                if (move_uploaded_file($up['tmp_name'], $dest)) {
                    // save filename in DB
                    $stmt = $pdo->prepare("UPDATE shipments SET carrier=?, tracking_number=?, shipping_method=?, shipping_cost=?, label_file=? WHERE id=?");
                    $stmt->execute([$carrier, $tracking, $method, $cost, $fileName, $id]);
                }
            }
        } else {
            $stmt = $pdo->prepare("UPDATE shipments SET carrier=?, tracking_number=?, shipping_method=?, shipping_cost=? WHERE id=?");
            $stmt->execute([$carrier, $tracking, $method, $cost, $id]);
        }
        header("Location: shipment_view.php?id={$id}");
        exit;
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM shipments WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: shipments.php');
        exit;
    } else {
        header("Location: shipment_view.php?id={$id}");
        exit;
    }
} catch (Exception $e) {
    error_log('Shipments action error: '.$e->getMessage());
    header("Location: shipment_view.php?id={$id}&error=1");
    exit;
}