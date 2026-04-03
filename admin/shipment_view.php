<?php
// admin/shipment_view.php
// Clean, professional shipment view with label upload/download + status actions
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// helpers
function h($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }
function status_badge($s){
    $map = [
        'pending' => ['bg'=>'bg-amber-50','text'=>'text-amber-700','label'=>'Pending'],
        'label_created' => ['bg'=>'bg-indigo-50','text'=>'text-indigo-700','label'=>'Label Created'],
        'in_transit' => ['bg'=>'bg-teal-50','text'=>'text-teal-700','label'=>'In Transit'],
        'delivered' => ['bg'=>'bg-green-50','text'=>'text-green-700','label'=>'Delivered'],
        'returned' => ['bg'=>'bg-red-50','text'=>'text-red-700','label'=>'Returned'],
        'cancelled' => ['bg'=>'bg-red-50','text'=>'text-red-700','label'=>'Cancelled'],
    ];
    $s = strtolower((string)$s);
    $info = $map[$s] ?? ['bg'=>'bg-slate-50','text'=>'text-slate-700','label'=>ucfirst($s)];
    return '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ' 
           . $info['bg'] . ' ' . $info['text'] . '">' . h($info['label']) . '</span>';
}

// page id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: shipments.php'); exit; }

// fetch shipment + order
try {
    $stmt = $pdo->prepare("SELECT s.*, o.order_number, o.customer_name FROM shipments s JOIN orders o ON s.order_id=o.id WHERE s.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $sh = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Shipment fetch error: ' . $e->getMessage());
    $sh = false;
}

$page_title = 'Shipment #' . ($sh['shipment_number'] ?? $id);
include __DIR__ . '/layout/header.php';

if (!$sh) {
    echo '<div class="page-wrap"><div class="card"><h2>Shipment not found</h2><p>The requested shipment does not exist or there was an error fetching it.</p><a href="shipments.php" class="btn mt-4">Back</a></div></div>';
    include __DIR__ . '/layout/footer.php';
    exit;
}

// resolve label URL: prefer uploaded file (public/uploads/labels/<file>) else fallback to developer file
// NOTE: change $publicLabelBase to your actual public uploads base if different.
$publicLabelBase = '/uploads/labels'; // change if you store labels elsewhere
$labelUrl = null;
if (!empty($sh['label_file']) && file_exists(__DIR__ . '/../public/uploads/labels/' . $sh['label_file'])) {
    $labelUrl = $publicLabelBase . '/' . rawurlencode($sh['label_file']);
} elseif (!empty($sh['label_file']) && file_exists(__DIR__ . '/../uploads/labels/' . $sh['label_file'])) {
    // alternative storage location
    $labelUrl = '/uploads/labels/' . rawurlencode($sh['label_file']);
} else {
    // fallback developer/test PDF you uploaded (local test path)
    // Developer note: tool will map /mnt/data/... into served URL when needed.
    $labelUrl = '/mnt/data/OD335927864916938100.pdf';
}

// small helper for formatted date/time
function fmt_dt($dt){
    if (empty($dt)) return '-';
    $t = strtotime($dt);
    if ($t === false) return h($dt);
    return date('d M Y H:i', $t);
}
?>
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
.page-wrap{max-width:980px;margin:28px auto;padding:20px}
.card{background:#fff;padding:22px;border-radius:12px;border:1px solid #eef2f7;box-shadow:0 8px 24px rgba(2,6,23,0.04)}
.header-flex{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.meta{color:#64748b;font-size:13px}
.grid-2{display:grid;grid-template-columns:1fr 360px;gap:18px;margin-top:18px}
.info-block{padding:12px;border-radius:10px;background:#fbfcfe;border:1px solid #f1f5f9}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #f1f5f9}
.info-row:last-child{border-bottom:none}
.actions{display:flex;gap:8px;align-items:center}
.btn {padding:10px 12px;border-radius:10px;font-weight:700;cursor:pointer;border:0}
.btn.primary{background:#0b76ff;color:#fff}
.btn.ghost{background:transparent;border:1px solid #e6eef7;color:#0f172a}
.small{font-size:13px;color:#64748b}
.badge{display:inline-block;padding:6px 10px;background:#eef2ff;color:#4f46e5;font-weight:700;border-radius:999px;font-size:13px}
.form-row{display:flex;gap:10px}
.form-row input[type="text"], .form-row input[type="number"], .form-row input[type="file"]{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7}
@media(max-width:900px){ .grid-2{grid-template-columns:1fr} .actions{flex-wrap:wrap} }
</style>

<div class="page-wrap">
  <div class="card">
    <div class="header-flex">
      <div>
        <h2 style="margin:0;font-weight:800">Shipment <span style="color:#0f172a"><?= h($sh['shipment_number']) ?></span></h2>
        <div class="meta" style="margin-top:6px">Order: <a href="order_view.php?id=<?= (int)$sh['order_id'] ?>"><?= h($sh['order_number']) ?></a> &nbsp; | &nbsp; Customer: <?= h($sh['customer_name']) ?></div>
      </div>

      <div style="text-align:right">
    <div style="margin-bottom:8px"><?= status_badge($sh['status']) ?></div>
    <div class="actions">
        <?php if (!empty($labelUrl) || true): // always use generated label endpoint ?>
        <a href="label_pdf.php?id=<?= (int)$sh['id'] ?>" class="btn ghost" download>Download Label</a>
        <a href="generate_label.php?id=<?= (int)$sh['id'] ?>&preview=1" target="_blank" class="btn">Preview</a>
        <?php else: ?>
        <button class="btn ghost" onclick="window.location.href='label_pdf.php?id=<?= (int)$sh['id'] ?>'">Download Label</button>
        <?php endif; ?>
        <a href="shipments.php" class="btn">Back</a>
    </div>
    </div>
    </div>

    <div class="grid-2">
      <div>
        <div class="info-block">
          <div class="info-row"><div><strong>Carrier</strong></div><div class="small"><?= h($sh['carrier'] ?: '-') ?></div></div>
          <div class="info-row"><div><strong>Tracking #</strong></div><div class="small"><?= h($sh['tracking_number'] ?: '-') ?></div></div>
          <div class="info-row"><div><strong>Shipment Date</strong></div><div class="small"><?= h(fmt_dt($sh['shipment_date'])) ?></div></div>
          <div class="info-row"><div><strong>Method</strong></div><div class="small"><?= h($sh['shipping_method'] ?: '-') ?></div></div>
          <div class="info-row"><div><strong>Shipping Cost</strong></div><div class="small">₹ <?= money($sh['shipping_cost'] ?? 0) ?></div></div>
          <div class="info-row"><div><strong>Weight</strong></div><div class="small"><?= h($sh['weight'] ?? '-') ?> kg</div></div>
          <div class="info-row"><div><strong>Notes</strong></div><div class="small"><?= nl2br(h($sh['notes'] ?: '-')) ?></div></div>
        </div>

        <div style="margin-top:18px" class="info-block">
          <div style="font-weight:700;margin-bottom:8px">Timeline</div>
          <div class="info-row"><div>Created</div><div class="small"><?= h(fmt_dt($sh['created_at'])) ?></div></div>
          <div class="info-row"><div>Shipped at</div><div class="small"><?= h(fmt_dt($sh['shipped_at'])) ?></div></div>
        </div>
      </div>

      <div>
        <div class="info-block">
          <div style="font-weight:700;margin-bottom:10px">Update shipment</div>

          <form method="post" action="shipments_action.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int)$sh['id'] ?>">

            <div class="form-row" style="margin-bottom:10px">
              <input type="text" name="carrier" placeholder="Carrier" value="<?= h($sh['carrier']) ?>" />
              <input type="text" name="tracking_number" placeholder="Tracking number" value="<?= h($sh['tracking_number']) ?>" />
            </div>

            <div class="form-row" style="margin-bottom:10px">
              <input type="text" name="shipping_method" placeholder="Shipping method" value="<?= h($sh['shipping_method']) ?>" />
              <input type="number" step="0.01" name="shipping_cost" placeholder="Shipping cost" value="<?= h($sh['shipping_cost']) ?>" />
            </div>

            <div class="form-row" style="margin-bottom:10px">
              <input type="datetime-local" name="shipment_date" value="<?= !empty($sh['shipment_date']) ? date('Y-m-d\TH:i', strtotime($sh['shipment_date'])) : '' ?>" />
            </div>

            <div style="margin-bottom:10px">
              <label class="small">Upload label PDF (optional)</label>
              <input type="file" name="label_pdf" accept="application/pdf" />
            </div>

            <div style="display:flex;gap:8px;align-items:center">
              <button type="submit" class="btn primary">Save changes</button>

              <?php if (($sh['status'] ?? '') !== 'delivered'): ?>
                <button type="submit" name="action" value="mark_shipped" formaction="shipments_action.php" formmethod="post" class="btn" style="background:#059669;color:#fff">Mark Shipped</button>
              <?php endif; ?>

              <?php if (($sh['status'] ?? '') !== 'delivered'): ?>
                <button type="submit" name="action" value="mark_delivered" formaction="shipments_action.php" formmethod="post" class="btn" style="background:#6b7280;color:#fff">Mark Delivered</button>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <div style="margin-top:12px" class="info-block">
          <div style="font-weight:700;margin-bottom:8px">Label file</div>
          <?php if (!empty($sh['label_file'])): ?>
            <div class="small">Stored file: <?= h($sh['label_file']) ?></div>
            <div class="mt-3">
            <div class="mt-3">
              <a href="label_pdf.php?id=<?= (int)$sh['id'] ?>" class="btn ghost" download>Download</a>
              <a href="generate_label.php?id=<?= (int)$sh['id'] ?>&preview=1" target="_blank" class="btn">Preview</a>
            </div>
          <?php else: ?>
            <div class="small">No label uploaded. You can generate a label PDF below.</div>
            <div class="mt-3">
              <a href="label_pdf.php?id=<?= (int)$sh['id'] ?>" class="btn ghost" download>Download Label</a>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
