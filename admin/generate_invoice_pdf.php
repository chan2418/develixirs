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
               o.total_amount AS order_total_amount,
               o.payment_status, 
               o.order_status, 
               o.created_at AS order_created_at
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

// numeric columns (safe defaults)
$discount            = isset($inv['discount'])         ? (float)$inv['discount']         : 0.00;
$other_discount      = isset($inv['other_discount'])   ? (float)$inv['other_discount']   : 0.00;
$shipping            = isset($inv['shipping_charge'])  ? (float)$inv['shipping_charge']  : 0.00;
$tax_rate            = isset($inv['tax_rate'])         ? (float)$inv['tax_rate']         : 0.00;
$tax_amount_explicit = (isset($inv['tax_amount']) && $inv['tax_amount'] !== null && $inv['tax_amount'] !== '') ? (float)$inv['tax_amount'] : null;
$other_fees          = isset($inv['other_fees'])       ? (float)$inv['other_fees']       : 0.00;
$total_fee           = isset($inv['total_fee'])        ? (float)$inv['total_fee']        : 0.00;

// subtotal
$subtotal = 0.0;
if (!empty($items)) {
    foreach ($items as $it) $subtotal += (float)$it['amount'];
} else {
    if (!empty($inv['amount'])) $subtotal = (float)$inv['amount'];
    elseif (!empty($inv['order_total_amount'])) $subtotal = (float)$inv['order_total_amount'];
    else $subtotal = 0.0;
}

// tax
$taxable_base = max(0.0, $subtotal - $discount - $other_discount);
if ($tax_amount_explicit !== null) {
    $tax_value = round($tax_amount_explicit, 2);
} else {
    $tax_value = round($taxable_base * ($tax_rate / 100.0), 2);
}

// total
$total = $subtotal - $discount - $other_discount + $other_fees + $shipping + $tax_value + $total_fee;
if ($total < 0) $total = 0.0;

