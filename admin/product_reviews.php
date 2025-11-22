<?php
// admin/product_reviews.php
// Product reviews admin list with search, status filters, approve/reject and delete actions

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// CSRF token helper
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// === Handle POST actions (approve/reject/delete) ===
$flash = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && !empty($_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        $flash[] = ['error' => 'Invalid CSRF token.'];
    } else {
        $action = $_POST['action'];
        $rid = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;

        if ($rid <= 0) {
            $flash[] = ['error' => 'Invalid review id.'];
        } else {
            try {
                if ($action === 'delete') {
                    $stmt = $pdo->prepare("DELETE FROM product_reviews WHERE id = ? LIMIT 1");
                    $stmt->execute([$rid]);
                    $flash[] = ['success' => 'Review deleted.'];
                } elseif ($action === 'approve' || $action === 'reject') {
                    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
                    $stmt = $pdo->prepare("UPDATE product_reviews SET status = ?, moderated_at = NOW() WHERE id = ?");
                    $stmt->execute([$newStatus, $rid]);
                    $flash[] = ['success' => 'Review status updated.'];
                } else {
                    $flash[] = ['error' => 'Unknown action.'];
                }
            } catch (Exception $e) {
                $flash[] = ['error' => 'DB error: ' . $e->getMessage()];
            }
        }
    }
    // After handling POST we redirect to avoid resubmits (preserve GET querystring)
    $qs = $_GET ? ('?' . http_build_query($_GET)) : '';
    header('Location: product_reviews.php' . $qs);
    exit;
}

