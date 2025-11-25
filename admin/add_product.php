<?php
// admin/add_product.php - upgraded to new admin layout & style with tags
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// set page title (helps sidebar highlight)
$page_title = 'Products';

// Helper to read & clear session flash
function session_flash($key) {
    if (empty($_SESSION[$key])) return null;
    $v = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $v;
}

// Load flashes
$errors        = session_flash('form_errors');          // array of error strings
$success       = session_flash('success_msg');          // success string
$old           = session_flash('old') ?? [];            // associative old input values
$upload_debug  = session_flash('upload_debug');         // array of uploaded file paths (from save_product)

// Helper to repopulate scalar value
function old_val($field, $default = '') {
    global $old;
    if (isset($old[$field])) {
        // if it's an array, don't escape here (used for tags)
        if (is_array($old[$field])) {
            return $old[$field];
        }
        return htmlspecialchars($old[$field], ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars($default, ENT_QUOTES, 'UTF-8');
}

// Helper for checking if a tag was previously selected
function old_has_tag($tagId) {
    global $old;
    if (empty($old['tags'])) return false;

    // old['tags'] might be array or comma-separated string
    if (is_array($old['tags'])) {
        return in_array((string)$tagId, array_map('strval', $old['tags']), true);
    }

    $parts = array_map('trim', explode(',', (string)$old['tags']));
    return in_array((string)$tagId, $parts, true);
}

// Support alternate keys from older code
$name_val             = old_val('title', old_val('name', ''));
$sku_val              = old_val('sku', '');
$price_val            = old_val('price', '');
$stock_val            = old_val('stock', '10');
$short_desc_val       = old_val('short_desc', old_val('short_description', ''));
$description_val      = old_val('description', '');
$category_id_val      = old_val('category_id', '');
$is_published_val     = old_val('is_published', old_val('is_active', '1'));
$meta_title_val       = old_val('meta_title', '');
$meta_description_val = old_val('meta_description', '');

// Load categories for select
$categories = [];
try {
    $categories = $pdo->query("
        SELECT id, COALESCE(title, name) AS title
        FROM categories
        ORDER BY COALESCE(title, name) ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    // ignore if categories table missing
}

// Load tags for multi-select
$tags = [];
try {
    $tags = $pdo->query("
        SELECT id, name
        FROM tags
        WHERE is_active = 1
        ORDER BY name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore if tags table missing
}

// ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// include layout header (uses sidebar & top header)
include __DIR__ . '/layout/header.php';
?>

<link rel="stylesheet" href="/assets/css/admin.css">

<div class="max-w-[1200px] mx-auto py-6">

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-800">Add New Product</h1>
      <p class="text-sm text-slate-500 mt-1">
        Create a product entry — upload images, assign tags and fill SEO fields.
        Errors and previous form values will be shown below if any.
      </p>
    </div>

    <div class="flex items-center gap-3">
      <a href="products.php" class="px-4 py-2 rounded-lg border bg-white">← Back to Products</a>
      <a href="add_product.php" class="px-4 py-2 rounded-lg bg-indigo-600 text-white">Add Product</a>
    </div>
  </div>

  <?php if (!empty($errors) && is_array($errors)): ?>
    <div class="bg-white p-4 rounded-lg border border-red-100 mb-4">
      <div class="font-bold text-red-700 mb-2">Please fix the following</div>
      <ul class="text-sm text-red-600 list-disc pl-5">
        <?php foreach ($errors as $e): ?>
          <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="bg-white p-4 rounded-lg border border-green-100 mb-4">
      <div class="font-bold text-green-700"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
  <?php endif; ?>

  <?php if (!empty($upload_debug) && is_array($upload_debug)): ?>
    <div class="bg-white p-4 rounded-lg border mb-4">
      <div class="font-semibold mb-2">Upload debug (files saved to server)</div>
      <div class="text-sm text-slate-600">
        <?php foreach ($upload_debug as $u): ?>
          <div><?php echo htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <form action="save_product.php" method="post" enctype="multipart/form-data"
        class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6 bg-transparent">
    <input type="hidden" name="csrf_token"
           value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

    <!-- LEFT COLUMN -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Product Name *</label>
        <input id="title" name="title" required
               class="w-full p-3 border rounded-lg"
               value="<?php echo $name_val; ?>"
               placeholder="E.g. Classic Herbal Hair Oil">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Short Description</label>
        <input id="short_desc" name="short_desc"
               class="w-full p-3 border rounded-lg"
               value="<?php echo $short_desc_val; ?>"
               placeholder="One-line summary">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Full Description</label>
        <textarea id="description" name="description" rows="6"
                  class="w-full p-3 border rounded-lg"
                  placeholder="Full product description"><?php echo $description_val; ?></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-2">SEO Title</label>
          <input id="meta_title" name="meta_title"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo $meta_title_val; ?>">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">SEO Description</label>
          <input id="meta_description" name="meta_description"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo $meta_description_val; ?>">
        </div>
      </div>

    </div>

    <!-- RIGHT COLUMN -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-4">

      <!-- Category -->
      <div>
        <label class="block text-sm font-semibold mb-2">Category</label>
        <select id="category_id" name="category_id" class="w-full p-3 border rounded-lg">
          <option value="">-- Select category --</option>
          <?php foreach($categories as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"
              <?php echo ((string)$category_id_val === (string)$c['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Tags (multi-select) -->
      <div>
        <label class="block text-sm font-semibold mb-2">
          Product Tags
          <span class="text-xs text-slate-400">(optional, multiple)</span>
        </label>

        <?php if (!empty($tags)): ?>
          <select name="tags[]" id="tags"
                  class="w-full p-3 border rounded-lg"
                  multiple size="5">
            <?php foreach ($tags as $t): ?>
              <option value="<?php echo (int)$t['id']; ?>"
                <?php echo old_has_tag($t['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-slate-500 mt-1">
            Hold <strong>Ctrl</strong> (Windows) / <strong>Cmd</strong> (Mac) to select multiple tags.
          </p>
        <?php else: ?>
          <p class="text-xs text-slate-500">
            No tags created yet. Go to <code>tags</code> page in admin and add some tag names first.
          </p>
        <?php endif; ?>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-semibold mb-2">Price (₹)</label>
          <input id="price" name="price" type="number" step="0.01" min="0"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo $price_val; ?>" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Stock</label>
          <input id="stock" name="stock" type="number" min="0"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo $stock_val; ?>" required>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">SKU</label>
        <input id="sku" name="sku"
               class="w-full p-3 border rounded-lg"
               value="<?php echo $sku_val; ?>" placeholder="Optional SKU">
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Visibility</label>
        <select id="is_published" name="is_published" class="w-full p-3 border rounded-lg">
          <option value="1" <?php echo ($is_published_val == '1') ? 'selected' : ''; ?>>Published</option>
          <option value="0" <?php echo ($is_published_val == '0') ? 'selected' : ''; ?>>Draft</option>
        </select>
      </div>

      <!-- Images -->
      <div>
        <label class="block text-sm font-semibold mb-2">Product Images</label>

        <label class="group relative flex flex-col items-center justify-center p-4 border-2 border-dashed rounded-lg cursor-pointer hover:border-indigo-500">
          <div class="text-sm font-semibold">Click to upload or drag & drop</div>
          <div class="text-xs text-slate-500">JPG, PNG, WEBP — up to 5 images</div>
          <input id="images_input" type="file" name="images[]" accept="image/*" multiple class="hidden">
        </label>

        <div id="preview" class="mt-3 grid grid-cols-4 gap-3">
          <!-- JS will put previews here -->
        </div>

        <p class="text-xs text-slate-500 mt-2">
          Tip: Uploaded files will be handled by <code>save_product.php</code>.
        </p>
      </div>

      <div class="pt-2">
        <button type="submit"
                class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold">
          Save Product
        </button>
        <a href="products.php"
           class="block text-center mt-3 border border-gray-200 rounded-lg py-2">
          Cancel
        </a>
      </div>

    </div>
  </form>
</div>

<!-- image preview script -->
<script>
(function(){
  const input = document.getElementById('images_input');
  const preview = document.getElementById('preview');

  // If server returned uploaded files (upload_debug), show them as well
  <?php if (!empty($upload_debug) && is_array($upload_debug)): ?>
    (function(){
      const existing = <?php echo json_encode($upload_debug); ?>;
      preview.innerHTML = '';
      existing.forEach(function(p){
        const wrapper = document.createElement('div');
        wrapper.className = 'w-20 h-20 rounded overflow-hidden border';
        const img = document.createElement('img');
        img.src = '/' + p.replace(/^\/+/, '');
        img.alt = '';
        img.className = 'object-cover w-full h-full';
        wrapper.appendChild(img);
        preview.appendChild(wrapper);
      });
    })();
  <?php endif; ?>

  if (!input) return;
  input.addEventListener('change', function(){
    preview.innerHTML = '';
    const files = Array.from(input.files).slice(0,5);
    files.forEach(function(f){
      if (!f.type.startsWith('image/')) return;
      const url = URL.createObjectURL(f);
      const wrapper = document.createElement('div');
      wrapper.className = 'w-20 h-20 rounded overflow-hidden border';
      const img = document.createElement('img');
      img.src = url;
      img.alt = '';
      img.className = 'object-cover w-full h-full';
      wrapper.appendChild(img);
      preview.appendChild(wrapper);
    });
  });

  const dropBox = document.querySelector('.group');
  if (!dropBox) return;
  ['dragenter','dragover','dragleave','drop'].forEach(evt =>
    dropBox.addEventListener(evt, e => e.preventDefault())
  );
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