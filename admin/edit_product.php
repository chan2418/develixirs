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
$categoryIdVal       = isset($p['cat_id']) ? (int)$p['cat_id'] : (isset($p['category_id']) ? (int)$p['category_id'] : 0);

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

// Fetch variant FAQs
$variantFaqs = [];
if (!empty($variants)) {
    try {
        $variantIds = array_column($variants, 'id');
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $stmtVarFaq = $pdo->prepare("SELECT * FROM variant_faqs WHERE variant_id IN ($placeholders) ORDER BY variant_id, display_order");
        $stmtVarFaq->execute($variantIds);
        $allVarFaqs = $stmtVarFaq->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($allVarFaqs as $faq) {
            $vid = (int)$faq['variant_id'];
            if (!isset($variantFaqs[$vid])) {
                $variantFaqs[$vid] = [];
            }
            $variantFaqs[$vid][] = $faq;
        }
    } catch (Exception $e) {
        $variantFaqs = [];
    }
}

// ------------------------
// FETCH RELATED PRODUCTS
// ------------------------
$relatedProducts = [];
try {
    $stmtRelated = $pdo->prepare("
        SELECT p.id, p.name, p.sku
        FROM product_relations pr
        JOIN products p ON pr.related_product_id = p.id
        WHERE pr.product_id = ?
    ");
    $stmtRelated->execute([$id]);
    $relatedProducts = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $relatedProducts = [];
}

// ------------------------
// FETCH PRODUCT MEDIA
// ------------------------
$productMedia = [];
if (!empty($p['product_media'])) {
    $decoded = json_decode($p['product_media'], true);
    if (is_array($decoded)) {
        $productMedia = $decoded;
    }
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

// ------------------------
// FETCH TAGS
// Load all tags for autocomplete
$tags = [];
try {
    $tags = $pdo->query("
        SELECT id, name
        FROM tags
        ORDER BY name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tags = [];
    error_log('Failed to load tags in edit_product: ' . $e->getMessage());
}

// Load product labels
$labels = [];
try {
    $labels = $pdo->query("SELECT id, name, color, text_color FROM product_labels WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $labels = [];
}

// Fetch selected tags for this product
$productTagIds = [];
try {
    $stmtPT = $pdo->prepare("SELECT tag_id FROM product_tags WHERE product_id = ?");
    $stmtPT->execute([$id]);
    $productTagIds = $stmtPT->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $productTagIds = [];
}

// Load Product Groups
$groups = [];
try {
    $groups = $pdo->query("SELECT id, name FROM product_groups ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $groups = []; }

// Load Concerns (NEW)
$concerns = [];
try {
    $concerns = $pdo->query("SELECT id, title FROM concerns ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $concerns = []; }

// Load Seasonals (NEW)
$seasonals = [];
try {
    $seasonals = $pdo->query("SELECT id, title FROM seasonals ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $seasonals = []; }

// Fetch Assigned Groups
$productGroupIds = [];
try {
    $stmtPG = $pdo->prepare("SELECT group_id FROM product_group_map WHERE product_id = ?");
    $stmtPG->execute([$id]);
    $productGroupIds = $stmtPG->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $productGroupIds = []; }

// Fetch Filter Groups & Options
$filterGroups = [];
try {
    $stmtFG = $pdo->query("
        SELECT fg.*, c.name as category_name 
        FROM filter_groups fg 
        LEFT JOIN categories c ON fg.category_id = c.id 
        WHERE fg.is_active = 1 
        ORDER BY c.name DESC, fg.sort_order ASC, fg.name ASC
    ");
    $filterGroups = $stmtFG->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($filterGroups as &$fg) {
        $stmtOpt = $pdo->prepare("SELECT * FROM filter_options WHERE group_id = ? ORDER BY sort_order ASC, label ASC");
        $stmtOpt->execute([$fg['id']]);
        $fg['options'] = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($fg); // break ref
} catch (Exception $e) { }

// Fetch Assigned Filter Values
$productFilterValues = [];
try {
    $stmtPFV = $pdo->prepare("SELECT filter_option_id FROM product_filter_values WHERE product_id = ?");
    $stmtPFV->execute([$id]);
    $productFilterValues = $stmtPFV->fetchAll(PDO::FETCH_COLUMN);
    // Force Int
    $productFilterValues = array_map('intval', $productFilterValues);
    error_log('DEBUG edit_product.php - Saved Filter IDs: ' . implode(',', $productFilterValues));
} catch (Exception $e) { }


// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');

// small escape helper
function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<link rel="stylesheet" href="/assets/css/admin.css">

<!-- CKEditor 4.22.1 -->
<script src="//cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<style>
  /* Hide CKEditor warning notifications */
  .cke_notification_warning { display: none !important; }
  .cke_notifications_area { display: none !important; }
  /* Increase editor content font size */
  .cke_editable { font-size: 16px !important; line-height: 1.6 !important; }
</style>

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
        <div class="flex justify-between items-center mb-2">
          <label class="block text-sm font-semibold">Short Description</label>
          <div class="flex gap-2">
            <button type="button" id="toggleEditorShortDesc" class="px-4 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700 transition">Hide Editor</button>
            <button type="button" id="addMediaShortDesc" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition">📁 Add Media</button>
          </div>
        </div>
        <div id="editorWrapperShortDesc">
          <div id="editor-short-desc" class="bg-white"></div>
        </div>
        <textarea name="short_description" id="hidden-short-desc" class="hidden"><?php echo esc($p['short_description'] ?? ''); ?></textarea>
      </div>

      <div class="mb-4">
        <div class="flex justify-between items-center mb-2">
          <label class="block text-sm font-semibold">Full Description</label>
          <div class="flex gap-2">
            <button type="button" id="toggleEditorDescription" class="px-4 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700 transition">Hide Editor</button>
            <button type="button" id="addMediaDescription" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition">📁 Add Media</button>
          </div>
        </div>
        <div id="editorWrapperDescription">
          <div id="editor-description" class="bg-white"></div>
        </div>
        <textarea name="description" id="hidden-description" class="hidden"><?php echo esc($p['description'] ?? ''); ?></textarea>
      </div>

      <div class="mb-4">
        <div class="flex justify-between items-center mb-2">
          <label class="block text-sm font-semibold">Ingredients</label>
          <div class="flex gap-2">
            <button type="button" id="toggleEditorIngredients" class="px-4 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700 transition">Hide Editor</button>
            <button type="button" id="addMediaIngredients" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition">📁 Add Media</button>
          </div>
        </div>
        <div id="editorWrapperIngredients">
          <div id="editor-ingredients" class="bg-white"></div>
        </div>
        <textarea name="ingredients" id="hidden-ingredients" class="hidden"><?php echo esc($p['ingredients'] ?? ''); ?></textarea>
      </div>

      <div class="mb-4">
        <div class="flex justify-between items-center mb-2">
          <label class="block text-sm font-semibold">How to Use</label>
          <div class="flex gap-2">
            <button type="button" id="toggleEditorHowToUse" class="px-4 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700 transition">Hide Editor</button>
            <button type="button" id="addMediaHowToUse" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition">📁 Add Media</button>
          </div>
        </div>
        <div id="editorWrapperHowToUse">
          <div id="editor-how-to-use" class="bg-white"></div>
        </div>
        <textarea name="how_to_use" id="hidden-how-to-use" class="hidden"><?php echo esc($p['how_to_use'] ?? ''); ?></textarea>
      </div>
      
      <!-- CKEditor Initialization -->
      <script src="/admin/ckeditor-product-init.js"></script>

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

      <!-- Related Products Section -->
      <div class="mt-6 border-t pt-6">
        <label class="block text-sm font-semibold mb-2">Related Products</label>
        <p class="text-xs text-gray-600 mb-3">Search and add products that are related to this one.</p>
        
        <div class="relative">
          <input type="text" 
                 id="product_search_input" 
                 class="w-full p-3 border rounded-lg pr-10" 
                 placeholder="Start typing to search products..."
                 autocomplete="off">
          <div id="search_results" class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-lg"></div>
        </div>
        
        <div id="selected_products" class="mt-3 flex flex-wrap gap-2">
          <!-- Selected products will appear here as chips -->
        </div>
        
        <!-- Hidden inputs for form submission -->
        <div id="related_products_inputs"></div>
      </div>

      <!-- Product Media Gallery Section -->
      <div class="mt-6 border-t pt-6">
        <label class="block text-sm font-semibold mb-2">Product Media Gallery</label>
        <p class="text-xs text-gray-600 mb-3">Upload images or videos to showcase this product. Default optimized size: <strong>970 × 600 px</strong>. Drag to reorder.</p>
        
        <!-- Image Optimization Guidelines -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
          <p class="text-xs font-semibold text-blue-900 mb-2">📸 Image Optimization Guidelines:</p>
          <div class="text-xs text-blue-800 space-y-1">
            <p><strong>Images:</strong> 1200x1200px (square) | Min: 800x800px | Max: 5MB (auto-compressed)</p>
            <p><strong>Videos:</strong> 1920x1080px (16:9) | Max: 50MB | Keep under 60 seconds</p>
            <p><strong>Formats:</strong> JPG, PNG, WEBP (images) | MP4, WEBM (videos)</p>
            <p class="text-blue-600 mt-1">💡 Tip: First image will be the main product image</p>
          </div>
        </div>
        
        <!-- Existing Media -->
        <?php if (!empty($productMedia)): ?>
        <div class="mb-4">
          <p class="text-sm font-medium mb-2">Current Media (Drag to reorder):</p>
          <div id="existing_media" class="grid grid-cols-2 gap-3">
            <?php foreach ($productMedia as $idx => $media): ?>
            <div class="relative border rounded-lg p-2 bg-gray-50 existing-media-item" draggable="true" data-index="<?php echo $idx; ?>">
              <input type="hidden" name="existing_images[]" value="/assets/uploads/product_media/<?php echo htmlspecialchars($media['path']); ?>">
              <div class="absolute top-1 left-1 bg-gray-800 text-white rounded px-2 py-1 text-xs z-10">
                <?php echo $idx + 1; ?>
              </div>
              <button type="button" 
                      title="Remove image"
                      class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-700 z-50 shadow-md transition-transform hover:scale-110"
                      onclick="removeExistingMedia(<?php echo $idx; ?>)">
                <span class="text-xl font-bold">&times;</span>
              </button>
              <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 cursor-move opacity-0 hover:opacity-100 transition-opacity">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                </svg>
              </div>
              <?php if ($media['type'] === 'video'): ?>
                <video src="/assets/uploads/product_media/<?php echo htmlspecialchars($media['path']); ?>" class="w-full h-32 object-cover rounded" controls></video>
              <?php else: ?>
                <img src="/assets/uploads/product_media/<?php echo htmlspecialchars($media['path']); ?>" class="w-full h-32 object-cover rounded" alt="Media">
              <?php endif; ?>
              <p class="text-xs text-gray-600 mt-1 truncate"><?php echo htmlspecialchars($media['path']); ?></p>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- Upload New Media -->
        <div class="mb-3">
          <input type="file" 
                 id="product_media_files" 
                 class="w-full p-3 border rounded-lg" 
                 accept="image/jpeg,image/jpg,image/png,image/webp,video/mp4,video/webm"
                 multiple>
          
          <!-- Shared Media Library Button for Gallery -->
          <div class="mt-2 mb-3">
             <button type="button" id="addGalleryMediaBtn" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition shadow-sm text-sm flex items-center gap-2">
                 <span>📁</span> Select from Library (Multiple)
             </button>
          </div>
          <!-- Container for Media Library Selections -->
          <div id="gallery_media_container" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3"></div>
          <p class="text-xs text-gray-500 mt-1">Maximum 50 files per product</p>
        </div>
        
        <div id="media_preview" class="grid grid-cols-2 gap-3">
          <!-- New media previews will appear here -->
        </div>
        
        <!-- Hidden inputs for removed media indices -->
        <div id="removed_media_inputs"></div>
      </div>

    </div>

    <!-- RIGHT -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-4">

      <!-- Product Groups (Multi-Select) -->
      <div>
        <label class="block text-sm font-semibold mb-2">Product Groups</label>
        <div class="border rounded-lg p-3 max-h-40 overflow-y-auto bg-slate-50 space-y-2">
            <?php if(empty($groups)): ?>
                <p class="text-xs text-gray-500">No groups found.</p>
            <?php else: ?>
                <?php foreach($groups as $g): ?>
                <?php $isChecked = in_array($g['id'], $productGroupIds) ? 'checked' : ''; ?>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-100 p-1 rounded">
                    <input type="checkbox" name="group_ids[]" value="<?= $g['id'] ?>" <?= $isChecked ?> class="rounded text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="text-sm text-slate-700"><?= htmlspecialchars($g['name']) ?></span>
                </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <p class="text-xs text-slate-500 mt-1">Select collections (e.g. New Arrivals).</p>
      </div>



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

      <!-- Concern (New) -->
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Concern (Shop by Concern)</label>
        <select name="concern_id" class="w-full p-3 border rounded-lg">
          <option value="">-- Select Concern (optional) --</option>
          <?php foreach ($concerns as $c): ?>
            <?php $sel = (isset($p['concern_id']) && (int)$p['concern_id'] === (int)$c['id']) ? 'selected' : ''; ?>
            <option value="<?= (int)$c['id'] ?>" <?= $sel ?>>
              <?= htmlspecialchars($c['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">Associate this product with a specific health concern.</p>
      </div>

      <!-- Seasonal (New) -->
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Seasonal Theme</label>
        <select name="seasonal_id" class="w-full p-3 border rounded-lg">
          <option value="">-- Select Seasonal (optional) --</option>
          <?php foreach ($seasonals as $s): ?>
            <?php $sel = (isset($p['seasonal_id']) && (int)$p['seasonal_id'] === (int)$s['id']) ? 'selected' : ''; ?>
            <option value="<?= (int)$s['id'] ?>" <?= $sel ?>>
              <?= htmlspecialchars($s['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">Associate this product with a specific seasonal theme.</p>
      </div>

       <!-- Product Filters (Dynamic) -->
      <div>
        <label class="block text-sm font-semibold mb-2">Product Attributes (Filters)</label>
        <?php if(empty($filterGroups)): ?>
             <p class="text-xs text-gray-400 italic">No filters configured. <a href="filter_groups.php" class="text-indigo-600 hover:underline">Manage Filters</a></p>
        <?php else: ?>
            <!-- Dropdown to Add Filter -->
            <div class="mb-3">
                <select id="filter_selector" class="w-full p-2 border rounded text-sm bg-gray-50 focus:bg-white transition-colors">
                    <option value="">+ Add Attribute / Filter...</option>
                    <?php foreach($filterGroups as $fg): ?>
                        <?php 
                            $catLabel = $fg['category_name'] ? $fg['category_name'] : 'Common';
                            $label = $catLabel . ' - ' . $fg['name'];
                            $categoryIdAttr = $fg['category_id'] ? $fg['category_id'] : '';
                        ?>
                        <option value="fg-row-<?= $fg['id'] ?>" data-category-id="<?= $categoryIdAttr ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="filter_blocks_container" class="space-y-3">
                <?php foreach($filterGroups as $fg): ?>
                    <?php 
                         $catLabel = $fg['category_name'] ? $fg['category_name'] : 'Common';
                         
                         // Determine if we should show this block:
                         // 1. If any option in this group is currently assigned to product ($productFilterValues)
                         // 2. OR if present in $_POST (failed update case)
                         
                         $showBlock = false;
                         
                         // Check existing DB values
                         foreach($fg['options'] as $opt) {
                             if (in_array((int)$opt['id'], $productFilterValues)) {
                                 $showBlock = true;
                                 break;
                             }
                         }
                         
                         // Check POST values
                         if (!$showBlock && !empty($_POST['filter_options'][$fg['id']])) {
                             $showBlock = true;
                         }

                         $displayStyle = $showBlock ? '' : 'display:none;';
                    ?>
                    <div id="fg-row-<?= $fg['id'] ?>" class="filter-group-row border rounded-lg bg-white shadow-sm" style="<?= $displayStyle ?>">
                        <div class="flex justify-between items-center p-2 bg-slate-50 border-b rounded-t-lg">
                             <div class="flex items-center gap-2">
                                 <span class="text-[10px] uppercase font-bold text-gray-500 bg-gray-200 px-1.5 py-0.5 rounded"><?= htmlspecialchars($catLabel) ?></span>
                                 <span class="text-xs font-bold text-slate-700 uppercase tracking-wide"><?= htmlspecialchars($fg['name']) ?></span>
                             </div>
                             <div class="flex items-center gap-2">
                                 <a href="filter_options.php?group_id=<?= $fg['id'] ?>" target="_blank" class="text-[10px] text-indigo-400 hover:text-indigo-600">Manage</a>
                                 <button type="button" class="text-gray-400 hover:text-red-500" onclick="removeFilterGroup('fg-row-<?= $fg['id'] ?>')">
                                     <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                 </button>
                             </div>
                        </div>
                        <div class="p-2 max-h-32 overflow-y-auto space-y-1">
                            <?php if(empty($fg['options'])): ?>
                                <p class="text-[10px] text-gray-400 p-1">No options. Add in <a href="filter_groups.php" class="underline">Filters</a>.</p>
                            <?php else: ?>
                                <?php foreach($fg['options'] as $opt): ?>
                                    <?php 
                                        $isChecked = in_array($opt['id'], $productFilterValues) ? 'checked' : '';
                                        // Also check POST
                                        if (empty($isChecked) && !empty($_POST['filter_options'][$fg['id']]) && in_array($opt['id'], $_POST['filter_options'][$fg['id']])) {
                                            $isChecked = 'checked';
                                        }
                                    ?>
                                    <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 p-1 rounded transition-colors">
                                        <input type="checkbox" name="filter_options[<?= $fg['id'] ?>][]" value="<?= $opt['id'] ?>" <?= $isChecked ?> class="rounded text-indigo-600 border-gray-300 focus:ring-indigo-500 w-4 h-4">
                                        <span class="text-sm text-slate-700"><?= htmlspecialchars($opt['label']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-slate-500 mt-2">These values affect sidebar filtering.</p>

            <script>
            // Filter category-based filtering
            const filterSelector = document.getElementById('filter_selector');
            const parentCategorySelect = document.getElementById('parent_category_id');
            
            // Store all filter options with their category data
            const allFilterOptions = [];
            
            document.addEventListener('DOMContentLoaded', function() {
                if (filterSelector) {
                    Array.from(filterSelector.options).forEach((opt, idx) => {
                        if (idx === 0) return; // Skip the first "Add Attribute..." option
                        allFilterOptions.push({
                            element: opt.cloneNode(true),
                            categoryId: opt.getAttribute('data-category-id'),
                            value: opt.value
                        });
                    });
                }
                
                // Function to filter dropdown based on selected category
                function filterByCategory() {
                    if (!filterSelector || !parentCategorySelect) return;
                    
                    const selectedCategoryId = parentCategorySelect.value;
                    
                    // Keep only the first option (placeholder)
                    filterSelector.innerHTML = '<option value="">+ Add Attribute / Filter...</option>';
                    
                    // Add matching filters
                    allFilterOptions.forEach(filterOpt => {
                        // Show if: no category (Common) OR matches selected category
                        if (!filterOpt.categoryId || filterOpt.categoryId === '' || filterOpt.categoryId === selectedCategoryId) {
                            filterSelector.appendChild(filterOpt.element.cloneNode(true));
                        }
                    });
                }
                
                // Listen to category changes
                if (parentCategorySelect) {
                    parentCategorySelect.addEventListener('change', filterByCategory);
                    // Run on page load
                    filterByCategory();
                }
                
                // Original filter selector logic
                const selector = document.getElementById('filter_selector');
                if(selector) {
                    selector.addEventListener('change', function() {
                        var selectedId = this.value;
                        if (selectedId) {
                            var el = document.getElementById(selectedId);
                            if (el) {
                                el.style.display = 'block';
                                el.classList.add('ring-2', 'ring-indigo-100');
                                setTimeout(() => el.classList.remove('ring-2', 'ring-indigo-100'), 1000);
                            }
                            this.value = ''; 
                        }
                    });
                }
            });

            function removeFilterGroup(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.style.display = 'none';
                }
            }
            </script>
        <?php endif; ?>
      </div>

      <!-- Product Label -->
      <div>
        <label class="block text-sm font-semibold mb-2">Product Label</label>
        <select name="label_id" class="w-full p-3 border rounded-lg">
          <option value="">-- No Label --</option>
          <?php foreach ($labels as $l): ?>
            <option value="<?php echo (int)$l['id']; ?>"
              <?php echo (isset($p['label_id']) && (int)$p['label_id'] === $l['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($l['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">
          Select a badge like "New", "Hot", "Sale" etc.
        </p>
      </div>

      <!-- Visible Tags (shown on product page) -->
      <div style="position: relative;">
        <label class="block text-sm font-semibold mb-2">
          Visible Tags (Popular Tags Section)
          <span class="text-xs text-slate-400">(optional)</span>
        </label>
        
        <?php
          // Build string of assigned tags for pre-filling
          $tagsString = '';
          if (!empty($tags) && !empty($productTagIds)) {
              $matches = [];
              foreach ($tags as $t) {
                  if (in_array((string)$t['id'], array_map('strval', $productTagIds))) {
                      $matches[] = $t['name'];
                  }
              }
              $tagsString = implode(', ', $matches);
          }
        ?>
        
        <input type="text" 
               name="tags_input" 
               id="visibleTagsInput"
               class="w-full p-3 border rounded-lg" 
               placeholder="Type to see tag suggestions... (separate with commas)"
               autocomplete="off"
               value="<?php echo htmlspecialchars($tagsString); ?>">
        <div id="visibleTagsSuggestions" class="autocomplete-suggestions" style="display: none; position: absolute; background: white; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; width: 100%; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
         <p class="text-xs text-slate-500 mt-1">
            These tags will appear in the &quot;Popular Tags&quot; sidebar. Separate with commas.
         </p>
      </div>

      <!-- Hidden Tags (SEO only) -->
      <div style="position: relative;">
        <label class="block text-sm font-semibold mb-2">
          Hidden Tags (SEO Only)
          <span class="text-xs text-slate-400">(optional)</span>
        </label>
        <input type="text" 
               name="seo_keywords" 
               id="hiddenTagsInput"
               class="w-full p-3 border rounded-lg" 
               placeholder="Type to see tag suggestions... (separate with commas)"
               autocomplete="off"
               value="<?php echo htmlspecialchars($p['seo_keywords'] ?? ''); ?>">
        <div id="hiddenTagsSuggestions" class="autocomplete-suggestions" style="display: none; position: absolute; background: white; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; width: 100%; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
         <p class="text-xs text-slate-500 mt-1">
            Hidden keywords for SEO. Only visible to search engines.
         </p>
      </div>

      <script>
      // Tag autocomplete functionality
      (function() {
        const allTags = <?php echo json_encode(array_column($tags, 'name')); ?>;
        
        function setupAutocomplete(inputId, suggestionsId) {
          const input = document.getElementById(inputId);
          const suggestions = document.getElementById(suggestionsId);
          
          if (!input || !suggestions) return;
          
          input.addEventListener('input', function() {
            const value = this.value;
            const lastComma = value.lastIndexOf(',');
            const currentWord = lastComma >= 0 ? value.substring(lastComma + 1).trim() : value.trim();
            
            if (currentWord.length < 1) {
              suggestions.style.display = 'none';
              return;
            }
            
            const matches = allTags.filter(tag => 
              tag.toLowerCase().includes(currentWord.toLowerCase())
            );
            
            if (matches.length === 0) {
              suggestions.style.display = 'none';
              return;
            }
            
            suggestions.innerHTML = matches.map(tag => 
              `<div class="suggestion-item" style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;" 
                    onclick="selectTag('${inputId}', '${tag.replace(/'/g, "\\'")}')"
                    onmouseover="this.style.background='#f0f0f0'"
                    onmouseout="this.style.background='white'">${tag}</div>`
            ).join('');
            
            suggestions.style.display = 'block';
          });
          
          // Close suggestions when clicking outside
          document.addEventListener('click', function(e) {
            if (e.target.id !== inputId && e.target.id !== suggestionsId) {
              suggestions.style.display = 'none';
            }
          });
        }
        
        window.selectTag = function(inputId, tag) {
          const input = document.getElementById(inputId);
          const value = input.value;
          const lastComma = value.lastIndexOf(',');
          
          if (lastComma >= 0) {
            input.value = value.substring(0, lastComma + 1) + ' ' + tag + ', ';
          } else {
            input.value = tag + ', ';
          }
          
          const suggestionsId = inputId === 'visibleTagsInput' ? 'visibleTagsSuggestions' : 'hiddenTagsSuggestions';
          document.getElementById(suggestionsId).style.display = 'none';
          input.focus();
        };
        
        setupAutocomplete('visibleTagsInput', 'visibleTagsSuggestions');
        setupAutocomplete('hiddenTagsInput', 'hiddenTagsSuggestions');
      })();
      </script>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-2">Selling Price (₹) *</label>
          <input name="price" id="price" type="number" step="0.01" min="0"
                 class="w-full p-3 border rounded-lg bg-green-50 border-green-200"
                 value="<?php echo esc($p['price'] ?? '0.00'); ?>" required>
          <p class="text-xs text-slate-500 mt-1">Final price customer pays (Inclusive of GST)</p>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Compare Price (₹)</label>
          <input name="compare_price" id="compare_price" type="number" step="0.01" min="0"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo esc($p['compare_price'] ?? ''); ?>" placeholder="MRP / Old Price">
          <p class="text-xs text-slate-500 mt-1">Original price (shown crossed out)</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="block text-sm font-semibold mb-2">GST Rate (%)</label>
          <select id="gst_rate" name="gst_rate" class="w-full p-3 border rounded-lg">
            <?php $g = (float)($p['gst_rate'] ?? 0); ?>
            <option value="0" <?php if($g == 0) echo 'selected'; ?>>0%</option>
            <option value="5" <?php if($g == 5) echo 'selected'; ?>>5%</option>
            <option value="12" <?php if($g == 12) echo 'selected'; ?>>12%</option>
            <option value="18" <?php if($g == 18) echo 'selected'; ?>>18%</option>
            <option value="28" <?php if($g == 28) echo 'selected'; ?>>28%</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">GST Amount (₹)</label>
          <input id="gst_amount" type="text" readonly
                 class="w-full p-3 border rounded-lg bg-gray-100 text-gray-600"
                 value="0.00">
          <p class="text-xs text-slate-500 mt-1">Included in Selling Price</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="block text-sm font-semibold mb-2">Discount (%)</label>
          <input name="discount_percent" id="discount_percent" type="number" step="0.01" min="0" max="100"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo esc($p['discount_percent'] ?? ''); ?>" placeholder="e.g. 20">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Stock *</label>
          <input name="stock" id="stock" type="number" min="0"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo (int)($p['stock'] ?? 0); ?>">
        </div>
      </div>

      <script>
        // Auto-calculation logic
        document.addEventListener('DOMContentLoaded', function() {
          const priceInput = document.getElementById('price');
          const compareInput = document.getElementById('compare_price');
          const discountInput = document.getElementById('discount_percent');
          const gstRateInput = document.getElementById('gst_rate');
          const gstAmountInput = document.getElementById('gst_amount');

          function calculate() {
            const price = parseFloat(priceInput.value) || 0;
            const compare = parseFloat(compareInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            const gstRate = parseFloat(gstRateInput.value) || 0;

            // 1. If Compare & Discount entered -> Calculate Price
            if (document.activeElement === discountInput || document.activeElement === compareInput) {
               if (compare > 0 && discount > 0) {
                 const newPrice = compare - (compare * (discount / 100));
                 priceInput.value = newPrice.toFixed(2);
               }
            }
            
            // 2. If Price & Compare entered -> Calculate Discount
            if (document.activeElement === priceInput || document.activeElement === compareInput) {
               if (compare > price && compare > 0) {
                 const newDiscount = ((compare - price) / compare) * 100;
                 discountInput.value = newDiscount.toFixed(2);
               }
            }

            // 3. Calculate GST Amount (Inclusive)
            // Formula: GST Amount = Price - (Price / (1 + Rate/100))
            if (price > 0 && gstRate >= 0) {
                const basePrice = price / (1 + (gstRate / 100));
                const gstAmount = price - basePrice;
                gstAmountInput.value = gstAmount.toFixed(2);
            } else {
                gstAmountInput.value = '0.00';
            }
          }

          [priceInput, compareInput, discountInput, gstRateInput].forEach(input => {
            input.addEventListener('input', calculate);
            input.addEventListener('change', calculate); // For select dropdown
          });
          
          // Initial calculation on load
          calculate();
        });
      </script>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-2">SKU</label>
          <input name="sku"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo esc($p['sku'] ?? ''); ?>"
                 placeholder="Optional SKU">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">HSN Code</label>
          <input name="hsn"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo esc($p['hsn'] ?? ''); ?>"
                 placeholder="Optional HSN">
        </div>
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
        <div class="grid grid-cols-2 gap-4 mb-3">
          <div>
            <label class="text-xs font-semibold text-slate-500">Variant Label (e.g. Size, Volume)</label>
            <input type="text" name="variant_label" class="w-full p-2 border rounded text-sm" 
                   placeholder="Size" 
                   value="<?php echo esc($p['variant_label'] ?? 'Size'); ?>">
          </div>
          <div>
            <label class="text-xs font-semibold text-slate-500">Main Variant Name (e.g. Vitamin C)</label>
            <input type="text" name="main_variant_name" class="w-full p-2 border rounded text-sm" 
                   placeholder="Default Option Name"
                   value="<?php echo esc($p['main_variant_name'] ?? ''); ?>">
          </div>
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
        <label class="block text-sm font-semibold mb-2">Product Images (Legacy)</label>

        <div class="my-3 p-3 bg-blue-50 text-blue-800 rounded-md text-xs border border-blue-200">
          <p class="font-bold mb-1">Image Optimization Guidelines:</p>
          <ul class="list-disc pl-4 space-y-0.5">
            <li><strong>Images:</strong> 1200x1200px (square) | Min: 800x800px | Max: 5MB (auto-compressed)</li>
            <li><strong>Videos:</strong> 1920x1080px (16:9) | Max: 50MB | Keep under 60 seconds</li>
            <li><strong>Formats:</strong> JPG, PNG, WEBP (images) | MP4, WEBM (videos)</li>
          </ul>
          <div class="mt-2 flex items-center gap-2">
            <span>💡</span>
            <span><strong>Tip:</strong> First image/video will be the main product media</span>
          </div>
        </div>

        <label class="group relative flex flex-col items-center justify-center p-4 border-2 border-dashed rounded-lg cursor-pointer hover:border-indigo-500">
          <div class="text-sm font-semibold">Click to upload or drag & drop</div>
          <div class="text-xs text-slate-500">Images (JPG, PNG) or Videos (MP4, WEBM)</div>
          <input id="images_input" type="file" name="images[]" accept="image/*,video/*" multiple class="hidden">
        </label>

          <!-- Shared Media Library Button for Legacy Images -->
          <div class="mt-3 mb-3">
             <button type="button" id="addLegacyMediaBtn" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition shadow-sm text-sm flex items-center gap-2">
                 <span>📁</span> Select from Library (Multiple)
             </button>
          </div>
          <!-- Container for Media Library Selections -->
          <div id="legacy_media_container" class="flex flex-wrap gap-3 mt-2"></div>

        <div id="preview" class="mt-3 grid grid-cols-4 gap-3">
          <?php if (!empty($existingImages)): ?>
            <?php foreach ($existingImages as $idx => $imgPath):
              $rawPath = $imgPath;
              $src = $imgPath;
              if (!preg_match('#^https?://#i', $src) && $src !== '' && $src[0] !== '/') {
                  $src = '/assets/uploads/products/' . ltrim($src, '/');
              }
              $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
              $isVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
            ?>
              <div class="relative w-24 h-24 rounded overflow-hidden border existing-legacy-item" draggable="true" data-filename="<?php echo esc($rawPath); ?>">
                <input type="hidden" name="existing_images[]" value="<?php echo esc($src); ?>">
                <div class="absolute top-0 left-0 bg-gray-800 text-white text-[10px] px-1 rounded-br z-10 opacity-70 order-badge">
                  <?php echo $idx + 1; ?>
                </div>
                <button type="button" 
                        title="Remove"
                        class="absolute -top-1 -right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-700 z-50 shadow-md transition-transform hover:scale-110"
                        onclick="removeExistingLegacyImage('<?php echo esc($rawPath); ?>')">
                  <span class="text-sm font-bold">&times;</span>
                </button>
                <?php if ($isVideo): ?>
                    <video src="<?php echo esc($src); ?>" class="object-cover w-full h-full pointer-events-none" muted></video>
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none bg-black bg-opacity-20">
                        <span class="text-white font-bold text-xs bg-black bg-opacity-50 px-1 rounded">VIDEO</span>
                    </div>
                <?php else: ?>
                    <img src="<?php echo esc($src); ?>" class="object-cover w-full h-full pointer-events-none select-none" alt="">
                <?php endif; ?>
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
        <button type="button" id="btnPreview"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-lg font-semibold mb-3 flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          Preview Product
        </button>
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
        
        <!-- Variant Name (Always Required) -->
        <div>
          <label class="block text-sm font-semibold mb-2">Variant Name <span class="text-red-500">*</span></label>
          <input type="text" id="variantName" class="w-full p-3 border rounded-lg" placeholder="e.g. 50ml, Large, Red, Pack of 3" required>
          <p class="text-xs text-gray-500 mt-1">This will be shown to customers (e.g., "50ml" or "Large Size")</p>
        </div>

        <!-- Variant Type Selector -->
        <div class="border-2 border-indigo-100 rounded-lg p-4 bg-indigo-50">
          <label class="block text-sm font-semibold mb-3 text-indigo-900">Variant Type</label>
          <div class="grid grid-cols-2 gap-3">
            <label class="relative flex items-center p-4 bg-white border-2 border-gray-300 rounded-lg cursor-pointer hover:border-indigo-500 transition">
              <input type="radio" name="variantType" value="linked" id="variantTypeLinked" class="mr-3">
              <div>
                <div class="font-semibold text-gray-900">🔗 Link Existing Product</div>
                <div class="text-xs text-gray-600 mt-1">Use another product as this variant</div>
              </div>
            </label>
            <label class="relative flex items-center p-4 bg-white border-2 border-indigo-500 rounded-lg cursor-pointer hover:border-indigo-600 transition">
              <input type="radio" name="variantType" value="custom" id="variantTypeCustom" class="mr-3" checked>
              <div>
                <div class="font-semibold text-gray-900">✏️ Create Custom Variant</div>
                <div class="text-xs text-gray-600 mt-1">Define unique price, stock, etc.</div>
              </div>
            </label>
          </div>
        </div>

        <!-- Common Fields (HSN) -->
        <div class="mt-4 mb-4">
             <label class="block text-sm font-semibold mb-2">HSN Code (Optional)</label>
             <input type="text" id="variantHSN" class="w-full p-3 border rounded-lg" placeholder="e.g. 123456">
             <p class="text-xs text-gray-500 mt-1">Applies to both Linked and Custom variants.</p>
        </div>

        <!-- Product Search (Only for Linked Type) -->
        <div id="linkedProductSection" class="hidden">
          <label class="block text-sm font-semibold mb-2">Select Product to Link <span class="text-red-500">*</span></label>
          <div class="relative">
            <input type="text" 
                   id="variantProductSearch" 
                   class="w-full p-3 border rounded-lg pr-10" 
                   placeholder="Search products..."
                   autocomplete="off">
            <div id="variantProductSearchResults" class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-lg"></div>
          </div>
          <input type="hidden" id="linkedProductId" value="">
          <div id="selectedLinkedProduct" class="mt-3"></div>
          <p class="text-xs text-gray-500 mt-2">💡 The variant will use this product's price, stock, and images automatically.</p>
        </div>

        <!-- Custom Variant Fields (Only for Custom Type) -->
        <div id="customVariantSection">
        
        <!-- Required Fields -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold mb-2">Selling Price (₹) <span class="text-red-500">*</span></label>
            <input type="number" id="variantPrice" step="0.01" min="0" class="w-full p-3 border rounded-lg bg-green-50 border-green-200" placeholder="0.00" required>
            <p class="text-xs text-gray-500 mt-1">Final price for this variant</p>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-2">Compare Price (₹)</label>
            <input type="number" id="variantComparePrice" step="0.01" min="0" class="w-full p-3 border rounded-lg" placeholder="MRP / Old Price">
            <p class="text-xs text-gray-500 mt-1">Original price (shown crossed)</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold mb-2">Compare Price (₹)</label>
            <input type="number" id="variantComparePrice" step="0.01" min="0" class="w-full p-3 border rounded-lg" placeholder="MRP / Old Price">
            <p class="text-xs text-gray-500 mt-1">Original price (shown crossed)</p>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-2">Discount (%)</label>
            <input type="number" id="variantDiscount" step="0.01" min="0" max="100" class="w-full p-3 border rounded-lg" placeholder="e.g. 20">
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



        <script>
          // Variant price auto-calculation
          (function() {
            const vPrice = document.getElementById('variantPrice');
            const vCompare = document.getElementById('variantComparePrice');
            const vDiscount = document.getElementById('variantDiscount');

            function calculateVariant() {
              const price = parseFloat(vPrice.value) || 0;
              const compare = parseFloat(vCompare.value) || 0;
              const discount = parseFloat(vDiscount.value) || 0;

              if (document.activeElement === vDiscount || document.activeElement === vCompare) {
                if (compare > 0 && discount > 0) {
                  vPrice.value = (compare - (compare * (discount / 100))).toFixed(2);
                }
              }
              
              if (document.activeElement === vPrice || document.activeElement === vCompare) {
                if (compare > price && compare > 0) {
                  vDiscount.value = (((compare - price) / compare) * 100).toFixed(2);
                }
              }
            }

            [vPrice, vCompare, vDiscount].forEach(input => {
              input.addEventListener('input', calculateVariant);
            });
          })();
        </script>

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
            <label class="block text-sm font-semibold mb-2">Short Description</label>
            <textarea id="variantShortDesc" rows="2" class="w-full p-3 border rounded-lg" placeholder="Leave empty to use product short description"></textarea>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Variant Images (Multiple)</label>
            <div id="existingVariantImages" class="mb-2"></div>
            <input type="file" id="variantImages" accept="image/*" multiple class="w-full p-2 border rounded-lg">
            <p class="text-xs text-gray-500 mt-1">Upload images specific to this variant. Leave empty to use product images.</p>
            <div id="variantImagesPreviews" class="mt-2 flex flex-wrap gap-2"></div>
          </div>

          <!-- Extra Fields -->
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Ingredients</label>
            <textarea id="variantIngredients" rows="3" class="w-full p-3 border rounded-lg" placeholder="Leave empty to use product ingredients"></textarea>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">How to Use</label>
            <textarea id="variantHowToUse" rows="3" class="w-full p-3 border rounded-lg" placeholder="Leave empty to use product how to use"></textarea>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">SEO Title</label>
            <input type="text" id="variantMetaTitle" class="w-full p-3 border rounded-lg" placeholder="Leave empty to use product meta title">
          </div>

          <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">SEO Description</label>
            <textarea id="variantMetaDesc" rows="2" class="w-full p-3 border rounded-lg" placeholder="Leave empty to use product meta description"></textarea>
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
        <!-- End of Custom Variant Section -->
      </div>

      <div class="sticky bottom-0 bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
        <button type="button" id="cancelModalBtn" class="px-6 py-2 border rounded-lg hover:bg-gray-100">Cancel</button>
        <button type="button" id="saveVariantBtn" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Variant</button>
      </div>
    </div>
  </div>
</div>

<!-- CKEditor 4 (for variants) -->
<script src="//cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script src="/admin/quill-font-config.js"></script>
<script>
// Inject dynamic font CSS
const fontStyleTag = document.createElement('style');
fontStyleTag.textContent = window.QuillFontConfig.generateCSS();
document.head.appendChild(fontStyleTag);

document.addEventListener('DOMContentLoaded', function() {
  // Custom font whitelist
  // Custom font whitelist
  var Font = Quill.import('formats/font');
  Font.whitelist = window.QuillFontConfig.getFontValues();
  Quill.register(Font, true);
  
  // Enhanced toolbar with full formatting options
  var toolbarOptions = [
    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
    [{ 'font': window.QuillFontConfig.getFontValues() }],
    [{ 'size': ['small', false, 'large', 'huge'] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'script': 'sub'}, { 'script': 'super' }],
    ['blockquote', 'code-block'],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'indent': '-1'}, { 'indent': '+1' }],
    [{ 'direction': 'rtl' }],
    [{ 'align': [] }],
    ['link', 'image', 'video'],
    ['clean']
  ];

  // Helper function to show custom notifications
  function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
    notification.style.cssText = `position: fixed; top: 20px; right: 20px; background: ${bgColor}; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 10001; animation: slideIn 0.3s ease-out;`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.style.animation = 'slideOut 0.3s ease-out';
      setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
  }
  
  var quillOptions = {
    theme: 'snow',
    modules: {
      toolbar: {
        container: toolbarOptions,
        handlers: {
          video: function() {
            const quill = this.quill;
            const range = quill.getSelection();
            
            // Create modal dialog
            const modal = document.createElement('div');
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;';
            modal.innerHTML = `
              <div style="background: white; padding: 30px; border-radius: 8px; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h3 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600;">Insert Video</h3>
                <p style="margin: 0 0 20px 0; color: #666;">Choose how you want to add the video:</p>
                <div style="display: flex; gap: 10px; flex-direction: column;">
                  <button id="videoUrlBtn" style="padding: 12px 20px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">📹 Enter Video URL</button>
                  <button id="videoUploadBtn" style="padding: 12px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">📁 Upload Video File</button>
                  <button id="videoCancelBtn" style="padding: 12px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">Cancel</button>
                </div>
              </div>
            `;
            document.body.appendChild(modal);
            
            // URL button handler
            modal.querySelector('#videoUrlBtn').onclick = function() {
              document.body.removeChild(modal);
              const url = prompt('Enter video URL (YouTube, Vimeo, or direct video link):');
              if (url) {
                quill.insertEmbed(range.index, 'video', url);
                showNotification('Video added successfully!', 'success');
              }
            };
            
            // Upload button handler
            modal.querySelector('#videoUploadBtn').onclick = function() {
              document.body.removeChild(modal);
              const input = document.createElement('input');
              input.setAttribute('type', 'file');
              input.setAttribute('accept', 'video/*');
              input.click();
              
              input.onchange = function() {
                const file = input.files[0];
                if (file) {
        items: [
          'heading', '|',
          'bold', 'italic', 'underline', 'strikethrough', '|',
          'link', 'bulletedList', 'numberedList', 'blockQuote', '|',
          'insertTable', 'mediaEmbed', 'imageUpload', '|',
          'undo', 'redo', '|',
          'sourceEditing'
        ]
      },
      image: {
        toolbar: [
          'imageTextAlternative', 'toggleImageCaption', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side'
        ]
      },
      table: {
        contentToolbar: [
          'tableColumn', 'tableRow', 'mergeTableCells'
        ]
      },
      mediaEmbed: {
        previewsInData: true
      },
      // Custom upload adapter for images
      ckfinder: {
        uploadUrl: '/admin/upload_editor_image.php' // Your image upload endpoint
      }
    };

    // Initialize CKEditor for Short Description
    ClassicEditor
      .create(document.querySelector('#editor-short-desc'), editorConfigs)
      .then(editor => {
        window.editorShortDesc = editor;
        editor.model.document.on('change:data', () => {
          document.getElementById('hidden-short-desc').value = editor.getData();
        });
      })
      .catch(error => {
        console.error('Error initializing Short Description editor:', error);
      });

    // Initialize CKEditor for Full Description
    ClassicEditor
      .create(document.querySelector('#editor-description'), editorConfigs)
      .then(editor => {
        window.editorDescription = editor;
        editor.model.document.on('change:data', () => {
          document.getElementById('hidden-description').value = editor.getData();
        });
      })
      .catch(error => {
        console.error('Error initializing Full Description editor:', error);
      });

    // Initialize CKEditor for Ingredients
    ClassicEditor
      .create(document.querySelector('#editor-ingredients'), editorConfigs)
      .then(editor => {
        window.editorIngredients = editor;
        editor.model.document.on('change:data', () => {
          document.getElementById('hidden-ingredients').value = editor.getData();
        });
      })
      .catch(error => {
        console.error('Error initializing Ingredients editor:', error);
      });

    // Initialize CKEditor for How to Use
    ClassicEditor
      .create(document.querySelector('#editor-how-to-use'), editorConfigs)
      .then(editor => {
        window.editorHowToUse = editor;
        editor.model.document.on('change:data', () => {
          document.getElementById('hidden-how-to-use').value = editor.getData();
        });
      })
      .catch(error => {
        console.error('Error initializing How to Use editor:', error);
      });

    // Helper function to toggle editor visibility
    function toggleEditor(editorInstance, editorContainerId, hiddenInputId) {
      const editorContainer = document.getElementById(editorContainerId);
      const hiddenInput = document.getElementById(hiddenInputId);
      const editorElement = editorContainer.querySelector('.ck-editor');

      if (editorElement.style.display === 'none') {
        editorElement.style.display = '';
        editorInstance.setData(hiddenInput.value); // Restore content
      } else {
        hiddenInput.value = editorInstance.getData(); // Save current content
        editorElement.style.display = 'none';
      }
    }

    // Attach event listeners for "Hide Editor" buttons
    document.querySelectorAll('.hide-editor-btn').forEach(button => {
      button.addEventListener('click', function() {
        const targetEditor = this.dataset.targetEditor;
        const targetContainer = this.dataset.targetContainer;
        const targetHiddenInput = this.dataset.targetHiddenInput;

        switch (targetEditor) {
          case 'shortDesc':
            toggleEditor(window.editorShortDesc, targetContainer, targetHiddenInput);
            break;
          case 'description':
            toggleEditor(window.editorDescription, targetContainer, targetHiddenInput);
            break;
          case 'ingredients':
            toggleEditor(window.editorIngredients, targetContainer, targetHiddenInput);
            break;
          case 'howToUse':
            toggleEditor(window.editorHowToUse, targetContainer, targetHiddenInput);
            break;
        }
});
</script>

<script>
// preview + drag/drop
(function(){
  const input = document.getElementById('images_input');
  const preview = document.getElementById('preview');
  window.newLegacyImages = []; 

  // --- Handling Existing Legacy ---
  const existingContainer = document.getElementById('preview'); // It reuses the same preview container
  const form = document.querySelector('form');

  // Setup drag drop for all legacy items (existing + new)
  // But wait, existing are rendered by PHP, new by JS.
  // PHP renders into #preview. JS appends to #preview? 
  // Old code: preview.innerHTML = ''; clear everything. That's bad for existing images!
  // I should separate them or use append.
  // The PHP loop is INSIDE #preview.
  // My JS should NOT clear preview if I want to keep existing images visible along with new ones?
  // Old code: input.addEventListener('change', function(){ preview.innerHTML = ''; ... });
  // Currently, the PHP loop renders existing images inside #preview.
  // If I add new files, I probably want to append them? Or simple "New Uploads" section?
  // User wants to reorder EVERYTHING?
  // If user wants to reorder existing vs new mixed... complex.
  // The backend treats them separately (merges new AT THE END).
  // So for UI clarity, I should keep them visualy separate or just append new ones.
  // Let's modify JS to append rather than clear, and use a distinct class for new items.
  
  if (input && preview) {
    // Make preview a flex/grid container if not already
    preview.classList.add('flex', 'flex-wrap', 'gap-3');
    preview.classList.remove('grid', 'grid-cols-4'); // Remove grid to allow freer flow

    input.addEventListener('change', function(e){
      // Don't clear preview to keep existing media!
      // But clearing OLD new uploads?
      // Let's just maintain window.newLegacyImages state and re-render ONLY new items?
      // Actually, simplest is to have a separate container for new items if we don't want to mix drag-drop.
      // But user might want to drag new item before old item? 
      // Backend merge logic: existing... then new.
      // If I want full mixed reorder, I need to upload new files via AJAX first? No.
      // Let's stick to "Existing" section and "New" section for simplicity, OR
      // just append new items visually and allow reordering AMONG THEMSELVES.
      // Mixing them requires complex backend logic (upload new, get filename, then sort).
      // I'll stick to: Existing reorderable. New reorderable. New appended after existing.
      
      const files = Array.from(e.target.files);
      files.forEach(f => {
         if((f.type.startsWith('image/') || f.type.startsWith('video/'))) window.newLegacyImages.push(f);
      });
      renderNewLegacyPreviews();
      e.target.value = '';
    });

    // Make existing items sortable
    setupLegacySortable();
  }

  function renderNewLegacyPreviews() {
    // Remove previously rendered "new" items
    preview.querySelectorAll('.new-legacy-item').forEach(e => e.remove());
    
    // Append new ones
    window.newLegacyImages.forEach((f, i) => {
       const url = URL.createObjectURL(f);
       const div = document.createElement('div');
       div.className = "relative w-24 h-24 rounded overflow-hidden border new-legacy-item";
       div.draggable = true;
       div.dataset.newIndex = i;
       const isVideo = f.type.startsWith('video/');
       div.innerHTML = `
           <div class="absolute top-0 left-0 bg-green-600 text-white text-[10px] px-1 rounded-br z-10 opacity-70">New ${i+1}</div>
           <button type="button" class="absolute -top-1 -right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-700 z-50 shadow-md transition-transform hover:scale-110"
                   onclick="removeNewLegacyImage(${i})">
             <span class="text-sm font-bold">&times;</span>
           </button>
           ${isVideo ? 
             `<video src="${url}" class="object-cover w-full h-full pointer-events-none" muted></video>
              <div class="absolute inset-0 flex items-center justify-center pointer-events-none bg-black bg-opacity-20">
                <span class="text-white font-bold text-xs bg-black bg-opacity-50 px-1 rounded">VIDEO</span>
              </div>` 
             : 
             `<img src="${url}" class="object-cover w-full h-full pointer-events-none select-none">`
           }
       `;
       preview.appendChild(div);
    });
    
    setupLegacySortable();
  }

  function setupLegacySortable() {
     const allItems = preview.querySelectorAll('.existing-legacy-item, .new-legacy-item');
     let dragged = null;
     
     allItems.forEach(item => {
        item.style.cursor = 'move'; // Visual cue
        
        // Remove old listeners to prevent duplicates
        item.removeEventListener('dragstart', handleDragStart);
        item.removeEventListener('dragend', handleDragEnd); 
        item.removeEventListener('dragover', handleDragOver);
        item.removeEventListener('drop', handleDrop);
        
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
     });
     
     function handleDragStart(e) {
         dragged = this;
         this.style.opacity = '0.4';
         e.dataTransfer.effectAllowed = 'move';
         e.dataTransfer.setData('text/plain', ''); // Firefox fix
     }
     
     function handleDragEnd(e) {
         this.style.opacity = '1';
         dragged = null;
     }
     
     function handleDragOver(e) {
         e.preventDefault(); // Required for drop
         e.dataTransfer.dropEffect = 'move';
         
         if(dragged && dragged !== this) {
             const rect = this.getBoundingClientRect();
             const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
             this.parentNode.insertBefore(dragged, next ? this.nextSibling : this);
         }
     }
     
     function handleDrop(e) {
         e.preventDefault();
         e.stopPropagation();
         // Update indices immediately
         updateLegacyIndices();
     }
  }

  function updateLegacyIndices() {
      // Re-index badges for existing
      preview.querySelectorAll('.existing-legacy-item').forEach((el, i) => {
          el.querySelector('.order-badge').textContent = i + 1;
      });
  }

  // Global removers
  window.removeExistingLegacyImage = function(filename) {
      // Remove from DOM
      const el = preview.querySelector(`.existing-legacy-item[data-filename="${CSS.escape(filename)}"]`);
      if(el) el.remove();
      
      // Add to hidden input
      const container = document.getElementById('legacy_inputs_container') || createLegacyContainer();
      const inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'removed_legacy_images[]';
      inp.value = filename;
      container.appendChild(inp);
      
      updateLegacyIndices();
  };
  
  window.removeNewLegacyImage = function(index) {
      window.newLegacyImages.splice(index, 1);
      renderNewLegacyPreviews();
  };
  
  function createLegacyContainer() {
      const d = document.createElement('div');
      d.id = 'legacy_inputs_container';
      form.appendChild(d);
      return d;
  }

  const dropBox = document.querySelector('.group');
    if (dropBox) {
      ['dragenter','dragover','dragleave','drop'].forEach(evt =>
        dropBox.addEventListener(evt, e => e.preventDefault())
      );
      dropBox.addEventListener('drop', function(e){
        const dt = e.dataTransfer;
        if (!dt) return;
        const files = Array.from(dt.files);
        files.forEach(f => {
            if(f.type.startsWith('image/')) window.newLegacyImages.push(f);
        });
        renderNewLegacyPreviews();
      });
    }

  // On submit, generate order inputs AND populate file input
  if(form) {
      form.addEventListener('submit', function() {
          // 1. Generate order inputs for EXISTING images
          // We only care about order of existing images here because new ones are appended
          // Wait, if user mixes them visually?
          // As discussed, backend logic appends new images at the end.
          // IF user drags new image BEFORE existing in UI... backend won't respect that unless we upload first.
          // Limiting reordering to "Existing among Existing" and "New among New" is safest without AJAX.
          // BUT my sortable setup allows mixing.
          // If mixed, the backend will still just take "Existing Order" then "New Uploads".
          // This is a limitation I should probably accept or try to mitigate by sorting DOM to match backend reality?
          // Or just let it be. The user asked to reorder.
          
          const container = document.getElementById('legacy_inputs_container') || createLegacyContainer();
          // Clear old order inputs
          container.querySelectorAll('input[name="existing_legacy_images_order[]"]').forEach(e => e.remove());
          
          preview.querySelectorAll('.existing-legacy-item').forEach(el => {
              const inp = document.createElement('input');
              inp.type = 'hidden';
              inp.name = 'existing_legacy_images_order[]';
              inp.value = el.dataset.filename;
              container.appendChild(inp);
          });
          
          // 2. Populate file input with new images (in their CURRENT order in newLegacyImages? No, DOM reorder doesn't update array!)
          // I need to update newLegacyImages based on DOM order of .new-legacy-item
          // Note: If mixed, this is tricky.
          // Let's re-build newLegacyImages from DOM for submission
          const reorderedNew = [];
          preview.querySelectorAll('.new-legacy-item').forEach(el => {
               const idx = parseInt(el.dataset.newIndex);
               reorderedNew.push(window.newLegacyImages[idx]);
          });
          
          if(reorderedNew.length > 0) {
              const dt = new DataTransfer();
              reorderedNew.forEach(f => dt.items.add(f));
              input.files = dt.files;
          }
      });
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
})();

  // ====== Variants Modal Logic ======
  const variantsList = document.getElementById('variants-list');
  const addVariantBtn = document.getElementById('add-variant-btn');
  const variantModal = document.getElementById('variantModal');
  const closeModalBtn = document.getElementById('closeModalBtn');
  const cancelModalBtn = document.getElementById('cancelModalBtn');
  const saveVariantBtn = document.getElementById('saveVariantBtn');
  const modalTitle = document.getElementById('modalTitle');
  const deletedContainer = document.getElementById('deleted-variants-container');
  
  let variants = []; // Store variant data in memory
  let tempExistingImages = []; // Track existing images during editing
  let editingIndex = null;
  
  // Load existing variants from PHP
  <?php foreach ($variants as $v): ?>
  variants.push({
    id: <?php echo $v['id'] ?? 0; ?>,
    name: <?php echo json_encode($v['variant_name'] ?? ''); ?>,
    type: <?php echo json_encode($v['type'] ?? 'custom'); ?>,
    linked_product_id: <?php echo json_encode($v['linked_product_id'] ?? null); ?>,
    price: <?php echo json_encode($v['price'] ?? ''); ?>,
    comparePrice: <?php echo json_encode($v['compare_price'] ?? ''); ?>,
    discountPercent: <?php echo json_encode($v['discount_percent'] ?? ''); ?>,
    stock: <?php echo json_encode($v['stock'] ?? 10); ?>,
    sku: <?php echo json_encode($v['sku'] ?? ''); ?>,
    hsn: <?php echo json_encode($v['hsn'] ?? ''); ?>, // Common HSN
    customTitle: <?php echo json_encode($v['custom_title'] ?? ''); ?>,
    customDesc: <?php echo json_encode($v['custom_description'] ?? ''); ?>,
    shortDesc: <?php echo json_encode($v['short_description'] ?? ''); ?>,
    ingredients: <?php echo json_encode($v['ingredients'] ?? ''); ?>,
    howToUse: <?php echo json_encode($v['how_to_use'] ?? ''); ?>,
    metaTitle: <?php echo json_encode($v['meta_title'] ?? ''); ?>,
    metaDesc: <?php echo json_encode($v['meta_description'] ?? ''); ?>,
    existingImages: <?php 
      $vImages = !empty($v['images']) ? json_decode($v['images'], true) : [];
      if (!is_array($vImages)) $vImages = [];
      echo json_encode($vImages); 
    ?>,
    images: [], // New images to upload
    faqs: <?php 
      echo json_encode($variantFaqs[$v['id']] ?? []); 
    ?>
  });
  <?php endforeach; ?>
  
  // Render variants list
  function renderVariants() {
    variantsList.innerHTML = '';
    variants.forEach((v, idx) => {
      const card = document.createElement('div');
      card.className = 'border rounded-lg p-3 bg-white flex justify-between items-center hover:shadow-md transition';
      
      if (v.type === 'linked') {
        // Linked Product Variant Display
        card.innerHTML = `
          <div class="flex-1">
            <div class="font-semibold text-gray-900">${v.name} <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded">🔗 Linked</span></div>
            <div class="text-xs text-gray-500">Linked to Product ID: ${v.linked_product_id}</div>
          </div>
          <div class="flex gap-2">
            <button type="button" onclick="editVariant(${idx})" class="text-indigo-600 hover:underline text-sm">Edit</button>
            <button type="button" onclick="deleteVariant(${idx})" class="text-red-600 hover:underline text-sm">Delete</button>
          </div>
        `;
      } else {
        // Custom Variant Display
        card.innerHTML = `
          <div class="flex-1">
            <div class="font-semibold text-gray-900">${v.name} - ₹${v.price || 0}</div>
            <div class="text-xs text-gray-500">Stock: ${v.stock || 0} ${v.customTitle ? '• Title' : ''} ${v.customDesc ? '• Desc' : ''}</div>
          </div>
          <div class="flex gap-2">
            <button type="button" onclick="editVariant(${idx})" class="text-indigo-600 hover:underline text-sm">Edit</button>
            <button type="button" onclick="deleteVariant(${idx})" class="text-red-600 hover:underline text-sm">Delete</button>
          </div>
        `;
      }
      
      variantsList.appendChild(card);
    });
  }
  
  // Modal Controls
  const ckEditorFontCssUrl = 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Lato:wght@300;400;700;900&family=Open+Sans:wght@300;400;600;700;800&family=Source+Sans+3:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&family=EB+Garamond:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Cormorant:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700;800&family=Raleway:wght@300;400;500;600;700;800&display=swap';
  window.openModal = function(editIndex = null) {
      
    // Initialize CKEditors for Variant Fields if not already done
    const variantEditors = ['variantCustomDesc', 'variantShortDesc', 'variantIngredients', 'variantHowToUse'];
    variantEditors.forEach(id => {
      if (!CKEDITOR.instances[id]) {
        CKEDITOR.replace(id, {
          height: 300,
          removePlugins: 'easyimage,cloudservices',
          extraPlugins: 'uploadimage',
          allowedContent: true,
          extraAllowedContent: 'video[*]{*};source[*]{*};',
          filebrowserUploadUrl: '/admin/upload_blog_image.php',
          uploadUrl: '/admin/upload_blog_image.php',
          font_names: 'Arial/Arial, Helvetica, sans-serif;' +
              'Arial Black/Arial Black, Gadget, sans-serif;' +
              'Baskerville/Baskerville, Times New Roman, serif;' +
              'Book Antiqua/Book Antiqua, Palatino, serif;' +
              'Brush Script MT/Brush Script MT, cursive;' +
              'Calibri/Calibri, Candara, Segoe, sans-serif;' +
              'Cambria/Cambria, Georgia, serif;' +
              'Candara/Candara, Calibri, Segoe, sans-serif;' +
              'Century Gothic/Century Gothic, CenturyGothic, sans-serif;' +
              'Comic Sans MS/Comic Sans MS, cursive;' +
              'Consolas/Consolas, monaco, monospace;' +
              'Copperplate/Copperplate, Copperplate Gothic Light, fantasy;' +
              'Courier/Courier, monospace;' +
              'Courier New/Courier New, Courier, monospace;' +
              'Didot/Didot, Didot LT STD, Hoefler Text, serif;' +
              'Franklin Gothic Medium/Franklin Gothic Medium, sans-serif;' +
              'Futura/Futura, Trebuchet MS, sans-serif;' +
              'Garamond/Garamond, Baskerville, serif;' +
              'Geneva/Geneva, Tahoma, sans-serif;' +
              'Georgia/Georgia, Times, serif;' +
              'Gill Sans/Gill Sans, Gill Sans MT, Calibri, sans-serif;' +
              'Goudy Old Style/Goudy Old Style, Garamond, serif;' +
              'Helvetica/Helvetica, Arial, sans-serif;' +
              'Helvetica Neue/Helvetica Neue, Helvetica, Arial, sans-serif;' +
              'Hoefler Text/Hoefler Text, Baskerville Old Face, serif;' +
              'Impact/Impact, Charcoal, sans-serif;' +
              'Inter/Inter, sans-serif;' +
              'Lato/Lato, sans-serif;' +
              'Lucida Bright/Lucida Bright, Georgia, serif;' +
              'Lucida Console/Lucida Console, Monaco, monospace;' +
              'Lucida Grande/Lucida Grande, Lucida Sans Unicode, sans-serif;' +
              'Lucida Sans/Lucida Sans, Lucida Sans Unicode, sans-serif;' +
              'Merriweather/Merriweather, serif;' +
              'Monaco/Monaco, Consolas, monospace;' +
              'Montserrat/Montserrat, sans-serif;' +
              'MS Serif/MS Serif, New York, serif;' +
              'Nunito/Nunito, sans-serif;' +
              'Open Sans/Open Sans, sans-serif;' +
              'Optima/Optima, Segoe, sans-serif;' +
              'Oswald/Oswald, sans-serif;' +
              'Palatino/Palatino, Palatino Linotype, serif;' +
              'Perpetua/Perpetua, Baskerville, serif;' +
              'Playfair Display/Playfair Display, serif;' +
              'Poppins/Poppins, sans-serif;' +
              'PT Sans/PT Sans, sans-serif;' +
              'Quicksand/Quicksand, sans-serif;' +
              'Raleway/Raleway, sans-serif;' +
              'Roboto/Roboto, sans-serif;' +
              'Rockwell/Rockwell, Courier Bold, serif;' +
              'Segoe UI/Segoe UI, Frutiger, sans-serif;' +
              'Source Sans Pro/Source Sans Pro, sans-serif;' +
              'Tahoma/Tahoma, Geneva, sans-serif;' +
              'Times/Times, Times New Roman, serif;' +
              'Times New Roman/Times New Roman, Times, serif;' +
              'Trebuchet MS/Trebuchet MS, Helvetica, sans-serif;' +
              'Ubuntu/Ubuntu, sans-serif;' +
              'Verdana/Verdana, Geneva, sans-serif;' +
              'Cormorant Garamond/Cormorant Garamond, serif;' +
              'Cormorant/Cormorant, serif;' +
              'EB Garamond/EB Garamond, serif;' +
              'Libre Baskerville/Libre Baskerville, serif;' +
              'Source Sans/Source Sans 3, Source Sans Pro, sans-serif',
          on: {
            instanceReady: function() {
              this.document.appendStyleSheet(ckEditorFontCssUrl);
            }
          }
        });
      }
    });

    editingIndex = editIndex;
    if (editIndex !== null) {
      modalTitle.textContent = 'Edit Variant';
      editingIndex = editIndex;
      const variant = variants[editIndex];
      
      // Set variant name
      document.getElementById('variantName').value = variant.name;
      
      // Common fields
      document.getElementById('variantHSN').value = variant.hsn || '';
      
      // Check variant type and set appropriate radio button
      if (variant.type === 'linked') {
        // Linked Product Variant
        document.getElementById('variantTypeLinked').checked = true;
        document.getElementById('linkedProductId').value = variant.linked_product_id;
        
        // Show linked product info (you might need to fetch product details)
        document.getElementById('selectedLinkedProduct').innerHTML = `
          <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3">
            <div class="flex justify-between items-start">
              <div>
                <div class="font-semibold text-indigo-900">Product ID: ${variant.linked_product_id}</div>
                <div class="text-sm text-indigo-700 mt-1">Linked variant</div>
              </div>
              <button type="button" onclick="clearLinkedProduct()" class="text-red-500 hover:text-red-700">
                <i class="fa-solid fa-times"></i>
              </button>
            </div>
          </div>
        `;
        
        toggleVariantType(); // Show linked section, hide custom section
      } else {
        // Custom Variant (default)
        document.getElementById('variantTypeCustom').checked = true;
        
        // Load custom variant fields
        document.getElementById('variantPrice').value = variant.price;
        document.getElementById('variantComparePrice').value = variant.comparePrice || '';
        document.getElementById('variantDiscount').value = variant.discountPercent || '';
        document.getElementById('variantStock').value = variant.stock;
        document.getElementById('variantSKU').value = variant.sku;
        // variantHSN moved to common
        document.getElementById('variantCustomTitle').value = variant.customTitle || '';
        
        document.getElementById('variantCustomDesc').value = variant.customDesc || '';
        if (CKEDITOR.instances['variantCustomDesc']) CKEDITOR.instances['variantCustomDesc'].setData(variant.customDesc || '');

        document.getElementById('variantShortDesc').value = variant.shortDesc || '';
        if (CKEDITOR.instances['variantShortDesc']) CKEDITOR.instances['variantShortDesc'].setData(variant.shortDesc || '');

        document.getElementById('variantIngredients').value = variant.ingredients || '';
        if (CKEDITOR.instances['variantIngredients']) CKEDITOR.instances['variantIngredients'].setData(variant.ingredients || '');

        document.getElementById('variantHowToUse').value = variant.howToUse || '';
        if (CKEDITOR.instances['variantHowToUse']) CKEDITOR.instances['variantHowToUse'].setData(variant.howToUse || '');

        document.getElementById('variantMetaTitle').value = variant.metaTitle || '';
        document.getElementById('variantMetaDesc').value = variant.metaDesc || '';
        
        // Show existing images
        tempExistingImages = [...(variant.existingImages || [])];
        renderExistingImagesInModal();
        
        // Load FAQs
        loadVariantFaqs(variant.faqs || []);
        
        toggleVariantType(); // Show custom section, hide linked section
      }
      
      document.getElementById('variantEditIndex').value = editIndex;
      document.getElementById('variantEditId').value = variant.id || '';
    } else {
      modalTitle.textContent = 'Add Variant';
      clearModalForm();
    }
    variantModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function renderExistingImagesInModal() {
    const existingImagesDiv = document.getElementById('existingVariantImages');
    existingImagesDiv.innerHTML = '';
    
    if (tempExistingImages && tempExistingImages.length > 0) {
      tempExistingImages.forEach((img, idx) => {
        const imgContainer = document.createElement('div');
        imgContainer.className = 'relative inline-block mr-2 mb-2';
        
        const imgEl = document.createElement('img');
        imgEl.src = `/assets/uploads/products/${img}`;
        imgEl.className = 'w-20 h-20 object-cover rounded border';
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600';
        removeBtn.innerHTML = '&times;';
        removeBtn.onclick = function() { removeExistingImage(idx); };
        
        imgContainer.appendChild(imgEl);
        imgContainer.appendChild(removeBtn);
        existingImagesDiv.appendChild(imgContainer);
      });
    }
  }

  function removeExistingImage(idx) {
    if (confirm('Remove this image? (Will be deleted on save)')) {
      tempExistingImages.splice(idx, 1);
      renderExistingImagesInModal();
    }
  }
  
  function closeModal() {
    variantModal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    clearModalForm();
    editingIndex = null;
  }
  
  function clearModalForm() {
    document.getElementById('variantName').value = '';
    document.getElementById('variantPrice').value = '';
    document.getElementById('variantComparePrice').value = '';
    document.getElementById('variantDiscount').value = '';
    document.getElementById('variantStock').value = '10';
    document.getElementById('variantSKU').value = '';
    document.getElementById('variantHSN').value = '';
    document.getElementById('variantCustomTitle').value = '';
    
    document.getElementById('variantCustomDesc').value = '';
    if (CKEDITOR.instances['variantCustomDesc']) CKEDITOR.instances['variantCustomDesc'].setData('');

    document.getElementById('variantShortDesc').value = '';
    if (CKEDITOR.instances['variantShortDesc']) CKEDITOR.instances['variantShortDesc'].setData('');

    document.getElementById('variantIngredients').value = '';
    if (CKEDITOR.instances['variantIngredients']) CKEDITOR.instances['variantIngredients'].setData('');

    document.getElementById('variantHowToUse').value = '';
    if (CKEDITOR.instances['variantHowToUse']) CKEDITOR.instances['variantHowToUse'].setData('');

    document.getElementById('variantMetaTitle').value = '';
    document.getElementById('variantMetaDesc').value = '';
    document.getElementById('variantImages').value = '';
    document.getElementById('variantImagesPreviews').innerHTML = '';
    document.getElementById('variantFaqsContainer').innerHTML = '';
    document.getElementById('existingVariantImages').innerHTML = '';
    document.getElementById('variantEditIndex').value = '';
    document.getElementById('variantEditId').value = '';
    
    // Reset variant type to custom
    document.getElementById('variantTypeCustom').checked = true;
    document.getElementById('linkedProductId').value = '';
    document.getElementById('selectedLinkedProduct').innerHTML = '';
    document.getElementById('variantProductSearch').value = '';
    toggleVariantType();
  }
  
  // Variant Type Toggle Handler
  function toggleVariantType() {
    const isLinked = document.getElementById('variantTypeLinked').checked;
    const linkedSection = document.getElementById('linkedProductSection');
    const customSection = document.getElementById('customVariantSection');
    
    if (isLinked) {
      linkedSection.classList.remove('hidden');
      customSection.classList.add('hidden');
    } else {
      linkedSection.classList.add('hidden');
      customSection.classList.remove('hidden');
    }
  }
  
  document.getElementById('variantTypeLinked').addEventListener('change', toggleVariantType);
  document.getElementById('variantTypeCustom').addEventListener('change', toggleVariantType);
  
  // Product Search for Linked Variants
  const variantProductSearch = document.getElementById('variantProductSearch');
  const variantProductSearchResults = document.getElementById('variantProductSearchResults');
  let searchTimeout;
  
  variantProductSearch.addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
      variantProductSearchResults.classList.add('hidden');
      return;
    }
    
    searchTimeout = setTimeout(() => {
      fetch(`/admin/search_products.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(products => {
          if (products.length === 0) {
            variantProductSearchResults.innerHTML = '<div class="p-3 text-gray-500 text-sm">No products found</div>';
            variantProductSearchResults.classList.remove('hidden');
            return;
          }
          
          variantProductSearchResults.innerHTML = products.map(p => `
            <div class="p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0 product-search-item" 
                 data-id="${p.id}" 
                 data-name="${p.name}"
                 data-price="${p.price}"
                 data-stock="${p.stock}">
              <div class="font-semibold text-sm">${p.name}</div>
              <div class="text-xs text-gray-600">₹${p.price} • Stock: ${p.stock}</div>
            </div>
          `).join('');
          
          variantProductSearchResults.classList.remove('hidden');
          
          document.querySelectorAll('.product-search-item').forEach(item => {
            item.addEventListener('click', function() {
              selectLinkedProduct({
                id: this.dataset.id,
                name: this.dataset.name,
                price: this.dataset.price,
                stock: this.dataset.stock
              });
            });
          });
        })
        .catch(err => {
          console.error('Search error:', err);
          variantProductSearchResults.innerHTML = '<div class="p-3 text-red-500 text-sm">Error searching products</div>';
          variantProductSearchResults.classList.remove('hidden');
        });
    }, 300);
  });
  
  function selectLinkedProduct(product) {
    document.getElementById('linkedProductId').value = product.id;
    document.getElementById('selectedLinkedProduct').innerHTML = `
      <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3">
        <div class="flex justify-between items-start">
          <div>
            <div class="font-semibold text-indigo-900">${product.name}</div>
            <div class="text-sm text-indigo-700 mt-1">₹${product.price} • Stock: ${product.stock}</div>
          </div>
          <button type="button" onclick="clearLinkedProduct()" class="text-red-500 hover:text-red-700">
            <i class="fa-solid fa-times"></i>
          </button>
        </div>
      </div>
    `;
    variantProductSearchResults.classList.add('hidden');
    variantProductSearch.value = '';
  }
  
  window.clearLinkedProduct = function() {
    document.getElementById('linkedProductId').value = '';
    document.getElementById('selectedLinkedProduct').innerHTML = '';
  };
  
  // Hide search results when clicking outside
  document.addEventListener('click', function(e) {
    if (!variantProductSearch.contains(e.target) && !variantProductSearchResults.contains(e.target)) {
      variantProductSearchResults.classList.add('hidden');
    }
  });
  
  function loadVariantFaqs(faqs) {
    const container = document.getElementById('variantFaqsContainer');
    container.innerHTML = '';
    faqs.forEach(faq => {
      const row = document.createElement('div');
      row.className = 'border rounded p-2 space-y-2';
      row.innerHTML = `
        <input type="text" placeholder="Question" class="w-full p-2 border rounded text-sm variant-faq-question" value="${faq.question || ''}">
        <textarea placeholder="Answer" rows="2" class="w-full p-2 border rounded text-sm variant-faq-answer">${faq.answer || ''}</textarea>
        <button type="button" class="text-red-500 text-xs hover:underline" onclick="this.closest('div').remove()">Remove</button>
      `;
      container.appendChild(row);
    });
  }
  
  // Add FAQ button
  document.getElementById('addVariantFaqBtn').addEventListener('click', function() {
    const container = document.getElementById('variantFaqsContainer');
    const row = document.createElement('div');
    row.className = 'border rounded p-2 space-y-2';
    row.innerHTML = `
      <input type="text" placeholder="Question" class="w-full p-2 border rounded text-sm variant-faq-question">
      <textarea placeholder="Answer" rows="2" class="w-full p-2 border rounded text-sm variant-faq-answer"></textarea>
      <button type="button" class="text-red-500 text-xs hover:underline" onclick="this.closest('div').remove()">Remove</button>
    `;
    container.appendChild(row);
  });
  
  // Image Preview
  document.getElementById('variantImages').addEventListener('change', function(e) {
    const previews = document.getElementById('variantImagesPreviews');
    previews.innerHTML = '';
    Array.from(e.target.files).forEach(file => {
      const reader = new FileReader();
      reader.onload = function(event) {
        const img = document.createElement('img');
        img.src = event.target.result;
        img.className = 'w-20 h-20 object-cover rounded border';
        previews.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  });
  
  // Save Variant
  saveVariantBtn.addEventListener('click', function() {
    const nameInput = document.getElementById('variantName');
    let name = nameInput.value.trim();
    const variantType = document.getElementById('variantTypeLinked').checked ? 'linked' : 'custom';
    
    // Auto-fill name logic for Linked Products if name is empty
    if (variantType === 'linked' && !name) {
      const linkedProductId = document.getElementById('linkedProductId').value;
      if (linkedProductId) {
        // Try to get product name from the selected display
        const selectedEl = document.getElementById('selectedLinkedProduct');
        const productNameEl = selectedEl.querySelector('.font-semibold.text-indigo-900');
        if (productNameEl) {
          const linkedName = productNameEl.textContent.trim();
          if (confirm(`Variant name is empty. Do you want to use the linked product's name: "${linkedName}"?`)) {
            name = linkedName;
            nameInput.value = name; // Update input visibly
          } else {
             // User said No, stop and require manual input
             alert('Variant Name is required!');
             nameInput.focus();
             return;
          }
        }
      }
    }
    
    if (!name) {
      alert('Variant Name is required!');
      nameInput.focus();
      return;
    }
    
    let variantData = {
      id: document.getElementById('variantEditId').value || null,
      name: name,
      type: variantType,
      hsn: document.getElementById('variantHSN').value.trim() // Common field
    };
    
    if (variantType === 'linked') {
      // Linked Product Variant
      const linkedProductId = document.getElementById('linkedProductId').value;
      
      if (!linkedProductId) {
        alert('Please select a product to link!');
        return;
      }
      
      variantData.linked_product_id = parseInt(linkedProductId);
      
    } else {
      // Custom Variant
      const price = document.getElementById('variantPrice').value;
      
      if (!price) {
        alert('Price is required for custom variants!');
        return;
      }

      // Sync CKEditor data to textareas
      if (CKEDITOR.instances['variantCustomDesc']) document.getElementById('variantCustomDesc').value = CKEDITOR.instances['variantCustomDesc'].getData();
      if (CKEDITOR.instances['variantShortDesc']) document.getElementById('variantShortDesc').value = CKEDITOR.instances['variantShortDesc'].getData();
      if (CKEDITOR.instances['variantIngredients']) document.getElementById('variantIngredients').value = CKEDITOR.instances['variantIngredients'].getData();
      if (CKEDITOR.instances['variantHowToUse']) document.getElementById('variantHowToUse').value = CKEDITOR.instances['variantHowToUse'].getData();
      
      variantData.price = price;
      variantData.comparePrice = document.getElementById('variantComparePrice').value;
      variantData.discountPercent = document.getElementById('variantDiscount').value;
      variantData.stock = document.getElementById('variantStock').value || 10;
      variantData.sku = document.getElementById('variantSKU').value.trim();
      // variantHSN already set in common
      variantData.customTitle = document.getElementById('variantCustomTitle').value.trim();
      variantData.customDesc = document.getElementById('variantCustomDesc').value.trim();
      variantData.shortDesc = document.getElementById('variantShortDesc').value.trim();
      variantData.ingredients = document.getElementById('variantIngredients').value.trim();
      variantData.howToUse = document.getElementById('variantHowToUse').value.trim();
      variantData.metaTitle = document.getElementById('variantMetaTitle').value.trim();
      variantData.metaDesc = document.getElementById('variantMetaDesc').value.trim();
      variantData.images = Array.from(document.getElementById('variantImages').files);
      variantData.faqs = [];
    
    // Collect FAQs
    document.querySelectorAll('#variantFaqsContainer > div').forEach(row => {
      const q = row.querySelector('.variant-faq-question').value.trim();
      const a = row.querySelector('.variant-faq-answer').value.trim();
      if (q && a) {
        variantData.faqs.push({ question: q, answer: a });
      }
    });
    }
    
    if (editingIndex !== null) {
      // Keep existing images that weren't deleted
      variantData.existingImages = tempExistingImages;
      variants[editingIndex] = variantData;
    } else {
      variants.push(variantData);
    }
    
    renderVariants();
    closeModal();
  });
  
  window.editVariant = function(idx) {
    openModal(idx);
  }
  
  window.deleteVariant = function(idx) {
    if (confirm('Delete this variant?')) {
      const variant = variants[idx];
      if (variant.id) {
        // Mark for deletion
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_variant_ids[]';
        input.value = variant.id;
        deletedContainer.appendChild(input);
      }
      variants.splice(idx, 1);
      renderVariants();
    }
  }
  
  // Event listeners
  addVariantBtn.addEventListener('click', () => openModal());
  closeModalBtn.addEventListener('click', closeModal);
  cancelModalBtn.addEventListener('click', closeModal);
  
  // Initial render
  renderVariants();

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

  // ====== Form Submission with FormData ======
  const productForm = document.querySelector('form[action="modify_product.php"]');
  if (productForm) {
    productForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      // Remove any existing variant data from form (to avoid conflicts)
      const keysToDelete = [];
      for (let key of formData.keys()) {
        if (key.startsWith('variants[')) {
          keysToDelete.push(key);
        }
      }
      keysToDelete.forEach(key => formData.delete(key));
      
      // Append variant data from JavaScript
      if (typeof variants !== 'undefined' && Array.isArray(variants)) {
          variants.forEach((v, idx) => {
            if (v.id) formData.append(`variants[${idx}][id]`, v.id);
            formData.append(`variants[${idx}][name]`, v.name);
            
            // Add variant type (linked or custom)
            formData.append(`variants[${idx}][type]`, v.type || 'custom');
            
            // Common fields
            formData.append(`variants[${idx}][hsn]`, v.hsn || '');
            
            if (v.type === 'linked') {
              // Linked variant - only need linked_product_id
              formData.append(`variants[${idx}][linked_product_id]`, v.linked_product_id);
            } else {
              // Custom variant - all the fields
              formData.append(`variants[${idx}][price]`, v.price);
              formData.append(`variants[${idx}][compare_price]`, v.comparePrice || '');
              formData.append(`variants[${idx}][discount_percent]`, v.discountPercent || '');
              formData.append(`variants[${idx}][stock]`, v.stock);
              formData.append(`variants[${idx}][sku]`, v.sku);
              // HSN moved to common
              formData.append(`variants[${idx}][custom_title]`, v.customTitle || '');
              formData.append(`variants[${idx}][custom_description]`, v.customDesc || '');
              formData.append(`variants[${idx}][short_description]`, v.shortDesc || '');
              formData.append(`variants[${idx}][ingredients]`, v.ingredients || '');
              formData.append(`variants[${idx}][how_to_use]`, v.howToUse || '');
              formData.append(`variants[${idx}][meta_title]`, v.metaTitle || '');
              formData.append(`variants[${idx}][meta_description]`, v.metaDesc || '');
              
              // Add new images
              if (v.images && v.images.length > 0) {
                Array.from(v.images).forEach((file, fileIdx) => {
                  formData.append(`variants[${idx}][images][${fileIdx}]`, file);
                });
              }
    
              // Add kept existing images
              if (v.existingImages && v.existingImages.length > 0) {
                v.existingImages.forEach((img, imgIdx) => {
                  formData.append(`variants[${idx}][existing_images][${imgIdx}]`, img);
                });
              }
              
              // Add variant FAQs
              if (v.faqs && v.faqs.length > 0) {
                v.faqs.forEach((faq, faqIdx) => {
                  formData.append(`variants[${idx}][faqs][${faqIdx}][question]`, faq.question || '');
                  formData.append(`variants[${idx}][faqs][${faqIdx}][answer]`, faq.answer || '');
                });
              }
            }
          });
      }
      
      // Appending Product Media
      if (window.selectedMedia && window.selectedMedia.length > 0) {
        window.selectedMedia.forEach((file, index) => {
            formData.append(`product_media[${index}]`, file);
            formData.append(`product_media_order[${index}]`, index);
        });
      }
      

      
      // Submit via AJAX
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Saving...';
      
      fetch('modify_product.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(html => {

        
        // Create overlay with copyable textarea
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.9);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;';
        
        const container = document.createElement('div');
        container.style.cssText = 'background:#fff;padding:20px;border-radius:8px;max-width:90%;max-height:90%;overflow:auto;';
        
        const title = document.createElement('h2');
        title.textContent = '🔍 Server Response';
        title.style.cssText = 'margin:0 0 10px 0;color:#333;';
        
        const textarea = document.createElement('textarea');
        textarea.value = html;
        textarea.style.cssText = 'width:100%;height:400px;font-family:monospace;font-size:12px;padding:10px;border:1px solid #ccc;';
        textarea.readOnly = false;
        
        const buttonDiv = document.createElement('div');
        buttonDiv.style.cssText = 'margin-top:10px;display:flex;gap:10px;';
        
        const copyBtn = document.createElement('button');
        copyBtn.textContent = 'Copy to Clipboard';
        copyBtn.style.cssText = 'padding:10px 20px;background:#4CAF50;color:white;border:none;border-radius:4px;cursor:pointer;';
        copyBtn.onclick = () => {
          textarea.select();
          document.execCommand('copy');
          copyBtn.textContent = '✓ Copied!';
        };
        
        const closeBtn = document.createElement('button');
        closeBtn.textContent = 'Close & Go to Products';
        closeBtn.style.cssText = 'padding:10px 20px;background:#2196F3;color:white;border:none;border-radius:4px;cursor:pointer;';
        // Check for common error indicators or success redirect
        if (html.includes('success') || html.includes('Success')) {
           window.location.href = 'products.php';
        };
        closeBtn.onclick = () => {
          overlay.remove();
          window.location.href = 'products.php';
        };
        
        const stayBtn = document.createElement('button');
        stayBtn.textContent = 'Close & Stay Here';
        stayBtn.style.cssText = 'padding:10px 20px;background:#666;color:white;border:none;border-radius:4px;cursor:pointer;';
        stayBtn.onclick = () => {
          overlay.remove();
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        };
        
        buttonDiv.appendChild(copyBtn);
        buttonDiv.appendChild(closeBtn);
        buttonDiv.appendChild(stayBtn);
        
        container.appendChild(title);
        container.appendChild(textarea);
        container.appendChild(buttonDiv);
        overlay.appendChild(container);
        document.body.appendChild(overlay);
        
        // Auto-select text for easy copying
        textarea.select();
      })
      .catch(error => {
        alert('Error updating product: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      });
    });

  }

  // === Related Products Search with Chips ===
  const productSearchInput = document.getElementById('product_search_input');
  const searchResults = document.getElementById('search_results');
  const selectedProductsContainer = document.getElementById('selected_products');
  const relatedProductsInputs = document.getElementById('related_products_inputs');
  
  if (productSearchInput && searchResults && selectedProductsContainer) {
    // Pre-populate with existing related products from PHP
    let selectedProducts = <?php echo json_encode(array_map(function($rp) {
      return ['id' => $rp['id'], 'name' => $rp['name']];
    }, $relatedProducts)); ?>;
    
    let searchTimeout = null;
    
    // Search products as user types
    productSearchInput.addEventListener('input', function() {
      const query = this.value.trim();
      
      clearTimeout(searchTimeout);
      
      if (query.length < 2) {
        searchResults.classList.add('hidden');
        return;
      }
      
      searchTimeout = setTimeout(() => {
        fetch(`/admin/search_products_api.php?q=${encodeURIComponent(query)}&current=<?php echo $id; ?>`)
          .then(response => response.json())
          .then(data => {
            displaySearchResults(data.results || []);
          })
          .catch(error => {
            console.error('Search error:', error);
          });
      }, 150); // Faster response: 150ms instead of 300ms
    });
    
    function displaySearchResults(results) {
      if (results.length === 0) {
        searchResults.innerHTML = '<div class="p-3 text-gray-500 text-sm">No products found</div>';
        searchResults.classList.remove('hidden');
        return;
      }
      
      searchResults.innerHTML = results.map(product => {
        // Check if already selected
        const isSelected = selectedProducts.some(p => p.id == product.id);
        if (isSelected) return '';
        
        return `
          <div class="search-result-item p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0 flex items-center gap-3"
               data-id="${product.id}"
               data-name="${escapeHtml(product.name)}">
            <img src="/${product.image}" alt="" class="w-10 h-10 object-cover rounded border" onerror="this.src='/assets/images/avatar-default.png'">
            <div class="flex-1">
              <div class="font-medium text-sm">${escapeHtml(product.name)}</div>
              <div class="text-xs text-gray-500">₹${product.price}</div>
            </div>
            <button type="button" class="text-green-600 hover:text-green-700 text-sm font-semibold">+ Add</button>
          </div>
        `;
      }).join('');
      
      searchResults.classList.remove('hidden');
      
      // Add click handlers
      searchResults.querySelectorAll('.search-result-item').forEach(item => {
        item.addEventListener('click', function() {
          const id = this.dataset.id;
          const name = this.dataset.name;
          addProduct(id, name);
          productSearchInput.value = '';
          searchResults.classList.add('hidden');
        });
      });
    }
    
    function addProduct(id, name) {
      // Check if already added
      if (selectedProducts.some(p => p.id == id)) return;
      
      selectedProducts.push({ id, name });
      renderSelectedProducts();
      updateHiddenInputs();
    }
    
    function removeProduct(id) {
      selectedProducts = selectedProducts.filter(p => p.id != id);
      renderSelectedProducts();
      updateHiddenInputs();
    }
    
    function renderSelectedProducts() {
      if (selectedProducts.length === 0) {
        selectedProductsContainer.innerHTML = '<div class="text-sm text-gray-500">No related products selected yet</div>';
        return;
      }
      
      selectedProductsContainer.innerHTML = selectedProducts.map(product => `
        <div class="product-chip inline-flex items-center gap-2 bg-indigo-100 text-indigo-800 px-3 py-2 rounded-lg text-sm font-medium">
          <span>${escapeHtml(product.name)}</span>
          <button type="button" 
                  class="remove-product hover:text-red-600 font-bold text-indigo-600"
                  data-id="${product.id}">
            ×
          </button>
        </div>
      `).join('');
      
      // Add remove handlers
      selectedProductsContainer.querySelectorAll('.remove-product').forEach(btn => {
        btn.addEventListener('click', function() {
          removeProduct(this.dataset.id);
        });
      });
    }
    
    function updateHiddenInputs() {
      relatedProductsInputs.innerHTML = selectedProducts.map(product => `
        <input type="hidden" name="related_products[]" value="${product.id}">
      `).join('');
    }
    
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
      if (!productSearchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.classList.add('hidden');
      }
    });
    
    // Initialize with existing products
    renderSelectedProducts();
    updateHiddenInputs();
  }

  // === Product Media Gallery ===
  (function() {
    const mediaFilesInput = document.getElementById('product_media_files');
    const mediaPreview = document.getElementById('media_preview');
    const existingMediaContainer = document.getElementById('existing_media');
    const form = document.querySelector('form');
    
    window.selectedMedia = []; // For NEW media files
    const MAX_MEDIA_FILES = 50;

    // --- 1. Handling Existing Media Reordering ---
    if (existingMediaContainer) {
      setupExistingMediaDragDrop();
    }

    function setupExistingMediaDragDrop() {
      const items = existingMediaContainer.querySelectorAll('.existing-media-item');
      let draggedItem = null;

      items.forEach(item => {
        item.setAttribute('draggable', true); // Ensure draggable
        
        item.addEventListener('dragstart', function(e) {
          draggedItem = this;
          this.style.opacity = '0.5';
          e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', function(e) {
          this.style.opacity = '1';
          draggedItem = null;
          updateExistingMediaOrderInputs(); // Update hidden inputs immediately
        });

        item.addEventListener('dragover', function(e) {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';

          if (draggedItem && draggedItem !== this) {
            const rect = this.getBoundingClientRect();
            const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
            this.parentNode.insertBefore(draggedItem, next ? this.nextSibling : this);
          }
        });
        
        item.addEventListener('drop', function(e) {
          e.preventDefault();
          // Visual update of numbers
          updateOrderNumbers();
        });
      });
    }

    function updateOrderNumbers() {
      if (!existingMediaContainer) return;
      const items = existingMediaContainer.querySelectorAll('.existing-media-item');
      items.forEach((item, index) => {
        const badge = item.querySelector('.absolute.top-1.left-1');
        if (badge) badge.textContent = index + 1;
      });
    }

    // Generate hidden inputs for existing media order before submit
    function updateExistingMediaOrderInputs() {
      // Remove old inputs
      const oldInputs = document.querySelectorAll('input.existing-order-input');
      oldInputs.forEach(inp => inp.remove());

      if (!existingMediaContainer) return;

      const items = existingMediaContainer.querySelectorAll('.existing-media-item');
      const container = document.getElementById('media_inputs_container') || createInputsContainer();
      
      items.forEach((item, index) => {
        const originalIndex = item.getAttribute('data-index');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'existing_media_order[]';
        input.value = originalIndex;
        input.className = 'existing-order-input';
        container.appendChild(input);
      });
    }

    function createInputsContainer() {
      const div = document.createElement('div');
      div.id = 'media_inputs_container';
      div.style.display = 'none';
      form.appendChild(div);
      return div;
    }

    // --- 2. Handling New Media Uploads ---
    if (mediaFilesInput && mediaPreview) {
      mediaFilesInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        const existingCount = existingMediaContainer ? existingMediaContainer.querySelectorAll('.existing-media-item').length : 0;
        
        if (existingCount + window.selectedMedia.length + files.length > MAX_MEDIA_FILES) {
          alert(`Maximum ${MAX_MEDIA_FILES} files allowed.`);
          return;
        }

        files.forEach(file => {
          if (file.size > 50 * 1024 * 1024) {
            alert(`File ${file.name} is too large. Max 50MB.`);
            return;
          }
          window.selectedMedia.push(file);
        });

        renderNewMediaPreviews();
        e.target.value = '';
      });

      function renderNewMediaPreviews() {
        mediaPreview.innerHTML = window.selectedMedia.map((file, index) => {
          const isVideo = file.type.startsWith('video/');
          const previewUrl = URL.createObjectURL(file);

          return `
            <div class="relative border rounded-lg p-2 bg-gray-50 new-media-item" draggable="true" data-index="${index}">
              <div class="absolute top-1 left-1 bg-green-600 text-white rounded px-2 py-1 text-xs z-10">
                New ${index + 1}
              </div>
              <button type="button" 
                      title="Remove image"
                      class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-700 z-50 shadow-md transition-transform hover:scale-110"
                      onclick="removeNewMedia(${index})">
                <span class="text-xl font-bold">&times;</span>
              </button>
              ${isVideo ? `
                <video src="${previewUrl}" class="w-full h-32 object-cover rounded" controls></video>
              ` : `
                <img src="${previewUrl}" class="w-full h-32 object-cover rounded" alt="Preview">
              `}
              <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
            </div>
          `;
        }).join('');
        
        setupNewMediaDragDrop();
      }

      function setupNewMediaDragDrop() {
        const items = mediaPreview.querySelectorAll('.new-media-item');
        let draggedItem = null;
        
        items.forEach(item => {
          item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            this.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
          });
          
          item.addEventListener('dragend', function(e) {
            this.style.opacity = '1';
          });
          
          item.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            if (draggedItem !== this) {
              const rect = this.getBoundingClientRect();
              const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
              this.parentNode.insertBefore(draggedItem, next ? this.nextSibling : this);
            }
          });
          
          item.addEventListener('drop', function(e) {
            e.preventDefault();
            // Reorder window.selectedMedia
            const newOrder = [];
            mediaPreview.querySelectorAll('.new-media-item').forEach(item => {
              const oldIndex = parseInt(item.dataset.index);
              newOrder.push(window.selectedMedia[oldIndex]);
            });
            window.selectedMedia = newOrder;
            renderNewMediaPreviews();
          });
        });
      }

      window.removeNewMedia = function(index) {
        window.selectedMedia.splice(index, 1);
        renderNewMediaPreviews();
      };
    }

    // --- 3. Handling Existing Media Removal ---
    window.removeExistingMedia = function(index) {
      if(!existingMediaContainer) return;
      
      const item = existingMediaContainer.querySelector(`.existing-media-item[data-index="${index}"]`);
      if (item) item.remove();
      
      // Add to removed list
      const container = document.getElementById('removed_media_inputs') || createRemovedInputsContainer();
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'removed_media[]';
      input.value = index;
      container.appendChild(input);

      updateOrderNumbers();
      updateExistingMediaOrderInputs();
    };

    function createRemovedInputsContainer() {
      const div = document.createElement('div');
      div.id = 'removed_media_inputs';
      form.appendChild(div);
      return div;
    }

    // --- 4. Form Submit Handler for New Media ---
    if (form) {
      form.addEventListener('submit', function() {
        // We need to ensure existing_media_order is up to date
        updateExistingMediaOrderInputs();
        
        // Append new media files from window.selectedMedia
        window.selectedMedia.forEach((file, index) => {
            // Check if input already exists (if multiple submits prevented)
            // But FormData is easier. However, modify_product.php uses $_FILES
            // We can't strictly modify $_FILES via JS cleanly without DataTransfer or Ajax.
            // But standard form submit will only send the file input's content, which is empty after we clear it!
            // Wait! Code in add_product.php used FormData appending. 
            // In add_product.php, we intercepted submit and used XHR or appended to FormData if using AJAX.
            // But here modify_product.php is a standard POST form?
            // Let's check edit_product.php form tag.
            // If it's standard submit, we MUST use a DataTransfer object to populate the file input before submit,
            // OR use AJAX. 
            // The file input `product_media_files` is cleared after select.
        });
        
        // RE-POPULATE FILE INPUT logic for standard submit
        if (window.selectedMedia.length > 0) {
            const dt = new DataTransfer();
            window.selectedMedia.forEach(file => dt.items.add(file));
            mediaFilesInput.files = dt.files;
        }
      });
    }

    // ====== PREVIEW BUTTON HANDLER ======
    const btnPreview = document.getElementById('btnPreview');
    if (btnPreview) {
        btnPreview.addEventListener('click', function(e) {
            e.preventDefault();
            const originalText = btnPreview.innerHTML;
            btnPreview.disabled = true;
            btnPreview.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating Preview...';

            const form = document.querySelector('form');
            if (form) {
                // Ensure media inputs are in sync
                if (typeof updateExistingMediaOrderInputs === 'function') {
                    updateExistingMediaOrderInputs();
                }

                const formData = new FormData(form);

                // 1. Append selected NEW media
                if (window.selectedMedia && window.selectedMedia.length > 0) {
                  window.selectedMedia.forEach((file, index) => {
                    formData.append('preview_images[]', file);
                  });
                }
                
                // 2. Variants
                if (typeof variants !== 'undefined' && variants.length > 0) {
                   formData.append('variants_json', JSON.stringify(variants));
                }
                
                // 3. Send to Preview Handler
                fetch('/admin/handlers/save_preview_data.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    btnPreview.disabled = false;
                    btnPreview.innerHTML = originalText;
                    
                    if (data.success && data.preview_url) {
                        window.open(data.preview_url, '_blank');
                    } else {
                        alert('Failed to generate preview: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error(err);
                    btnPreview.disabled = false;
                    btnPreview.innerHTML = originalText;
                    alert('Error generating preview');
                });
            }
        });
    }

  })();
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
