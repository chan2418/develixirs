<?php
// Based on working pdf_test.php - minimal additions only
require __DIR__ . '/../vendor/autoload.php';

// Manual auth check (no includes)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged'])) die('Auth failed');

$id = (int)($_GET['id'] ?? 0);

// Manual DB connection (copy from db.php but inline)
try {
    $host = 'localhost';
    $dbname = 'u295126515_chandruprasath'; // Replace with your actual DB name
    $username = 'u295126515_admin';        // Replace with your actual username  
    $password = 'Chandru@2418';            // Replace with your actual password
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('DB Error');
}

// Fetch data
$stmt = $pdo->prepare("SELECT s.*, o.order_number, o.customer_name FROM shipments s JOIN orders o ON s.order_id=o.id WHERE s.id = ?");
$stmt->execute([$id]);
$sh = $stmt->fetch();

if (!$sh) die('Not found');

// Get address
$stmt = $pdo->prepare("SELECT customer_address, customer_phone FROM orders WHERE id = ?");
$stmt->execute([$sh['order_id']]);
$order = $stmt->fetch() ?: [];

// Build HTML (same as working test style)
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

// Generate PDF (exact same as working test)
$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A6');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="label-'.$sh['shipment_number'].'.pdf"');
echo $dompdf->output();
