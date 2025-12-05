<?php
// admin/generate_label_pdf.php
// Generate shipping label PDF (uses dompdf).
//
// Usage: /admin/generate_label_pdf.php?id=13

ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }

// get id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo "Invalid shipment id";
    exit;
}

// fetch shipment + order
try {
    $stmt = $pdo->prepare("
      SELECT s.*, o.order_number, o.customer_name, o.customer_address, o.customer_phone, o.customer_email
      FROM shipments s
      LEFT JOIN orders o ON s.order_id = o.id
      WHERE s.id = ? LIMIT 1
    ");
    $stmt->execute([$id]);
    $sh = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Label fetch error: " . $e->getMessage());
    $sh = false;
}

if (!$sh) {
    http_response_code(404);
    echo "<h2>Shipment not found</h2>";
    exit;
}

// fetch items
$items = [];
try {
    $it = $pdo->prepare("SELECT product_name, sku, qty, weight FROM shipment_items WHERE shipment_id = ? ORDER BY id ASC");
    $it->execute([$id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $items = [];
}

// Prepare Logo
$logoPath = __DIR__ . '/../develixir-logo.png';
$logoData = '';
if (file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents($logoPath);
    $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// Build HTML
$html = '<!doctype html><html><head><meta charset="utf-8"><title>Label '.h($sh['shipment_number']).'</title>
<style>
@page { margin: 0px; }
body { font-family: "DejaVu Sans", sans-serif; margin: 0; padding: 20px; color: #333; font-size: 14px; }
.container { border: 2px solid #000; padding: 20px; max-width: 600px; margin: 0 auto; }
.header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
.logo { max-width: 150px; max-height: 50px; }
.title { font-size: 24px; font-weight: bold; text-transform: uppercase; }
.grid { display: table; width: 100%; margin-bottom: 20px; }
.col { display: table-cell; width: 50%; vertical-align: top; padding: 10px; border: 1px solid #ccc; }
.label { font-size: 10px; text-transform: uppercase; color: #666; margin-bottom: 5px; }
.value { font-size: 14px; font-weight: bold; }
.address { white-space: pre-line; }
.barcode { text-align: center; margin-top: 20px; border-top: 2px dashed #000; padding-top: 20px; }
.footer { font-size: 10px; text-align: center; margin-top: 20px; color: #666; }
</style>
</head><body>
<div class="container">
    <table style="width:100%; border-bottom: 2px solid #000; margin-bottom: 20px;">
        <tr>
            <td style="width: 50%;">';
            if ($logoData) {
                $html .= '<img src="'.$logoData.'" class="logo">';
            } else {
                $html .= '<h2>DEVELIXIR</h2>';
            }
$html .= '</td>
            <td style="width: 50%; text-align: right;">
                <div class="title">PRIORITY MAIL</div>
                <div>Shipment #: '.h($sh['shipment_number']).'</div>
                <div>Date: '.date('d M Y').'</div>
            </td>
        </tr>
    </table>

    <table style="width:100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding: 10px; border: 1px solid #000;">
                <div class="label">SHIP FROM:</div>
                <div class="value">DEVELIXIR</div>
                <div class="address">No:6, 3rd Cross Street
Kamatchiamman Garden, Sethukkarai
Gudiyatham-632602, Vellore
Tamil Nadu, INDIA
Ph: 9500650454</div>
            </td>
            <td style="width: 50%; vertical-align: top; padding: 10px; border: 1px solid #000;">
                <div class="label">SHIP TO:</div>
                <div class="value">'.h($sh['recipient_name'] ?? $sh['customer_name']).'</div>
                <div class="address">'.nl2br(h($sh['recipient_address'] ?? $sh['customer_address'])).'</div>
                <div style="margin-top:5px">Ph: '.h($sh['recipient_phone'] ?? $sh['customer_phone']).'</div>
            </td>
        </tr>
    </table>

    <table style="width:100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 33%; padding: 10px; border: 1px solid #ccc;">
                <div class="label">CARRIER</div>
                <div class="value">'.h($sh['carrier'] ?? 'Standard').'</div>
            </td>
            <td style="width: 33%; padding: 10px; border: 1px solid #ccc;">
                <div class="label">TRACKING #</div>
                <div class="value">'.h($sh['tracking_number'] ?? 'N/A').'</div>
            </td>
            <td style="width: 33%; padding: 10px; border: 1px solid #ccc;">
                <div class="label">WEIGHT</div>
                <div class="value">'.h($sh['weight'] ?? '-').' kg</div>
            </td>
        </tr>
    </table>

    <div class="barcode">
        <div style="font-size: 12px; margin-bottom: 5px;">INTERNAL USE ONLY</div>
        <div style="font-size: 18px; font-weight: bold; letter-spacing: 2px;">'.h($sh['shipment_number']).'</div>
        <div style="margin-top: 5px;">Order #: '.h($sh['order_number']).'</div>
    </div>

    <div class="footer">
        Generated by Develixir Admin Panel
    </div>
</div>
</body></html>';

// Render PDF
if (class_exists('Dompdf\\Dompdf') || class_exists('Dompdf')) {
    try {
        if (!class_exists('Dompdf\\Dompdf') && class_exists('Dompdf')) {
            $dompdf = new Dompdf();
        } else {
            $dompdf = new \Dompdf\Dompdf();
        }
        $dompdf->setPaper('A6', 'portrait'); // Standard label size
        $dompdf->loadHtml($html);
        $dompdf->render();
        $filename = 'label-'.$sh['shipment_number'].'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.basename($filename).'"');
        echo $dompdf->output();
        exit;
    } catch (Exception $e) {
        error_log('DOMPDF render error: ' . $e->getMessage());
    }
}

// Fallback
http_response_code(500);
echo "<h2>PDF generation failed</h2><p>Could not generate PDF.</p>";
exit;
?>
