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
// ------------------------
$tags = [];
try {
    $tags = $pdo->query("
        SELECT id, name
        FROM tags
        WHERE is_active = 1
        ORDER BY name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tags = [];
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

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');

// small escape helper
function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<link rel="stylesheet" href="/assets/css/admin.css">

<!-- Google Fonts for Quill Editor -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;600;700&family=Lato:wght@300;400;700&family=Montserrat:wght@300;400;600;700&family=Raleway:wght@300;400;600;700&family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@400;700&family=Merriweather:wght@300;400;700&family=Ubuntu:wght@300;400;500;700&family=Nunito:wght@300;400;600;700&family=PT+Sans:wght@400;700&family=Oswald:wght@300;400;600;700&family=Crimson+Text:wght@400;600;700&family=Work+Sans:wght@300;400;600;700&family=Quicksand:wght@300;400;600;700&display=swap" rel="stylesheet">

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
  .ql-editor { min-height: 150px; font-family: inherit; }
  .ql-container { border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; }
  .ql-toolbar { border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; }
  
  /* Custom font styles for Quill */
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="roboto"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="roboto"]::before {
    content: 'Roboto';
    font-family: 'Roboto', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="open-sans"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="open-sans"]::before {
    content: 'Open Sans';
    font-family: 'Open Sans', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="lato"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="lato"]::before {
    content: 'Lato';
    font-family: 'Lato', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="montserrat"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="montserrat"]::before {
    content: 'Montserrat';
    font-family: 'Montserrat', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="raleway"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="raleway"]::before {
    content: 'Raleway';
    font-family: 'Raleway', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="poppins"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="poppins"]::before {
    content: 'Poppins';
    font-family: 'Poppins', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="playfair"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="playfair"]::before {
    content: 'Playfair Display';
    font-family: 'Playfair Display', serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="merriweather"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="merriweather"]::before {
    content: 'Merriweather';
    font-family: 'Merriweather', serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="ubuntu"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="ubuntu"]::before {
    content: 'Ubuntu';
    font-family: 'Ubuntu', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="nunito"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="nunito"]::before {
    content: 'Nunito';
    font-family: 'Nunito', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="pt-sans"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="pt-sans"]::before {
    content: 'PT Sans';
    font-family: 'PT Sans', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="oswald"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="oswald"]::before {
    content: 'Oswald';
    font-family: 'Oswald', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="crimson"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="crimson"]::before {
    content: 'Crimson Text';
    font-family: 'Crimson Text', serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="work-sans"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="work-sans"]::before {
    content: 'Work Sans';
    font-family: 'Work Sans', sans-serif;
  }
  .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="quicksand"]::before,
  .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="quicksand"]::before {
    content: 'Quicksand';
    font-family: 'Quicksand', sans-serif;
  }
  
  /* Apply fonts in editor */
  .ql-font-roboto { font-family: 'Roboto', sans-serif; }
  .ql-font-open-sans { font-family: 'Open Sans', sans-serif; }
  .ql-font-lato { font-family: 'Lato', sans-serif; }
  .ql-font-montserrat { font-family: 'Montserrat', sans-serif; }
  .ql-font-raleway { font-family: 'Raleway', sans-serif; }
  .ql-font-poppins { font-family: 'Poppins', sans-serif; }
  .ql-font-playfair { font-family: 'Playfair Display', serif; }
  .ql-font-merriweather { font-family: 'Merriweather', serif; }
  .ql-font-ubuntu { font-family: 'Ubuntu', sans-serif; }
  .ql-font-nunito { font-family: 'Nunito', sans-serif; }
  .ql-font-pt-sans { font-family: 'PT Sans', sans-serif; }
  .ql-font-oswald { font-family: 'Oswald', sans-serif; }
  .ql-font-crimson { font-family: 'Crimson Text', serif; }
  .ql-font-work-sans { font-family: 'Work Sans', sans-serif; }
  .ql-font-quicksand { font-family: 'Quicksand', sans-serif; }
  
  /* Short description editor min height */
  #editor-short-desc .ql-editor { min-height: 60px; }
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
        <label class="block text-sm font-semibold mb-2">Short Description</label>
        <div id="editor-short-desc" class="bg-white"></div>
        <textarea name="short_description" id="hidden-short-desc" class="hidden"><?php echo esc($p['short_description'] ?? ''); ?></textarea>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Full Description</label>
        <div id="editor-description" class="bg-white"></div>
        <textarea name="description" id="hidden-description" class="hidden"><?php echo esc($p['description'] ?? ''); ?></textarea>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Ingredients</label>
        <div id="editor-ingredients" class="bg-white"></div>
        <textarea name="ingredients" id="hidden-ingredients" class="hidden"><?php echo esc($p['ingredients'] ?? ''); ?></textarea>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">How to Use</label>
        <div id="editor-how-to-use" class="bg-white"></div>
        <textarea name="how_to_use" id="hidden-how-to-use" class="hidden"><?php echo esc($p['how_to_use'] ?? ''); ?></textarea>
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
        <p class="text-xs text-gray-600 mb-3">Upload images or videos to showcase this product (Max 10 files)</p>
        
        <!-- Existing Media -->
        <?php if (!empty($productMedia)): ?>
        <div class="mb-4">
          <p class="text-sm font-medium mb-2">Current Media:</p>
          <div id="existing_media" class="grid grid-cols-2 gap-3">
            <?php foreach ($productMedia as $idx => $media): ?>
            <div class="relative border rounded-lg p-2 bg-gray-50" data-index="<?php echo $idx; ?>">
              <button type="button" 
                      class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 z-10"
                      onclick="removeExistingMedia(<?php echo $idx; ?>)">
                ×
              </button>
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
          <p class="text-xs text-gray-500 mt-1">Supported: JPG, PNG, WEBP (images) | MP4, WEBM (videos)</p>
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
                <?php echo in_array((string)$t['id'], array_map('strval', $productTagIds)) ? 'selected' : ''; ?>>
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
            <label class="block text-sm font-semibold mb-2">Selling Price (₹) <span class="text-red-500">*</span></label>
            <input type="number" id="variantPrice" step="0.01" min="0" class="w-full p-3 border rounded-lg bg-green-50 border-green-200" placeholder="0.00" required>
            <p class="text-xs text-gray-500 mt-1">Final price for this variant</p>
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
      </div>

      <div class="sticky bottom-0 bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
        <button type="button" id="cancelModalBtn" class="px-6 py-2 border rounded-lg hover:bg-gray-100">Cancel</button>
        <button type="button" id="saveVariantBtn" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Variant</button>
      </div>
    </div>
  </div>