// build invoice HTML (keeps styling inline-friendly for dompdf)
$company_name = 'DEVELIXIR';
$html = '<!doctype html><html><head><meta charset="utf-8"><title>Invoice '.h($inv['invoice_number']).'</title>
<style>
body{font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color:#0f172a; padding:20px}
.header{display:flex;justify-content:space-between;align-items:center}
.brand{font-weight:800;font-size:20px}
.small{font-size:12px;color:#6b7280}
.table{width:100%;border-collapse:collapse;margin-top:18px}
.table th,.table td{padding:8px;border-bottom:1px solid #eee}
.table thead th{background:#f7fafc;text-align:left}
.right{text-align:right}
.summary{width:360px;margin-left:auto;border:1px solid #f1f5f9;padding:10px;border-radius:6px}
.summary .row{display:flex;justify-content:space-between;padding:6px 0}
.summary .total{font-weight:900;font-size:16px}
.notes{margin-top:12px;font-size:12px;color:#475569}
</style>
</head><body>';

$html .= '<div class="header"><div><div class="brand">'.h($company_name).'</div><div class="small">Invoice</div></div>';
$html .= '<div class="right"><div><strong>Invoice #: '.h($inv['invoice_number']).'</strong></div>';
$html .= '<div class="small">Created: '.h(date('d M Y H:i', strtotime($inv['created_at'] ?? $inv['order_created_at']))).'</div>';
$html .= '<div class="small">Status: '.h(ucfirst($inv['status'] ?? 'issued')).'</div></div></div>';

$html .= '<hr>';
$html .= '<div style="display:flex;justify-content:space-between;margin-top:12px">';
$html .= '<div><strong>Bill To</strong><div>' . nl2br(h($inv['customer_name'] ?: '-')) . '</div></div>';
$html .= '<div class="right"><strong>Order</strong><div>' . h($inv['order_number']) . '</div></div>';
$html .= '</div>';

$html .= '<table class="table" aria-label="items"><thead><tr><th>Description</th><th style="width:60px">Qty</th><th style="width:120px" class="right">Unit</th><th style="width:120px" class="right">Amount</th></tr></thead><tbody>';
if (!empty($items)) {
    foreach ($items as $it) {
        $html .= '<tr><td>'.h($it['description']).'</td><td>'.h($it['qty']).'</td><td class="right">₹ '.money($it['unit_price']).'</td><td class="right">₹ '.money($it['amount']).'</td></tr>';
    }
} else {
    $html .= '<tr><td>Order '.h($inv['order_number']).' — goods / services</td><td>1</td><td class="right">₹ '.money($subtotal).'</td><td class="right">₹ '.money($subtotal).'</td></tr>';
}
$html .= '</tbody></table>';

// summary block
$html .= '<div style="margin-top:18px;display:flex;gap:20px;align-items:flex-start">';
$html .= '<div style="flex:1" class="small"><strong>Notes</strong><div class="notes">Thank you for your business.</div></div>';
$html .= '<div class="summary">';
$html .= '<div class="row"><div>Subtotal</div><div class="right">₹ '.money($subtotal).'</div></div>';
if ($discount > 0) $html .= '<div class="row"><div>Discount</div><div class="right">- ₹ '.money($discount).'</div></div>';
if ($other_discount > 0) $html .= '<div class="row"><div>Other Discount</div><div class="right">- ₹ '.money($other_discount).'</div></div>';
if ($other_fees > 0) $html .= '<div class="row"><div>Other Fees</div><div class="right">₹ '.money($other_fees).'</div></div>';
if ($shipping > 0) $html .= '<div class="row"><div>Shipping</div><div class="right">₹ '.money($shipping).'</div></div>';
if ($total_fee > 0) $html .= '<div class="row"><div>Total Fee</div><div class="right">₹ '.money($total_fee).'</div></div>';
$html .= '<div class="row"><div>Tax '.($tax_rate?('('.money($tax_rate).'%)'):'').'</div><div class="right">₹ '.money($tax_value).'</div></div>';
$html .= '<div class="row total"><div>Total</div><div class="right">₹ '.money($total).'</div></div>';
$html .= '</div></div>';

$html .= '</body></html>';

// Try to use DOMPDF if available
if (class_exists('Dompdf\\Dompdf') || class_exists('Dompdf')) {
    // support both namespaced and older autoloaded classes
    try {
        if (!class_exists('Dompdf\\Dompdf') && class_exists('Dompdf')) {
            // older style
            $dompdf = new Dompdf();
        } else {
            $dompdf = new \Dompdf\Dompdf();
        }
        // set paper and render
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();
        // stream the PDF
        $filename = 'invoice-'.$inv['invoice_number'].'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.basename($filename).'"');
        echo $dompdf->output();
        exit;
    } catch (Exception $e) {
        error_log('DOMPDF render error: ' . $e->getMessage());
        // fallback below
    }
}

// DOMPDF not installed or failed: try to serve uploaded PDF if available
if (file_exists(__DIR__ . '/../uploads/invoices/invoice_' . $inv['id'] . '.pdf')) {
    $path = realpath(__DIR__ . '/../uploads/invoices/invoice_' . $inv['id'] . '.pdf');
    header('Content-Type: application/pdf');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="invoice-'.basename($path).'"');
    readfile($path);
    exit;
}

// Developer fallback file (uploaded by you during testing)
if (file_exists($developer_pdf)) {
    // NOTE: your environment may map this local path to a served URL; if not, copy this file into /uploads/invoices/
    header('Content-Type: application/pdf');
    header('Content-Length: ' . filesize($developer_pdf));
    header('Content-Disposition: attachment; filename="invoice-fallback.pdf"');
    readfile($developer_pdf);
    exit;
}

// Final fallback: instruct how to install dompdf
http_response_code(500);
echo "<h2>PDF generation not available</h2>";
echo "<p>Server does not have a PDF generator installed (dompdf). Install it with Composer on the server:</p>";
echo "<pre>composer require dompdf/dompdf</pre>";
echo "<p>Or place a PDF file for this invoice into <code>uploads/invoices/invoice_{$inv['id']}.pdf</code> and retry.</p>";
exit;