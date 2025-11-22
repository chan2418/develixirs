<?php
// admin/generate_shipment_pdf.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: shipments.php'); exit; }

// fetch shipment etc...
$stmt = $pdo->prepare("SELECT s.*, o.order_number, o.customer_name FROM shipments s JOIN orders o ON s.order_id=o.id WHERE s.id = ? LIMIT 1");
$stmt->execute([$id]);
$sh = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sh) { echo 'Shipment not found'; exit; }

// build HTML for label (simple)
$html = "<h1>Shipping Label: " . htmlspecialchars($sh['shipment_number']) . "</h1>";
$html .= "<p>To: " . htmlspecialchars($sh['customer_name']) . "</p>";
$html .= "<p>Order: " . htmlspecialchars($sh['order_number']) . "</p>";
$html .= "<p>Carrier: " . htmlspecialchars($sh['carrier']) . " — Tracking: " . htmlspecialchars($sh['tracking_number']) . "</p>";

// use DOMPDF to render
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A6','portrait');
$dompdf->render();
$pdf = $dompdf->output();

// save file and update shipments.label_file
$filename = 'label_shipment_' . $id . '_' . time() . '.pdf';
$path = __DIR__ . '/../uploads/invoices/' . $filename;
file_put_contents($path, $pdf);

$stmt = $pdo->prepare("UPDATE shipments SET label_file=?, status='label_created' WHERE id=?");
$stmt->execute([$filename, $id]);

// return the PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$filename.'"');
echo $pdf;
exit;