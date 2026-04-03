<?php
// admin/label_pdf.php
// EXACT COPY of working invoice structure
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function strip_label_gst_text($text){
    $text = (string)$text;
    if ($text === '') {
        return '';
    }

    $lines = preg_split('/\R/', $text);
    $clean = [];
    foreach ($lines as $line) {
        if (preg_match('/\bGST(?:IN|TIN)?(?:\/UIN)?\b/i', $line)) {
            continue;
        }
        $clean[] = $line;
    }

    $text = implode("\n", $clean);
    $text = preg_replace('/(?:^|[\s,])GST(?:IN|TIN)?(?:\/UIN)?\s*[:\-]?\s*[0-9A-Z]{15}\b/i', '', $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);

    return trim($text);
}

// get id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo "Missing shipment id";
    exit;
}

// fetch shipment + order
try {
    $stmt = $pdo->prepare("SELECT s.*, o.order_number, o.customer_name FROM shipments s JOIN orders o ON s.order_id=o.id WHERE s.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $sh = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Shipment fetch error: ' . $e->getMessage());
    $sh = false;
}

if (!$sh) {
    http_response_code(404);
    echo "<h2>Shipment not found</h2>";
    exit;
}

// fetch order details - get ALL columns
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$sh['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) $order = [];
} catch (Exception $e) {
    $order = [];
}

// Fetch Company Settings
// Fetch Company Settings
// Fetch Company Settings
$settings = [];
try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) { }

$companyName = !empty($settings['company_name']) ? $settings['company_name'] : 'DEVELIXIR';
$companyAddress = !empty($settings['company_address']) ? $settings['company_address'] : '';
$companyPhone = !empty($settings['company_phone']) ? $settings['company_phone'] : '';
if ($companyAddress !== '') {
    $companyAddress = strip_label_gst_text($companyAddress);
}

// Admin Phone
$adminPhone = $companyPhone;
if (empty($adminPhone)) $adminPhone = '+91 9999999999';

// Fetch Admin Phone from Settings (This block is now redundant but kept for minimal diff if needed, otherwise removing it is better)
// We already fetched company_phone above. Let's start using $companyPhone variable.
$adminPhone = $companyPhone;

// Fetch Admin Phone from Settings
$adminPhone = '';
try {
    $stmtSettings = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'company_phone'");
    $stmtSettings->execute();
    $adminPhone = $stmtSettings->fetchColumn();
} catch (Exception $e) { $adminPhone = ''; }

// Resolve Address & Phone (Robust Logic)
$addressString = $order['shipping_address'] ?? $order['customer_address'] ?? $order['address'] ?? 'N/A';
$customerPhone = $order['customer_phone'] ?? $order['phone'] ?? '';

// 1. Try to decode if JSON
if (strpos($addressString, '{') === 0) {
    $decoded = @json_decode($addressString, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $parts = [];
        if (!empty($decoded['address'])) $parts[] = $decoded['address'];
        $cityState = [];
        if (!empty($decoded['city'])) $cityState[] = $decoded['city'];
        if (!empty($decoded['state'])) $cityState[] = $decoded['state'];
        if (!empty($decoded['postal'])) $cityState[] = $decoded['postal'];
        if (!empty($cityState)) $parts[] = implode(', ', $cityState);
        $addressString = implode("\n", $parts);
        
        if (empty($customerPhone) && !empty($decoded['phone'])) {
            $customerPhone = $decoded['phone'];
        }
    }
}

