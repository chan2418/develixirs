<?php
ob_start();
session_start();

// Auth check
if (!isset($_SESSION['admin_logged'])) {
    ob_end_clean();
    die('Unauthorized');
}

$id = (int)($_GET['id'] ?? 0);

// Get data
require __DIR__ . '/../includes/db.php';
$stmt = $pdo->prepare("SELECT s.*, o.order_number, o.customer_name FROM shipments s JOIN orders o ON s.order_id=o.id WHERE s.id = ?");
$stmt->execute([$id]);
$sh = $stmt->fetch();

if (!$sh) die('Not found');

// Get address
$stmt = $pdo->prepare("SELECT customer_address, customer_phone FROM orders WHERE id = ?");
$stmt->execute([$sh['order_id']]);
$order = $stmt->fetch() ?: [];

// Simple HTML
$html = '<html><body style="font:12px Arial;margin:20px">
<div style="border:2px solid #000;padding:20px">
<h1>SHIPPING LABEL</h1>
<p><b>Shipment:</b> '.htmlspecialchars($sh['shipment_number']).'</p>
<p><b>Order:</b> '.htmlspecialchars($sh['order_number']).'</p>
<p><b>Customer:</b> '.htmlspecialchars($sh['customer_name']).'</p>
<p>'.nl2br(htmlspecialchars($order['customer_address'] ?? 'N/A')).'</p>
<p><b>Phone:</b> '.htmlspecialchars($order['customer_phone'] ?? 'N/A').'</p>
<p><b>Carrier:</b> '.htmlspecialchars($sh['carrier'] ?? 'N/A').'</p>
<p><b>Tracking:</b> '.htmlspecialchars($sh['tracking_number'] ?? 'N/A').'</p>
<p><b>Weight:</b> '.htmlspecialchars($sh['weight'] ?? 'N/A').' kg</p>
</div></body></html>';

// Generate PDF
require __DIR__ . '/../vendor/autoload.php';
$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A6');
$dompdf->render();

// Clear everything
while(ob_get_level()) ob_end_clean();

// Send
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="label-'.$sh['shipment_number'].'.pdf"');
echo $dompdf->output();
exit;
