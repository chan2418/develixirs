<?php
// admin/orders.php
// DEVELIXIR - Orders list (improved: safe column checks, logging, dev debug)

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// DEVELOPMENT toggle: set to false in production
define('APP_DEBUG', true);

// use new layout header (page_title must be set before include if header uses it)
$page_title = 'Orders';
include __DIR__ . '/layout/header.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// read inputs
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// allowed statuses (for select)
$statuses = ['pending','processing','packed','shipped','delivered','cancelled'];

// --- Helper functions ----------------------------------------------------
function status_badge($s) {
    $map = [
        'pending' => ['bg'=>'bg-amber-50','text'=>'text-amber-700','label'=>'Pending'],
        'processing' => ['bg'=>'bg-indigo-50','text'=>'text-indigo-700','label'=>'Processing'],
        'packed' => ['bg'=>'bg-green-50','text'=>'text-green-700','label'=>'Packed'],
        'shipped' => ['bg'=>'bg-teal-50','text'=>'text-teal-700','label'=>'Shipped'],
        'delivered' => ['bg'=>'bg-green-50','text'=>'text-green-700','label'=>'Delivered'],
        'cancelled' => ['bg'=>'bg-red-50','text'=>'text-red-700','label'=>'Cancelled'],
    ];
    $s = strtolower((string)$s);
    $info = $map[$s] ?? ['bg'=>'bg-slate-50','text'=>'text-slate-700','label'=>ucfirst($s)];
    return '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ' . $info['bg'] . ' ' . $info['text'] . '">' . htmlspecialchars($info['label']) . '</span>';
}

function preserve_qs($overrides = []) {
    $qs = $_GET;
    foreach ($overrides as $k => $v) $qs[$k] = $v;
    return '?' . http_build_query($qs);
}

// check which columns exist in `orders` table (so we don't reference missing columns)
function getTableColumns(PDO $pdo, string $table) : array {
    try {
        $cols = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) $cols[] = $r['Field'];
        return $cols;
    } catch (Exception $e) {
        // if show columns fails, return common defaults to avoid fatal errors
        error_log("getTableColumns error: " . $e->getMessage());
        return ['id','order_number','customer_name','total_amount','payment_status','order_status','created_at'];
    }
}

// get available columns once
$ordersColumns = getTableColumns($pdo, 'orders');

// Build WHERE safely: only use searchable columns that exist
$whereParts = [];
$params = [];

if ($q !== '') {
    // choose only the columns we actually have
    $searchCols = array_intersect($ordersColumns, ['order_number','customer_name','email']);
    if (!empty($searchCols)) {
        $orParts = [];
        $i = 0;
        foreach ($searchCols as $col) {
            $i++;
            $param = ':q' . $i;
            $orParts[] = "$col LIKE $param";
            $params[$param] = "%{$q}%";
        }
        if ($orParts) {
            $whereParts[] = '(' . implode(' OR ', $orParts) . ')';
        }
    } else {
        // no searchable columns exist, fall back to nothing (no search)
        if (APP_DEBUG) error_log("Orders search: no searchable columns exist in orders table. Columns: " . implode(',', $ordersColumns));
    }
}

if ($status !== '') {
    // ensure order_status column exists before using it
    if (in_array('order_status', $ordersColumns)) {
        $whereParts[] = "order_status = :status";
        $params[':status'] = $status;
    } else {
        if (APP_DEBUG) error_log("Orders filter: 'order_status' column not present in orders table.");
    }
}

$whereSql = $whereParts ? implode(' AND ', $whereParts) : '1=1';

// Total count (with try/catch so any SQL error gets logged)
try {
    $countSql = "SELECT COUNT(*) FROM orders WHERE {$whereSql}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $total = 0;
    error_log("Orders count error: " . $e->getMessage() . " — SQL: {$countSql}");
    if (APP_DEBUG) $countError = $e->getMessage();
}

// paging calculations
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;

// list query (safe binding of limit/offset)
$orders = [];
try {
    $selectCols = implode(',', array_intersect($ordersColumns, ['id','order_number','customer_name','email','total_amount','payment_status','order_status','created_at']));
    // fallback if intersection returned empty
    if (!$selectCols) $selectCols = 'id, order_number, customer_name, total_amount, payment_status, order_status, created_at';

    $listSql = "SELECT {$selectCols}
                FROM orders
                WHERE {$whereSql}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($listSql);

    // bind dynamic params (string types)
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $orders = [];
    error_log("Orders list error: " . $e->getMessage() . " — SQL: {$listSql}");
    if (APP_DEBUG) $listError = $e->getMessage();
}

