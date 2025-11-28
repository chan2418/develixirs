<?php
// admin/edit_product.php
// Edit Product (new admin UI/layout) with Main + Sub category

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
// FETCH CATEGORIES (parent + sub)
// ------------------------
$parentCategories = [];
$subCategories    = [];
try {
    $rows = $pdo->query("
        SELECT id, COALESCE(title, name) AS title, parent_id
        FROM categories
        ORDER BY COALESCE(title, name) ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $row['id']        = (int)$row['id'];
        $row['parent_id'] = $row['parent_id'] !== null ? (int)$row['parent_id'] : null;

        if (empty($row['parent_id'])) {
            $parentCategories[] = $row;  // top level
        } else {
            $subCategories[]    = $row;  // children
        }
    }
} catch (Exception $e) {
    $parentCategories = [];
    $subCategories    = [];
}

// ------------------------
// DERIVE MAIN & SUB CATEGORY VALUES FOR THIS PRODUCT
// ------------------------
$parentCategoryIdVal = isset($p['parent_category_id']) ? (int)$p['parent_category_id'] : 0;
$categoryIdVal       = isset($p['category_id']) ? (int)$p['category_id'] : 0;

// Fallback logic: if parent_category_id is empty, but we have category_id, derive from categories table
if ($parentCategoryIdVal === 0 && $categoryIdVal > 0 && !empty($rows)) {
    $currentCat = null;
    foreach ($rows as $row) {
        if ((int)$row['id'] === $categoryIdVal) {
            $currentCat = $row;
            break;
        }
    }
    if ($currentCat) {
        if (empty($currentCat['parent_id'])) {
            // product is directly in a top-level category
            $parentCategoryIdVal = (int)$currentCat['id'];
            // No subcategory in this case
        } else {
            // product is in a subcategory
            $parentCategoryIdVal = (int)$currentCat['parent_id'];
        }
    }
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

// ------------------------
// FETCH VARIANTS
// ------------------------
$variants = [];
try {
    $stmtVar = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY price ASC");
    $stmtVar->execute([$id]);
    $variants = $stmtVar->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $variants = [];
}

// ------------------------
// FETCH FAQS
// ------------------------
$faqs = [];
try {
    $stmtFaq = $pdo->prepare("SELECT * FROM product_faqs WHERE product_id = ? ORDER BY id ASC");
    $stmtFaq->execute([$id]);
    $faqs = $stmtFaq->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $faqs = [];
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');

// small escape helper
function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<link rel="stylesheet" href="/assets/css/admin.css">

<div class="max-w-[1200px] mx-auto py-6">

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-800">Edit Product</h1>
      <p class="text-sm text-slate-500 mt-1">
        Modify product details and click <strong>Save Product</strong>.
      </p>
    </div>

    <div class="flex items-center gap-3">
      <a href="products.php" class="px-4 py-2 rounded-lg border bg-white">← Back to Products</a>
      <a href="add_product.php" class="px-4 py-2 rounded-lg bg-indigo-600 text-white">Add Product</a>
    </div>
  </div>

  <form action="modify_product.php" method="post" enctype="multipart/form-data"
        class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6">
    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

    <!-- LEFT -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Product Name *</label>
        <input name="name" required
               class="w-full p-3 border rounded-lg"
               value="<?php echo esc($p['name'] ?? ''); ?>"
               placeholder="Product name">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Slug (URL)</label>
        <input name="slug"
               class="w-full p-3 border rounded-lg"
               value="<?php echo esc($p['slug'] ?? ''); ?>"
               placeholder="Optional - auto-generate">
        <p class="text-xs text-slate-500 mt-2">Leave blank to auto-generate from product name.</p>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Short Description</label>
        <input name="short_description"
               class="w-full p-3 border rounded-lg"
               value="<?php echo esc($p['short_description'] ?? ''); ?>"
               placeholder="One-line description">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Full Description</label>
        <textarea name="description" rows="6"
                  class="w-full p-3 border rounded-lg"><?php echo esc($p['description'] ?? ''); ?></textarea>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Ingredients</label>
        <textarea name="ingredients" rows="4"
                  class="w-full p-3 border rounded-lg"
                  placeholder="List the ingredients used in this product"><?php echo esc($p['ingredients'] ?? ''); ?></textarea>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">How to Use</label>
        <textarea name="how_to_use" rows="4"
                  class="w-full p-3 border rounded-lg"
                  placeholder="Instructions on how to use this product"><?php echo esc($p['how_to_use'] ?? ''); ?></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-2">SEO Title</label>
          <input name="meta_title"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo esc($p['meta_title'] ?? ''); ?>">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">SEO Description</label>
          <input name="meta_description"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo esc($p['meta_description'] ?? ''); ?>">
        </div>
      </div>

    </div>

    <!-- RIGHT -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-4">

      <!-- Main + Sub Category -->
      <div>
        <label class="block text-sm font-semibold mb-2">Main Category (Top Level)</label>
        <select id="parent_category_id" name="parent_category_id" class="w-full p-3 border rounded-lg">
          <option value="">-- Select main category --</option>
          <?php foreach ($parentCategories as $c): ?>
            <option
              value="<?php echo (int)$c['id']; ?>"
              <?php echo ((int)$parentCategoryIdVal === (int)$c['id']) ? 'selected' : ''; ?>>
              <?php echo esc($c['title']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">
          Example: Men Care, Baby care, etc.
        </p>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Sub Category</label>
        <select id="category_id" name="category_id" class="w-full p-3 border rounded-lg">
          <option value="">-- Select sub category (optional) --</option>
          <?php foreach ($subCategories as $c): ?>
            <option
              value="<?php echo (int)$c['id']; ?>"
              data-parent-id="<?php echo (int)$c['parent_id']; ?>"
              <?php echo ((int)$categoryIdVal === (int)$c['id']) ? 'selected' : ''; ?>>
              <?php echo esc($c['title']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">
          Example: Hair Wash, Hair oil, Face wash under Men Care.
        </p>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-semibold mb-2">Price (₹)</label>
          <input name="price" type="number" step="0.01" min="0"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo esc($p['price'] ?? '0.00'); ?>">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Stock</label>
          <input name="stock" type="number" min="0"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo (int)($p['stock'] ?? 0); ?>">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">SKU</label>
        <input name="sku"
               class="w-full p-3 border rounded-lg"
               value="<?php echo esc($p['sku'] ?? ''); ?>"
               placeholder="Optional SKU">
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Visibility</label>
        <select name="is_active" class="w-full p-3 border rounded-lg">
          <option value="1" <?php if ((string)($p['is_active'] ?? '1') === '1') echo 'selected'; ?>>Published</option>
          <option value="0" <?php if ((string)($p['is_active'] ?? '1') === '0') echo 'selected'; ?>>Draft</option>
        </select>
      </div>

      <!-- Variants -->
      <div>
        <label class="block text-sm font-semibold mb-2">Product Variants (Sizes/Options)</label>
        
        <!-- Variant Label Input -->
        <div class="mb-3">
          <label class="text-xs font-semibold text-slate-500">Variant Label (e.g. Size, Volume, Ingredient)</label>
          <input type="text" name="variant_label" class="w-full p-2 border rounded text-sm" 
                 placeholder="Size" 
                 value="<?php echo esc($p['variant_label'] ?? 'Size'); ?>">
        </div>

        <!-- Variants List -->
        <div class="border rounded-lg p-4 bg-slate-50">
          <div id="variants-list" class="space-y-2 mb-3">
            <!-- Variant cards will be displayed here -->
          </div>
          <button type="button" id="add-variant-btn" class="w-full py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
            + Add Variant
          </button>
        </div>
        <p class="text-xs text-slate-500 mt-1">
          Variants can override product title, description, images, and FAQs.
        </p>
        
        <!-- Hidden container for deleted variant IDs -->
        <div id="deleted-variants-container"></div>
      </div>

      <!-- FAQs -->
      <div>
        <label class="block text-sm font-semibold mb-2">Product FAQs</label>
        <div class="border rounded-lg p-4 bg-slate-50">
          <div id="faqs-container" class="space-y-3">
            <?php foreach ($faqs as $idx => $f): ?>
              <div class="border-b pb-3 last:border-0 last:pb-0 mb-2 faq-row">
                <input type="hidden" name="faqs[<?php echo $idx; ?>][id]" value="<?php echo $f['id']; ?>">
                <div class="flex justify-between items-start gap-2">
                  <div class="w-full space-y-2">
                    <div>
                      <label class="text-xs font-semibold text-slate-500">Question</label>
                      <input type="text" name="faqs[<?php echo $idx; ?>][question]" value="<?php echo esc($f['question']); ?>" required class="w-full p-2 border rounded text-sm">
                    </div>
                    <div>
                      <label class="text-xs font-semibold text-slate-500">Answer</label>
                      <textarea name="faqs[<?php echo $idx; ?>][answer]" rows="2" required class="w-full p-2 border rounded text-sm"><?php echo esc($f['answer']); ?></textarea>
                    </div>
                  </div>
                  <button type="button" class="text-red-500 hover:text-red-700 mt-6 delete-faq-btn" data-id="<?php echo $f['id']; ?>">
                    &times;
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" id="add-faq-btn" class="mt-3 text-sm text-indigo-600 font-semibold hover:underline">
            + Add FAQ
          </button>
        </div>
      </div>

      <!-- Hidden input for deleted FAQ IDs -->
      <input type="hidden" name="delete_faq_ids" id="deleted-faqs-container" value="">

      <div>
        <label class="block text-sm font-semibold mb-2">Product Images</label>

        <label class="group relative flex flex-col items-center justify-center p-4 border-2 border-dashed rounded-lg cursor-pointer hover:border-indigo-500">
          <div class="text-sm font-semibold">Click to upload or drag & drop</div>
          <div class="text-xs text-slate-500">JPG, PNG, WEBP — up to 5 images</div>
          <input id="images_input" type="file" name="images[]" accept="image/*" multiple class="hidden">
        </label>

        <div id="preview" class="mt-3 grid grid-cols-4 gap-3">
          <?php if (!empty($existingImages)): ?>
            <?php foreach ($existingImages as $imgPath):
              $src = $imgPath;
              if (!preg_match('#^https?://#i', $src) && $src !== '' && $src[0] !== '/') {
                  $src = '/assets/uploads/products/' . ltrim($src, '/');
              }
            ?>
              <div class="w-20 h-20 rounded overflow-hidden border">
                <img src="<?php echo esc($src); ?>" class="object-cover w-full h-full" alt="">
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <p class="text-xs text-slate-500 mt-2">
          Tip: Uploaded files will be handled by <code>modify_product.php</code>.
          If you need per-image removal, we can add checkboxes + server-side delete.
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

  <!-- Variant Modal -->
  <div id="variantModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4" style="overflow-y:auto;">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
        <h3 class="text-xl font-bold text-gray-900" id="modalTitle">Add Variant</h3>
        <button type="button" id="closeModalBtn" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
      </div>
      
      <div class="p-6 space-y-4">
        <!-- Hidden fields for editing -->
        <input type="hidden" id="variantEditIndex" value="">
        <input type="hidden" id="variantEditId" value="">
        
        <!-- Required Fields -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold mb-2">Variant Name <span class="text-red-500">*</span></label>
            <input type="text" id="variantName" class="w-full p-3 border rounded-lg" placeholder="e.g. 250ml, XL, Red" required>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-2">Price (₹) <span class="text-red-500">*</span></label>
            <input type="number" id="variantPrice" step="0.01" min="0" class="w-full p-3 border rounded-lg" placeholder="0.00" required>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold mb-2">Stock</label>
            <input type="number" id="variantStock" min="0" value="10" class="w-full p-3 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-2">SKU (Optional)</label>
            <input type="text" id="variantSKU" class="w-full p-3 border rounded-lg" placeholder="Optional">
          </div>
        </div>

        <!-- Override Fields -->
        <div class="border-t pt-4">
          <h4 class="font-semibold text-gray-700 mb-3">Override Product Info (Optional)</h4>
          
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Custom Title</label>
            <input type="text" id="variantCustomTitle" class="w-full p-3 border rounded-lg" placeholder="Leave empty to use product title">
          </div>

          <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Custom Description</label>
            <textarea id="variantCustomDesc" rows="4" class="w-full p-3 border rounded-lg" placeholder="Leave empty to use product description"></textarea>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Variant Images (Multiple)</label>
            <div id="existingVariantImages" class="mb-2"></div>
            <input type="file" id="variantImages" accept="image/*" multiple class="w-full p-2 border rounded-lg">
            <p class="text-xs text-gray-500 mt-1">Upload images specific to this variant. Leave empty to use product images.</p>
            <div id="variantImagesPreviews" class="mt-2 flex flex-wrap gap-2"></div>
          </div>

          <!-- Variant FAQs -->
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Variant FAQs</label>
            <div id="variantFaqsContainer" class="space-y-2 mb-2">
              <!-- FAQ rows will be added here -->
            </div>
            <button type="button" id="addVariantFaqBtn" class="text-sm text-indigo-600 font-semibold hover:underline">
              + Add FAQ
            </button>
            <p class="text-xs text-gray-500 mt-1">Leave empty to use product FAQs.</p>
          </div>
        </div>
      </div>

      <div class="sticky bottom-0 bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
        <button type="button" id="cancelModalBtn" class="px-6 py-2 border rounded-lg hover:bg-gray-100">Cancel</button>
        <button type="button" id="saveVariantBtn" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Variant</button>
      </div>
    </div>
  </div>
</div>

<script>
// preview + drag/drop
(function(){
  const input = document.getElementById('images_input');
  const preview = document.getElementById('preview');
  if (input && preview) {
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

    const dropBox = document.querySelector('.group');
    if (dropBox) {
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
    }
  }

  // ====== Parent/Subcategory dynamic filtering ======
  const parentSelect = document.getElementById('parent_category_id');
  const subSelect    = document.getElementById('category_id');

  if (parentSelect && subSelect) {
    const allSubOptions = Array.from(subSelect.querySelectorAll('option[data-parent-id]'));
    const initialSubVal = subSelect.value;

    function refreshSubOptions() {
      const currentParent = parentSelect.value;
      const currentSub    = subSelect.value;

      subSelect.innerHTML = '';
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = '-- Select sub category (optional) --';
      subSelect.appendChild(placeholder);

      if (!currentParent) {
        // No main category → keep only placeholder
        return;
      }

      allSubOptions.forEach(function(opt){
        if (opt.getAttribute('data-parent-id') === currentParent) {
          subSelect.appendChild(opt.cloneNode(true));
        }
      });

      if (currentSub) {
        subSelect.value = currentSub;
      }
    }

    // Initial build (for existing product values)
    refreshSubOptions();

    // If we had an initial subcategory that didn't match the default filter,
    // try one more time with stored value
    if (initialSubVal && !subSelect.value) {
      subSelect.value = initialSubVal;
    }

    parentSelect.addEventListener('change', function(){
      subSelect.value = '';
      refreshSubOptions();
    });
  }

  // ====== Variants Logic ======
  const variantsContainer = document.getElementById('variants-container');
  const addVariantBtn     = document.getElementById('add-variant-btn');
  const deletedContainer  = document.getElementById('deleted-variants-container');

  if (variantsContainer && addVariantBtn) {
    // Start index from existing count to avoid collision
    let variantCount = <?php echo count($variants); ?>;

    function addVariantRow() {
      const index = variantCount++;
      const row = document.createElement('div');
      row.className = 'grid grid-cols-2 md:grid-cols-5 gap-2 items-end border-b pb-3 last:border-0 last:pb-0 variant-row';
      row.innerHTML = `
        <div>
          <label class="text-xs font-semibold text-slate-500">Name</label>
          <input type="text" name="variants[${index}][name]" required class="w-full p-2 border rounded text-sm" placeholder="Variant Name">
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Price (₹)</label>
          <input type="number" name="variants[${index}][price]" step="0.01" min="0" required class="w-full p-2 border rounded text-sm" placeholder="Price">
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Stock</label>
          <input type="number" name="variants[${index}][stock]" min="0" value="10" required class="w-full p-2 border rounded text-sm" placeholder="Stock">
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">SKU</label>
          <input type="text" name="variants[${index}][sku]" class="w-full p-2 border rounded text-sm" placeholder="SKU">
        </div>
        <div class="relative">
          <label class="text-xs font-semibold text-slate-500">Image (Opt)</label>
          <div class="flex gap-1">
            <input type="file" name="variants[${index}][image]" accept="image/*" class="w-full p-1 border rounded text-xs">
            <button type="button" class="text-red-500 hover:text-red-700 px-2" onclick="this.closest('.variant-row').remove()">
              &times;
            </button>
          </div>
        </div>
      `;
      variantsContainer.appendChild(row);
    }

    addVariantBtn.addEventListener('click', addVariantRow);

    // Handle delete for existing variants
    variantsContainer.addEventListener('click', function(e) {
      if (e.target.classList.contains('delete-variant-btn')) {
        const id = e.target.getAttribute('data-id');
        if (id) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'delete_variant_ids[]';
          input.value = id;
          deletedContainer.appendChild(input);
        }
        e.target.closest('.variant-row').remove();
      }
    });
  }

  // ====== FAQs Logic =====
  const faqsContainer = document.getElementById('faqs-container');
  const addFaqBtn     = document.getElementById('add-faq-btn');
  const deletedFaqsContainer = document.getElementById('deleted-faqs-container');

  if (faqsContainer && addFaqBtn) {
    let faqCount = <?php echo count($faqs); ?>;

    function addFaqRow() {
      const index = faqCount++;
      const row = document.createElement('div');
      row.className = 'border-b pb-3 last:border-0 last:pb-0 mb-2 faq-row';
      row.innerHTML = `
        <div class="flex justify-between items-start gap-2">
          <div class="w-full space-y-2">
            <div>
              <label class="text-xs font-semibold text-slate-500">Question</label>
              <input type="text" name="faqs[${index}][question]" required class="w-full p-2 border rounded text-sm" placeholder="e.g. How often should I use this?">
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500">Answer</label>
              <textarea name="faqs[${index}][answer]" rows="2" required class="w-full p-2 border rounded text-sm" placeholder="e.g. Use it daily for best results."></textarea>
            </div>
          </div>
          <button type="button" class="text-red-500 hover:text-red-700 mt-6" onclick="this.closest('.faq-row').remove()">
            &times;
          </button>
        </div>
      `;
      faqsContainer.appendChild(row);
    }

    addFaqBtn.addEventListener('click', addFaqRow);

    // Handle delete existing FAQ
    faqsContainer.addEventListener('click', function(e) {
      if (e.target.classList.contains('delete-faq-btn')) {
        const id = e.target.getAttribute('data-id');
        if (id) {
          const currentDeleted = deletedFaqsContainer.value ? deletedFaqsContainer.value.split(',') : [];
          currentDeleted.push(id);
          deletedFaqsContainer.value = currentDeleted.join(',');
        }
        e.target.closest('.faq-row').remove();
      }
    });
  }

})();
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>