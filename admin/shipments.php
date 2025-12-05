<?php
// admin/shipments.php
// DEVELIXIR - Shipments list (styled to match Orders page)
// - Safe escaping using h()
// - Filter/search, pagination, status badges
// - Re-uses layout/header/footer

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Shipments';
include __DIR__ . '/layout/header.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// helpers
function h($s) {
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
}

function preserve_qs($overrides = []) {
    $qs = $_GET;
    foreach ($overrides as $k => $v) $qs[$k] = $v;
    return '?' . http_build_query($qs);
}

function status_badge($s) {
    $map = [
        'pending'       => ['bg'=>'bg-amber-50','text'=>'text-amber-700','label'=>'Pending'],
        'label_created' => ['bg'=>'bg-indigo-50','text'=>'text-indigo-700','label'=>'Label Created'],
        'in_transit'    => ['bg'=>'bg-teal-50','text'=>'text-teal-700','label'=>'In Transit'],
        'delivered'     => ['bg'=>'bg-green-50','text'=>'text-green-700','label'=>'Delivered'],
        'returned'      => ['bg'=>'bg-red-50','text'=>'text-red-700','label'=>'Returned'],
        'cancelled'     => ['bg'=>'bg-red-50','text'=>'text-red-700','label'=>'Cancelled'],
    ];
    $s = strtolower((string)$s);
    $info = $map[$s] ?? ['bg'=>'bg-slate-50','text'=>'text-slate-700','label'=>ucfirst($s)];
    return '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ' 
           . $info['bg'] . ' ' . $info['text'] . '">' . h($info['label']) . '</span>';
}

// input
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// build where
$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(s.shipment_number LIKE :q OR s.tracking_number LIKE :q OR o.order_number LIKE :q OR o.customer_name LIKE :q)";
    $params[':q'] = "%{$q}%";
}
if ($status !== '') {
    $where[] = "s.status = :status";
    $params[':status'] = $status;
}
$whereSql = $where ? implode(' AND ', $where) : '1=1';

// total count
try {
    $countSql = "SELECT COUNT(*) FROM shipments s JOIN orders o ON s.order_id = o.id WHERE {$whereSql}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    error_log('Shipments count error: ' . $e->getMessage());
    $total = 0;
}

// paging
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;

// fetch list
$shipments = [];
try {
    $listSql = "SELECT s.*, o.order_number, o.customer_name
                FROM shipments s
                JOIN orders o ON s.order_id = o.id
                WHERE {$whereSql}
                ORDER BY s.created_at DESC
                LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($listSql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Shipments list error: ' . $e->getMessage());
    $shipments = [];
}
?>

<div class="max-w-[1200px] mx-auto">
  <div class="flex items-start justify-between gap-6 mb-6">
    <div>
      <h2 class="text-2xl font-extrabold text-slate-800">Shipments</h2>
      <p class="text-sm text-slate-500 mt-1">Manage shipping labels, tracking and statuses.</p>
    </div>

    <div class="flex items-center gap-3">
      <a href="shipments_create.php" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 text-white font-semibold">Create Shipment</a>
      <a href="dashboard.php" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-gray-200 text-sm hover:shadow">Dashboard</a>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow p-6 border border-gray-100">
    <!-- Filters -->
    <form method="get" class="flex flex-col md:flex-row md:items-center gap-3 mb-4">
      <input type="search" name="q" value="<?= h($q) ?>" placeholder="Search shipment #, tracking, order, customer" class="w-full md:w-1/2 px-4 py-2 rounded-lg border border-gray-200 focus:outline-none" />
      <select name="status" class="px-4 py-2 rounded-lg border border-gray-200">
        <option value="">All statuses</option>
        <?php foreach (['pending','label_created','in_transit','delivered','returned','cancelled'] as $s): ?>
          <option value="<?= h($s) ?>" <?= $s === $status ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="flex items-center gap-2 ml-auto">
        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold">Filter</button>
        <a href="shipments.php" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">Reset</a>
      </div>
    </form>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">#</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Shipment</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Order</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Customer</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Carrier</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Tracking</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Created</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <?php if (empty($shipments)): ?>
            <tr>
              <td colspan="9" class="px-6 py-12 text-center text-sm text-slate-500">No shipments found.</td>
            </tr>
          <?php else: foreach ($shipments as $s): ?>
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-4 text-sm"><?= (int)$s['id'] ?></td>

              <td class="px-4 py-4">
                <div class="font-semibold text-slate-800"><?= h($s['shipment_number']) ?></div>
                <div class="text-xs text-slate-400">Shipment ID: <?= (int)$s['id'] ?></div>
              </td>

              <td class="px-4 py-4">
                <a href="order_view.php?id=<?= (int)$s['order_id'] ?>" class="font-semibold text-indigo-600"><?= h($s['order_number']) ?></a>
                <div class="text-xs text-slate-400">Order ID: <?= (int)$s['order_id'] ?></div>
              </td>

              <td class="px-4 py-4">
                <div class="font-semibold"><?= h($s['customer_name']) ?></div>
              </td>

              <td class="px-4 py-4 text-sm text-slate-600"><?= h($s['carrier'] ?? '-') ?></td>

              <td class="px-4 py-4 text-sm text-slate-600"><?= h($s['tracking_number'] ?? '') ?></td>

              <td class="px-4 py-4"><?= status_badge($s['status'] ?? '') ?></td>

              <td class="px-4 py-4 text-sm text-slate-500"><?= h(date('d M Y', strtotime($s['created_at'] ?? 'now'))) ?></td>

              <td class="px-4 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <a href="shipment_view.php?id=<?= (int)$s['id'] ?>" class="px-3 py-1 rounded-lg text-sm text-indigo-600 border border-indigo-100">View</a>

                  <?php if (!empty($s['label_file'])): ?>
                    <a href="download_label.php?id=<?= (int)$s['id'] ?>" class="px-3 py-1 rounded-lg text-sm border border-gray-200" download>Label</a>
                  <?php endif; ?>

                  <?php if (($s['status'] ?? '') !== 'delivered'): ?>
                    <form method="post" action="shipments_action.php" style="display:inline">
                      <input type="hidden" name="action" value="mark_shipped">
                      <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                      <button class="px-3 py-1 rounded-lg bg-green-600 text-white text-sm">Mark Shipped</button>
                    </form>
                  <?php endif; ?>
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
        Showing <?= $total === 0 ? 0 : ($offset + 1); ?> - <?= min($total, $offset + count($shipments)); ?> of <?= $total; ?> shipments
      </div>

      <nav class="flex items-center gap-2" role="navigation" aria-label="Pagination">
        <?php
          $start = max(1, $page - 3);
          $end   = min($pages, $page + 3);

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

<?php include __DIR__ . '/layout/footer.php'; ?>