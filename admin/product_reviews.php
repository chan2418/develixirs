<?php
// admin/product_reviews.php
// Product reviews admin list with search, status filters, bulk actions
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Product Reviews';

// Helper to read & clear session flash
function session_flash($key) {
    if (empty($_SESSION[$key])) return null;
    $v = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $v;
}
$error = session_flash('error');
$success = session_flash('success');

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// === Handle Bulk Actions ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_action'])) {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $_SESSION['error'] = 'Invalid CSRF token.';
    } else {
        $action = $_POST['bulk_action'];
        $ids = $_POST['selected_ids'] ?? [];
        if (empty($ids)) {
            $_SESSION['error'] = 'No reviews selected.';
        } else {
             // Safe INT casting
             $ids = array_map('intval', $ids);
             $inQuery = implode(',', $ids);
             
             try {
                if ($action === 'delete') {
                    $pdo->exec("DELETE FROM product_reviews WHERE id IN ($inQuery)");
                    $_SESSION['success'] = count($ids) . ' review(s) deleted.';
                } elseif (in_array($action, ['approved', 'hidden', 'spam', 'pending'])) {
                    $pdo->exec("UPDATE product_reviews SET status = '$action' WHERE id IN ($inQuery)");
                     $_SESSION['success'] = count($ids) . ' review(s) status updated to ' . ucfirst($action) . '.';
                } elseif ($action === 'feature') {
                    $pdo->exec("UPDATE product_reviews SET is_featured = 1 WHERE id IN ($inQuery)");
                    $_SESSION['success'] = count($ids) . ' review(s) marked as Featured.';
                } elseif ($action === 'unfeature') {
                    $pdo->exec("UPDATE product_reviews SET is_featured = 0 WHERE id IN ($inQuery)");
                    $_SESSION['success'] = count($ids) . ' review(s) removed from Featured.';
                } else {
                     $_SESSION['error'] = 'Unknown bulk action.';
                }
             } catch (Exception $e) {
                 $_SESSION['error'] = 'DB Error: ' . $e->getMessage();
             }
        }
    }
    header('Location: product_reviews.php');
    exit;
}