// === Read query params ===
$q = trim($_GET['q'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// allowed status values
$allowed_status = ['pending','approved','rejected'];

// build where clause safely
$where = [];
$params = [];

if ($q !== '') {
    // search product name, review text, or reviewer name/email
    $where[] = "(p.name LIKE :q OR r.comment LIKE :q OR r.reviewer_name LIKE :q OR r.reviewer_email LIKE :q)";
    $params[':q'] = "%{$q}%";
}
if ($status_filter !== '' && in_array(strtolower($status_filter), $allowed_status, true)) {
    $where[] = "r.status = :status";
    $params[':status'] = $status_filter;
}
$whereSql = $where ? implode(' AND ', $where) : '1=1';

// total count
try {
    $countSql = "SELECT COUNT(*) FROM product_reviews r LEFT JOIN products p ON r.product_id = p.id WHERE {$whereSql}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $total = 0;
}

// pagination math
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;

// fetch rows with product info
$listSql = "SELECT r.*, p.name AS product_name, p.sku AS product_sku
            FROM product_reviews r
            LEFT JOIN products p ON r.product_id = p.id
            WHERE {$whereSql}
            ORDER BY r.created_at DESC
            LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($listSql);

// bind dynamic params
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// helper: preserve qs for links
function preserve_qs($overrides = []) {
    $qs = $_GET;
    foreach ($overrides as $k => $v) $qs[$k] = $v;
    return '?' . http_build_query($qs);
}

// small badge helper
function status_badge($s) {
    $map = [
        'pending' => ['bg'=>'#fff7ed','color'=>'#c2410c','label'=>'Pending'],
        'approved' => ['bg'=>'#ecfdf5','color'=>'#059669','label'=>'Approved'],
        'rejected' => ['bg'=>'#fff1f2','color'=>'#991b1b','label'=>'Rejected'],
    ];
    $s = strtolower((string)$s);
    $info = $map[$s] ?? ['bg'=>'#f8fafc','color'=>'#334155','label'=>ucfirst($s)];
    return '<span style="display:inline-block;padding:6px 10px;border-radius:999px;background:'.htmlspecialchars($info['bg']).';color:'.htmlspecialchars($info['color']).';font-weight:700;font-size:13px;">'.htmlspecialchars($info['label']).'</span>';
}

// include layout header (adjust path to your project)
include __DIR__ . '/layout/header.php';
?>

<link rel="stylesheet" href="/assets/css/admin.css">
<style>
.page-wrap { max-width:1200px; margin:28px auto; padding:0 18px 60px; font-family:Inter,system-ui,Arial; color:#0f172a; }
.header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
.h-title { font-size:20px; font-weight:800; margin:0; }
.h-sub { color:#64748b; margin:0; font-size:13px; }
.card { background:#fff; padding:16px; border-radius:12px; border:1px solid #e6eef7; box-shadow:0 8px 30px rgba(2,6,23,0.04); }
.filter-row { display:flex; gap:8px; align-items:center; margin-bottom:12px; flex-wrap:wrap; }
.input, select { padding:8px 10px; border-radius:8px; border:1px solid #e6eef7; background:#fbfdff; font-size:14px; }
.btn { padding:8px 12px; border-radius:8px; font-weight:700; cursor:pointer; border:0; }
.btn.primary { background:#0b76ff; color:#fff; }
.btn.ghost { background:#fff; border:1px solid #e6eef7; color:#0f172a; }
.table { width:100%; border-collapse:collapse; font-size:14px; }
.table th, .table td { padding:12px 14px; border-bottom:1px solid #f1f5f9; text-align:left; vertical-align:middle; }
.table th { background:#fbfcfe; color:#475569; font-weight:700; font-size:13px; }
.row-hover tbody tr:hover { background:#fbfdff; }
.small { font-size:13px; color:#64748b; }
.actions a, .actions form { margin-right:8px; display:inline-block; }
.icon-btn { display:inline-flex; align-items:center; justify-content:center; padding:8px 10px; border-radius:8px; border:1px solid #eef2f7; background:#fff; cursor:pointer; font-weight:700; }
.pagination { display:flex; gap:6px; align-items:center; justify-content:flex-end; margin-top:12px; flex-wrap:wrap; }
.page-link { padding:6px 8px; border-radius:6px; text-decoration:none; background:#f3f6fb; color:#0b1220; }
.page-link.active { background:#0b76ff; color:#fff; }
</style>

<div class="page-wrap">
  <div class="header">
    <div>
      <h1 class="h-title">Product Reviews</h1>
      <p class="h-sub">Moderate and manage product reviews left by customers.</p>
    </div>

    <div style="display:flex;gap:8px;align-items:center;">
      <a href="/admin/products.php" class="btn ghost">← Back to Products</a>
      <a href="/admin/add_product.php" class="btn primary">+ Add Product</a>
    </div>
  </div>

  <?php if (!empty($flash)): ?>
    <?php foreach ($flash as $f): if (!empty($f['error'])): ?>
      <div class="card" style="margin-bottom:12px; border-left:4px solid #ef4444;"><strong style="color:#b91c1c;">Error</strong><div class="small" style="margin-top:8px;"><?php echo htmlspecialchars($f['error']); ?></div></div>
    <?php elseif (!empty($f['success'])): ?>
      <div class="card" style="margin-bottom:12px; border-left:4px solid #10b981;"><strong style="color:#065f46;">Success</strong><div class="small" style="margin-top:8px;"><?php echo htmlspecialchars($f['success']); ?></div></div>
    <?php endif; endforeach; endif; ?>

  <div class="card">
    <form method="get" class="filter-row" style="align-items:center;">
      <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search product, reviewer or comment" class="input" style="width:360px;">
      <select name="status" class="input">
        <option value="">All statuses</option>
        <?php foreach ($allowed_status as $s): ?>
          <option value="<?php echo htmlspecialchars($s); ?>" <?php if ($status_filter === $s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="btn primary">Filter</button>
      <a href="product_reviews.php" class="btn ghost">Reset</a>

      <div style="margin-left:auto;" class="small">
        Showing <?php echo $total === 0 ? 0 : ($offset + 1); ?> - <?php echo min($total, $offset + count($rows)); ?> of <?php echo $total; ?> reviews
      </div>
    </form>

    <div style="overflow:auto;">
      <table class="table row-hover" aria-describedby="reviews-list">
        <thead>
          <tr>
            <th style="width:48px;">#</th>
            <th>Product / Reviewer</th>
            <th style="width:120px;">Rating</th>
            <th>Status</th>
            <th>Comment</th>
            <th style="width:160px;">Created</th>
            <th style="text-align:right; width:180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="small" style="padding:40px 14px; text-align:center; color:#64748b;">No reviews found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?php echo (int)$r['id']; ?></td>
              <td>
                <div style="font-weight:800;"><?php echo htmlspecialchars($r['product_name'] ?: '—'); ?></div>
                <div class="small"><?php echo htmlspecialchars($r['reviewer_name'] ?: $r['reviewer_email'] ?? 'Anonymous'); ?> &middot; <?php echo htmlspecialchars($r['product_sku'] ?? ''); ?></div>
              </td>
              <td>
                <div style="font-weight:800;"><?php echo (float)$r['rating']; ?> / 5</div>
                <div class="small"><?php echo str_repeat('★', (int)round($r['rating'])); ?></div>
              </td>
              <td><?php echo status_badge($r['status']); ?></td>
              <td style="max-width:420px;"><div class="small" style="color:#334155;"><?php echo nl2br(htmlspecialchars($r['comment'])); ?></div></td>
              <td class="small"><?php echo htmlspecialchars(date('d M Y H:i', strtotime($r['created_at']))); ?></td>
              <td style="text-align:right;">
                <!-- Approve / Reject form -->
                <?php if ($r['status'] !== 'approved'): ?>
                  <form method="post" style="display:inline-block;" onsubmit="return confirm('Approve this review?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="review_id" value="<?php echo (int)$r['id']; ?>">
                    <input type="hidden" name="action" value="approve">
                    <button class="icon-btn" type="submit" title="Approve">Approve</button>
                  </form>
                <?php endif; ?>

                <?php if ($r['status'] !== 'rejected'): ?>
                  <form method="post" style="display:inline-block;" onsubmit="return confirm('Reject this review?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="review_id" value="<?php echo (int)$r['id']; ?>">
                    <input type="hidden" name="action" value="reject">
                    <button class="icon-btn" type="submit" title="Reject">Reject</button>
                  </form>
                <?php endif; ?>

                <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete this review? This cannot be undone.');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="review_id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="action" value="delete">
                  <button class="icon-btn" type="submit" title="Delete" style="border-color:#fee2e2;color:#dc2626;">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- pagination -->
    <div class="pagination" aria-label="Pagination">
      <?php
        $start = max(1, $page - 3);
        $end = min($pages, $page + 3);
        if ($page > 1) {
            echo '<a class="page-link" href="' . preserve_qs(['page'=>$page-1]) . '">Prev</a>';
        }
        if ($start > 1) {
            echo '<a class="page-link" href="' . preserve_qs(['page'=>1]) . '">1</a>';
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
        if ($page < $pages) {
            echo '<a class="page-link" href="'. preserve_qs(['page'=> $page+1]) .'">Next</a>';
        }
      ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>