// 2. Fallback to user profile if address invalid ("Not provided")
$cleanAddr = trim(strip_tags($addressString));
if (empty($cleanAddr) || stripos($cleanAddr, 'Not provided') !== false || strlen($cleanAddr) < 5) {
    if (!empty($order['user_id'])) {
        try {
            $stmtAddr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1");
            $stmtAddr->execute([$order['user_id']]);
            $uAddr = $stmtAddr->fetch(PDO::FETCH_ASSOC);
            
            if ($uAddr) {
                $parts = [];
                if (!empty($uAddr['address_line1'])) $parts[] = $uAddr['address_line1'];
                if (!empty($uAddr['address_line2'])) $parts[] = $uAddr['address_line2'];
                $cityState = [];
                if (!empty($uAddr['city'])) $cityState[] = $uAddr['city'];
                if (!empty($uAddr['state'])) $cityState[] = $uAddr['state'];
                if (!empty($uAddr['pincode'])) $cityState[] = $uAddr['pincode'];
                
                if (!empty($cityState)) $parts[] = implode(', ', $cityState);
                $addressString = implode("\n", $parts);
                
                if (empty($customerPhone) && !empty($uAddr['phone'])) {
                    $customerPhone = $uAddr['phone'];
                }
            }
        } catch (Exception $e) {}
    }
}

// 3. Last resort for phone
if (empty($customerPhone) && !empty($order['user_id'])) {
    try {
        $stmtPhone = $pdo->prepare("SELECT phone FROM user_addresses WHERE user_id = ? AND phone != '' AND phone IS NOT NULL ORDER BY is_default DESC LIMIT 1");
        $stmtPhone->execute([$order['user_id']]);
        $foundPhone = $stmtPhone->fetchColumn();
        if ($foundPhone) $customerPhone = $foundPhone;
    } catch (Exception $e) {}
}

$addressString = strip_label_gst_text($addressString);

// Calculate COD amount (try multiple approaches)
$codAmount = 0;
if (isset($order['payment_method']) && (strtolower($order['payment_method']) === 'cod' || strtolower($order['payment_method']) === 'cash on delivery')) {
    $codAmount = $order['total_amount'] ?? $order['total'] ?? $order['grand_total'] ?? 0;
} elseif (isset($sh['cod_amount']) && $sh['cod_amount'] > 0) {
    $codAmount = $sh['cod_amount'];
} else {
    // If payment is COD but no column found, just use total
    $codAmount = $order['total_amount'] ?? $order['total'] ?? $order['grand_total'] ?? 0;
}

