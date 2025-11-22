<?php
// admin/invoice_view.php
// Fixed / hardened professional invoice view with Discount & Tax support
// - Safe DB handling and clear fallbacks
// - Computes subtotal from invoice_items or invoice.amount/order total
// - Renders discount, other_discount, shipping, other_fees, total_fee, tax_rate/tax_amount
// - Sets $page_title before including header to avoid "headers already sent" issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }

// Get invoice id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    // redirect to invoices list if id missing/invalid
    header('Location: invoices.php');
    exit;
}

// fetch invoice + order (safe try/catch)
$inv = false;
try {
    $sql = "
        SELECT i.*, 
               o.order_number, 
               o.customer_name, 
               o.customer_address, 
               o.total_amount AS order_total_amount,
               o.payment_status, 
               o.order_status, 
               o.created_at AS order_created_at
        FROM invoices i
        JOIN orders o ON i.order_id = o.id
        WHERE i.id = ? LIMIT 1
    ";
    // $stmt = $pdo->prepare($sql);
    // $stmt->execute([$id]);
    // $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("
        SELECT i.*,
            o.order_number,
            o.customer_name,
            o.total_amount,
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
    // log server-side and show friendly message
    error_log('Invoice fetch error: ' . $e->getMessage());
    $inv = false;
}

if (!$inv) {
    // Render header with "Invoice not found" message
    $page_title = 'Invoice not found';
    include __DIR__ . '/layout/header.php';
    echo '<div class="page-wrap"><div class="card"><h2>Invoice not found</h2><p>The requested invoice does not exist or there was an error fetching it.</p></div></div>';
    include __DIR__ . '/layout/footer.php';
    exit;
}

// Set page title before including header
$page_title = 'Invoice: ' . h($inv['invoice_number'] ?? ('#' . $id));
include __DIR__ . '/layout/header.php';