</div>

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
  var Font = Quill.import('formats/font');
  Font.whitelist = window.QuillFontConfig.getFontValues();
  Quill.register(Font, true);
  
  var toolbarOptions = [
    [{ 'font': window.QuillFontConfig.getFontValues() }],
    [{ 'size': ['small', false, 'large', 'huge'] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'script': 'sub'}, { 'script': 'super' }],
    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'indent': '-1'}, { 'indent': '+1' }],
    [{ 'align': [] }],
    ['link', 'clean']
  ];

  var quillOptions = {
    theme: 'snow',
    modules: {
      toolbar: toolbarOptions
    }
  };

  // Initialize Editors
  var quillShortDesc = new Quill('#editor-short-desc', quillOptions);
  var quillDesc = new Quill('#editor-description', quillOptions);
  var quillIng = new Quill('#editor-ingredients', quillOptions);
  var quillUse = new Quill('#editor-how-to-use', quillOptions);

  // Load initial content
  quillShortDesc.root.innerHTML = document.getElementById('hidden-short-desc').value;
  quillDesc.root.innerHTML = document.getElementById('hidden-description').value;
  quillIng.root.innerHTML = document.getElementById('hidden-ingredients').value;
  quillUse.root.innerHTML = document.getElementById('hidden-how-to-use').value;

  // Sync content on change
  quillShortDesc.on('text-change', function() {
    document.getElementById('hidden-short-desc').value = quillShortDesc.root.innerHTML;
  });
  quillDesc.on('text-change', function() {
    document.getElementById('hidden-description').value = quillDesc.root.innerHTML;
  });
  quillIng.on('text-change', function() {
    document.getElementById('hidden-ingredients').value = quillIng.root.innerHTML;
  });
  quillUse.on('text-change', function() {
    document.getElementById('hidden-how-to-use').value = quillUse.root.innerHTML;
  });
  
  // Initialize searchable font picker
  window.QuillFontConfig.initSearchablePicker();
});
</script>

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
    price: <?php echo json_encode($v['price'] ?? ''); ?>,
    comparePrice: <?php echo json_encode($v['compare_price'] ?? ''); ?>,
    discountPercent: <?php echo json_encode($v['discount_percent'] ?? ''); ?>,
    stock: <?php echo json_encode($v['stock'] ?? 10); ?>,
    sku: <?php echo json_encode($v['sku'] ?? ''); ?>,
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
      card.className = 'bg-white border rounded-lg p-4 flex justify-between items-center';
      
      const info = [];
      info.push(v.name);
      info.push('₹' + parseFloat(v.price).toFixed(2));
      if (v.customTitle) info.push('• Custom Title');
      if (v.existingImages && v.existingImages.length > 0) info.push('• ' + v.existingImages.length + ' img(s)');
      if (v.images && v.images.length > 0) info.push('• +' + v.images.length + ' new img(s)');
      if (v.faqs && v.faqs.length > 0) info.push('• ' + v.faqs.length + ' FAQ(s)');
      
      card.innerHTML = `
        <div>
          <div class="font-semibold">${info.join(' ')}</div>
          <div class="text-xs text-gray-500">Stock: ${v.stock}</div>
        </div>
        <div class="flex gap-2">
          <button type="button" class="text-blue-600 hover:underline text-sm" onclick="editVariant(${idx})">Edit</button>
          <button type="button" class="text-red-600 hover:underline text-sm" onclick="deleteVariant(${idx})">Delete</button>
        </div>
      `;
      variantsList.appendChild(card);
    });
  }
  
  // Modal Controls
  window.openModal = function(editIndex = null) {
    editingIndex = editIndex;
    if (editIndex !== null) {
      modalTitle.textContent = 'Edit Variant';
      const variant = variants[editIndex];
      document.getElementById('variantName').value = variant.name;
      document.getElementById('variantPrice').value = variant.price;
      document.getElementById('variantComparePrice').value = variant.comparePrice || '';
      document.getElementById('variantDiscount').value = variant.discountPercent || '';
      document.getElementById('variantStock').value = variant.stock;
      document.getElementById('variantSKU').value = variant.sku;
      document.getElementById('variantCustomTitle').value = variant.customTitle || '';
      document.getElementById('variantCustomDesc').value = variant.customDesc || '';
      document.getElementById('variantShortDesc').value = variant.shortDesc || '';
      document.getElementById('variantIngredients').value = variant.ingredients || '';
      document.getElementById('variantHowToUse').value = variant.howToUse || '';
      document.getElementById('variantMetaTitle').value = variant.metaTitle || '';
      document.getElementById('variantMetaDesc').value = variant.metaDesc || '';
      document.getElementById('variantEditIndex').value = editIndex;
      document.getElementById('variantEditId').value = variant.id || '';
      
      // Show existing images
      tempExistingImages = [...(variant.existingImages || [])];
      renderExistingImagesInModal();
      
      // Load FAQs
      loadVariantFaqs(variant.faqs || []);
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
    document.getElementById('variantCustomTitle').value = '';
    document.getElementById('variantCustomDesc').value = '';
    document.getElementById('variantShortDesc').value = '';
    document.getElementById('variantIngredients').value = '';
    document.getElementById('variantHowToUse').value = '';
    document.getElementById('variantMetaTitle').value = '';
    document.getElementById('variantMetaDesc').value = '';
    document.getElementById('variantImages').value = '';
    document.getElementById('variantImagesPreviews').innerHTML = '';
    document.getElementById('variantFaqsContainer').innerHTML = '';
    document.getElementById('existingVariantImages').innerHTML = '';
    document.getElementById('variantEditIndex').value = '';
    document.getElementById('variantEditId').value = '';
  }
  
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
    const name = document.getElementById('variantName').value.trim();
    const price = document.getElementById('variantPrice').value;
    
    if (!name || !price) {
      alert('Variant name and price are required');
      return;
    }
    
    const variantData = {
      id: document.getElementById('variantEditId').value || null,
      name: name,
      price: price,
      comparePrice: document.getElementById('variantComparePrice').value,
      discountPercent: document.getElementById('variantDiscount').value,
      stock: document.getElementById('variantStock').value || 10,
      sku: document.getElementById('variantSKU').value.trim(),
      customTitle: document.getElementById('variantCustomTitle').value.trim(),
      customDesc: document.getElementById('variantCustomDesc').value.trim(),
      shortDesc: document.getElementById('variantShortDesc').value.trim(),
      ingredients: document.getElementById('variantIngredients').value.trim(),
      howToUse: document.getElementById('variantHowToUse').value.trim(),
      metaTitle: document.getElementById('variantMetaTitle').value.trim(),
      metaDesc: document.getElementById('variantMetaDesc').value.trim(),
      images: Array.from(document.getElementById('variantImages').files),
      faqs: []
    };
    
    // Collect FAQs
    document.querySelectorAll('#variantFaqsContainer > div').forEach(row => {
      const q = row.querySelector('.variant-faq-question').value.trim();
      const a = row.querySelector('.variant-faq-answer').value.trim();
      if (q && a) {
        variantData.faqs.push({ question: q, answer: a });
      }
    });
    
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
      variants.forEach((v, idx) => {
        if (v.id) formData.append(`variants[${idx}][id]`, v.id);
        formData.append(`variants[${idx}][name]`, v.name);
        formData.append(`variants[${idx}][price]`, v.price);
        formData.append(`variants[${idx}][compare_price]`, v.comparePrice || '');
        formData.append(`variants[${idx}][discount_percent]`, v.discountPercent || '');
        formData.append(`variants[${idx}][stock]`, v.stock);
        formData.append(`variants[${idx}][sku]`, v.sku);
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
        
        // Add FAQs
        if (v.faqs && v.faqs.length > 0) {
          v.faqs.forEach((faq, faqIdx) => {
            formData.append(`variants[${idx}][faqs][${faqIdx}][question]`, faq.question || '');
            formData.append(`variants[${idx}][faqs][${faqIdx}][answer]`, faq.answer || '');
          });
        }
      });
      
      // Add product media files (new uploads)
      if (window.selectedMedia && window.selectedMedia.length > 0) {
        window.selectedMedia.forEach((file, index) => {
          formData.append(`product_media[${index}]`, file);
        });
      }
      
      // Submit via fetch
      fetch('modify_product.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(html => {
        // Check for success
        if (html.includes('success') || html.includes('Success') || html.includes('updated')) {
          window.location.href = 'products.php';
        } else {
          // Show response
          document.body.innerHTML = html;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
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

  // === Product Media Gallery for Edit Product ===
  const mediaFilesInput = document.getElementById('product_media_files');
  const mediaPreview = document.getElementById('media_preview');
  window.selectedMedia = [];
  window.removedMediaIndices = [];
  const MAX_MEDIA_FILES = 10;
  
  if (mediaFilesInput && mediaPreview) {
    mediaFilesInput.addEventListener('change', function(e) {
      const files = Array.from(e.target.files);
      const existingCount = document.querySelectorAll('#existing_media [data-index]').length;
      const totalCount = existingCount + window.selectedMedia.length + files.length;
      
      if (totalCount > MAX_MEDIA_FILES) {
        alert(`Maximum ${MAX_MEDIA_FILES} files allowed. You currently have ${existingCount + window.selectedMedia.length}.`);
        return;
      }
      
      files.forEach(file => {
        if (file.size > 50 * 1024 * 1024) {
          alert(`File ${file.name} is too large. Maximum 50MB per file.`);
          return;
        }
        window.selectedMedia.push(file);
      });
      
      renderMediaPreviews();
      e.target.value = '';
    });
    
    function renderMediaPreviews() {
      mediaPreview.innerHTML = window.selectedMedia.map((file, index) => {
        const isVideo = file.type.startsWith('video/');
        const previewUrl = URL.createObjectURL(file);
        
        return `
          <div class="relative border rounded-lg p-2 bg-gray-50">
            <button type="button" 
                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 z-10"
                    onclick="removeNewMedia(${index})">
              ×
            </button>
            ${isVideo ? `
              <video src="${previewUrl}" class="w-full h-32 object-cover rounded" controls></video>
              <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
            ` : `
              <img src="${previewUrl}" class="w-full h-32 object-cover rounded" alt="Preview">
              <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
            `}
          </div>
        `;
      }).join('');
    }
    
    window.removeNewMedia = function(index) {
      window.selectedMedia.splice(index, 1);
      renderMediaPreviews();
    };
    
    window.removeExistingMedia = function(index) {
      window.removedMediaIndices.push(index);
      document.querySelector(`#existing_media [data-index="${index}"]`).remove();
      updateRemovedMediaInputs();
    };
    
    function updateRemovedMediaInputs() {
      const container = document.getElementById('removed_media_inputs');
      container.innerHTML = window.removedMediaIndices.map(idx => 
        `<input type="hidden" name="removed_media[]" value="${idx}">`
      ).join('');
    }
  }

})();
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>