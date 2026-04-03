<?php
// admin/product_review_form.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Review Details';
$is_edit = isset($_GET['id']);
$review = [];
$error = '';

// Fetch review if editing
if ($is_edit) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM product_reviews WHERE id = ?");
    $stmt->execute([$id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$review) {
        header('Location: product_reviews.php');
        exit;
    }
}

// Fetch all products for dropdown
$products = $pdo->query("SELECT id, name, sku FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Defaults
$r_status = $review['status'] ?? 'pending';
$r_rating = $review['rating'] ?? 5;
$r_featured = $review['is_featured'] ?? 0;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[800px] mx-auto py-10 px-4">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">
                <?= $is_edit ? 'Edit Review' : 'Create New Review' ?>
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                <?= $is_edit ? 'Updating review #' . $review['id'] : 'Manually add a review on behalf of a customer.' ?>
            </p>
        </div>
        <a href="product_reviews.php" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-semibold transition">
            Cancel
        </a>
    </div>

    <form action="save_review.php" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sm:p-8">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $review['id'] ?? '' ?>">
        
        <?php if ($is_edit && $review['user_id']): ?>
            <div class="mb-6 p-4 bg-blue-50 text-blue-800 rounded-lg text-sm flex items-center gap-3">
                <span class="text-lg">ℹ️</span>
                <span>This review is linked to a registered user (ID: <?= $review['user_id'] ?>).</span>
            </div>
        <?php endif; ?>

        <!-- Product Selection -->
        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Product <span class="text-red-500">*</span></label>
            <select name="product_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                <option value="">-- Select Product --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($review['product_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?> (SKU: <?= htmlspecialchars($p['sku']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Reviewer Name -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Reviewer Name <span class="text-red-500">*</span></label>
                <input type="text" name="reviewer_name" required value="<?= htmlspecialchars($review['reviewer_name'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            
            <!-- Rating -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Rating <span class="text-red-500">*</span></label>
                <select name="rating" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <?php for($i=5; $i>=1; $i--): ?>
                        <option value="<?= $i ?>" <?= $r_rating == $i ? 'selected' : '' ?>>
                            <?= $i ?> Stars <?= str_repeat('★', $i) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <!-- Review Title -->
        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Review Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($review['title'] ?? '') ?>" placeholder="e.g. Great product!" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>

        <!-- Review Content -->
        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Review Content <span class="text-red-500">*</span></label>
            <textarea name="comment" rows="6" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Write the full review here..."><?= htmlspecialchars($review['comment'] ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 border-t pt-6">
            <!-- Status -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="pending" <?= $r_status === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                    <option value="approved" <?= $r_status === 'approved' ? 'selected' : '' ?>>Approved (Visible)</option>
                    <option value="hidden" <?= $r_status === 'hidden' ? 'selected' : '' ?>>Hidden</option>
                    <option value="spam" <?= $r_status === 'spam' ? 'selected' : '' ?>>Spam</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Only Approved reviews are shown on the store.</p>
            </div>

            <!-- Featured Toggle -->
            <div class="flex items-center pt-8">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 border-gray-300" <?= $r_featured ? 'checked' : '' ?>>
                    <span class="ml-3 text-sm font-bold text-gray-800">Mark as Featured Review</span>
                </label>
            </div>
        </div>

        <?php if($is_edit): ?>
        <div class="mb-8">
            <label class="block text-sm font-bold text-gray-700 mb-2">Admin Internal Note</label>
            <textarea name="admin_note" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-gray-50 text-sm"><?= htmlspecialchars($review['admin_note'] ?? '') ?></textarea>
            <p class="text-xs text-gray-500 mt-1">Private note for admins only.</p>
        </div>
        <?php endif; ?>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-4 pt-6 border-t">
            <?php if($is_edit): ?>
                <button type="submit" name="delete" value="1" onclick="return confirm('Delete permanently?')" class="px-6 py-2 text-red-600 hover:text-red-700 font-semibold text-sm mr-auto">
                    Delete Review
                </button>
            <?php endif; ?>
            
            <a href="product_reviews.php" class="px-6 py-2 text-gray-600 hover:text-gray-900 font-semibold">Cancel</a>
            <button type="submit" class="px-8 py-2.5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 shadow-md shadow-indigo-200 transition transform hover:-translate-y-0.5">
                <?= $is_edit ? 'Update Review' : 'Create Review' ?>
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
