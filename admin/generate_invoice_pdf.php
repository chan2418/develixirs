<?php
// admin/generate_invoice_pdf.php
// Generate invoice PDF (uses dompdf if installed). Falls back to a developer PDF file if dompdf missing.
//
// Usage: /admin/generate_invoice_pdf.php?id=13
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }

// developer fallback PDF path (you uploaded this file earlier)
$developer_pdf = '/mnt/data/OD335927864916938100.pdf'; // <-- environment maps this path to a served file in your setup

// get id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo "Missing invoice id";
    exit;
}

// fetch invoice + order (keep SQL compatible with your DB)
try {
    $stmt = $pdo->prepare("
        SELECT i.*, 
               o.order_number, 
               o.customer_name, 
               o.customer_address,
               o.total_amount AS order_total_amount,
               o.payment_status, 
               o.order_status, 
               o.created_at AS order_created_at,
               o.shipping_charge AS order_shipping,
               o.coupon_discount AS order_discount,
               o.coupon_code,
               o.tax_amount AS order_tax
        FROM invoices i
        JOIN orders o ON i.order_id = o.id
        WHERE i.id = ? LIMIT 1
    ");
    $stmt->execute([$id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Invoice fetch error: ' . $e->getMessage());
    $inv = false;
}

if (!$inv) {
    // invoice not found -> friendly HTML (so browser doesn't try to interpret binary)
    http_response_code(404);
    echo "<h2>Invoice not found</h2><p>The requested invoice does not exist or there was an error fetching it.</p>";
    exit;
}

// fetch items (optional)
$items = [];
try {
    $it = $pdo->prepare("SELECT description, qty, unit_price, amount FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
    $it->execute([$id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $items = [];
}

// numeric columns (prefer order data if invoice data is missing/zero, as invoice table might be minimal)
$discount       = !empty($inv['order_discount']) ? (float)$inv['order_discount'] : (isset($inv['discount']) ? (float)$inv['discount'] : 0.00);
$shipping       = !empty($inv['order_shipping']) ? (float)$inv['order_shipping'] : (isset($inv['shipping_charge']) ? (float)$inv['shipping_charge'] : 0.00);
$tax_amount     = !empty($inv['order_tax'])      ? (float)$inv['order_tax']      : (isset($inv['tax_amount']) ? (float)$inv['tax_amount'] : 0.00);

// Subtotal calculation
$subtotal = 0.0;
if (!empty($items)) {
    foreach ($items as $it) $subtotal += (float)$it['amount'];
} else {
    // fallback
    $subtotal = (float)$inv['order_total_amount'] - $shipping + $discount - $tax_amount;
}

// Recalculate total to be sure
$total = $subtotal + $shipping + $tax_amount - $discount;


// build invoice HTML (keeps styling inline-friendly for dompdf)
// Fetch company details from site_settings
$company_name = 'DEVELIXIR';
$company_address = "123 Herbal Street, Green City\nKerala, India - 670001";
$company_email = 'support@develixir.com';

try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        if ($row['setting_key'] === 'company_name') $company_name = $row['setting_value'];
        if ($row['setting_key'] === 'company_address') $company_address = $row['setting_value'];
        if ($row['setting_key'] === 'company_email') $company_email = $row['setting_value'];
    }
} catch (Exception $e) {
    // ignore
}

// Append email to address if not already there
if (strpos($company_address, '@') === false && !empty($company_email)) {
    $company_address .= "\n" . $company_email;
}

// Prepare Logo (Base64 for DOMPDF reliability)
$logoPath = __DIR__ . '/../develixir-logo.png';
$logoData = '';
if (file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents($logoPath);
    $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$html = '<!doctype html><html><head><meta charset="utf-8"><title>Invoice '.h($inv['invoice_number']).'</title>
<style>
@page { margin: 0px; }
body { font-family: "DejaVu Sans", "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 20px; font-size: 14px; line-height: 1.5; background-color: #f1f5f9; }
.invoice-container { background-color: #ffffff; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0; }
.header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #f3f4f6; padding-bottom: 20px; }
.logo-img { max-height: 60px; max-width: 200px; }
.company-info { font-size: 12px; color: #64748b; text-align: right; line-height: 1.4; }
.invoice-title { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 5px; letter-spacing: 1px; }
.invoice-number { font-size: 20px; font-weight: 700; color: #0f172a; }
.meta-grid { display: table; width: 100%; margin-bottom: 40px; }
.meta-col { display: table-cell; width: 33%; vertical-align: top; }
.label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin-bottom: 5px; }
.value { font-size: 14px; font-weight: 500; color: #0f172a; }
.address { white-space: pre-line; color: #334155; }
.table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
.table th { text-align: left; padding: 12px 15px; background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
.table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.table .right { text-align: right; }
.table .total-row td { border-bottom: none; font-weight: 700; color: #0f172a; }
.summary-box { width: 300px; margin-left: auto; background: #f8fafc; border-radius: 8px; padding: 20px; }
.summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; color: #475569; }
.summary-row.total { border-top: 2px solid #e2e8f0; margin-top: 10px; padding-top: 10px; font-weight: 800; font-size: 18px; color: #0f172a; }
.footer { position: fixed; bottom: 40px; left: 40px; right: 40px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 20px; }
.badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: #ecfdf5; color: #047857; }
</style>
</head><body>
<div class="invoice-container">

<div class="header">
    <div>';
if ($logoData) {
    $html .= '<img src="'.$logoData.'" class="logo-img" alt="'.h($company_name).'">';
} else {
    $html .= '<div style="font-size: 28px; font-weight: 800; color: #0f172a;">'.h($company_name).'</div>';
}
$html .= '</div>
    <div class="company-info">
        '.nl2br(h($company_address)).'
    </div>
</div>';

$html .= '<div class="meta-grid">
    <div class="meta-col">
        <div class="label">Billed To</div>
        <div class="value">'.h($inv['customer_name']).'</div>
        <div class="address">'.h($inv['customer_address'] ?? '').'</div>
    </div>
    <div class="meta-col">
        <div class="label">Invoice Details</div>
        <div class="value">#'.h($inv['invoice_number']).'</div>
        <div class="value" style="font-size:12px;color:#64748b;margin-top:4px">Issued: '.date('M d, Y', strtotime($inv['created_at'])).'</div>
    </div>
    <div class="meta-col" style="text-align:right">
        <div class="label">Total Amount</div>
        <div class="invoice-number">₹ '.money($total).'</div>
        <div style="margin-top:5px"><span class="badge">'.h($inv['payment_status'] ?? 'Paid').'</span></div>
    </div>
</div>';

$html .= '<table class="table">
    <thead>
        <tr>
            <th style="width:50%">Description</th>
            <th style="width:15%">Quantity</th>
            <th style="width:15%" class="right">Unit Price</th>
            <th style="width:20%" class="right">Amount</th>
        </tr>
    </thead>
    <tbody>';

if (!empty($items)) {
    foreach ($items as $it) {
        $html .= '<tr>
            <td>'.h($it['description']).'</td>
            <td>'.h($it['qty']).'</td>
            <td class="right">₹ '.money($it['unit_price']).'</td>
            <td class="right">₹ '.money($it['amount']).'</td>
        </tr>';
    }
} else {
    $html .= '<tr>
        <td>Order #'.h($inv['order_number']).'</td>
        <td>1</td>
        <td class="right">₹ '.money($subtotal).'</td>
        <td class="right">₹ '.money($subtotal).'</td>
    </tr>';
}

$html .= '</tbody></table>';

$html .= '<div class="summary-box">
    <div class="summary-row">
        <span>Subtotal</span>
        <span>₹ '.money($subtotal).'</span>
    </div>';

if ($discount > 0) {
    $html .= '<div class="summary-row" style="color:#16a34a">
        <span>Discount</span>
        <span>- ₹ '.money($discount).'</span>
    </div>';
}

if ($tax_amount > 0) {
    $html .= '<div class="summary-row">
        <span>Tax (18% GST)</span>
        <span>₹ '.money($tax_amount).'</span>
    </div>';
}

if ($shipping > 0) {
    $html .= '<div class="summary-row">
        <span>Shipping</span>
        <span>₹ '.money($shipping).'</span>
    </div>';
}

$html .= '<div class="summary-row total">
        <span>Total</span>
        <span>₹ '.money($total).'</span>
    </div>
</div>';

$html .= '<div class="footer">
    Thank you for your business! &bull; For support, email support@develixir.com
</div>';

$html .= '</div></body></html>';

// Try to use DOMPDF if available
if (class_exists('Dompdf\\Dompdf') || class_exists('Dompdf')) {
    try {
        if (!class_exists('Dompdf\\Dompdf') && class_exists('Dompdf')) {
            $dompdf = new Dompdf();
        } else {
            $dompdf = new \Dompdf\Dompdf();
        }
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();
        $filename = 'invoice-'.$inv['invoice_number'].'.pdf';
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