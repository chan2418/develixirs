<?php
// admin/user_view.php
// View user details and their order history

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function h($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }
function status_badge($s) {
    $map = [
        'pending' => ['bg'=>'bg-amber-50','text'=>'text-amber-700'],
        'processing' => ['bg'=>'bg-blue-50','text'=>'text-blue-700'],
        'shipped' => ['bg'=>'bg-indigo-50','text'=>'text-indigo-700'],
        'delivered' => ['bg'=>'bg-green-50','text'=>'text-green-700'],
        'cancelled' => ['bg'=>'bg-red-50','text'=>'text-red-700'],
        'returned' => ['bg'=>'bg-rose-50','text'=>'text-rose-700'],
    ];
    $s = strtolower((string)$s);
    $c = $map[$s] ?? ['bg'=>'bg-slate-50','text'=>'text-slate-700'];
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium '. $c['bg'] .' '. $c['text'] .'">'. ucfirst($s) .'</span>';
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: users.php');
    exit;
}

// Fetch User
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("User fetch error: " . $e->getMessage());
    $user = false;
}

if (!$user) {
    $page_title = 'User Not Found';
    include __DIR__ . '/layout/header.php';
    echo '<div class="max-w-4xl mx-auto mt-10 p-6 bg-white rounded-xl shadow-sm border border-gray-100 text-center">';
    echo '<h2 class="text-xl font-bold text-slate-800">User not found</h2>';
    echo '<p class="text-slate-500 mt-2">The requested user ID does not exist.</p>';
    echo '<a href="users.php" class="inline-block mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg">Back to Users</a>';
    echo '</div>';
    include __DIR__ . '/layout/footer.php';
    exit;
}

// Fallback to email if name is empty
if (empty($user['name'])) {
    $user['name'] = $user['email'];
}

// Format name if it's an email (e.g. nam@gmail.com -> nam)
if (strpos($user['name'], '@') !== false) {
    $parts = explode('@', $user['name']);
    $user['name'] = $parts[0];
}

// Populate username from email if empty
if (empty($user['username']) && !empty($user['email'])) {
    $parts = explode('@', $user['email']);
    $user['username'] = $parts[0];
}

// Fetch Default Address
$address_display = '-';
try {
    $stmtAddr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1");
    $stmtAddr->execute([$id]);
    $addr = $stmtAddr->fetch(PDO::FETCH_ASSOC);

    if ($addr) {
        $parts = [
            $addr['full_name'],
            $addr['address_line1'],
            $addr['address_line2'],
            $addr['city'] . ', ' . $addr['state'] . ' - ' . $addr['pincode'],
            'Phone: ' . $addr['phone']
        ];
        $address_display = implode("\n", array_filter(array_map('trim', $parts)));
    } else {
        // Fallback to users table address if available
        $address_display = $user['address'] ?? '-';
    }
} catch (Exception $e) {
    $address_display = $user['address'] ?? '-';
}

// Fetch Orders
$orders = [];
$stats = ['total_orders' => 0, 'total_spent' => 0];
try {
    // Try to match by user_id first, fallback to email if user_id is 0/null in orders table (if applicable)
    // Assuming orders table has user_id. If not, we might need to rely on email.
    // For safety, let's check if user_id column exists or just use email if user_id is 0.
    // A robust query: WHERE user_id = ? OR (email = ? AND email != '')
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $o) {
        $stats['total_orders']++;
        if ($o['payment_status'] === 'paid' || $o['payment_status'] === 'completed') {
            $stats['total_spent'] += $o['total_amount'];
        }
    }
} catch (Exception $e) {
    error_log("User orders fetch error: " . $e->getMessage());
    $orders = [];
}

$page_title = 'User: ' . h($user['name']);
include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[1200px] mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="users.php" class="p-2 rounded-lg hover:bg-gray-100 text-slate-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800"><?= h($user['name']) ?></h1>
                <div class="text-sm text-slate-500">Member since <?= date('M d, Y', strtotime($user['created_at'])) ?></div>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="user_edit.php?id=<?= $id ?>" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-gray-50">Edit Profile</a>
            <form action="user_delete.php" method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-sm font-medium hover:bg-red-100">Delete User</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Profile & Stats -->
        <div class="space-y-6">
            <!-- Profile Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="font-bold text-lg text-slate-800"><?= h($user['name']) ?></div>
                        <div class="text-slate-500 text-sm"><?= h($user['email']) ?></div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between py-3 border-b border-gray-50">
                        <span class="text-slate-500 text-sm">Status</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= ($user['status'] ?? 1) ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?>">
                            <?= ($user['status'] ?? 1) ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-gray-50">
                        <span class="text-slate-500 text-sm">Phone</span>
                        <span class="text-slate-800 text-sm font-medium"><?= h($user['phone'] ?? '-') ?></span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-gray-50">
                        <span class="text-slate-500 text-sm">Username</span>
                        <span class="text-slate-800 text-sm font-medium"><?= h($user['username'] ?? '-') ?></span>
                    </div>
                    <div class="py-3">
                        <span class="text-slate-500 text-sm block mb-1">Address</span>
                        <span class="text-slate-800 text-sm block leading-relaxed"><?= nl2br(h($address_display)) ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Customer Stats</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 rounded-lg text-center">
                        <div class="text-2xl font-bold text-indigo-600"><?= $stats['total_orders'] ?></div>
                        <div class="text-xs text-slate-500 font-medium uppercase tracking-wide mt-1">Total Orders</div>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-lg text-center">
                        <div class="text-2xl font-bold text-emerald-600">₹<?= money($stats['total_spent']) ?></div>
                        <div class="text-xs text-slate-500 font-medium uppercase tracking-wide mt-1">Total Spent</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Orders -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800">Order History</h3>
                    <span class="text-xs text-slate-400"><?= count($orders) ?> orders found</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-slate-500 font-medium">
                            <tr>
                                <th class="px-6 py-3">Order</th>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Total</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                        No orders found for this user.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <a href="order_view.php?id=<?= $o['id'] ?>" class="font-medium text-indigo-600 hover:text-indigo-700">
                                                <?= h($o['order_number']) ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <?= date('M d, Y', strtotime($o['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= status_badge($o['order_status']) ?>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-slate-800">
                                            ₹<?= money($o['total_amount']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="order_view.php?id=<?= $o['id'] ?>" class="text-slate-400 hover:text-indigo-600">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
