<?php
// admin/users.php (fixed: defensive column selection, no username assumption)
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// page title used by layout/header.php so sidebar highlights correctly
$page_title = 'Users';

// --- detect users table columns ---
$cols = [];
try {
    $res = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($res as $r) $cols[] = $r['Field'];
} catch (Exception $e) {
    $cols = [];
    $showColumnsError = $e->getMessage();
}

// helper: pick first available candidate column
function pick_col($candidates, $available) {
    foreach ($candidates as $c) {
        if (in_array($c, $available, true)) return $c;
    }
    return null;
}

// choose columns (allow null for optional ones)
$name_col       = pick_col(['name','full_name','display_name','username'], $cols) ?: 'name';
$email_col      = pick_col(['email','user_email','mail'], $cols) ?: 'email';
$username_col   = pick_col(['username','user_name','login'], $cols); // may be null
$status_col     = pick_col(['status','is_active','active','user_status'], $cols); // may be null
$created_col    = pick_col(['created_at','created','joined_at','registered_at'], $cols) ?: 'created_at';
$last_login_col = pick_col(['last_login','last_seen','last_active','last_login_at'], $cols); // may be null

// read inputs
$q = trim($_GET['q'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// build where/params (if status column missing, ignore status filter)
$whereParts = [];
$params = [];

if ($q !== '') {
    $searchCols = array_unique(array_filter([$name_col, $email_col, $username_col]));
    $subs = [];
    foreach ($searchCols as $c) {
        $subs[] = "$c LIKE :q";
    }
    if ($subs) {
        $whereParts[] = '(' . implode(' OR ', $subs) . ')';
        $params[':q'] = "%$q%";
    }
}
if ($filter_status !== '' && $status_col !== null) {
    $whereParts[] = "{$status_col} = :status";
    $params[':status'] = $filter_status;
}
$whereSql = $whereParts ? implode(' AND ', $whereParts) : '1=1';

// total count
$total = 0;
$countError = null;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE {$whereSql}");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $countError = $e->getMessage();
    $total = 0;
}

// pagination
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;

// build SELECT columns defensively
$selectCols = [];
$selectCols[] = 'id';
$selectCols[] = "{$name_col} AS name";
$selectCols[] = "{$email_col} AS email";
if ($username_col) {
    $selectCols[] = "{$username_col} AS username";
} else {
    $selectCols[] = "NULL AS username";
}
if ($status_col) {
    $selectCols[] = "{$status_col} AS status";
} else {
    $selectCols[] = "NULL AS status";
}
$selectCols[] = "{$created_col} AS created_at";
if ($last_login_col) {
    $selectCols[] = "{$last_login_col} AS last_login";
} else {
    $selectCols[] = "NULL AS last_login";
}

$listError = null;
$users = [];
try {
    $sql = "SELECT " . implode(', ', $selectCols) . "
            FROM users
            WHERE {$whereSql}
            ORDER BY {$created_col} DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $listError = $e->getMessage();
    $users = [];
}

// helper badge for status (handles numeric 1/0 and strings)
function user_badge_html($s) {
    $sStr = strtolower((string)$s);
    if ($sStr === '1' || $sStr === 'active' || $sStr === 'yes' || $sStr === 'true') {
        return '<span style="display:inline-block;padding:6px 10px;border-radius:999px;background:#ecfdf5;color:#065f46;font-weight:700;font-size:13px;">Active</span>';
    }
    if ($sStr === '0' || $sStr === 'inactive' || $sStr === 'no' || $sStr === 'false') {
        return '<span style="display:inline-block;padding:6px 10px;border-radius:999px;background:#fff7ed;color:#c2410c;font-weight:700;font-size:13px;">Inactive</span>';
    }
    if ($sStr === '' || $s === null) {
        return '<span class="small" style="color:#64748b">—</span>';
    }
    return '<span style="display:inline-block;padding:6px 10px;border-radius:999px;background:#f8fafc;color:#334155;font-weight:700;font-size:13px;">' . htmlspecialchars(ucfirst($sStr)) . '</span>';
}

// preserve query string helper
function preserve_qs($overrides = []) {
    $qs = $_GET;
    foreach ($overrides as $k => $v) $qs[$k] = $v;
    return '?' . http_build_query($qs);
}

// include layout header to use the new UI chrome & highlight sidebar
include __DIR__ . '/layout/header.php';
?>
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
.container { max-width:1200px; margin:28px auto; padding:0 18px 60px; font-family:Inter,system-ui,Arial; color:#0f172a; }
.card { background:#fff; padding:16px; border-radius:12px; border:1px solid #e6eef7; box-shadow:0 8px 30px rgba(2,6,23,0.04); }
.header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
.h-title { font-size:20px; font-weight:800; margin:0; }
.h-sub { color:#64748b; margin:0; font-size:13px; }
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
.debug { margin-top:14px; padding:12px; border-radius:10px; background:#fff8e6; color:#92400e; border:1px solid #fff1cc; }
</style>

<div class="container">
  <div class="header">
    <div>
      <h1 class="h-title">Users</h1>
      <p class="h-sub">Manage registered users — this page auto-detects your users table columns.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <a href="users_export.php<?php echo preserve_qs(); ?>" class="btn ghost">Export</a>
      <a href="user_add.php" class="btn primary">+ Add User</a>
    </div>
  </div>

  <div class="card">
    <form method="get" class="filter-row">
      <input type="search" name="q" class="input" placeholder="Search name or email" value="<?php echo htmlspecialchars($q, ENT_QUOTES); ?>" style="width:300px;">
      <?php if ($status_col !== null): ?>
        <select name="status" class="input">
          <option value="">All status</option>
          <option value="1" <?php if ($filter_status === '1') echo 'selected'; ?>>Active</option>
          <option value="0" <?php if ($filter_status === '0') echo 'selected'; ?>>Inactive</option>
        </select>
      <?php else: ?>
        <div class="small">(No status filter — <code><?php echo htmlspecialchars($status_col ?? 'status'); ?></code> column not found)</div>
      <?php endif; ?>

      <button class="btn primary" type="submit">Filter</button>
      <a href="users.php" class="btn ghost">Reset</a>

      <div style="margin-left:auto;" class="small">
        Showing <?php echo $total === 0 ? 0 : ($offset + 1); ?> - <?php echo min($total, $offset + count($users)); ?> of <?php echo $total; ?> users
      </div>
    </form>

    <div style="overflow:auto;">
      <table class="table row-hover" aria-describedby="users-list">
        <thead>
          <tr>
            <th style="width:48px;">#</th>
            <th>User</th>
            <th>Email</th>
            <th>Joined</th>
            <th>Last login</th>
            <th>Status</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr><td colspan="7" class="small" style="padding:36px;text-align:center;color:#64748b;">No users found.</td></tr>
          <?php else: foreach ($users as $u): ?>
            <tr>
              <td><?php echo (int)$u['id']; ?></td>
              <td>
                <div style="font-weight:700;"><?php echo htmlspecialchars($u['name'] ?: ($u['username'] ?: '—'), ENT_QUOTES); ?></div>
                <div class="small"><?php echo htmlspecialchars($u['username'] ?: '-', ENT_QUOTES); ?></div>
              </td>
              <td><?php echo htmlspecialchars($u['email'] ?: '-', ENT_QUOTES); ?></td>
              <td class="small"><?php echo $u['created_at'] ? htmlspecialchars(date('d M Y', strtotime($u['created_at']))) : '-'; ?></td>
              <td class="small"><?php echo $u['last_login'] ? htmlspecialchars(date('d M Y H:i', strtotime($u['last_login']))) : '-'; ?></td>
              <td><?php echo user_badge_html($u['status']); ?></td>
              <td style="text-align:right;">
                <a href="user_view.php?id=<?php echo (int)$u['id']; ?>">View</a>
                <a href="user_edit.php?id=<?php echo (int)$u['id']; ?>" style="margin-left:8px;">Edit</a>
                <a href="user_delete.php?id=<?php echo (int)$u['id']; ?>" style="color:#dc2626;margin-left:8px;" onclick="return confirm('Delete user?');">Delete</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (empty($users) || empty($cols)): ?>
      <div class="debug">
        <strong>Debug info</strong>
        <div style="margin-top:8px;" class="small">
          <?php if (!empty($showColumnsError)): ?>
            • SHOW COLUMNS error: <?php echo htmlspecialchars($showColumnsError); ?><br>
          <?php else: ?>
            • Detected users table columns: <?php echo htmlspecialchars(implode(', ', $cols)); ?>.<br>
            • Using mappings — name: <code><?php echo htmlspecialchars($name_col); ?></code>,
              email: <code><?php echo htmlspecialchars($email_col); ?></code>,
              username: <code><?php echo htmlspecialchars($username_col ?? 'n/a'); ?></code>,
              created: <code><?php echo htmlspecialchars($created_col); ?></code>,
              last_login: <code><?php echo htmlspecialchars($last_login_col ?? 'n/a'); ?></code>,
              status: <code><?php echo htmlspecialchars($status_col ?? 'n/a'); ?></code>.<br>
          <?php endif; ?>

          <?php if (!empty($countError)): ?>
            • Count query error: <?php echo htmlspecialchars($countError); ?><br>
          <?php endif; ?>
          <?php if (!empty($listError)): ?>
            • List query error: <?php echo htmlspecialchars($listError); ?><br>
          <?php endif; ?>

          <?php
          try {
              $sample = $pdo->query("SELECT * FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
              if ($sample) {
                  echo "• Example row keys: " . htmlspecialchars(implode(', ', array_keys($sample))) . ".";
              } else {
                  echo "• The `users` table appears empty (no rows).";
              }
          } catch (Exception $e) {
              echo "• Could not read a sample row: " . htmlspecialchars($e->getMessage());
          }
          ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- pagination -->
    <div style="display:flex;justify-content:flex-end;margin-top:12px;">
      <?php
        $start = max(1, $page - 3);
        $end = min($pages, $page + 3);
        if ($page > 1) echo '<a class="btn ghost" href="'. preserve_qs(['page'=>$page-1]) .'" style="margin-right:6px;">Prev</a>';
        for ($p = $start; $p <= $end; $p++) {
            $cls = $p === $page ? 'btn primary' : 'btn ghost';
            echo '<a class="'. $cls .'" href="'. preserve_qs(['page'=>$p]) .'" style="margin-right:6px;">'. $p .'</a>';
        }
        if ($page < $pages) echo '<a class="btn ghost" href="'. preserve_qs(['page'=>$page+1]) .'">Next</a>';
      ?>
    </div>

  </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>