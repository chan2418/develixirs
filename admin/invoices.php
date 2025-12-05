<?php
// admin/invoices.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
// include __DIR__ . '/header.php';
include __DIR__ . '/layout/header.php';


// basic session ensure (header.php usually starts session, but be safe)
if (session_status() === PHP_SESSION_NONE) session_start();

// handle POST actions: mark cleared
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    // simple CSRF guard (header.php sets $_SESSION['csrf_token'])
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['flash_error'] = 'Invalid request.';
        header('Location: invoices.php');
        exit;
    }

    $action = $_POST['action'];
    if ($action === 'clear' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'cleared', cleared_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_success'] = 'Invoice marked as cleared.';
        header('Location: invoices.php');
        exit;
    }

    if ($action === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_success'] = 'Invoice deleted.';
        header('Location: invoices.php');
        exit;
    }
}

// read filters
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// build where
$where = [];
$params = [];

if ($q !== '') {
    // search invoice_number or order_number or customer name
    $where[] = "(i.invoice_number LIKE :q OR o.order_number LIKE :q OR o.customer_name LIKE :q)";
    $params[':q'] = "%{$q}%";
}
if ($status !== '') {
    $where[] = "i.status = :status";
    $params[':status'] = $status;
}
$whereSql = $where ? implode(' AND ', $where) : '1=1';

// total
try {
    $countSql = "SELECT COUNT(*) FROM invoices i JOIN orders o ON i.order_id = o.id WHERE {$whereSql}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $total = 0;
}

// pages
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;

// list query
try {
    $sql = "SELECT i.id, i.invoice_number, i.amount, i.status, i.created_at, i.cleared_at,
                   o.id AS order_id, o.order_number, o.customer_name
            FROM invoices i
            JOIN orders o ON i.order_id = o.id
            WHERE {$whereSql}
            ORDER BY i.created_at DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $invoices = [];
}

// helper: preserve query string
function preserve_qs($overrides = []) {
    $qs = $_GET;
    foreach ($overrides as $k => $v) $qs[$k] = $v;
    return '?'.http_build_query($qs);
}

// flash messages
$flash_err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
$flash_ok  = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']);

