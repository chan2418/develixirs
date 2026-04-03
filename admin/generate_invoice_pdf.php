<?php
// admin/generate_invoice_pdf.php
// Tax Invoice Generation
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_number_helper.php';
require_once __DIR__ . '/../includes/order_pricing_helper.php';
if (session_status() === PHP_SESSION_NONE) session_start();

ensure_order_pricing_schema($pdo);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }

// Helper for Amount in Words
function numberToWords($number) {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array('0' => '', '1' => 'One', '2' => 'Two',
    '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
    '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
    '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
    '13' => 'Thirteen', '14' => 'Fourteen',
    '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
    '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty',
    '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
    '60' => 'Sixty', '70' => 'Seventy',
    '80' => 'Eighty', '90' => 'Ninety');
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] .
                " " . $digits[$counter] . $plural . " " . $hundred
                :
                $words[floor($number / 10) * 10] . " " .
                $words[$number % 10] . " " .
                $digits[$counter] . $plural . " " . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    return $result . " Rupees Only";
}

// get id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Missing invoice id");
}

try {
    sync_invoice_number($pdo, $id);
} catch (Exception $e) {
    error_log('Invoice number sync error: ' . $e->getMessage());
}

// fetch invoice + order
try {
    $stmt = $pdo->prepare("
        SELECT i.*, 
               o.order_number, 
               o.user_id,
               o.customer_name, 
               o.customer_address,
               u.phone AS customer_phone,
               o.total_amount AS order_total_amount,
               o.base_subtotal AS order_base_subtotal,
               o.payment_status, 
               o.order_status, 
               o.created_at AS order_created_at,
               o.shipping_charge AS order_shipping,
               o.coupon_discount AS order_coupon_discount,
               o.subscription_discount AS order_subscription_discount,
               o.subscription_plan_name,
               o.subscription_discount_percent,
               o.applied_discount_type,
               o.coupon_code,
               o.tax_amount AS order_tax
        FROM invoices i
        JOIN orders o ON i.order_id = o.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE i.id = ? LIMIT 1
    ");
    $stmt->execute([$id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { 
    die("SQL Error: " . $e->getMessage());
}

if (!$inv) die("Invoice not found");

// Infer Payment Method
$payment_method = (strpos($inv['order_number'], 'COD-') === 0) ? 'Cash on Delivery' : 'Online / UPI';

// Address Logic
$addressString = $inv['customer_address'];
$customerGstin = '';

if (!empty($addressString) && preg_match('/GST(?:IN)?\s*[:\-]?\s*([0-9A-Z]{15})/i', $addressString, $m)) {
    $customerGstin = strtoupper($m[1]);
}

// 1. Try to decode if it looks like JSON
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
        if (empty($customerGstin) && !empty($decoded['gstin'])) $customerGstin = strtoupper(trim((string)$decoded['gstin']));
        if (empty($customerGstin) && !empty($decoded['gst'])) $customerGstin = strtoupper(trim((string)$decoded['gst']));
    }
}

// 2. If address is unusable ("Not provided" or empty), fetch from user profile
$cleanAddr = trim(strip_tags($addressString));
if (empty($cleanAddr) || stripos($cleanAddr, 'Not provided') !== false || strlen($cleanAddr) < 5) {
    if (!empty($inv['user_id'])) {
        try {
            $stmtAddr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1");
            $stmtAddr->execute([$inv['user_id']]);
            $uAddr = $stmtAddr->fetch(PDO::FETCH_ASSOC);
            
            if ($uAddr) {
                // Construct address from user_addresses table
                $parts = [];
                if (!empty($uAddr['address_line1'])) $parts[] = $uAddr['address_line1'];
                if (!empty($uAddr['address_line2'])) $parts[] = $uAddr['address_line2'];
                $cityState = [];
                if (!empty($uAddr['city'])) $cityState[] = $uAddr['city'];
                if (!empty($uAddr['state'])) $cityState[] = $uAddr['state'];
                if (!empty($uAddr['pincode'])) $cityState[] = $uAddr['pincode'];
                
                if (!empty($cityState)) $parts[] = implode(', ', $cityState);
                
                $addressString = implode("\n", $parts);
                
                // Also update phone if missing
                if (empty($inv['customer_phone']) && !empty($uAddr['phone'])) {
                    $inv['customer_phone'] = $uAddr['phone'];
                }
            }
        } catch (Exception $e) { /* ignore */ }
    }
}

// 3. Last Resort: If Phone is STILL missing, look specifically for ANY phone in user_addresses
if (empty($inv['customer_phone']) && !empty($inv['user_id'])) {
    try {
        $stmtPhone = $pdo->prepare("SELECT phone FROM user_addresses WHERE user_id = ? AND phone != '' AND phone IS NOT NULL ORDER BY is_default DESC LIMIT 1");
        $stmtPhone->execute([$inv['user_id']]);
        $foundPhone = $stmtPhone->fetchColumn();
        if ($foundPhone) {
            $inv['customer_phone'] = $foundPhone;
        }
    } catch (Exception $e) {}
}

// Clean structured lines from address block (we print phone and GST separately)
if (!empty($addressString)) {
    if (empty($inv['customer_phone']) && preg_match('/Phone\s*[:\-]?\s*([0-9+\-\s()]{6,20})/i', $addressString, $pm)) {
        $inv['customer_phone'] = trim($pm[1]);
    }
    if (empty($customerGstin) && preg_match('/GST(?:IN)?\s*[:\-]?\s*([0-9A-Z]{15})/i', $addressString, $gm)) {
        $customerGstin = strtoupper($gm[1]);
    }

    $addressString = preg_replace('/(?:\R|^)\s*Phone\s*[:\-]?\s*[0-9+\-\s()]{6,20}\s*(?=\R|$)/i', '', $addressString);
    $addressString = preg_replace('/(?:\R|^)\s*GST(?:IN)?\s*[:\-]?\s*[0-9A-Z]{15}\s*(?=\R|$)/i', '', $addressString);
    $addressString = trim((string)$addressString);
}

// fetch items with product details
$items = [];
try {
    $stmtIt = $pdo->prepare("
        SELECT 
            oi.product_name AS description,
            oi.qty,
            oi.price AS unit_price,
            (oi.price * oi.qty) AS amount,
            p.hsn,
            p.gst_rate
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $stmtIt->execute([$inv['order_id']]);
    $items = $stmtIt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $items = []; }

// Company Details
$company_name = 'DEVELIXIR';
$company_address = "Chennai, Tamil Nadu";
$company_email = 'support@develixir.com';
$company_gstin = ''; // Default empty
$company_cin = ''; // Default empty
$company_pan = ''; // Default empty

try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        if ($row['setting_key'] === 'company_name') $company_name = $row['setting_value'];
        if ($row['setting_key'] === 'company_address') $company_address = $row['setting_value'];
        if ($row['setting_key'] === 'company_email') $company_email = $row['setting_value'];
        if ($row['setting_key'] === 'company_gstin') $company_gstin = $row['setting_value'];
        if ($row['setting_key'] === 'company_cin') $company_cin = $row['setting_value'];
        if ($row['setting_key'] === 'company_pan') $company_pan = $row['setting_value'];
    }
} catch (Exception $e) {}

// Use Uploaded Logo if available in preview context, otherwise local
// Assuming a fixed logo path for now or base64
$logoPath = __DIR__ . '/../develixir-logo.png'; 
if (!file_exists($logoPath)) {
    // Try to find any png in uploads for logo? No, safest is text if missing.
    // For now, let's look for a transparent logo in assets or similar
    $logoPath = __DIR__ . '/../assets/img/logo.png'; // Common path
}

$logoData = '';
if (file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents($logoPath);
    $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// Calculations
$shipping       = !empty($inv['order_shipping']) ? (float)$inv['order_shipping'] : 0.00;
$couponDiscount = !empty($inv['order_coupon_discount']) ? (float)$inv['order_coupon_discount'] : 0.00;
$subscriptionDiscount = !empty($inv['order_subscription_discount']) ? (float)$inv['order_subscription_discount'] : 0.00;
$appliedDiscountType = (string)($inv['applied_discount_type'] ?? '');
$storedInvoiceTotal = !empty($inv['amount']) ? (float)$inv['amount'] : 0.00;
$storedOrderTotal = !empty($inv['order_total_amount']) ? (float)$inv['order_total_amount'] : 0.00;
$grand_total    = 0.00;
$discount       = 0.00;
$discountLabel  = 'Discount';

if ($appliedDiscountType === 'subscription' && $subscriptionDiscount > 0) {
    $discount = $subscriptionDiscount;
    $discountLabel = 'Subscription Discount';
    if (!empty($inv['subscription_plan_name'])) {
        $discountLabel .= ' (' . trim((string)$inv['subscription_plan_name']) . ')';
    }
} elseif ($couponDiscount > 0) {
    $discount = $couponDiscount;
    $discountLabel = 'Coupon Discount';
    if (!empty($inv['coupon_code'])) {
        $discountLabel .= ' (' . trim((string)$inv['coupon_code']) . ')';
    }
} elseif ($subscriptionDiscount > 0) {
    $discount = $subscriptionDiscount;
    $discountLabel = 'Subscription Discount';
}

// Determine tax mode (auto by address, but override supported by query)
$supplierState = 'Tamil Nadu';
$recipientState = 'Tamil Nadu';
if (stripos($addressString, 'Tamil Nadu') === false && stripos($addressString, 'Chennai') === false) {
    $recipientState = 'Other';
}

$taxModeOverride = strtolower(trim((string)($_GET['tax_mode'] ?? ($_GET['sale_type'] ?? ''))));
if (in_array($taxModeOverride, ['intrastate', 'intra', 'local'], true)) {
    $isInterState = false;
} elseif (in_array($taxModeOverride, ['interstate', 'inter'], true)) {
    $isInterState = true;
} else {
    $isInterState = ($supplierState !== $recipientState);
}

// --- HTML GENERATION ---
$companyPan = strtoupper(preg_replace('/\s+/', '', (string)$company_pan));

$customerStateCode = $isInterState ? 'Inter State' : '33';
if (!empty($customerGstin) && preg_match('/^([0-9]{2})/', $customerGstin, $mState)) {
    $customerStateCode = $mState[1];
}

$invoiceDate = date('d-M-y', strtotime($inv['created_at']));
$orderDate = date('d-M-y', strtotime($inv['order_created_at'] ?? $inv['created_at']));
$placeOfSupply = $isInterState ? 'Inter State' : 'Tamil Nadu';

$compactInline = static function ($text) {
    $text = trim((string)$text);
    if ($text === '') return '';
    $text = preg_replace('/\s*\R+\s*/', ', ', $text);
    $text = preg_replace('/\s{2,}/', ' ', $text);
    return trim((string)$text, " ,");
};

$addressCompact = $compactInline($addressString);
$companyAddressCompact = $compactInline($company_address);

$contentScore = strlen((string)$addressString) + strlen((string)$company_address) + (count($items) * 90);
if ($contentScore > 760) {
    $baseFont = '6.8px';
} elseif ($contentScore > 560) {
    $baseFont = '7.2px';
} else {
    $baseFont = '7.8px';
}

$preparedItems = [];
$totalMrpInclTax = 0.0;
$maxItemGstRate = 0.0;
foreach ($items as $item) {
    $qty = max(0, (float)($item['qty'] ?? 0));
    if ($qty <= 0) {
        continue;
    }
    $mrpUnit = (float)($item['unit_price'] ?? 0);
    $mrpLine = round(max(0, $mrpUnit * $qty), 2);
    $gstPercent = !empty($item['gst_rate']) ? (float)$item['gst_rate'] : 18.0;

    $preparedItems[] = [
        'description' => (string)($item['description'] ?? ''),
        'hsn' => (string)($item['hsn'] ?? '-'),
        'qty' => $qty,
        'mrp_unit' => $mrpUnit,
        'mrp_line' => $mrpLine,
        'gst_percent' => $gstPercent,
    ];
    $totalMrpInclTax += $mrpLine;
    $maxItemGstRate = max($maxItemGstRate, $gstPercent);
}

$sn = 1;
$totalQty = 0.0;
$totalTaxableRaw = 0.0;
$totalTaxAmtRaw = 0.0;
$productLinesTotal = 0.0;
$totalDiscountApplied = 0.0;
$itemRowsHtml = '';
$auditRowsHtml = '';

$remainingDiscount = round(min(max($discount, 0), $totalMrpInclTax), 2);
$itemCount = count($preparedItems);

foreach ($preparedItems as $idx => $item) {
    $lineDiscount = 0.0;
    if ($remainingDiscount > 0 && $totalMrpInclTax > 0) {
        if ($idx === $itemCount - 1) {
            $lineDiscount = $remainingDiscount;
        } else {
            $lineDiscount = round(($item['mrp_line'] / $totalMrpInclTax) * $discount, 2);
            $lineDiscount = min($lineDiscount, $remainingDiscount);
        }
        $lineDiscount = min($lineDiscount, $item['mrp_line']);
    }

    $remainingDiscount = round(max(0, $remainingDiscount - $lineDiscount), 2);
    $lineAmount = round(max(0, $item['mrp_line'] - $lineDiscount), 2);
    $lineTaxableRaw = $item['gst_percent'] > 0 ? ($lineAmount / (1 + ($item['gst_percent'] / 100))) : $lineAmount;
    $lineTaxRaw = $lineAmount - $lineTaxableRaw;

    $totalQty += $item['qty'];
    $productLinesTotal += $lineAmount;
    $totalDiscountApplied += $lineDiscount;
    $totalTaxableRaw += $lineTaxableRaw;
    $totalTaxAmtRaw += $lineTaxRaw;

    $itemRowsHtml .= '<tr>
        <td class="cell c-center c-mid b-r b-b">' . $sn++ . '</td>
        <td class="cell b-r b-b">
            <div class="item-title">' . h($item['description']) . '</div>
            <div class="muted">MRP includes GST</div>
        </td>
        <td class="cell c-center c-mid b-r b-b">' . h($item['hsn']) . '</td>
        <td class="cell c-center c-mid b-r b-b">' . rtrim(rtrim(number_format($item['gst_percent'], 2), '0'), '.') . '%</td>
        <td class="cell c-center c-mid b-r b-b">' . number_format($item['qty'], 3) . '</td>
        <td class="cell c-right c-mid b-r b-b">' . money($item['mrp_line']) . '</td>
        <td class="cell c-right c-mid b-r b-b">' . money($lineDiscount) . '</td>
        <td class="cell c-right c-mid b-b"><b>' . money($lineAmount) . '</b></td>
    </tr>';

    $auditRowsHtml .= '<tr>
        <td class="cell b-r b-b">' . h($item['description']) . '</td>
        <td class="cell c-center c-mid b-r b-b">' . rtrim(rtrim(number_format($item['gst_percent'], 2), '0'), '.') . '%</td>
        <td class="cell c-right c-mid b-r b-b">' . money($lineTaxableRaw) . '</td>
        <td class="cell c-right c-mid b-r b-b">' . money($lineTaxRaw) . '</td>
        <td class="cell c-right c-mid b-b"><b>' . money($lineAmount) . '</b></td>
    </tr>';
}

if ($itemCount === 0) {
    $itemRowsHtml .= '<tr><td class="cell b-b c-center" colspan="8">No items found for this invoice.</td></tr>';
}

$tableTotalAmount = round(max(0, $productLinesTotal), 2);
$shippingGstRate = $maxItemGstRate > 0 ? $maxItemGstRate : 0.0;
$shippingTaxableRaw = $shippingGstRate > 0 ? ($shipping / (1 + ($shippingGstRate / 100))) : $shipping;
$shippingTaxRaw = max(0, $shipping - $shippingTaxableRaw);

if ($shipping > 0) {
    $totalTaxableRaw += $shippingTaxableRaw;
    $totalTaxAmtRaw += $shippingTaxRaw;
    $auditRowsHtml .= '<tr>
        <td class="cell b-r b-b">Shipping Charges</td>
        <td class="cell c-center c-mid b-r b-b">' . ($shippingGstRate > 0 ? rtrim(rtrim(number_format($shippingGstRate, 2), '0'), '.') . '%' : '-') . '</td>
        <td class="cell c-right c-mid b-r b-b">' . money($shippingTaxableRaw) . '</td>
        <td class="cell c-right c-mid b-r b-b">' . money($shippingTaxRaw) . '</td>
        <td class="cell c-right c-mid b-b"><b>' . money($shipping) . '</b></td>
    </tr>';
}

$computedGrandTotal = round(max(0, $tableTotalAmount + $shipping), 2);
$grand_total = $computedGrandTotal;
if ($storedOrderTotal > 0 && abs($storedOrderTotal - $computedGrandTotal) <= 0.05) {
    $grand_total = round($storedOrderTotal, 2);
}

$totalTaxAmount = round(max(0, $totalTaxAmtRaw), 2);
$summaryTaxable = round(max(0, $grand_total - $totalTaxAmount), 2);

$cgstAmount = 0.0;
$sgstAmount = 0.0;
$igstAmount = 0.0;
if ($isInterState) {
    $igstAmount = $totalTaxAmount;
} else {
    $cgstAmount = round($totalTaxAmount / 2, 2);
    $sgstAmount = round($totalTaxAmount - $cgstAmount, 2);
}

$discountSummaryDisplay = $totalDiscountApplied > 0 ? '-' . money($totalDiscountApplied) : money(0);
$shippingRateLabel = $shippingGstRate > 0 ? rtrim(rtrim(number_format($shippingGstRate, 2), '0'), '.') . '%' : '-';
$invoiceValueCheck = round($summaryTaxable + $cgstAmount + $sgstAmount + $igstAmount, 2);

$auditRowsHtml .= '<tr style="font-weight:700; background:#f7f7f7;">
    <td class="cell b-r b-t">Total</td>
    <td class="cell c-center c-mid b-r b-t">-</td>
    <td class="cell c-right c-mid b-r b-t">' . money($summaryTaxable) . '</td>
    <td class="cell c-right c-mid b-r b-t">' . money($totalTaxAmount) . '</td>
    <td class="cell c-right c-mid b-t"><b>' . money($invoiceValueCheck) . '</b></td>
</tr>';

$totalDisplayQty = number_format($totalQty, 3);

ob_start();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin: 5.5mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: <?= $baseFont ?>; line-height: 1.15; color: #111; margin: 0; padding: 0; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.frame { border: 1px solid #6f6f6f; table-layout: fixed; }
.b-r { border-right: 1px solid #a0a0a0; }
.b-b { border-bottom: 1px solid #a0a0a0; }
.b-t { border-top: 1px solid #a0a0a0; }
.cell { padding: 2px 3px; vertical-align: top; }
.c-center { text-align: center; }
.c-right { text-align: right; }
.c-mid { vertical-align: middle; }
.title { text-align: center; font-size: 12px; font-weight: 700; padding: 0 0 4px; }
.section-title { font-weight: 700; margin-bottom: 1px; }
.item-title { font-weight: 700; }
.muted { color: #444; font-size: 7px; }
.logo { max-width: 84px; max-height: 30px; margin-bottom: 2px; }
.small { font-size: 7px; }
.tight { line-height: 1.1; }
.break { word-break: break-word; }
.nowrap { white-space: nowrap; }
.meta { table-layout: fixed; }
.meta td { border-bottom: 1px solid #c0c0c0; padding: 2px 3px; vertical-align: top; word-break: break-word; }
.meta tr:last-child td { border-bottom: none; }
</style>
</head>
<body>
<div class="title">Tax Invoice</div>

<table class="frame">
    <tr>
        <td class="cell b-r b-b break" style="width:35%;">
            <?php if (!empty($logoData)): ?>
                <img src="<?= $logoData ?>" class="logo" alt="Logo">
            <?php endif; ?>
            <div class="section-title"><?= h($company_name) ?></div>
            <div class="tight"><?= h($companyAddressCompact) ?></div>
            <?php if (!empty($company_email)): ?><div>E-Mail : <?= h($company_email) ?></div><?php endif; ?>
            <?php if (!empty($company_gstin)): ?><div>GSTIN/UIN : <?= h($company_gstin) ?></div><?php endif; ?>
            <?php if (!empty($company_cin)): ?><div>CIN : <?= h($company_cin) ?></div><?php endif; ?>
        </td>
        <td class="cell b-b" style="width:65%; padding:0;">
            <table class="meta">
                <tr>
                    <td style="width:18%;">Invoice No.</td>
                    <td style="width:32%;" class="nowrap"><b><?= h($inv['invoice_number']) ?></b></td>
                    <td style="width:18%;">Dated</td>
                    <td style="width:32%;" class="nowrap"><b><?= h($invoiceDate) ?></b></td>
                </tr>
                <tr>
                    <td>Delivery Note</td>
                    <td class="nowrap"><?= h($inv['order_number']) ?></td>
                    <td>Mode/Terms of Payment</td>
                    <td><?= h($payment_method) ?></td>
                </tr>
                <tr>
                    <td>Reference No. &amp; Date.</td>
                    <td colspan="3">Order #<?= h($inv['order_number']) ?>, <?= h($orderDate) ?></td>
                </tr>
                <tr>
                    <td>Destination</td>
                    <td><?= h($recipientState === 'Other' ? 'Inter State' : 'Tamil Nadu') ?></td>
                    <td>Delivery Date</td>
                    <td><?= h($invoiceDate) ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td class="cell b-r b-b break" style="width:56%;">
            <div class="section-title">Buyer (Bill to)</div>
            <div><b><?= h($inv['customer_name']) ?></b></div>
            <div class="tight"><?= h($addressCompact) ?></div>
            <?php if (!empty($customerGstin)): ?><div>GSTIN/UIN : <?= h($customerGstin) ?></div><?php endif; ?>
            <div>State : <?= h($recipientState === 'Other' ? 'Other State' : 'Tamil Nadu') ?> | Code : <?= h($customerStateCode) ?></div>
            <div>Place of Supply : <?= h($placeOfSupply) ?></div>
            <?php if (!empty($inv['customer_phone'])): ?><div>Contact : <?= h($inv['customer_phone']) ?></div><?php endif; ?>
        </td>
        <td class="cell b-b break" style="width:44%;">
            <div class="section-title">Consignee (Ship to)</div>
            <div><b><?= h($inv['customer_name']) ?></b></div>
            <div>Same as Buyer (Bill to)</div>
            <?php if (!empty($customerGstin)): ?><div>GSTIN/UIN : <?= h($customerGstin) ?></div><?php endif; ?>
            <div>Place of Supply : <?= h($placeOfSupply) ?></div>
            <?php if (!empty($inv['customer_phone'])): ?><div>Contact : <?= h($inv['customer_phone']) ?></div><?php endif; ?>
        </td>
    </tr>
</table>

<table class="frame" style="border-top:none;">
    <colgroup>
        <col style="width:5%;">
        <col style="width:28%;">
        <col style="width:10%;">
        <col style="width:8%;">
        <col style="width:7%;">
        <col style="width:14%;">
        <col style="width:10%;">
        <col style="width:18%;">
    </colgroup>
    <tr style="background:#f7f7f7; font-weight:700;">
        <td class="cell b-r b-b c-center">Sl No.</td>
        <td class="cell b-r b-b c-center">Item Description</td>
        <td class="cell b-r b-b c-center">HSN Code</td>
        <td class="cell b-r b-b c-center">GST Rate</td>
        <td class="cell b-r b-b c-center">Qty</td>
        <td class="cell b-r b-b c-center">MRP<br>(Incl. of Tax)</td>
        <td class="cell b-r b-b c-center">Discount</td>
        <td class="cell b-b c-center">Amount</td>
    </tr>
    <?= $itemRowsHtml ?>

    <tr style="font-weight:700;">
        <td class="cell b-r b-t c-right" colspan="4">Total</td>
        <td class="cell b-r b-t c-center"><b><?= h($totalDisplayQty) ?></b></td>
        <td class="cell b-r b-t c-right"><b><?= money($totalMrpInclTax) ?></b></td>
        <td class="cell b-r b-t c-right"><b><?= money($totalDiscountApplied) ?></b></td>
        <td class="cell b-t c-right"><b><?= money($tableTotalAmount) ?></b></td>
    </tr>
</table>

<table class="frame" style="border-top:none;">
    <tr>
        <td class="cell b-r" style="width:68%;">
            <div class="small">Amount Chargeable (in words)</div>
            <div><b><?= h(numberToWords($grand_total)) ?></b></div>
            <div class="small" style="margin-top:2px;">Declaration: This invoice shows the actual price of goods and particulars are true and correct.</div>
            <div class="small" style="margin-top:2px;">
                <?php
                    $companyIds = [];
                    if (!empty($company_gstin)) $companyIds[] = 'GSTIN: ' . h($company_gstin);
                    if (!empty($company_cin)) $companyIds[] = 'CIN: ' . h($company_cin);
                    if (!empty($companyPan)) $companyIds[] = 'PAN: ' . h($companyPan);
                    echo implode(' | ', $companyIds);
                ?>
            </div>
            <div class="small" style="margin-top:4px;"><b>Terms &amp; Conditions</b></div>
            <div class="small">1. Goods once sold will not be taken back.</div>
            <div class="small">2. Subject to Tamil Nadu jurisdiction.</div>
            <div class="small">3. This is a computer generated invoice.</div>
            <div class="small" style="margin-top:2px;">E. &amp; O.E</div>
        </td>
        <td class="cell" style="width:32%;">
            <table style="width:100%;">
                <tr><td class="small">Products Subtotal (MRP)</td><td class="c-right"><?= money($totalMrpInclTax) ?></td></tr>
                <tr><td class="small"><?= h($discountLabel) ?></td><td class="c-right"><?= h($discountSummaryDisplay) ?></td></tr>
                <tr><td class="small">Products Total</td><td class="c-right"><?= money($tableTotalAmount) ?></td></tr>
                <tr><td class="small">Shipping (GST @ <?= h($shippingRateLabel) ?>)</td><td class="c-right"><?= money($shipping) ?></td></tr>
                <tr><td class="small">Taxable Value</td><td class="c-right"><?= money($summaryTaxable) ?></td></tr>
                <?php if ($isInterState): ?>
                    <tr><td class="small">IGST</td><td class="c-right"><?= money($igstAmount) ?></td></tr>
                <?php else: ?>
                    <tr><td class="small">CGST</td><td class="c-right"><?= money($cgstAmount) ?></td></tr>
                    <tr><td class="small">SGST</td><td class="c-right"><?= money($sgstAmount) ?></td></tr>
                <?php endif; ?>
                <tr><td class="b-t"><b>Grand Total</b></td><td class="c-right b-t"><b><?= money($grand_total) ?></b></td></tr>
            </table>
            <div style="height:8px;"></div>
            <div class="c-right"><b>for <?= h($company_name) ?></b></div>
            <div style="height:12px;"></div>
            <div class="c-right small">Authorised Signatory</div>
        </td>
    </tr>
    <tr>
        <td class="cell b-r b-t small">Customer's Seal and Signature</td>
        <td class="cell b-t c-center small">Authorised Signatory</td>
    </tr>
</table>

<table class="frame" style="border-top:none;">
    <colgroup>
        <col style="width:40%;">
        <col style="width:12%;">
        <col style="width:16%;">
        <col style="width:16%;">
        <col style="width:16%;">
    </colgroup>
    <tr style="background:#f7f7f7; font-weight:700;">
        <td class="cell b-r b-b c-center">Item</td>
        <td class="cell b-r b-b c-center">GST Rate</td>
        <td class="cell b-r b-b c-center">Taxable Value</td>
        <td class="cell b-r b-b c-center">GST Amount</td>
        <td class="cell b-b c-center">Invoice Value</td>
    </tr>
    <?= $auditRowsHtml ?>
</table>
<div class="c-center small" style="margin-top:2px;">SUBJECT TO CHITTOGARH JURISDICTION | Computer Generated Invoice</div>

</body>
</html>
<?php
$html = ob_get_clean();

// DOMPDF RENDER (Same as before)
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
        $filename = 'Tax-Invoice-'.$inv['invoice_number'].'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.basename($filename).'"');
        echo $dompdf->output();
        exit;
    } catch (Exception $e) { error_log('DOMPDF error: ' . $e->getMessage()); }
}

http_response_code(500);
echo "Error generating PDF. library not found.";
?>
