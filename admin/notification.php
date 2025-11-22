<?php
// admin/notifications.php
// Full notifications admin page (list, mark read, mark all, pagination)
// Requires: /admin/_auth.php, /includes/db.php, /admin/header.php, /admin/footer.php

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/header.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// read inputs
$filter = trim($_GET['filter'] ?? 'all'); // 'all' or 'unread'
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// handle optional server-side actions (non-AJAX fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'mark_read' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_success'] = 'Notification marked read.';
        header('Location: notifications.php' . (isset($_GET['page']) ? '?page=' . (int)$_GET['page'] : ''));
        exit;
    } elseif ($action === 'mark_all') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
        $stmt->execute();
        $_SESSION['flash_success'] = 'All notifications marked read.';
        header('Location: notifications.php');
        exit;
    }
}

// count total
$whereParts = [];
$params = [];
if ($filter === 'unread') {
    $whereParts[] = "is_read = 0";
}
$whereSql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

try {
    $countSql = "SELECT COUNT(*) FROM notifications $whereSql";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $total = 0;
}

$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;

// fetch page rows
try {
    $sql = "SELECT id, title, message, url, is_read, created_at FROM notifications $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rows = [];
}

// unread count for header/subtext
$stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$unreadCount = (int)$stmt->fetchColumn();

// flash
$flashSuccess = null;
if (!empty($_SESSION['flash_success'])) { $flashSuccess = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }

function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

?>

