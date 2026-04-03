<?php
// admin/invoices.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_number_helper.php';
include __DIR__ . '/layout/header.php';

// basic session ensure (header.php usually starts session, but be safe)
if (session_status() === PHP_SESSION_NONE) session_start();

try {
    sync_all_invoice_numbers($pdo);
} catch (Exception $e) {
    error_log('Invoice sync error: ' . $e->getMessage());
}

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

<div class="max-w-[1200px] mx-auto py-6 px-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Invoices</h1>
            <p class="text-sm text-slate-500 mt-1">View, clear, and manage order invoices.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="orders.php" class="px-3 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:shadow-sm">Back to Orders</a>
            <a href="invoice_create.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-sm font-semibold">
                + Create Invoice
            </a>
        </div>
    </div>

    <?php if ($flash_err): ?>
        <div class="bg-red-50 text-red-800 p-4 rounded-lg mb-4 border border-red-200"><?= htmlspecialchars($flash_err) ?></div>
    <?php endif; ?>
    <?php if ($flash_ok): ?>
        <div class="bg-green-50 text-green-800 p-4 rounded-lg mb-4 border border-green-200"><?= htmlspecialchars($flash_ok) ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
        <form method="get" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Search</label>
                <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" class="w-full p-2 border rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none" placeholder="Invoice #, Order # or Customer...">
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full p-2 border rounded-lg text-sm bg-gray-50 focus:bg-white">
                    <option value="">All Statuses</option>
                    <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>Issued</option>
                    <option value="cleared" <?= $status === 'cleared' ? 'selected' : '' ?>>Cleared</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-semibold hover:bg-slate-900 border border-transparent">
                Filter
            </button>
            <a href="invoices.php" class="px-4 py-2 bg-white border border-gray-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-gray-50">
                Reset
            </a>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm divide-y divide-gray-100">
                <thead class="bg-gray-50 text-gray-500 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="p-4 w-16">ID</th>
                        <th class="p-4">Invoice</th>
                        <th class="p-4">Order</th>
                        <th class="p-4">Customer</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Created</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="8" class="p-8 text-center text-gray-500">No invoices found.</td></tr>
                    <?php else: foreach ($invoices as $inv): ?>
                        <tr class="hover:bg-gray-50 group">
                            <td class="p-4 text-gray-400">#<?= (int)$inv['id'] ?></td>
                            <td class="p-4">
                                <div class="font-bold text-gray-900"><?= htmlspecialchars($inv['invoice_number']) ?></div>
                            </td>
                            <td class="p-4">
                                <a href="order_view.php?id=<?= (int)$inv['order_id'] ?>" class="font-semibold text-indigo-600 hover:text-indigo-800"><?= htmlspecialchars($inv['order_number']) ?></a>
                                <div class="text-xs text-gray-400">ID: <?= (int)$inv['order_id'] ?></div>
                            </td>
                            <td class="p-4 text-gray-700"><?= htmlspecialchars($inv['customer_name']) ?></td>
                            <td class="p-4 font-bold text-slate-800">₹ <?= number_format($inv['amount'], 2) ?></td>
                            <td class="p-4">
                                <?php if ($inv['status'] === 'cleared'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800">Cleared</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">Issued</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-gray-500 text-xs"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($inv['created_at']))) ?></td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="invoice_view.php?id=<?= (int)$inv['id'] ?>" class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs bg-indigo-50 px-2 py-1 rounded">View</a>
                                    <a href="generate_invoice_pdf.php?id=<?= (int)$inv['id'] ?>" class="text-gray-600 hover:text-gray-900 font-semibold text-xs bg-gray-100 px-2 py-1 rounded">PDF</a>
                                    
                                    <?php if ($inv['status'] !== 'cleared'): ?>
                                    <form method="post" class="inline-block" onsubmit="return confirm('Mark invoice as cleared?')">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="action" value="clear">
                                        <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
                                        <button type="submit" class="text-green-600 hover:text-green-800 font-semibold text-xs bg-green-50 px-2 py-1 rounded">Clear</button>
                                    </form>
                                    <?php endif; ?>

                                    <form method="post" class="inline-block" onsubmit="return confirm('Delete invoice?')">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-semibold text-xs bg-red-50 px-2 py-1 rounded">Del</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex items-center justify-between sm:px-6">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                   <p class="text-sm text-gray-700">
                      Showing <span class="font-medium"><?= $total === 0 ? 0 : ($offset + 1) ?></span> to <span class="font-medium"><?= min($total, $offset + count($invoices)) ?></span> of <span class="font-medium"><?= $total ?></span> results
                   </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php
                        $start = max(1, $page - 2);
                        $end = min($pages, $page + 2);
                        
                        if ($page > 1) {
                            echo '<a href="'.preserve_qs(['page'=>$page-1]).'" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Prev</a>';
                        }
                        
                        for ($p = $start; $p <= $end; $p++) {
                            $activeClass = ($p === $page) ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
                            echo '<a href="'.preserve_qs(['page'=>$p]).'" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium '.$activeClass.'">'.$p.'</a>';
                        }
                        
                        if ($page < $pages) {
                            echo '<a href="'.preserve_qs(['page'=>$page+1]).'" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Next</a>';
                        }
                    ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
