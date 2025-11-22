<?php
// admin/generate_label.php
// Renders a printable shipping label (HTML) similar to your invoice download page.
// Also exposes a direct sample PDF link (developer/test file) at /mnt/data/OD335927864916938100.pdf

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function h($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
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
      SELECT s.*, o.order_number, o.customer_name
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

// try reading shipment items if table exists (optional)
$items = [];
try {
    $it = $pdo->prepare("SELECT product_name, sku, qty, weight FROM shipment_items WHERE shipment_id = ? ORDER BY id ASC");
    $it->execute([$id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore if table doesn't exist
    $items = [];
}

// developer/test PDF (provided in environment)
$samplePdf = '/mnt/data/OD335927864916938100.pdf';
// You can also point to public/uploads/labels/<file> if you store label file in webroot and set $labelUrl accordingly
$labelUrl = null;
if (!empty($sh['label_file'])) {
    // attempt to resolve public path
    if (file_exists(__DIR__ . '/../public/uploads/labels/' . $sh['label_file'])) {
        $labelUrl = '/uploads/labels/' . rawurlencode($sh['label_file']);
    } elseif (file_exists(__DIR__ . '/../uploads/labels/' . $sh['label_file'])) {
        $labelUrl = '/uploads/labels/' . rawurlencode($sh['label_file']);
    }
}
// fallback to developer sample if no uploaded label
if ($labelUrl === null && file_exists($samplePdf)) {
    // developer note: your environment maps /mnt/data/... to a served URL in some setups
    $labelUrl = $samplePdf;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Shipping Label <?= h($sh['shipment_number']) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{--primary:#0b76ff;--muted:#64748b;--card-bg:#fff}
    body{font-family:Inter, system-ui, Arial, sans-serif;background:#f3f4f6;padding:18px}
    .page{max-width:900px;margin:18px auto;background:var(--card-bg);padding:22px;border-radius:10px;border:1px solid #e6eef7;box-shadow:0 8px 30px rgba(2,6,23,0.04)}
    .head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
    .brand{display:flex;gap:12px;align-items:center}
    .brand-logo{width:64px;height:64px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-weight:800;color:#334155}
    .title{font-size:18px;font-weight:800;margin:0}
    .meta{color:var(--muted);font-size:13px}
    .actions{display:flex;gap:8px}
    .btn{padding:9px 12px;border-radius:8px;font-weight:700;text-decoration:none;display:inline-block}
    .btn.primary{background:var(--primary);color:#fff}
    .btn.ghost{background:transparent;border:1px solid #e6eef7;color:#0f172a}
    .grid{display:grid;grid-template-columns:1fr 320px;gap:18px;margin-top:18px}
    .panel{background:#fbfcfe;border:1px solid #f1f5f9;padding:12px;border-radius:8px}
    .row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #f1f5f9}
    .row:last-child{border-bottom:0}
    .small{color:var(--muted);font-size:13px}
    table.items{width:100%;border-collapse:collapse;margin-top:12px}
    table.items th, table.items td{padding:8px;border:1px solid #eef2f7;text-align:left;font-size:14px}
    .center{text-align:center}
    @media (max-width:900px){ .grid{grid-template-columns:1fr} .actions{flex-wrap:wrap} }
    /* print styles */
    @media print {
      body{background:#fff;padding:0}
      .page{box-shadow:none;border:0;border-radius:0;margin:0;padding:0}
      .no-print{display:none}
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="head">
      <div class="brand">
        <div class="brand-logo">D</div>
        <div>
          <div class="title">DEVELIXIR — Shipping Label</div>
          <div class="meta">Shipment #: <strong><?= h($sh['shipment_number']) ?></strong></div>
          <div class="meta">Order #: <a href="order_view.php?id=<?= (int)$sh['order_id'] ?>"><?= h($sh['order_number']) ?></a></div>
        </div>
      </div>

      <div class="no-print">
        <div class="meta center" style="margin-bottom:8px">Created: <?= h(date('d M Y H:i', strtotime($sh['created_at'] ?? 'now'))) ?></div>
        <div class="actions">
          <a class="btn primary" onclick="window.print();return false;">Download PDF</a>

          <?php if ($labelUrl): ?>
            <!-- direct link (forces download in browsers when using download attr, otherwise preview) -->
            <a class="btn ghost" href="<?= h($labelUrl) ?>" download>Download Raw PDF</a>
            <a class="btn" href="<?= h($labelUrl) ?>" target="_blank">Preview Raw PDF</a>
          <?php else: ?>
            <a class="btn ghost" onclick="alert('No label file available.')">No label file</a>
          <?php endif; ?>

          <a class="btn" href="shipments.php">Back</a>
        </div>
      </div>
    </div>

    <hr style="margin:14px 0;border:none;border-top:1px solid #f1f5f9">

    <div class="grid">
      <div>
        <div style="display:flex;justify-content:space-between;gap:12px">
          <div>
            <div style="font-weight:800">Ship To</div>
            <div class="small" style="margin-top:6px">
              <?php
                // if you stored recipient address in shipments table, use it; otherwise show order customer
                $recipient = $sh['recipient_name'] ?? $sh['customer_name'] ?? '-';
                $recipient_addr = $sh['recipient_address'] ?? $sh['customer_address'] ?? '-';
              ?>
              <div style="font-weight:700"><?= h($recipient) ?></div>
              <div><?= nl2br(h($recipient_addr)) ?></div>
              <?php if (!empty($sh['recipient_phone']) || !empty($sh['customer_phone'])): ?>
                <div style="margin-top:6px">Phone: <?= h($sh['recipient_phone'] ?? $sh['customer_phone'] ?? '-') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div style="text-align:right">
            <div style="font-weight:800;font-size:18px">Carrier</div>
            <div class="small" style="margin-top:6px"><?= h($sh['carrier'] ?: '-') ?></div>

            <div style="margin-top:12px;font-weight:800;font-size:18px">Tracking</div>
            <div style="font-size:16px;margin-top:6px"><?= h($sh['tracking_number'] ?: '-') ?></div>
          </div>
        </div>

        <div style="margin-top:14px" class="panel">
          <div class="row"><div>Shipment #</div><div class="small"><?= h($sh['shipment_number']) ?></div></div>
          <div class="row"><div>Order</div><div class="small"><?= h($sh['order_number']) ?></div></div>
          <div class="row"><div>Method</div><div class="small"><?= h($sh['shipping_method'] ?: '-') ?></div></div>
          <div class="row"><div>Weight</div><div class="small"><?= h($sh['weight'] ?: '-') ?> kg</div></div>
          <div class="row"><div>Shipping Cost</div><div class="small">₹ <?= money($sh['shipping_cost'] ?? 0) ?></div></div>
        </div>

        <?php if (!empty($items)): ?>
          <h4 style="margin-top:12px">Items</h4>
          <table class="items">
            <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Weight</th></tr></thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?= h($it['product_name']) ?></td>
                  <td><?= h($it['sku']) ?></td>
                  <td><?= (int)$it['qty'] ?></td>
                  <td><?= h($it['weight']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

        <div style="margin-top:18px" class="small">
          <strong>Notes:</strong><br>
          <?= nl2br(h($sh['notes'] ?? 'No special notes.')) ?>
        </div>
      </div>

      <div>
        <div class="panel" style="text-align:center">
          <div style="font-weight:900;font-size:20px;margin-bottom:8px">SHIP FROM</div>
          <div style="font-weight:700">DEVELIXIR</div>
          <div class="small" style="margin-top:6px">
            No:6, 3rd Cross Street, Kamatchiamman Garden,<br>
            Sethukkarai, Gudiyatham-632602, Vellore, Tamil Nadu, INDIA<br>
            Phone: 9500650454<br>
            Email: sales@develixirs.com
          </div>

          <div style="margin-top:18px;font-weight:800;font-size:16px">Label</div>
          <div style="margin-top:8px" class="small">Scan/attach shipping label here</div>

          <?php if ($labelUrl): ?>
            <div style="margin-top:12px">
              <a class="btn ghost" href="<?= h($labelUrl) ?>" download>Download Raw PDF</a>
              <a class="btn" href="<?= h($labelUrl) ?>" target="_blank">Preview Raw PDF</a>
            </div>
          <?php else: ?>
            <div style="margin-top:12px" class="small">No stored label PDF found. Use 'Download PDF' to save this view as PDF.</div>
          <?php endif; ?>
        </div>

        <div style="margin-top:12px" class="panel">
          <div style="font-weight:800;margin-bottom:8px">Status</div>
          <div class="small"><?= h(ucfirst($sh['status'] ?? '-')) ?></div>
          <div style="margin-top:10px;font-size:12px;color:#6b7280">Created: <?= h(date('d M Y H:i', strtotime($sh['created_at'] ?? 'now'))) ?></div>
        </div>
      </div>
    </div> <!-- grid -->

    <div class="small" style="text-align:center;margin-top:14px;color:#94a3b8">
      Tip: Click "Download PDF" then choose "Save as PDF" in the browser print dialog to save/print the label.
    </div>
  </div>
</body>
</html>