// --- Fetch invoice items (if table exists) ---
$items = [];
try {
    $it = $pdo->prepare("SELECT description, qty, unit_price, amount FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
    $it->execute([$id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // invoice_items may not exist or be empty — that's OK
    $items = [];
}

// --- Numeric invoice columns with safe defaults ---
$discount            = isset($inv['discount'])         ? (float)$inv['discount']         : 0.00;
$other_discount      = isset($inv['other_discount'])   ? (float)$inv['other_discount']   : 0.00;
$shipping            = isset($inv['shipping_charge'])  ? (float)$inv['shipping_charge']  : 0.00;
$tax_rate            = isset($inv['tax_rate'])         ? (float)$inv['tax_rate']         : 0.00; // percent
$tax_amount_explicit = (isset($inv['tax_amount']) && $inv['tax_amount'] !== null && $inv['tax_amount'] !== '') ? (float)$inv['tax_amount'] : null;
$other_fees          = isset($inv['other_fees'])       ? (float)$inv['other_fees']       : 0.00;
$total_fee           = isset($inv['total_fee'])        ? (float)$inv['total_fee']        : 0.00;

// --- Subtotal calculation: sum invoice_items OR fallback to invoice.amount or order_total_amount ---
$subtotal = 0.0;
if (!empty($items)) {
    foreach ($items as $it) {
        // ensure amount numeric
        $subtotal += (float)$it['amount'];
    }
} else {
    if (!empty($inv['amount'])) $subtotal = (float)$inv['amount'];
    elseif (!empty($inv['order_total_amount'])) $subtotal = (float)$inv['order_total_amount'];
    else $subtotal = 0.0;
}

// --- Taxable base and tax value calculation ---
$taxable_base = max(0.0, $subtotal - $discount - $other_discount);

// tax value: prefer explicit tax_amount if present, otherwise compute from tax_rate
if ($tax_amount_explicit !== null) {
    $tax_value = round($tax_amount_explicit, 2);
} else {
    $tax_value = round($taxable_base * ($tax_rate / 100.0), 2);
}

// --- Final total ---
$total = $subtotal - $discount - $other_discount + $other_fees + $shipping + $tax_value + $total_fee;
if ($total < 0) $total = 0.0;

// --- PDF resolution (fallback to developer file if not present in uploads) ---
$pdfPath = null;
$possibleLocal = __DIR__ . '/../uploads/invoices/invoice_' . $inv['id'] . '.pdf';
if (file_exists($possibleLocal)) {
    $pdfPath = '/uploads/invoices/invoice_' . $inv['id'] . '.pdf';
} elseif (!empty($inv['pdf_file']) && file_exists(__DIR__ . '/../uploads/invoices/' . $inv['pdf_file'])) {
    $pdfPath = '/uploads/invoices/' . $inv['pdf_file'];
} else {
    // developer/test fallback — move to webroot/uploads if you want it publicly available
    $pdfPath = '/mnt/data/OD335927864916938100.pdf';
}

?>
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
/* (same polished styles as before) */
.page-wrap { max-width:1000px; margin:30px auto; padding:0 18px 60px; font-family:Inter,system-ui,Arial; color:#0f172a; }
.card { background:#fff; padding:22px; border-radius:12px; border:1px solid #eef2f7; box-shadow:0 10px 30px rgba(2,6,23,0.04); }
.invoice-head { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; }
.brand { display:flex; gap:14px; align-items:center; }
.brand-logo { width:68px; height:68px; border-radius:8px; overflow:hidden; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-weight:800; color:#334155 }
.h1 { font-size:20px; font-weight:800; margin:0; }
.subtle { color:#64748b; font-size:13px; }
.bill-grid { display:grid; grid-template-columns: 1fr 1fr; gap:18px; margin-top:18px; }
.section-title { font-weight:700; margin-bottom:8px; color:#0f172a }
.details { color:#475569; font-size:14px; line-height:1.5 }
.meta-row { display:flex; gap:10px; align-items:center; color:#94a3b8; font-size:13px; margin-top:4px; }

.table { width:100%; border-collapse:collapse; margin-top:20px; font-size:14px; }
.table th, .table td { padding:12px 14px; border-bottom:1px solid #f1f5f9; text-align:left; vertical-align:middle; }
.table thead th { background:#fbfcfe; color:#475569; font-weight:700; font-size:13px; }
.table tfoot td { padding:10px 14px; font-weight:800; }

.summary { width:360px; margin-left:auto; border-radius:8px; padding:12px; background:#fafafa; border:1px solid #f1f5f9; }
.summary .row { display:flex; justify-content:space-between; padding:6px 0; color:#475569; }
.summary .row.total { font-weight:900; font-size:18px; color:#0f172a; }

.actions { display:flex; gap:10px; align-items:center; }
.btn { padding:10px 12px; border-radius:10px; font-weight:700; cursor:pointer; border:0; }
.btn.primary { background:#0b76ff; color:#fff; }
.btn.ghost { background:transparent; border:1px solid #e6eef7; color:#0f172a; }
.badge { display:inline-block; padding:6px 10px; background:#eef2ff; color:#4f46e5; font-weight:700; border-radius:999px; font-size:13px; }

.right { text-align:right; }
.small { font-size:13px; color:#64748b; }

@media(max-width:900px) {
  .bill-grid { grid-template-columns: 1fr; }
  .invoice-head { flex-direction:column; align-items:flex-start; }
  .summary { width:100%; margin-left:0; margin-top:14px; }
}
</style>

<div class="page-wrap">
  <div class="card">
    <div class="invoice-head">
      <div class="brand">
        <div class="brand-logo">D</div>
        <div>
          <div class="h1">DEVELIXIR</div>
          <div class="subtle">Invoice</div>
        </div>
      </div>

      <div class="right">
        <div class="small">Invoice</div>
        <div style="font-weight:800;font-size:18px"><?= h($inv['invoice_number']) ?></div>
        <div class="meta-row">
          <div class="small">Created: <?= h(date('d M Y H:i', strtotime($inv['created_at'] ?? $inv['order_created_at'] ?? date('Y-m-d H:i')))) ?></div>
          <div class="badge"><?= h(ucfirst($inv['status'] ?? 'issued')) ?></div>
        </div>
      </div>
    </div>

    <div class="bill-grid">
      <div>
        <div class="section-title">Bill To</div>
        <div class="details">
          <?= nl2br(h($inv['customer_name'] ?: '—')) ?>
          <?php if (!empty($inv['customer_address'])): ?>
            <div class="small" style="margin-top:8px"><?= nl2br(h($inv['customer_address'])) ?></div>
          <?php endif; ?>
          <div class="small" style="margin-top:8px">Order: <a href="order_view.php?id=<?= (int)$inv['order_id'] ?>"><?= h($inv['order_number']) ?></a></div>
        </div>
      </div>

      <div class="right">
        <div class="section-title">Summary</div>
        <div class="details">
          <div>Invoice Date: <strong><?= h(date('d M Y', strtotime($inv['created_at'] ?? $inv['order_created_at'] ?? date('Y-m-d')))) ?></strong></div>
          <div style="margin-top:6px">Payment status: <strong><?= h($inv['payment_status'] ?? '-') ?></strong></div>
        </div>
      </div>
    </div>

    <!-- Items -->
    <table class="table" aria-label="Invoice items">
      <thead>
        <tr>
          <th style="width:58%;">Description</th>
          <th style="width:10%;">Qty</th>
          <th style="width:16%;" class="right">Unit</th>
          <th style="width:16%;" class="right">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($items)): ?>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= h($it['description']) ?></td>
              <td><?= h($it['qty']) ?></td>
              <td class="right">₹ <?= money($it['unit_price']) ?></td>
              <td class="right">₹ <?= money($it['amount']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td>Order <?= h($inv['order_number']) ?> — goods / services</td>
            <td>1</td>
            <td class="right">₹ <?= money($subtotal) ?></td>
            <td class="right">₹ <?= money($subtotal) ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Summary -->
    <div style="display:flex; gap:20px; align-items:flex-start; margin-top:18px; flex-wrap:wrap;">
      <div style="flex:1; min-width:260px;" class="small">
        <div><strong>Notes</strong></div>
        <div style="margin-top:6px">Thank you for your business. If you have questions about this invoice, contact accounts@develixir.example</div>
      </div>

      <div class="summary" role="status" aria-live="polite">
        <div class="row"><div>Subtotal</div><div class="right">₹ <?= money($subtotal) ?></div></div>

        <?php if ($discount > 0): ?>
          <div class="row"><div>Discount</div><div class="right">- ₹ <?= money($discount) ?></div></div>
        <?php endif; ?>

        <?php if ($other_discount > 0): ?>
          <div class="row"><div>Other Discount</div><div class="right">- ₹ <?= money($other_discount) ?></div></div>
        <?php endif; ?>

        <?php if ($other_fees > 0): ?>
          <div class="row"><div>Other Fees</div><div class="right">₹ <?= money($other_fees) ?></div></div>
        <?php endif; ?>

        <?php if ($shipping > 0): ?>
          <div class="row"><div>Shipping</div><div class="right">₹ <?= money($shipping) ?></div></div>
        <?php endif; ?>

        <?php if ($total_fee > 0): ?>
          <div class="row"><div>Total Fee</div><div class="right">₹ <?= money($total_fee) ?></div></div>
        <?php endif; ?>

        <div class="row"><div>Tax <?= $tax_rate ? '(' . money($tax_rate) . '%)' : '' ?></div><div class="right">₹ <?= money($tax_value) ?></div></div>

        <div class="row total" style="margin-top:8px;">
          <div>Total</div>
          <div class="right">₹ <?= money($total) ?></div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;gap:12px;">
      <div class="small">
        <div><strong>Payment instructions</strong></div>
        <div style="margin-top:6px">Please clear payment within agreed terms. For support contact accounts@develixir.example</div>
      </div>

      <div class="actions">
        <?php if ($pdfPath): ?>
          <!-- <a href="<?= h($pdfPath) ?>" class="btn ghost" download>Download PDF</a> -->
           <a href="/admin/generate_invoice_pdf.php?id=<?= (int)$inv['id'] ?>" class="btn ghost">Download PDF</a>
        <?php else: ?>
          <button class="btn ghost" onclick="alert('No PDF available. You can print or generate a PDF from this page.')">Download PDF</button>
        <?php endif; ?>

        <button onclick="window.print()" class="btn primary">Print Invoice</button>

        <?php if (($inv['status'] ?? '') !== 'cleared'): ?>
          <form method="post" action="invoices.php" onsubmit="return confirm('Mark invoice as cleared?');" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="action" value="clear">
            <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
            <button type="submit" class="btn" style="background:#059669;color:#fff;border-radius:10px;padding:10px 12px;font-weight:800">Mark as cleared</button>
          </form>
        <?php else: ?>
          <div class="small">Cleared at: <?= h($inv['cleared_at'] ?? '-') ?></div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>