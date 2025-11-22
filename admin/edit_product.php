<?php
// admin/edit_product.php
// Edit Product (new admin UI/layout)

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// set page_title BEFORE including layout header so sidebar highlight works
$page_title = 'Products';

// include new layout header (uses layout/sidebar.php which highlights by $page_title)
include __DIR__ . '/layout/header.php';

// ------------------------
// READ & VALIDATE ID
// ------------------------
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

// ------------------------
// FETCH PRODUCT
// ------------------------
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $p = false;
}

if (!$p) {
    echo '<div class="max-w-[1200px] mx-auto p-6"><div class="bg-white p-6 rounded-lg shadow">Product not found.</div></div>';
    include __DIR__ . '/layout/footer.php';
    exit;
}

// ------------------------
// FETCH CATEGORIES
// ------------------------
try {
    $cats = $pdo->query("SELECT id, COALESCE(title, name) AS title FROM categories ORDER BY COALESCE(title, name) ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cats = [];
}

// ------------------------
// HELPERS
// ------------------------
function decode_images($val) {
    if (empty($val)) return [];
    $json = @json_decode($val, true);
    if (is_array($json)) return array_values($json);
    if (strpos($val, ',') !== false) {
        return array_values(array_filter(array_map('trim', explode(',', $val))));
    }
    return [trim($val)];
}
$existingImages = decode_images($p['images'] ?? '');

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = htmlspecialchars($_SESSION['csrf_token']);

// small escape helper
function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<link rel="stylesheet" href="/assets/css/admin.css">

<div class="max-w-[1200px] mx-auto py-6">

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-800">Edit Product</h1>
      <p class="text-sm text-slate-500 mt-1">Modify product details and click <strong>Save Product</strong>.</p>
    </div>

    <div class="flex items-center gap-3">
      <a href="products.php" class="px-4 py-2 rounded-lg border bg-white">← Back to Products</a>
      <a href="add_product.php" class="px-4 py-2 rounded-lg bg-indigo-600 text-white">Add Product</a>
    </div>
  </div>

  <form action="modify_product.php" method="post" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6">
    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

    <!-- LEFT -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Product Name *</label>
        <input name="name" required class="w-full p-3 border rounded-lg" value="<?php echo esc($p['name'] ?? ''); ?>" placeholder="Product name">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Slug (URL)</label>
        <input name="slug" class="w-full p-3 border rounded-lg" value="<?php echo esc($p['slug'] ?? ''); ?>" placeholder="Optional - auto-generate">
        <p class="text-xs text-slate-500 mt-2">Leave blank to auto-generate from product name.</p>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Short Description</label>
        <input name="short_description" class="w-full p-3 border rounded-lg" value="<?php echo esc($p['short_description'] ?? ''); ?>" placeholder="One-line description">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Full Description</label>
        <textarea name="description" rows="6" class="w-full p-3 border rounded-lg"><?php echo esc($p['description'] ?? ''); ?></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-2">SEO Title</label>
          <input name="meta_title" class="w-full p-3 border rounded-lg" value="<?php echo esc($p['meta_title'] ?? ''); ?>">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">SEO Description</label>
          <input name="meta_description" class="w-full p-3 border rounded-lg" value="<?php echo esc($p['meta_description'] ?? ''); ?>">
        </div>
      </div>

    </div>

    <!-- RIGHT -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-4">

      <div>
        <label class="block text-sm font-semibold mb-2">Category</label>
        <select name="category_id" class="w-full p-3 border rounded-lg">
          <option value="">-- Select category --</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php if ((int)$p['category_id'] === (int)$c['id']) echo 'selected'; ?>>
              <?php echo esc($c['title']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-semibold mb-2">Price (₹)</label>
          <input name="price" type="number" step="0.01" min="0" class="w-full p-3 border rounded-lg" value="<?php echo esc($p['price'] ?? '0.00'); ?>">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Stock</label>
          <input name="stock" type="number" min="0" class="w-full p-3 border rounded-lg" value="<?php echo (int)($p['stock'] ?? 0); ?>">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">SKU</label>
        <input name="sku" class="w-full p-3 border rounded-lg" value="<?php echo esc($p['sku'] ?? ''); ?>" placeholder="Optional SKU">
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Visibility</label>
        <select name="is_active" class="w-full p-3 border rounded-lg">
          <option value="1" <?php if ((string)($p['is_active'] ?? '1') === '1') echo 'selected'; ?>>Published</option>
          <option value="0" <?php if ((string)($p['is_active'] ?? '1') === '0') echo 'selected'; ?>>Draft</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Product Images</label>

        <label class="group relative flex flex-col items-center justify-center p-4 border-2 border-dashed rounded-lg cursor-pointer hover:border-indigo-500">
          <div class="text-sm font-semibold">Click to upload or drag & drop</div>
          <div class="text-xs text-slate-500">JPG, PNG, WEBP — up to 5 images</div>
          <input id="images_input" type="file" name="images[]" accept="image/*" multiple class="hidden">
        </label>

        <div id="preview" class="mt-3 grid grid-cols-4 gap-3">
          <?php if (!empty($existingImages)): foreach ($existingImages as $imgPath):
            $src = $imgPath;
            if (!preg_match('#^https?://#i', $src) && $src !== '' && $src[0] !== '/') {
                $src = '/assets/uploads/products/' . ltrim($src, '/');
            }
          ?>
            <div class="w-20 h-20 rounded overflow-hidden border">
              <img src="<?php echo esc($src); ?>" class="object-cover w-full h-full" alt="">
            </div>
          <?php endforeach; endif; ?>
        </div>

        <p class="text-xs text-slate-500 mt-2">Tip: Uploaded files will be handled by <code>modify_product.php</code>. If you need per-image removal, I can add checkboxes + server-side delete.</p>
      </div>

      <div class="pt-2">
        <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold">Save Product</button>
        <a href="products.php" class="block text-center mt-3 border border-gray-200 rounded-lg py-2">Cancel</a>
      </div>

    </div>
  </form>
</div>

<script>
// preview and drag/drop similar to existing behavior
(function(){
  const input = document.getElementById('images_input');
  const preview = document.getElementById('preview');
  if (!input || !preview) return;

  input.addEventListener('change', function(){
    preview.innerHTML = '';
    const files = Array.from(this.files).slice(0,5);
    files.forEach(function(f){
      if (!f.type.startsWith('image/')) return;
      const url = URL.createObjectURL(f);
      const wrapper = document.createElement('div');
      wrapper.className = 'w-20 h-20 rounded overflow-hidden border';
      const img = document.createElement('img');
      img.src = url;
      img.className = 'object-cover w-full h-full';
      wrapper.appendChild(img);
      preview.appendChild(wrapper);
    });
  });

  // drag & drop
  const dropBox = document.querySelector('.group');
  ['dragenter','dragover','dragleave','drop'].forEach(evt => dropBox.addEventListener(evt, e => e.preventDefault()));
  dropBox.addEventListener('drop', function(e){
    const dt = e.dataTransfer;
    if (!dt) return;
    const files = Array.from(dt.files).slice(0,5);
    if (typeof DataTransfer !== 'undefined') {
      const dtObj = new DataTransfer();
      files.forEach(f => dtObj.items.add(f));
      input.files = dtObj.files;
    }
    input.dispatchEvent(new Event('change'));
  });
})();
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>