?>
<div class="max-w-[1200px] mx-auto">
  <div class="flex items-start justify-between gap-6 mb-6">
    <div>
      <h2 class="text-2xl font-extrabold text-slate-800">Orders</h2>
      <p class="text-sm text-slate-500 mt-1">View and manage customer orders. Use filters to narrow results.</p>
    </div>

    <div class="flex items-center gap-3">
      <a href="orders_export.php<?php echo preserve_qs(); ?>" class="inline-flex items-center gap-2 border border-gray-200 px-3 py-2 rounded-lg text-sm hover:shadow">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M8 12h8M8 8h8M8 4h8"/></svg>
        Export CSV
      </a>
      <a href="dashboard.php" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-gray-200 text-sm hover:shadow">Dashboard</a>
    </div>
  </div>

  <?php if (defined('APP_DEBUG') && APP_DEBUG && !empty($countError)): ?>
    <div class="mb-4 p-3 rounded border border-red-200 bg-red-50 text-sm text-red-700">
      <strong>Debug:</strong> Count error: <?php echo htmlspecialchars($countError); ?>
    </div>
  <?php endif; ?>

  <?php if (defined('APP_DEBUG') && APP_DEBUG && !empty($listError)): ?>
    <div class="mb-4 p-3 rounded border border-red-200 bg-red-50 text-sm text-red-700">
      <strong>Debug:</strong> List error: <?php echo htmlspecialchars($listError); ?>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow p-6 border border-gray-100">
    <!-- Filters -->
    <form method="get" class="flex flex-col md:flex-row md:items-center gap-3 mb-4">
      <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search order #, customer or email" class="w-full md:w-1/2 px-4 py-2 rounded-lg border border-gray-200 focus:outline-none" />
      <select name="status" class="px-4 py-2 rounded-lg border border-gray-200">
        <option value="">All Statuses</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?php echo htmlspecialchars($s); ?>" <?php if ($status === $s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>

      <div class="flex items-center gap-2 ml-auto">
        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold">Filter</button>
        <a href="orders.php" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">Reset</a>
      </div>
    </form>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">#</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Order</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Customer</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Amount</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Payment</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Created</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-500">No orders found.</td>
            </tr>
          <?php else: foreach ($orders as $o): ?>
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-4 text-sm"><?php echo (int)$o['id']; ?></td>
              <td class="px-4 py-4">
                <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($o['order_number']); ?></div>
                <div class="text-xs text-slate-400">Order ID: <?php echo (int)$o['id']; ?></div>
              </td>
              <td class="px-4 py-4">
                <div class="font-semibold"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                <div class="text-xs text-slate-400"><?php echo htmlspecialchars($o['email'] ?? ''); ?></div>
              </td>
              <td class="px-4 py-4 font-semibold">₹ <?php echo number_format($o['total_amount'], 2); ?></td>
              <td class="px-4 py-4 text-sm text-slate-600"><?php echo htmlspecialchars(ucfirst($o['payment_status'] ?? '-')); ?></td>
              <td class="px-4 py-4"><?php echo status_badge($o['order_status'] ?? '-'); ?></td>
              <td class="px-4 py-4 text-sm text-slate-500"><?php echo htmlspecialchars(date('d M Y H:i', strtotime($o['created_at'] ?? 'now'))); ?></td>
              <td class="px-4 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <a href="order_view.php?id=<?php echo (int)$o['id']; ?>" class="px-3 py-1 rounded-lg text-sm text-indigo-600 border border-indigo-100">View</a>
                  <a href="order_invoice.php?id=<?php echo (int)$o['id']; ?>" class="px-3 py-1 rounded-lg text-sm border border-gray-200">Invoice</a>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- pagination -->
    <div class="mt-4 flex items-center justify-between gap-4">
      <div class="text-sm text-slate-500">
        Showing <?php echo $total === 0 ? 0 : ($offset + 1); ?> - <?php echo min($total, $offset + count($orders)); ?> of <?php echo $total; ?> orders
      </div>

      <nav class="flex items-center gap-2" role="navigation" aria-label="Pagination">
        <?php
          $start = max(1, $page - 3);
          $end = min($pages, $page + 3);

          if ($page > 1) {
            echo '<a href="'. preserve_qs(['page'=> $page-1]) .'" class="px-3 py-1 rounded-md border border-gray-200 text-sm bg-white">Prev</a>';
          }
          if ($start > 1) {
            echo '<a href="'. preserve_qs(['page'=>1]) .'" class="px-3 py-1 rounded-md border border-gray-200 text-sm bg-white">1</a>';
            if ($start > 2) echo '<span class="px-2 text-sm text-slate-400">…</span>';
          }
          for ($p = $start; $p <= $end; $p++) {
            $active = $p === $page ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700';
            echo '<a href="'. preserve_qs(['page'=>$p]) .'" class="px-3 py-1 rounded-md border border-gray-200 text-sm '. $active .'">'. $p .'</a>';
          }
          if ($end < $pages) {
            if ($end < $pages - 1) echo '<span class="px-2 text-sm text-slate-400">…</span>';
            echo '<a href="'. preserve_qs(['page'=>$pages]) .'" class="px-3 py-1 rounded-md border border-gray-200 text-sm bg-white">'. $pages .'</a>';
          }
          if ($page < $pages) {
            echo '<a href="'. preserve_qs(['page'=> $page+1]) .'" class="px-3 py-1 rounded-md border border-gray-200 text-sm bg-white">Next</a>';
          }
        ?>
      </nav>
    </div>
  </div>
</div>

<?php
include __DIR__ . '/layout/footer.php';