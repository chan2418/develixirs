<?php
// admin/order_returns.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$page_title = "Order Returns";
$activeMenu = "order_returns";

include __DIR__ . '/layout/header.php';

// Handle Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $newStatus = '';

    if ($action === 'approve') $newStatus = 'approved';
    if ($action === 'reject') $newStatus = 'rejected';

    if ($newStatus) {
        $stmt = $pdo->prepare("UPDATE order_returns SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
    }
}

// Filter
$statusFilter = $_GET['status'] ?? 'all';
$whereClaus = "1";
$params = [];

if ($statusFilter !== 'all') {
    $whereClaus .= " AND r.status = ?";
    $params[] = $statusFilter;
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Count
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM order_returns r WHERE $whereClaus");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$pages = max(1, ceil($total / $perPage));

// Fetch
$sql = "
    SELECT r.*, o.order_number, u.name as user_name, u.email as user_email
    FROM order_returns r
    JOIN orders o ON r.order_id = o.id
    JOIN users u ON r.user_id = u.id
    WHERE $whereClaus
    ORDER BY r.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- MAIN CONTENT -->
<div class="max-w-[1200px] mx-auto py-8 px-4">

    <!-- Header -->
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800">Order Returns</h2>
            <p class="text-sm text-slate-500 mt-1">Manage and track customer return requests.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-4 mb-6">
        <div class="flex flex-wrap gap-2">
            <a href="?status=all" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $statusFilter === 'all' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'; ?>">
                All Requests
            </a>
            <a href="?status=pending" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $statusFilter === 'pending' ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'; ?>">
                Pending
            </a>
            <a href="?status=approved" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $statusFilter === 'approved' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'; ?>">
                Approved
            </a>
            <a href="?status=rejected" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $statusFilter === 'rejected' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'; ?>">
                Rejected
            </a>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl shadow p-4">
        <div class="overflow-auto">
            <table class="w-full min-w-[1000px] divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Order Number</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3 w-1/4">Reason</th>
                        <th class="px-4 py-3">Proof</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php if (empty($returns)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                                <i class="fa-solid fa-box-open text-4xl mb-3 opacity-20"></i>
                                <p>No return requests found.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($returns as $r): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                    #<?php echo $r['id']; ?>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <a href="view_order.php?id=<?php echo $r['order_id']; ?>" class="text-indigo-600 hover:text-indigo-800 font-semibold hover:underline">
                                        #<?php echo htmlspecialchars($r['order_number']); ?>
                                    </a>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <div class="font-medium text-slate-800"><?php echo htmlspecialchars($r['user_name']); ?></div>
                                    <div class="text-xs text-slate-400"><?php echo htmlspecialchars($r['user_email']); ?></div>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">
                                    <div title="<?php echo htmlspecialchars($r['reason']); ?>" class="line-clamp-2 max-w-xs">
                                        <?php echo htmlspecialchars($r['reason']); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <?php 
                                        $imgs = json_decode($r['images'], true);
                                        if (!empty($imgs)): 
                                    ?>
                                        <a href="/assets/uploads/returns/<?php echo htmlspecialchars($imgs[0]); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-xs font-medium text-gray-700 transition shadow-sm">
                                            <i class="fa-solid fa-image text-gray-400"></i> 
                                            View <?php echo count($imgs); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">No images</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-500">
                                    <div><?php echo date('d M Y', strtotime($r['created_at'])); ?></div>
                                    <div class="text-xs opacity-70"><?php echo date('h:i A', strtotime($r['created_at'])); ?></div>
                                </td>
                                <td class="px-4 py-4">
                                    <?php
                                        $stClass = match($r['status']) {
                                            'approved' => 'bg-green-100 text-green-800 border border-green-200',
                                            'rejected' => 'bg-red-100 text-red-800 border border-red-200',
                                            default => 'bg-amber-100 text-amber-800 border border-amber-200'
                                        };
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php echo $stClass; ?>">
                                        <?php echo ucfirst($r['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <div class="flex items-center justify-end gap-2">
                                            <form method="POST" onsubmit="return confirm('Approve this return?');">
                                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="p-2 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition border border-green-200" title="Approve">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('Reject this return?');">
                                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition border border-red-200" title="Reject">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 font-medium">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer / Pagination -->
        <?php if ($pages > 1): ?>
        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
            <div class="text-sm text-slate-500">
                Showing page <?php echo $page; ?> of <?php echo $pages; ?>
            </div>
            <div class="flex gap-1">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>" 
                       class="px-3 py-1 rounded-lg border text-sm font-medium transition <?php echo $i == $page ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
