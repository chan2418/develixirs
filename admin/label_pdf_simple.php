<?php
// admin/label_pdf_simple.php - Ultra-simple PDF generator without database dependencies
ob_start();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check auth
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    ob_end_clean();
    die('Unauthorized');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load database
require_once __DIR__ . '/../includes/db.php';

// Fetch shipment data using exact same query as shipment_view.php
try {
    $stmt = $pdo->prepare("SELECT s.*, o.order_number, o.customer_name FROM shipments s JOIN orders o ON s.order_id=o.id WHERE s.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $sh = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    die('Database error: ' . $e->getMessage());
}

if (!$sh) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    die('Shipment not found');
}

// Now fetch order details for full address
try {
    $stmt = $pdo->prepare("SELECT customer_address, customer_phone, customer_email FROM orders WHERE id = ?");
    $stmt->execute([$sh['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $order = [];
}

// Extract data
$shipmentNumber = $sh['shipment_number'];
$orderNumber = $sh['order_number'];
$customerName = $sh['customer_name'];
$customerAddress = $order['customer_address'] ?? 'N/A';
$customerPhone = $order['customer_phone'] ?? 'N/A';
$carrier = $sh['carrier'] ?? 'N/A';
$tracking = $sh['tracking_number'] ?? 'N/A';
$weight = $sh['weight'] ?? 'N/A';

// Build HTML
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { border: 2px solid #000; padding: 20px; }
        .title { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .section { margin: 15px 0; }
        .label { font-size: 11px; color: #666; text-transform: uppercase; }
        .value { font-size: 14px; font-weight: bold; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">SHIPPING LABEL</div>
        <div class="section">
            <div class="label">Shipment</div>
            <div class="value">' . htmlspecialchars($shipmentNumber) . '</div>
        </div>
        <div class="section">
            <div class="label">Order</div>
            <div class="value">' . htmlspecialchars($orderNumber) . '</div>
        </div>
        <div class="section">
            <div class="label">Ship To</div>
            <div class="value">' . htmlspecialchars($customerName) . '</div>
            <div>' . nl2br(htmlspecialchars($customerAddress)) . '</div>
            <div>' . htmlspecialchars($customerPhone) . '</div>
        </div>
        <div class="section">
            <div class="label">Carrier</div>
            <div class="value">' . htmlspecialchars($carrier) . '</div>
        </div>
        <div class="section">
            <div class="label">Tracking</div>
            <div class="value">' . htmlspecialchars($tracking) . '</div>
        </div>
        <div class="section">
            <div class="label">Weight</div>
            <div>' . htmlspecialchars($weight) . ' kg</div>
        </div>
    </div>
</body>
</html>';

// Load Dompdf
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A6', 'portrait');
    $dompdf->render();
    
    // Clean all buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="shipping-label-' . $id . '.pdf"');
    header('Cache-Control: private');
    header('Pragma: private');
    
    echo $dompdf->output();
    exit;
    
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    die('PDF Error: ' . $e->getMessage());
}