$page_title = 'Invoices';
?>
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
.page-wrap{max-width:1200px;margin:28px auto;padding:0 18px 60px;font-family:Inter,system-ui,Arial}
.card{background:#fff;padding:16px;border-radius:12px;border:1px solid #eef2f7;box-shadow:0 8px 30px rgba(2,6,23,0.04)}
.table{width:100%;border-collapse:collapse;font-size:14px}
.table th,.table td{padding:12px 14px;border-bottom:1px solid #f1f5f9;text-align:left;vertical-align:middle}
.table th{background:#fbfcfe;color:#475569;font-weight:700;font-size:13px}
.badge {display:inline-block;padding:6px 10px;border-radius:999px;font-weight:700;font-size:13px}
.badge.issued{background:#fffbeb;color:#92400e}
.badge.cleared{background:#ecfdf5;color:#065f46}
.small{font-size:13px;color:#64748b}
.actions a, .actions button { margin-right:8px; text-decoration:none; font-weight:700; color:#0b76ff; background:transparent; border:0; cursor:pointer; }
</style>

<div class="page-wrap">
  <div class="card" style="margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h2 style="margin:0;font-size:20px;font-weight:800">Invoices</h2>
        <div class="small">View, clear or print invoices.</div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <a href="orders.php" class="btn ghost">Back to Orders</a>
        <a href="invoice_create.php" class="btn primary">Create Invoice</a>
      </div>
    </div>
  </div>

  <?php if ($flash_err): ?>
    <div class="card" style="margin-bottom:12px;border-left:4px solid #ef4444;color:#b91c1c;"><?= htmlspecialchars($flash_err) ?></div>
  <?php endif; ?>
  <?php if ($flash_ok): ?>
    <div class="card" style="margin-bottom:12px;border-left:4px solid #10b981;color:#065f46;"><?= htmlspecialchars($flash_ok) ?></div>
  <?php endif; ?>

  <div class="card" style="margin-bottom:12px;">
    <form class="filter-row" method="get" style="display:flex;gap:8px;align-items:center;">
      <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search invoice, order or customer" class="input" style="padding:8px 10px;border-radius:8px;border:1px solid #e6eef7;background:#fbfdff">
      <select name="status" class="input" style="padding:8px 10px;border-radius:8px;border:1px solid #e6eef7;background:#fbfdff">
        <option value="">All statuses</option>
        <option value="issued" <?= $status==='issued' ? 'selected' : '' ?>>Issued</option>
        <option value="cleared" <?= $status==='cleared' ? 'selected' : '' ?>>Cleared</option>
      </select>
      <button class="btn primary" type="submit" style="padding:8px 12px;border-radius:8px;background:#0b76ff;color:#fff;font-weight:700;border:0">Filter</button>
      <a href="invoices.php" class="btn ghost" style="padding:8px 12px;border-radius:8px;border:1px solid #e6eef7;">Reset</a>

      <div style="margin-left:auto;" class="small">
        Showing <?= $total === 0 ? 0 : ($offset + 1) ?> - <?= min($total, $offset + count($invoices)) ?> of <?= $total ?>
      </div>
    </form>
  </div>

  <div class="card">
    <div style="overflow:auto;">
      <table class="table">
        <thead>
          <tr>
            <th style="width:56px">#</th>
            <th>Invoice</th>
            <th>Order</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Created</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($invoices)): ?>
            <tr><td colspan="8" class="small" style="padding:40px 14px;text-align:center;color:#64748b">No invoices found.</td></tr>
          <?php else: foreach ($invoices as $inv): ?>
            <tr>
              <td><?= (int)$inv['id'] ?></td>
              <td>
                <div style="font-weight:700;"><?= htmlspecialchars($inv['invoice_number']) ?></div>
                <div class="small">#<?= (int)$inv['id'] ?></div>
              </td>
              <td>
                <div style="font-weight:700;"><a href="order_view.php?id=<?= (int)$inv['order_id'] ?>"><?= htmlspecialchars($inv['order_number']) ?></a></div>
                <div class="small">Order ID: <?= (int)$inv['order_id'] ?></div>
              </td>
              <td><?= htmlspecialchars($inv['customer_name']) ?></td>
              <td style="font-weight:700;">₹ <?= number_format($inv['amount'], 2) ?></td>
              <td>
                <?php if ($inv['status'] === 'cleared'): ?>
                  <span class="badge cleared">Cleared</span>
                <?php else: ?>
                  <span class="badge issued">Issued</span>
                <?php endif; ?>
              </td>
              <td class="small"><?= htmlspecialchars(date('d M Y H:i', strtotime($inv['created_at']))) ?></td>
              <td style="text-align:right" class="actions">
                <a href="invoice_view.php?id=<?= (int)$inv['id'] ?>" class="btn" style="color:#0b76ff">View</a>
                <a href="generate_invoice_pdf.php?id=<?= (int)$inv['id'] ?>" class="btn" style="color:#4b5563">PDF</a>

                <?php if ($inv['status'] !== 'cleared'): ?>
                <form method="post" style="display:inline-block" onsubmit="return confirm('Mark invoice as cleared?')">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                  <input type="hidden" name="action" value="clear">
                  <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
                  <button type="submit" style="color:#059669;background:none;border:0;font-weight:700;cursor:pointer">Clear</button>
                </form>
                <?php endif; ?>

                <form method="post" style="display:inline-block" onsubmit="return confirm('Delete invoice?')">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
                  <button type="submit" style="color:#ef4444;background:none;border:0;font-weight:700;cursor:pointer">Delete</button>
                </form>

              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <div class="pagination" style="display:flex;gap:6px;align-items:center;justify-content:flex-end;margin-top:12px">
      <?php
        $start = max(1, $page - 3);
        $end = min($pages, $page + 3);
        if ($page > 1) echo '<a class="page-link" href="'. preserve_qs(['page'=>$page-1]) .'">Prev</a>';
        if ($start > 1) {
          echo '<a class="page-link" href="'. preserve_qs(['page'=>1]) .'">1</a>';
          if ($start > 2) echo '<span class="small">…</span>';
        }
        for ($p = $start; $p <= $end; $p++) {
          $cls = $p === $page ? 'page-link active' : 'page-link';
          echo '<a class="'. $cls .'" href="'. preserve_qs(['page'=>$p]) .'">'. $p .'</a>';
        }
        if ($end < $pages) {
          if ($end < $pages - 1) echo '<span class="small">…</span>';
          echo '<a class="page-link" href="'. preserve_qs(['page'=>$pages]) .'">'. $pages .'</a>';
        }
        if ($page < $pages) echo '<a class="page-link" href="'. preserve_qs(['page'=>$page+1]) .'">Next</a>';
      ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
