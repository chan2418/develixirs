<?php
ob_start(); // Start buffering immediately to catch any whitespace/errors from includes
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
    echo "Invalid shipment id";
    exit;
}

// fetch shipment + order
try {
    $stmt = $pdo->prepare("
      SELECT s.*, o.order_number, o.user_id, o.customer_name, o.customer_address, o.customer_phone, o.customer_email
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

// Fetch Company Settings for "Ship From"
$companyName = 'DEVELIXIR'; 
$companyAddress = "No:6, 3rd Cross Street\nKamatchiamman Garden, Sethukkarai\nGudiyatham-632602, Vellore\nTamil Nadu, INDIA";
$companyPhone = '9500650454';

try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('company_name', 'company_address', 'company_phone')");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        if ($row['setting_key'] === 'company_name' && !empty($row['setting_value'])) $companyName = $row['setting_value'];
        if ($row['setting_key'] === 'company_address' && !empty($row['setting_value'])) $companyAddress = $row['setting_value'];
        if ($row['setting_key'] === 'company_phone' && !empty($row['setting_value'])) $companyPhone = $row['setting_value'];
    }
} catch (Exception $e) { 
    // minimal fallback already set 
}

$companyAddress = strip_label_gst_text($companyAddress);

// Resolve Address & Phone (Same logic as invoice)
$addressString = $sh['recipient_address'] ?? $sh['customer_address'];
$customerPhone = $sh['recipient_phone'] ?? $sh['customer_phone'];

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
        
        // Update phone if present in JSON and current phone is empty
        if (empty($customerPhone) && !empty($decoded['phone'])) {
            $customerPhone = $decoded['phone'];
        }
    }
}

// 2. Fallback to user profile if address invalid
$cleanAddr = trim(strip_tags($addressString));
if (empty($cleanAddr) || stripos($cleanAddr, 'Not provided') !== false || strlen($cleanAddr) < 5) {
    if (!empty($sh['user_id'])) {
        try {
            $stmtAddr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1");
            $stmtAddr->execute([$sh['user_id']]);
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
if (empty($customerPhone) && !empty($sh['user_id'])) {
    try {
        $stmtPhone = $pdo->prepare("SELECT phone FROM user_addresses WHERE user_id = ? AND phone != '' AND phone IS NOT NULL ORDER BY is_default DESC LIMIT 1");
        $stmtPhone->execute([$sh['user_id']]);
        $foundPhone = $stmtPhone->fetchColumn();
        if ($foundPhone) $customerPhone = $foundPhone;
    } catch (Exception $e) {}
}

$addressString = strip_label_gst_text($addressString);

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
                <div class="value"><?= h($companyName) ?></div>
                <div class="address"><?= nl2br(h($companyAddress)) ?>
Ph: <?= h($companyPhone) ?></div>
            </td>
            <td style="width: 50%; vertical-align: top; padding: 10px; border: 1px solid #000;">
                <div class="label">SHIP TO:</div>
                <div class="value">'.h($sh['recipient_name'] ?? $sh['customer_name']).'</div>
                <div class="address">'.nl2br(h($addressString)).'</div>
                <div style="margin-top:5px">Ph: '.h($customerPhone).'</div>
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
        
        if (ob_get_length()) ob_end_clean(); // Clean any previous output

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.basename($filename).'"');
        echo $dompdf->output();
        exit;
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/pdf_error.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
        error_log('DOMPDF render error: ' . $e->getMessage());
    }
}

// Fallback
http_response_code(500);
echo "<h2>PDF generation failed</h2><p>Could not generate PDF.</p>";
exit;
?>
