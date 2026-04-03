<?php
// admin/external_review_form.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$review = null;
$page_title = $id ? 'Edit External Review' : 'Add External Review';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM external_reviews WHERE id = ?");
    $stmt->execute([$id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$review) die("Review not found.");
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// Helper
function old($key, $default = '') {
    global $review;
    if ($review) return $review[$key] ?? $default;
    return $_POST[$key] ?? $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        die("Invalid CSRF token");
    }

    $platform_name = trim($_POST['platform_name']);
    $reviewer_name = trim($_POST['reviewer_name']);
    $review_content = trim($_POST['review_content']);
    $rating = (float)$_POST['rating'];
    $review_link = trim($_POST['review_link']);
    $platform_icon = trim($_POST['platform_icon']); // Assuming URL for now, can add upload later
    $sort_order = (int)$_POST['sort_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Handle File Upload for Icon if provided
    if (!empty($_FILES['icon_upload']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
        $filename = $_FILES['icon_upload']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newname = 'platform_' . time() . '.' . $ext;
            $destination = __DIR__ . '/../assets/uploads/icons/' . $newname;
            if (!is_dir(__DIR__ . '/../assets/uploads/icons/')) {
                mkdir(__DIR__ . '/../assets/uploads/icons/', 0777, true);
            }
            if (move_uploaded_file($_FILES['icon_upload']['tmp_name'], $destination)) {
                $platform_icon = '/assets/uploads/icons/' . $newname;
            }
        }
    } elseif (!empty($_POST['platform_icon_media'])) {
        $platform_icon = trim($_POST['platform_icon_media']);
    }

    // Handle File Upload for Product Image if provided
    $product_image = trim($_POST['product_image'] ?? '');
    if (!empty($_FILES['product_image_upload']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['product_image_upload']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newname = 'ext_product_' . time() . '.' . $ext;
            $destination = __DIR__ . '/../assets/uploads/products/' . $newname; 
            // ensure dir exists
            if (!is_dir(__DIR__ . '/../assets/uploads/products/')) {
                 @mkdir(__DIR__ . '/../assets/uploads/products/', 0777, true);
            }
            if (move_uploaded_file($_FILES['product_image_upload']['tmp_name'], $destination)) {
                $product_image = '/assets/uploads/products/' . $newname;
            }
        }
    } elseif (!empty($_POST['product_image_media'])) {
        $product_image = trim($_POST['product_image_media']);
    }

    $product_name = trim($_POST['product_name'] ?? '');

    if ($id) {
        $stmt = $pdo->prepare("UPDATE external_reviews SET platform_name=?, reviewer_name=?, review_content=?, rating=?, review_link=?, platform_icon=?, sort_order=?, is_active=?, is_featured=?, product_name=?, product_image=? WHERE id=?");
        $stmt->execute([$platform_name, $reviewer_name, $review_content, $rating, $review_link, $platform_icon, $sort_order, $is_active, isset($_POST['is_featured']) ? 1 : 0, $product_name, $product_image, $id]);
        $_SESSION['success'] = "Review updated successfully.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO external_reviews (platform_name, reviewer_name, review_content, rating, review_link, platform_icon, sort_order, is_active, is_featured, product_name, product_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$platform_name, $reviewer_name, $review_content, $rating, $review_link, $platform_icon, $sort_order, $is_active, isset($_POST['is_featured']) ? 1 : 0, $product_name, $product_image]);
        $_SESSION['success'] = "Review added successfully.";
    }
    
    header('Location: external_reviews.php');
    exit;
}

include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[800px] mx-auto py-10 px-4">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800"><?= $page_title ?></h1>
        <a href="external_reviews.php" class="text-indigo-600 hover:underline">← Back to List</a>
    </div>

    <form method="post" enctype="multipart/form-data" class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Platform Name</label>
                <input type="text" name="platform_name" required value="<?= htmlspecialchars(old('platform_name')) ?>" class="w-full p-2.5 border rounded-lg" placeholder="e.g. Google, Amazon">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Reviewer Name</label>
                <input type="text" name="reviewer_name" required value="<?= htmlspecialchars(old('reviewer_name')) ?>" class="w-full p-2.5 border rounded-lg" placeholder="e.g. John Doe">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Review Content</label>
            <textarea name="review_content" rows="4" class="w-full p-2.5 border rounded-lg" placeholder="What did they say?"><?= htmlspecialchars(old('review_content')) ?></textarea>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Rating (0-5)</label>
                <input type="number" step="0.1" min="0" max="5" name="rating" value="<?= htmlspecialchars(old('rating', '5.0')) ?>" class="w-full p-2.5 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Link to Review (Optional)</label>
                <input type="url" name="review_link" value="<?= htmlspecialchars(old('review_link')) ?>" class="w-full p-2.5 border rounded-lg" placeholder="https://...">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Platform Icon</label>
            <div class="flex items-center gap-4">
                <input type="file" name="icon_upload" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <input type="text" name="platform_icon" value="<?= htmlspecialchars(old('platform_icon')) ?>" class="flex-1 p-2.5 border rounded-lg hidden" placeholder="Or Image URL...">
                <?php if ($id && $review['platform_icon']): ?>
                    <img src="<?= htmlspecialchars($review['platform_icon']) ?>" class="h-10 w-10 object-contain border rounded p-1">
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-400 mt-1">Upload an icon (like Google 'G' logo, or Amazon logo)</p>

            <div class="mt-2 text-xs text-gray-500 font-medium text-center">- OR -</div>
            <button type="button" onclick="openMediaModal('icon_input_hidden', 'icon_preview_img')" class="mt-2 px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50 shadow-sm w-full">
                📁 Select from Media
            </button>
            <input type="hidden" name="platform_icon_media" id="icon_input_hidden">
            
            <div id="icon_preview_container" class="hidden mt-2">
                <img id="icon_preview_img" src="" class="h-10 w-10 object-contain border rounded p-1">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 pt-4 border-t">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Product Name (Optional)</label>
                <input type="text" name="product_name" value="<?= htmlspecialchars(old('product_name')) ?>" class="w-full p-2.5 border rounded-lg" placeholder="e.g. Hair Oil 100ml">
                <p class="text-xs text-gray-400 mt-1">If set, displays "Verified Purchase • [Name]" instead of platform link.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Product Image (Optional)</label>
                <div class="flex items-center gap-4">
                    <input type="file" name="product_image_upload" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <input type="hidden" name="product_image" value="<?= htmlspecialchars(old('product_image')) ?>">
                    <?php if ($id && !empty($review['product_image'])): ?>
                        <div class="relative group">
                            <img src="<?= htmlspecialchars($review['product_image']) ?>" class="h-10 w-10 object-cover border rounded p-1">
                        </div>
                    <?php endif; ?>

                </div>
                <p class="text-xs text-gray-400 mt-1">If uploaded, replaces the Platform Icon on the card.</p>
                
                <div class="mt-2 text-xs text-gray-500 font-medium text-center">- OR -</div>
                <button type="button" onclick="openMediaModal('product_image_hidden', 'product_image_preview')" class="mt-2 px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50 shadow-sm w-full">
                    📁 Select from Media
                </button>
                <input type="hidden" name="product_image_media" id="product_image_hidden">
                
                <!-- Preview for Media Selection -->
                <div id="product_image_preview_container" class="hidden mt-2">
                    <img id="product_image_preview" src="" class="h-10 w-10 object-cover border rounded p-1">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Sort Order</label>
                <input type="number" name="sort_order" value="<?= htmlspecialchars(old('sort_order', '0')) ?>" class="w-24 p-2.5 border rounded-lg">
            </div>
            <div class="flex items-center h-full pt-6 gap-6">
                 <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer" <?= old('is_active', 1) ? 'checked' : '' ?>>
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    <span class="ms-3 text-sm font-medium text-gray-900">Active</span>
                </label>

                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" class="sr-only peer" <?= old('is_featured', 0) ? 'checked' : '' ?>>
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-400"></div>
                    <span class="ms-3 text-sm font-medium text-gray-900">Featured on Home</span>
                </label>
            </div>
        </div>

        <div class="pt-4 border-t mt-6 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition shadow-lg">
                <?= $id ? 'Update Review' : 'Save Review' ?>
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