// === Read query params ===
$q = trim($_GET['q'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$rating_filter = trim($_GET['rating'] ?? '');
// Sorting params
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC');

// Validate sort/order to prevent SQL injection
$allowed_sorts = ['rating', 'status', 'created_at'];
if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($q !== '') {
    // UPDATED: Using `comment` instead of `review_text`
    $where[] = "(p.name LIKE :q OR r.comment LIKE :q OR r.reviewer_name LIKE :q OR r.title LIKE :q)";
    $params[':q'] = "%{$q}%";
}
if ($status_filter !== '') {
    $where[] = "r.status = :status";
    $params[':status'] = $status_filter;
}
if ($rating_filter !== '') {
    $where[] = "r.rating = :rating";
    $params[':rating'] = $rating_filter;
}

$whereSql = $where ? implode(' AND ', $where) : '1=1';

// Count
$total = 0;
try {
    $countSql = "SELECT COUNT(*) FROM product_reviews r LEFT JOIN products p ON r.product_id = p.id WHERE {$whereSql}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $total = 0;
}

// Pagination
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;

// Fetch
$listSql = "SELECT r.*, p.name AS product_name 
            FROM product_reviews r
            LEFT JOIN products p ON r.product_id = p.id
            WHERE {$whereSql}
            ORDER BY r.{$sort} {$order}
            LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($listSql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout/header.php';

// Helper for sort links
function sort_link($label, $col, $currentSort, $currentOrder, $q, $status, $rating) {
    $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $arrow = '';
    if ($currentSort === $col) {
        $arrow = ($currentOrder === 'ASC') ? ' ↑' : ' ↓';
    }
    
    // Build query
    $params = [
        'q' => $q,
        'status' => $status,
        'rating' => $rating,
        'sort' => $col,
        'order' => $newOrder
    ];
    // Remove empty params to clean URL
    if (empty($params['q'])) unset($params['q']);
    if (empty($params['status'])) unset($params['status']);
    if (empty($params['rating'])) unset($params['rating']);

    $url = '?' . http_build_query($params);
    
    return '<a href="'.htmlspecialchars($url).'" class="group inline-flex items-center gap-1 hover:text-indigo-600 transition">' 
           . htmlspecialchars($label) 
           . '<span class="text-xs text-gray-400 group-hover:text-indigo-500">'. $arrow .'</span></a>';
}
?>

<div class="max-w-[1200px] mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Product Reviews</h1>
            <p class="text-sm text-slate-500 mt-1">Manage, moderate, and respond to customer reviews.</p>
        </div>
        <a href="product_review_form.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-sm font-semibold">
            + Add Review
        </a>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 text-green-800 p-4 rounded-lg mb-4 border border-green-200"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-50 text-red-800 p-4 rounded-lg mb-4 border border-red-200"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
        <form method="get" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Search</label>
                <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" class="w-full p-2 border rounded-lg text-sm" placeholder="Reviewer, Product, or Content...">
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full p-2 border rounded-lg text-sm">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="hidden" <?= $status_filter === 'hidden' ? 'selected' : '' ?>>Hidden</option>
                    <option value="spam" <?= $status_filter === 'spam' ? 'selected' : '' ?>>Spam</option>
                </select>
            </div>
            <div class="w-32">
                 <label class="block text-xs font-semibold text-gray-500 mb-1">Rating</label>
                 <select name="rating" class="w-full p-2 border rounded-lg text-sm">
                    <option value="">All Ratings</option>
                    <?php for($i=5; $i>=1; $i--): ?>
                        <option value="<?= $i ?>" <?= $rating_filter == $i ? 'selected' : '' ?>><?= $i ?> Stars</option>
                    <?php endfor; ?>
                 </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-semibold hover:bg-gray-900">Filter</button>
            <a href="product_reviews.php" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50">Reset</a>
        </form>
    </div>

    <!-- Bulk Actions Form -->
    <form method="post" id="bulkForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-3 border-b bg-gray-50 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 ml-3">
                    <span class="text-xs text-gray-500 font-medium uppercase tracking-wide ml-2">Select All</span>
                </div>
                <div class="flex gap-2">
                    <select name="bulk_action" class="text-sm border-gray-300 rounded-lg p-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Bulk Actions --</option>
                        <option value="approved">Mark as Approved</option>
                        <option value="hidden">Mark as Hidden</option>
                        <option value="spam">Mark as Spam</option>
                        <option value="feature">Mark as Featured (Home)</option>
                        <option value="unfeature">Remove from Featured</option>
                        <option value="delete">Delete Permanently</option>
                    </select>
                    <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded-lg hover:bg-gray-900">Apply</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold border-b">
                        <tr>
                            <th class="p-4 w-10"></th>
                            <th class="p-4">Product</th>
                            <th class="p-4 w-16 text-center">Home</th>
                            <th class="p-4">Reviewer</th>
                            <th class="p-4">
                                <?= sort_link('Rating', 'rating', $sort, $order, $q, $status_filter, $rating_filter) ?>
                            </th>
                            <th class="p-4 w-1/3">Review</th>
                            <th class="p-4">
                                <?= sort_link('Status', 'status', $sort, $order, $q, $status_filter, $rating_filter) ?>
                            </th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-500">No reviews found matching your criteria.</td>
                            </tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr class="hover:bg-gray-50 group transition">
                                <td class="p-4">
                                    <input type="checkbox" name="selected_ids[]" value="<?= $r['id'] ?>" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                </td>
                                <td class="p-4 align-top">
                                    <div class="font-bold text-gray-900 mb-1"><?= htmlspecialchars($r['product_name'] ?: 'Unknown Product') ?></div>
                                    <div class="text-xs text-gray-400">ID: <?= $r['product_id'] ?></div>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if($r['is_featured']): ?>
                                        <span class="text-xl text-yellow-400" title="Featured on Homepage">★</span>
                                    <?php else: ?>
                                        <span class="text-xl text-gray-200" title="Not Featured">★</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 align-top">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($r['reviewer_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($r['user_id'] ? 'User' : 'Guest') ?></div>
                                    <div class="text-xs text-slate-400 mt-1"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
                                </td>
                                <td class="p-4 align-top">
                                    <div class="flex text-yellow-400 mb-1">
                                        <?php for($i=1; $i<=5; $i++) echo ($i <= $r['rating']) ? '★' : '<span class="text-gray-200">★</span>'; ?>
                                    </div>
                                    <div class="text-xs font-bold text-gray-700"><?= $r['rating'] ?>.0 / 5.0</div>
                                </td>
                                <td class="p-4 align-top">
                                    <?php if ($r['title']): ?>
                                        <div class="font-bold text-gray-800 mb-1">"<?= htmlspecialchars($r['title']) ?>"</div>
                                    <?php endif; ?>
                                    <!-- UPDATED: Using `comment` instead of `review_text` -->
                                    <p class="text-gray-600 text-xs leading-relaxed line-clamp-3">
                                        <?= nl2br(htmlspecialchars($r['comment'] ?? '')) ?>
                                    </p>
                                </td>
                                <td class="p-4 align-top">
                                    <?php
                                    $sClass = match($r['status']) {
                                        'approved' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-orange-100 text-orange-800',
                                        'hidden' => 'bg-gray-100 text-gray-800',
                                        'spam' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $sClass ?>">
                                        <?= ucfirst($r['status']) ?>
                                    </span>
                                </td>
                                <td class="p-4 align-top text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
                                        <a href="product_review_form.php?id=<?= $r['id'] ?>" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Edit</a>
                                        <a href="/product_view.php?id=<?= $r['product_id'] ?>" target="_blank" class="text-gray-400 hover:text-gray-600" title="View Product">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 00-2 2h10a2 2 0 00-2-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="p-4 border-t bg-gray-50 flex items-center justify-end">
                <div class="flex gap-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&q=<?= urlencode($q) ?>&status=<?= urlencode($status_filter) ?>&rating=<?= urlencode($rating_filter) ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="px-3 py-1 border rounded bg-white hover:bg-gray-50 text-sm">Prev</a>
                    <?php endif; ?>
                    <span class="px-3 py-1 text-sm text-gray-600">Page <?= $page ?> of <?= $pages ?></span>
                    <?php if ($page < $pages): ?>
                        <a href="?page=<?= $page+1 ?>&q=<?= urlencode($q) ?>&status=<?= urlencode($status_filter) ?>&rating=<?= urlencode($rating_filter) ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="px-3 py-1 border rounded bg-white hover:bg-gray-50 text-sm">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>