<?php
// admin/label_download.php - Simplified label PDF generator
// Minimal dependencies, direct PDF output

// Start output buffering FIRST
ob_start();

// Suppress all errors from display
ini_set('display_errors', '0');
error_reporting(0);

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication (but don't include _auth.php which might have output)
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    ob_end_clean();
    http_response_code(401);
    die('Unauthorized');
}

// Load dependencies
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

function strip_label_gst_text($text) {
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

// Get shipment ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    ob_end_clean();
    die('Invalid shipment ID');
}

// Fetch shipment data
try {
    $stmt = $pdo->prepare("SELECT s.*, o.order_number, o.customer_name, o.customer_address, o.customer_phone 
                           FROM shipments s 
                           LEFT JOIN orders o ON s.order_id = o.id 
                           WHERE s.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    ob_end_clean();
    die('Database error');
}

if (!$shipment) {
    ob_end_clean();
    die('Shipment not found');
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
    // minimal fallback
}

$companyAddress = strip_label_gst_text($companyAddress);
$shipToAddress = strip_label_gst_text($shipment['customer_address'] ?? '');

// Build HTML content
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
        .container { border: 2px solid #000; padding: 15px; }
        .header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 20px; font-weight: bold; }
        .row { margin: 8px 0; }
        .label { font-size: 10px; color: #666; text-transform: uppercase; }
        .value { font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">SHIPPING LABEL</div>
            <div>Shipment: ' . htmlspecialchars($shipment['shipment_number']) . '</div>
            <div>Order: ' . htmlspecialchars($shipment['order_number']) . '</div>
        </div>
        
        <div class="row">
            <div class="label">SHIP FROM:</div>
            <div class="value">' . htmlspecialchars($companyName) . '</div>
            <div>' . nl2br(htmlspecialchars($companyAddress)) . '</div>
            <div>Phone: ' . htmlspecialchars($companyPhone) . '</div>
        </div>

        <div class="row">
            <div class="label">SHIP TO:</div>
            <div class="value">' . htmlspecialchars($shipment['customer_name'] ?? '') . '</div>
            <div>' . nl2br(htmlspecialchars($shipToAddress)) . '</div>
            <div>Phone: ' . htmlspecialchars($shipment['customer_phone'] ?? '') . '</div>
        </div>
        
        <div class="row">
            <div class="label">CARRIER:</div>
            <div class="value">' . htmlspecialchars($shipment['carrier'] ?? 'N/A') . '</div>
        </div>
        
        <div class="row">
            <div class="label">TRACKING NUMBER:</div>
            <div class="value">' . htmlspecialchars($shipment['tracking_number'] ?? 'N/A') . '</div>
        </div>
        
        <div class="row">
            <div class="label">WEIGHT:</div>
            <div>' . htmlspecialchars($shipment['weight'] ?? 'N/A') . ' kg</div>
        </div>
    </div>
</body>
</html>';

// Generate PDF
try {
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A6', 'portrait');
    $dompdf->render();
    
    // Clean all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="label-' . $shipment['shipment_number'] . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Output PDF
    echo $dompdf->output();
    exit;
    
} catch (Exception $e) {
    // Log the error
    $errorMsg = date('Y-m-d H:i:s') . ' - PDF Error: ' . $e->getMessage() . "\n";
    @file_put_contents(__DIR__ . '/label_errors.log', $errorMsg, FILE_APPEND);
    
    ob_end_clean();
    http_response_code(500);
    echo 'PDF generation failed. Please check the server logs.';
    exit;
}
