<?php
// admin/external_reviews.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'External Reviews';

// Helper: Session Flash
function session_flash($key) {
    if (empty($_SESSION[$key])) return null;
    $v = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $v;
}

$error = session_flash('error');
$success = session_flash('success');

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// === Handle Delete Action ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $_SESSION['error'] = 'Invalid CSRF token.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->exec("DELETE FROM external_reviews WHERE id = $id");
            $_SESSION['success'] = 'Review deleted successfully.';
        }
    }
    header('Location: external_reviews.php');
    exit;
}

// === Handle Status Toggle ===
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE external_reviews SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = 'Status updated.';
    header('Location: external_reviews.php');
    exit;
}

// === Handle Feature Toggle ===
if (isset($_GET['toggle_featured'])) {
    $id = (int)$_GET['toggle_featured'];
    $stmt = $pdo->prepare("UPDATE external_reviews SET is_featured = NOT is_featured WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = 'Featured status updated.';
    header('Location: external_reviews.php');
    exit;
}

// Fetch All Reviews
$stmt = $pdo->query("SELECT * FROM external_reviews ORDER BY sort_order ASC, created_at DESC");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[1200px] mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">External Reviews</h1>
            <p class="text-sm text-slate-500 mt-1">Manage reviews from other platforms (Google, Amazon, etc.)</p>
        </div>
        <a href="external_review_form.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-sm font-semibold">
            + Add New Review
        </a>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 text-green-800 p-4 rounded-lg mb-4 border border-green-200"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-50 text-red-800 p-4 rounded-lg mb-4 border border-red-200"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600 font-semibold border-b">
                    <tr>
                        <th class="p-4 w-16">Icon</th>
                        <th class="p-4">Platform</th>
                        <th class="p-4">Reviewer</th>
                        <th class="p-4">Rating</th>
                        <th class="p-4 w-1/3">Content</th>
                        <th class="p-4">Sort</th>
                        <th class="p-4 text-center">Featured</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-500">No external reviews added yet.</td>
                        </tr>
                    <?php else: foreach ($reviews as $r): ?>
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="p-4 align-top">
                                <?php if ($r['platform_icon']): ?>
                                    <img src="<?= htmlspecialchars($r['platform_icon']) ?>" class="w-8 h-8 object-contain" alt="Icon">
                                <?php else: ?>
                                    <span class="text-2xl">💬</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 align-top font-bold text-gray-800">
                                <?= htmlspecialchars($r['platform_name']) ?>
                            </td>
                            <td class="p-4 align-top">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($r['reviewer_name']) ?></div>
                            </td>
                            <td class="p-4 align-top">
                                <span class="text-yellow-500 font-bold">★ <?= $r['rating'] ?></span>
                            </td>
                            <td class="p-4 align-top text-gray-600 text-xs leading-relaxed">
                                <?= nl2br(htmlspecialchars(substr($r['review_content'], 0, 100))) ?><?= strlen($r['review_content']) > 100 ? '...' : '' ?>
                                <?php if($r['review_link']): ?>
                                    <br><a href="<?= htmlspecialchars($r['review_link']) ?>" target="_blank" class="text-indigo-500 hover:underline mt-1 inline-block">View Link ↗</a>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 align-top text-gray-500">
                                <?= $r['sort_order'] ?>
                            </td>
                            <td class="p-4 align-top text-center">
                                <a href="?toggle_featured=<?= $r['id'] ?>" class="text-xl no-underline" title="Toggle Featured">
                                    <?= $r['is_featured'] ? '⭐' : '☆' ?>
                                </a>
                            </td>
                            <td class="p-4 align-top text-center">
                                <a href="?toggle_status=<?= $r['id'] ?>" class="px-2 py-1 rounded text-xs font-bold <?= $r['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                                    <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
                                </a>
                            </td>
                            <td class="p-4 align-top text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="external_review_form.php?id=<?= $r['id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this review?');" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