<link rel="stylesheet" href="/assets/css/admin.css">
<style>
/* Notifications page */
.page-wrap{ max-width:1100px; margin:28px auto; padding:0 18px 60px; font-family:Inter,system-ui,Arial; color:#0f172a; }
.header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
.h-title { font-size:20px; font-weight:800; margin:0; }
.h-sub { color:#64748b; margin:0; font-size:13px; }
.controls { display:flex; gap:8px; align-items:center; }
.card { background:#fff; padding:16px; border-radius:12px; border:1px solid #eef2f7; box-shadow:0 8px 30px rgba(2,6,23,0.04); }
.filter-row { display:flex; gap:8px; align-items:center; margin-bottom:12px; flex-wrap:wrap; }
.btn { padding:8px 12px; border-radius:8px; font-weight:700; cursor:pointer; border:0; }
.btn.primary { background:#0b76ff; color:#fff; }
.btn.ghost { background:#fff; border:1px solid #e6eef7; color:#0f172a; }
.pill { padding:8px 10px; border-radius:999px; background:#f3f4f6; font-weight:700; color:#0f172a; }
.notif-list { display:flex; flex-direction:column; gap:10px; }
.notif-item { display:flex; gap:12px; padding:12px; border-radius:10px; border:1px solid #f1f5f9; align-items:flex-start; }
.notif-item.unread { background:#f8fafc; border-color:#e6eef7; }
.notif-title { font-weight:700; color:#0f172a; }
.notif-msg { color:#475569; margin-top:6px; }
.notif-meta { color:#94a3b8; font-size:12px; margin-top:6px; }
.side-actions { margin-left:auto; display:flex; gap:8px; align-items:center; }
.empty { padding:48px; text-align:center; color:#64748b; }
.pagination { display:flex; gap:6px; align-items:center; justify-content:flex-end; margin-top:12px; flex-wrap:wrap; }
.page-link { padding:6px 8px; border-radius:6px; text-decoration:none; background:#f3f6fb; color:#0b1220; }
.page-link.active { background:#0b76ff; color:#fff; }
.small { font-size:13px; color:#64748b; }
@media(max-width:900px){ .header{flex-direction:column;align-items:flex-start} .pagination{justify-content:flex-start} }
</style>

<div class="page-wrap">
  <div class="header">
    <div>
      <h1 class="h-title">Notifications</h1>
      <p class="h-sub">Manage system notifications. Unread: <strong><?php echo $unreadCount; ?></strong></p>
    </div>

    <div class="controls">
      <a href="dashboard.php" class="btn ghost">Dashboard</a>
      <form method="post" style="margin:0;">
        <input type="hidden" name="action" value="mark_all">
        <button class="btn ghost" type="submit" onclick="return confirm('Mark all notifications as read?')">Mark all read</button>
      </form>
    </div>
  </div>

  <?php if ($flashSuccess): ?>
    <div class="card" style="margin-bottom:12px; border-left:4px solid #10b981; color:#065f46;">
      <?php echo esc($flashSuccess); ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="filter-row" style="justify-content:space-between;">
      <div style="display:flex;gap:10px;align-items:center;">
        <a class="pill" href="notifications.php?filter=all" style="<?php echo $filter==='all' ? 'background:#eef2ff;color:#6366f1;' : ''; ?>">All</a>
        <a class="pill" href="notifications.php?filter=unread" style="<?php echo $filter==='unread' ? 'background:#eef2ff;color:#6366f1;' : ''; ?>">Unread</a>
      </div>

      <div class="small">Showing <?php echo $total === 0 ? 0 : ($offset+1); ?> - <?php echo min($total, $offset + count($rows)); ?> of <?php echo $total; ?></div>
    </div>

    <?php if (empty($rows)): ?>
      <div class="empty">
        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/></svg>
        <h3 style="margin-top:12px;font-weight:700">No notifications</h3>
        <p style="margin-top:8px">You have no <?php echo $filter === 'unread' ? 'unread ' : ''; ?>notifications at the moment.</p>
      </div>
    <?php else: ?>
      <div class="notif-list" id="notifList">
        <?php foreach ($rows as $r): ?>
          <div class="notif-item <?php echo ((int)$r['is_read'] === 0) ? 'unread' : ''; ?>" data-id="<?php echo (int)$r['id']; ?>">
            <div style="min-width:48px;display:flex;align-items:center;justify-content:center">
              <div style="width:40px;height:40px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#64748b;font-weight:700">🔔</div>
            </div>

            <div style="flex:1">
              <div class="notif-title"><?php echo esc($r['title']); ?></div>
              <div class="notif-msg"><?php echo nl2br(esc($r['message'])); ?></div>
              <div class="notif-meta"><?php echo esc(date('d M Y H:i', strtotime($r['created_at']))); ?><?php if (!empty($r['url'])): ?> • <a href="<?php echo esc($r['url']); ?>">Open</a><?php endif; ?></div>
            </div>

            <div class="side-actions">
              <?php if ((int)$r['is_read'] === 0): ?>
                <button class="btn ghost mark-read-btn" data-id="<?php echo (int)$r['id']; ?>">Mark read</button>
              <?php else: ?>
                <span class="small" style="color:#64748b">Read</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="pagination" aria-label="Pagination">
        <?php
          $start = max(1, $page - 3);
          $end = min($pages, $page + 3);
          if ($page > 1) echo '<a class="page-link" href="notifications.php?filter=' . urlencode($filter) . '&page=' . ($page-1) . '">Prev</a>';
          if ($start > 1) { echo '<a class="page-link" href="notifications.php?filter=' . urlencode($filter) . '&page=1">1</a>'; if ($start > 2) echo '<span class="small">…</span>'; }
          for ($p=$start;$p<=$end;$p++) {
              $cls = $p===$page ? 'page-link active' : 'page-link';
              echo '<a class="'. $cls .'" href="notifications.php?filter='.urlencode($filter).'&page='.$p.'">'. $p .'</a>';
          }
          if ($end < $pages) { if ($end < $pages-1) echo '<span class="small">…</span>'; echo '<a class="page-link" href="notifications.php?filter='.urlencode($filter).'&page=' . $pages . '">' . $pages . '</a>'; }
          if ($page < $pages) echo '<a class="page-link" href="notifications.php?filter=' . urlencode($filter) . '&page=' . ($page+1) . '">Next</a>';
        ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
// mark single read via AJAX (uses your existing notifications_api.php)
document.addEventListener('click', function(e){
  const btn = e.target.closest('.mark-read-btn');
  if (!btn) return;
  e.preventDefault();
  const id = btn.getAttribute('data-id');
  if (!id) return;
  btn.disabled = true;
  btn.textContent = 'Marking...';

  const fd = new FormData();
  fd.append('action','mark_read');
  fd.append('id', id);

  fetch('/admin/notifications_api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json && json.ok) {
        // visually mark item as read
        const item = btn.closest('.notif-item');
        if (item) {
          item.classList.remove('unread');
          // replace button with 'Read' text
          btn.parentNode.innerHTML = '<span class="small" style="color:#64748b">Read</span>';
        }
        // optionally update header badge if present
        const badge = document.getElementById('notifBadge');
        if (badge) {
          // trigger background refresh for badge
          fetch('/admin/notifications_api.php').then(r => r.json()).then(j => {
            if (j && typeof j.unread !== 'undefined') {
              if (j.unread > 0) { badge.style.display = 'inline-flex'; badge.textContent = String(j.unread); }
              else { badge.style.display = 'none'; }
            }
          }).catch(()=>{});
        }
      } else {
        alert('Failed to mark read');
        btn.disabled = false;
        btn.textContent = 'Mark read';
      }
    }).catch(err => { console.error(err); alert('Network error'); btn.disabled = false; btn.textContent = 'Mark read'; });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>