// Prepare Logo (Base64 for DOMPDF)
$logoPath = __DIR__ . '/../develixir-logo.png';
$logoData = '';
if (file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents($logoPath);
    $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// Get order items if available
$items = [];
try {
    $stmt = $pdo->prepare("SELECT product_name as description, qty, price FROM order_items WHERE order_id = ?");
    $stmt->execute([$sh['order_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $items = [];
}

// Build HTML with professional courier label design (no QR, single page)
$html = '<!doctype html><html><head><meta charset="utf-8"><title>Label '.h($sh['shipment_number']).'</title>
<style>
@page { margin: 0; size: A6 portrait; }
body { font-family: Arial, sans-serif; margin: 0; padding: 8px; font-size: 9px; line-height: 1.2; }
.label { border: 2px dashed #000; padding: 6px; }
.row { border-bottom: 1px dashed #000; padding: 4px 0; margin: 2px 0; }
.row-flex { display: flex; justify-content: space-between; }
.section-title { font-weight: bold; font-size: 8px; margin-bottom: 2px; text-transform: uppercase; }
.value { font-size: 9px; line-height: 1.3; }
.large-text { font-size: 11px; font-weight: bold; }
.barcode-placeholder { width: 100%; height: 35px; border: 1px solid #000; margin: 3px 0; text-align: center; line-height: 35px; font-family: "Courier New", monospace; font-weight: bold; font-size: 8px; }
table { width: 100%; border-collapse: collapse; margin: 3px 0; }
table td, table th { border: 1px solid #000; padding: 2px; font-size: 8px; }
table th { background: #f0f0f0; font-weight: bold; }
.footer { text-align: center; margin-top: 3px; font-size: 8px; }
.logo { max-height: 30px; max-width: 120px; }
.black-bar { background:#000; color:#fff; font-weight:bold; padding:4px; }
</style>
</head><body>
<div class="label">

<!-- COD Amount + Logo -->
<div class="row row-flex">
  <div>
    <div class="section-title">COD Collect Amount: Rs. '.number_format($codAmount, 2).'</div>
  </div>
  <div>';
if ($logoData) {
    $html .= '<img src="'.$logoData.'" class="logo" />';
} else {
    $html .= '<div style="font-weight:bold;font-size:12px;">DEVELIXIR</div>';
}
$html .= '</div>
</div>

<!-- Delivery Address -->
<div class="row">
  <div class="section-title">Delivery Address:</div>
  <div class="value">'.h($sh['customer_name']).'<br>'.nl2br(h($addressString)).'<br>Phone: '.h($customerPhone).'</div>
</div>

<!-- Courier Info -->
<div class="row row-flex">
  <div>
    <div class="section-title">Courier:</div>
    <div class="value">'.h($sh['carrier'] ?? 'Standard').'</div>
  </div>
  <div>
    <div class="section-title">CPD:</div>
    <div class="value">'.date('d-m', strtotime($sh['shipment_date'] ?? 'now')).'</div>
  </div>
  <div>
    <div class="section-title">Weight:</div>
    <div class="value">'.h($sh['weight'] ?? 'N/A').' kg</div>
  </div>
</div>

<!-- Sold By -->
<!-- Sold By -->
<div class="row" style="border-bottom: 2px solid #000;">
  <div class="section-title">Sold By:</div>
  <div class="value"><span style="font-weight:bold; font-size:10px;">'.h($companyName).'</span>, '.nl2br(h($companyAddress)).'</div>
  <div class="value">Ph: '.h($adminPhone).'</div>
</div>

<!-- Products Table -->
<table>
  <tr>
    <th style="width:75%">Product</th>
    <th style="width:25%">Qty</th>
  </tr>';

if (!empty($items)) {
    $totalQty = 0;
    foreach ($items as $item) {
        $totalQty += (int)$item['qty'];
        $html .= '<tr>
          <td>'.h($item['description']).'</td>
          <td style="text-align:center;">'.h($item['qty']).'</td>
        </tr>';
    }
    $html .= '<tr>
        <td><strong>Total</strong></td>
        <td style="text-align:center;"><strong>'.$totalQty.'</strong></td>
      </tr>';
} else {
    $html .= '<tr>
      <td>Order #'.h($sh['order_number']).'</td>
      <td style="text-align:center;">1</td>
    </tr>
    <tr>
      <td><strong>Total</strong></td>
      <td style="text-align:center;"><strong>1</strong></td>
    </tr>';
}

$html .= '</table>

<!-- Handover Section -->
<div class="black-bar">
  Handover to '.h($sh['carrier'] ?? 'Courier').' - STD
</div>

<!-- Tracking ID -->
<div class="row">
  <div class="section-title">Tracking ID:</div>
  <div class="large-text">'.h($sh['tracking_number'] ?? 'N/A').'</div>
</div>

<!-- Barcode -->
<div class="barcode-placeholder">||||| '.h($sh['tracking_number'] ?? 'XXXXXXXXXXXX').' |||||</div>

<!-- Order ID -->
<div class="row" style="border:none;">
  <div class="section-title">Order ID: '.h($sh['order_number']).'</div>
</div>

<!-- Footer -->
<div class="footer">
  Ordered Through <strong>DEVELIXIR</strong>
</div>

</div>
</body></html>';

// Try to use DOMPDF (exact same structure as invoice)
if (class_exists('Dompdf\\Dompdf') || class_exists('Dompdf')) {
    try {
        if (!class_exists('Dompdf\\Dompdf') && class_exists('Dompdf')) {
            $dompdf = new Dompdf();
        } else {
            $dompdf = new \Dompdf\Dompdf();
        }
        $dompdf->setPaper('A6', 'portrait');
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
echo "<h2>PDF generation failed</h2>";
exit;
?>
