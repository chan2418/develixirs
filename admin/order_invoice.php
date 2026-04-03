<?php
// admin/order_invoice.php
// Redirects to existing invoice PDF or generates a new one if missing.

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_number_helper.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

// Check if invoice exists
$stmt = $pdo->prepare("SELECT id FROM invoices WHERE order_id = ?");
$stmt->execute([$orderId]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if ($invoice) {
    // Invoice exists, redirect to PDF generator
    header('Location: generate_invoice_pdf.php?id=' . $invoice['id']);
    exit;
}

// Invoice doesn't exist, create it automatically
try {
    // Fetch order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Order not found.");
    }

    // Generate Invoice Number with shared format
    $invoiceNum = build_invoice_number($pdo, (int)$orderId, $order['created_at'] ?? null);

    // Insert Invoice
    $stmt = $pdo->prepare("
        INSERT INTO invoices (
            order_id, invoice_number, amount, tax_amount, status, created_at
        ) VALUES (
            ?, ?, ?, ?, 'issued', NOW()
        )
    ");
    $stmt->execute([
        $orderId,
        $invoiceNum,
        $order['total_amount'],
        $order['tax_amount'] ?? 0
    ]);
    $invoiceId = $pdo->lastInsertId();

    // Copy items
    $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmtItems->execute([$orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    $stmtInsItem = $pdo->prepare("
        INSERT INTO invoice_items (
            invoice_id, description, qty, unit_price, amount
        ) VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $desc = $item['product_name'];
        if (!empty($item['variant'])) $desc .= ' (' . $item['variant'] . ')';
        
        $lineTotal = $item['price'] * $item['qty'];
        
        $stmtInsItem->execute([
            $invoiceId,
            $desc,
            $item['qty'],
            $item['price'],
            $lineTotal
        ]);
    }

    // Redirect to PDF generator
    header('Location: generate_invoice_pdf.php?id=' . $invoiceId);
    exit;

} catch (Exception $e) {
    die("Error generating invoice: " . $e->getMessage());
}
?>
