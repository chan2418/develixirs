<?php
// admin/add_product.php - upgraded to new admin layout & style with tags + parent/sub categories
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
$parent_category_id_val = old_val('parent_category_id', '');
$category_id_val        = old_val('category_id', '');
$is_published_val     = old_val('is_published', old_val('is_active', '1'));
$meta_title_val       = old_val('meta_title', '');
$meta_description_val = old_val('meta_description', '');

// Load categories: split into parent + subcategories
$parentCategories = [];
$subCategories    = []; // flat list of all subcats
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
            $parentCategories[] = $row;        // top level
        } else {
            $subCategories[]    = $row;        // children
        }
    }
} catch(Exception $e) {
    $parentCategories = [];
    $subCategories    = [];
}


// Load Product Groups
$groups = [];
try {
    $groups = $pdo->query("SELECT id, name FROM product_groups ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $groups = []; }

// Load Filter Groups & Options
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
    unset($fg);
} catch (Exception $e) { }


// Load Tags
$tags = [];
try {
    $tags = $pdo->query("SELECT id, name FROM tags ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $tags = []; }

// Load Labels
$labels = [];
try {
    $labels = $pdo->query("SELECT id, name, color, text_color FROM product_labels WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $labels = []; }

// Load Concerns
$concerns = [];
try {
    $concerns = $pdo->query("SELECT id, title FROM concerns ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $concerns = []; }

// Load Seasonals (NEW)
$seasonals = [];
try {
    $seasonals = $pdo->query("SELECT id, title FROM seasonals ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $seasonals = []; }

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// include layout header (uses sidebar & top header)
include __DIR__ . '/layout/header.php';
?>

<link rel="stylesheet" href="/assets/css/admin.css">

<!-- Google Fonts for Quill Editor - 50+ Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;600;700&family=Lato:wght@300;400;700&family=Montserrat:wght@300;400;600;700&family=Raleway:wght@300;400;600;700&family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@400;700&family=Merriweather:wght@300;400;700&family=Ubuntu:wght@300;400;500;700&family=Nunito:wght@300;400;600;700&family=PT+Sans:wght@400;700&family=Oswald:wght@300;400;600;700&family=Crimson+Text:wght@400;600;700&family=Work+Sans:wght@300;400;600;700&family=Quicksand:wght@300;400;600;700&family=Source+Sans+Pro:wght@300;400;600;700&family=Noto+Sans:wght@300;400;600;700&family=Rubik:wght@300;400;500;700&family=Inter:wght@300;400;600;700&family=Mulish:wght@300;400;600;700&family=Karla:wght@300;400;600;700&family=Barlow:wght@300;400;600;700&family=Titillium+Web:wght@300;400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Mukta:wght@300;400;600;700&family=Libre+Baskerville:wght@400;700&family=Merriweather+Sans:wght@300;400;600;700&family=Hind:wght@300;400;600;700&family=Oxygen:wght@300;400;700&family=Cabin:wght@400;500;600;700&family=Arimo:wght@400;500;600;700&family=Red+Hat+Display:wght@300;400;600;700&family=DM+Sans:wght@400;500;700&family=Fjalla+One&family=Bebas+Neue&family=Architects+Daughter&family=Pacifico&family=Dancing+Script:wght@400;500;700&family=Satisfy&family=Great+Vibes&family=Caveat:wght@400;500;700&family=Indie+Flower&family=Shadows+Into+Light&family=Kalam:wght@300;400;700&family=Permanent+Marker&family=Abril+Fatface&family=Alfa+Slab+One&family=Anton&family=Lobster&family=Righteous&family=Fredoka+One&family=Bungee&family=IBM+Plex+Sans:wght@300;400;600;700&family=IBM+Plex+Serif:wght@300;400;600;700&family=IBM+Plex+Mono:wght@300;400;600;700&family=Fira+Sans:wght@300;400;600;700&family=Libre+Franklin:wght@300;400;600;700&family=Yanone+Kaffeesatz:wght@300;400;600;700&family=Saira:wght@300;400;600;700&family=Spectral:wght@300;400;600;700&display=swap" rel="stylesheet">

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
        <textarea name="short_desc" id="hidden-short-desc" class="hidden"><?php echo $short_desc_val; ?></textarea>
      </div>

      <!-- CKEditor 4.22.1 -->
      <script src="//cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
      <style>
        /* Hide CKEditor warning notifications */
        .cke_notification_warning { display: none !important; }
        .cke_notifications_area { display: none !important; }
        /* Increase editor content font size */
        .cke_editable { font-size: 16px !important; line-height: 1.6 !important; }
      </style>

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
        <textarea name="description" id="hidden-description" class="hidden"><?php echo $description_val; ?></textarea>
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
        <textarea name="ingredients" id="hidden-ingredients" class="hidden"><?php echo $old['ingredients'] ?? ''; ?></textarea>
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
        <textarea name="how_to_use" id="hidden-how-to-use" class="hidden"><?php echo $old['how_to_use'] ?? ''; ?></textarea>
      </div>

      <!-- CKEditor Initialization -->
      <script src="/admin/ckeditor-product-init.js"></script>

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
          <!-- Media previews will appear here -->
        </div>
        
        <!-- Hidden inputs for media files -->
        <div id="media_inputs_container"></div>
      </div>

    </div>

    <!-- RIGHT COLUMN -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-4">

      <!-- Product Groups (Multi-Select) -->
      <div>
        <label class="block text-sm font-semibold mb-2">Product Groups</label>
        <div class="border rounded-lg p-3 max-h-40 overflow-y-auto bg-slate-50 space-y-2">
            <?php if(empty($groups)): ?>
                <p class="text-xs text-gray-500">No groups found. <a href="product_groups.php" class="text-indigo-600 underline">Create one</a></p>
            <?php else: ?>
                <?php foreach($groups as $g): ?>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-100 p-1 rounded">
                    <input type="checkbox" name="group_ids[]" value="<?= $g['id'] ?>" class="rounded text-indigo-600 border-gray-300 focus:ring-indigo-500">
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
            <option value="<?php echo (int)$c['id']; ?>"
              <?php echo ((string)$parent_category_id_val === (string)$c['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">
          Example: Men Care, Baby care, Hair Care, etc.
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
              <?php echo ((string)$category_id_val === (string)$c['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">
          Example: Hair Wash, Hair oil, Face wash under Men Care.  
          If you leave this empty, the product will only be attached to the main category.
        </p>
      </div>

      <!-- Concern (Shop by Concern) -->
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Concern (Shop by Concern)</label>
        <select name="concern_id" class="w-full p-3 border rounded-lg">
          <option value="">-- Select Concern (optional) --</option>
          <?php foreach ($concerns as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (old_val('concern_id') == $c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">Associate this product with a specific health concern.</p>
      </div>

      <!-- Seasonal (Shop by Seasonal) -->
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Seasonal Theme</label>
        <select name="seasonal_id" class="w-full p-3 border rounded-lg">
          <option value="">-- Select Seasonal (optional) --</option>
          <?php foreach ($seasonals as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= (old_val('seasonal_id') == $s['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">Associate with a seasonal theme.</p>
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

            <!-- Filter Blocks Container -->
            <div id="filter_blocks_container" class="space-y-3">
                <?php foreach($filterGroups as $fg): ?>
                    <?php 
                         $catLabel = $fg['category_name'] ? $fg['category_name'] : 'Common'; 
                         // Check checks for edit page compatibility or sticky form (if post failed)
                         $hasChecked = false;
                         if (!empty($_POST['filter_options'][$fg['id']])) {
                             $hasChecked = true;
                         }
                         $displayStyle = $hasChecked ? '' : 'display:none;';
                    ?>
                    <div id="fg-row-<?= $fg['id'] ?>" class="filter-group-row border rounded-lg bg-white shadow-sm" style="<?= $displayStyle ?>">
                        <div class="flex justify-between items-center p-2 bg-slate-50 border-b rounded-t-lg">
                             <div class="flex items-center gap-2">
                                 <span class="text-[10px] uppercase font-bold text-gray-500 bg-gray-200 px-1.5 py-0.5 rounded"><?= htmlspecialchars($catLabel) ?></span>
                                 <span class="text-xs font-bold text-slate-700 uppercase tracking-wide"><?= htmlspecialchars($fg['name']) ?></span>
                             </div>
                             <button type="button" class="text-gray-400 hover:text-red-500" onclick="removeFilterGroup('fg-row-<?= $fg['id'] ?>')">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                             </button>
                        </div>
                        <div class="p-2 max-h-32 overflow-y-auto space-y-1">
                            <?php if(empty($fg['options'])): ?>
                                <p class="text-[10px] text-gray-400 p-1">No options.</p>
                            <?php else: ?>
                                <?php foreach($fg['options'] as $opt): ?>
                                    <?php 
                                        $checked = '';
                                        if ($hasChecked && in_array($opt['id'], $_POST['filter_options'][$fg['id']])) {
                                            $checked = 'checked';
                                        }
                                    ?>
                                    <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 p-1 rounded transition-colors">
                                        <input type="checkbox" name="filter_options[<?= $fg['id'] ?>][]" value="<?= $opt['id'] ?>" <?= $checked ?> class="rounded text-indigo-600 border-gray-300 focus:ring-indigo-500 w-4 h-4">
                                        <span class="text-sm text-slate-700"><?= htmlspecialchars($opt['label']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


            <script>
            // Filter category-based filtering
            const filterSelector = document.getElementById('filter_selector');
            const parentCategorySelect = document.getElementById('parent_category_id');
            
            // Store all filter options with their category data
            const allFilterOptions = [];
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
            if (filterSelector) {
                filterSelector.addEventListener('change', function() {
                    var selectedId = this.value;
                    if (selectedId) {
                        var el = document.getElementById(selectedId);
                        if (el) {
                            el.style.display = 'block';
                            // Optional: Scroll to it or highlight it
                            el.classList.add('ring-2', 'ring-indigo-100');
                            setTimeout(() => el.classList.remove('ring-2', 'ring-indigo-100'), 1000);
                        }
                        this.value = ''; // Reset dropdown
                    }
                });
            }

            function removeFilterGroup(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.style.display = 'none';
                    // Uncheck all inputs inside when hiding? 
                    // Usually better to keep them checked in case accidental click, 
                    // but "removing" implies clearing. Let's start with just hiding for safety 
                    // as user might just want to collapse it. 
                    // If true removal needed:
                    // el.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
                }
            }
            </script>
      </div>

      <!-- Product Label -->
      <div>
        <label class="block text-sm font-semibold mb-2">Product Label</label>
        <select name="label_id" class="w-full p-3 border rounded-lg">
          <option value="">-- No Label --</option>
          <?php foreach ($labels as $l): ?>
            <option value="<?php echo (int)$l['id']; ?>"
              <?php echo (old_val('label_id') == $l['id']) ? 'selected' : ''; ?>>
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
        <input type="text" 
               name="tags_input" 
               id="visibleTagsInput"
               class="w-full p-3 border rounded-lg" 
               placeholder="Type to see tag suggestions... (separate with commas)"
               autocomplete="off"
               value="<?php echo isset($_POST['tags_input']) ? htmlspecialchars($_POST['tags_input']) : ''; ?>">
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
               value="<?php echo isset($_POST['seo_keywords']) ? htmlspecialchars($_POST['seo_keywords']) : ''; ?>">
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
          <input id="price" name="price" type="number" step="0.01" min="0"
                 class="w-full p-3 border rounded-lg bg-green-50 border-green-200"
                 value="<?php echo $price_val; ?>" required>
          <p class="text-xs text-slate-500 mt-1">Final price customer pays (Inclusive of GST)</p>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Compare Price (₹)</label>
          <input id="compare_price" name="compare_price" type="number" step="0.01" min="0"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo old_val('compare_price', ''); ?>" placeholder="MRP / Old Price">
          <p class="text-xs text-slate-500 mt-1">Original price (shown crossed out)</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="block text-sm font-semibold mb-2">GST Rate (%)</label>
          <select id="gst_rate" name="gst_rate" class="w-full p-3 border rounded-lg">
            <option value="0">0%</option>
            <option value="5">5%</option>
            <option value="12">12%</option>
            <option value="18">18%</option>
            <option value="28">28%</option>
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
          <input id="discount_percent" name="discount_percent" type="number" step="0.01" min="0" max="100"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo old_val('discount_percent', ''); ?>" placeholder="e.g. 20">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Stock *</label>
          <input id="stock" name="stock" type="number" min="0"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo $stock_val; ?>" required>
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
        });
      </script>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-2">SKU</label>
          <input id="sku" name="sku"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo $sku_val; ?>" placeholder="Optional SKU">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">HSN Code</label>
          <input id="hsn" name="hsn"
                 class="w-full p-3 border rounded-lg"
                 value="<?php echo old_val('hsn', ''); ?>" placeholder="Optional HSN">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Visibility</label>
        <select id="is_published" name="is_published" class="w-full p-3 border rounded-lg">
          <option value="1" <?php echo ($is_published_val == '1') ? 'selected' : ''; ?>>Published</option>
          <option value="0" <?php echo ($is_published_val == '0') ? 'selected' : ''; ?>>Draft</option>
        </select>
      </div>

      <!-- Variants -->
      <div>
        <label class="block text-sm font-semibold mb-2">Product Variants (Sizes/Options)</label>
        
        <!-- Variant Label Input -->
        <div class="grid grid-cols-2 gap-4 mb-3">
          <div>
            <label class="text-xs font-semibold text-slate-500">Variant Label (e.g. Size, Volume)</label>
            <input type="text" name="variant_label" class="w-full p-2 border rounded text-sm" placeholder="Size" value="Size">
          </div>
          <div>
            <label class="text-xs font-semibold text-slate-500">Main Variant Name (e.g. Vitamin C)</label>
            <input type="text" name="main_variant_name" class="w-full p-2 border rounded text-sm" placeholder="Default Option Name">
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
      </div>

      <!-- FAQs -->
      <div>
        <label class="block text-sm font-semibold mb-2">Product FAQs</label>
        <div class="border rounded-lg p-4 bg-slate-50">
          <div id="faqs-container" class="space-y-3">
            <!-- FAQ rows will be added here -->
          </div>
          <button type="button" id="add-faq-btn" class="mt-3 text-sm text-indigo-600 font-semibold hover:underline">
            + Add FAQ
          </button>
        </div>
      </div>

      <!-- Images -->
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
            <span><strong>Tip:</strong> First image will be the main product image</span>
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
          <!-- JS will put previews here -->
        </div>

        <p class="text-xs text-slate-500 mt-2">
          Tip: Uploaded files will be handled by <code>save_product.php</code>.
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
        <!-- Hidden ID for editing -->
        <input type="hidden" id="variantEditIndex" value="">
        
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

          <div>
            <label class="block text-sm font-semibold mb-2">Discount (%)</label>
            <input type="number" id="variantDiscount" step="0.01" min="0" max="100" class="w-full p-3 border rounded-lg" placeholder="e.g. 20">
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

        <div class="grid grid-cols-2 gap-4 mt-4">
          <div>
            <label class="block text-sm font-semibold mb-2">HSN Code (Optional)</label>
            <input type="text" id="variantHSN" class="w-full p-3 border rounded-lg" placeholder="e.g. 123456">
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
        <!-- End of Custom Variant Section -->
      </div>

      <div class="sticky bottom-0 bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
        <button type="button" id="cancelModalBtn" class="px-6 py-2 border rounded-lg hover:bg-gray-100">Cancel</button>
        <button type="button" id="saveVariantBtn" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Variant</button>
      </div>
    </div>
  </div>
</div>

<!-- image preview + subcategory filtering script -->
<script>
(function(){
  const input   = document.getElementById('images_input');
  const preview = document.getElementById('preview');
  window.legacyImages = []; // Global store for reordering

  // Initial rendering if any files (unlikely for new add, but good practice)
  
  if (input && preview) {
    // Update container to flex for better drag-drop flow
    preview.className = 'mt-3 flex flex-wrap gap-3';
    
    input.addEventListener('change', function(e){
      const files = Array.from(e.target.files);
      files.forEach(f => {
        if (!f.type.startsWith('image/') && !f.type.startsWith('video/')) return;
        window.legacyImages.push(f);
      });
      renderLegacyPreviews();
      e.target.value = ''; // Reset to allow adding more
    });
  }

  function renderLegacyPreviews() {
    if(!preview) return;
    preview.innerHTML = window.legacyImages.map((f, i) => {
      const url = URL.createObjectURL(f);
      const isVideo = f.type.startsWith('video/');
      return `
        <div class="relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200 shadow-sm bg-white legacy-item hover:shadow-md transition-shadow" 
             draggable="true" 
             data-index="${i}">
           <div class="absolute top-0 left-0 bg-gray-800 text-white text-[10px] px-1.5 py-0.5 rounded-br z-10 opacity-70">
             ${i + 1}
           </div>
           
           <button type="button" 
                   title="Remove"
                   class="absolute -top-2 -right-2 bg-white text-red-600 rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-50 hover:scale-110 z-50 shadow-md border border-gray-100 transition-all"
                   onclick="event.preventDefault(); event.stopPropagation(); removeLegacyImage(${i})">
             <span class="text-lg font-bold leading-none">&times;</span>
           </button>
           
           ${isVideo ? 
             `<video src="${url}" class="object-cover w-full h-full pointer-events-none" muted></video>
              <div class="absolute inset-0 flex items-center justify-center pointer-events-none bg-black bg-opacity-20">
                <span class="text-white font-bold text-xs bg-black bg-opacity-50 px-1 rounded">VIDEO</span>
              </div>` 
             : 
             `<img src="${url}" class="object-cover w-full h-full pointer-events-none select-none" draggable="false" alt="">`
           }
        </div>
      `;
    }).join('');
    
    setupLegacyDragDrop();
  }
  
  function setupLegacyDragDrop() {
    const items = preview.querySelectorAll('.legacy-item');
    let draggedItem = null;
    
    items.forEach(item => {
      // Ensure cursor indicates draggable
      item.style.cursor = 'move';
      
      item.addEventListener('dragstart', function(e) {
        draggedItem = this;
        this.style.opacity = '0.4';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', ''); // Required for Firefox
        this.classList.add('dragging');
      });
      
      item.addEventListener('dragend', function(e) {
        this.style.opacity = '1';
        this.classList.remove('dragging');
        draggedItem = null;
      });
      
      item.addEventListener('dragover', function(e) {
        e.preventDefault(); // Necessary to allow dropping
        e.dataTransfer.dropEffect = 'move';
        
        if (draggedItem && draggedItem !== this) {
          const rect = this.getBoundingClientRect();
          const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
          this.parentNode.insertBefore(draggedItem, next ? this.nextSibling : this);
        }
      });
      
      item.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Stop bubbling
        
        // Reorder array based on new DOM order
        const newOrder = [];
        preview.querySelectorAll('.legacy-item').forEach(el => {
          const idx = parseInt(el.dataset.index);
          newOrder.push(window.legacyImages[idx]);
        });
        window.legacyImages = newOrder;
        
        // Use timeout to avoid interrupting the dragend event
        setTimeout(renderLegacyPreviews, 50);
      });
    });
  }
  
  window.removeLegacyImage = function(index) {
    window.legacyImages.splice(index, 1);
    renderLegacyPreviews();
  };

  const dropBox = document.querySelector('.group');
  if (dropBox && input) {
    ['dragenter','dragover','dragleave','drop'].forEach(evt =>
      dropBox.addEventListener(evt, e => e.preventDefault())
    );
    dropBox.addEventListener('drop', function(e){
      const dt = e.dataTransfer;
      if (!dt) return;
      const files = Array.from(dt.files);
      files.forEach(f => {
        if(f.type.startsWith('image/')) window.legacyImages.push(f);
      });
      renderLegacyPreviews();
    });
  }
  
  // Hook into form submit to send legacy images
  const form = document.querySelector('form');
  if(form) {
      form.addEventListener('submit', function() {
          if(window.legacyImages.length > 0) {
              const dt = new DataTransfer();
              window.legacyImages.forEach(f => dt.items.add(f));
              input.files = dt.files;
          }
      });
  }

  // ====== Parent/Subcategory dynamic filtering ======
  const parentSelect = document.getElementById('parent_category_id');
  const subSelect    = document.getElementById('category_id');

  if (parentSelect && subSelect) {
    const allSubOptions = Array.from(subSelect.querySelectorAll('option[data-parent-id]'));

    function refreshSubOptions() {
      const currentParent = parentSelect.value;
      const currentSub    = subSelect.value;

      // reset list
      subSelect.innerHTML = '';
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = '-- Select sub category (optional) --';
      subSelect.appendChild(placeholder);

      if (!currentParent) {
        // if no main category: either show all subs OR keep it empty
        // here we choose to show NONE until a main category is chosen
        return;
      }

      allSubOptions.forEach(function(opt){
        if (opt.getAttribute('data-parent-id') === currentParent) {
          subSelect.appendChild(opt.cloneNode(true));
        }
      });

      // try restore previous selection (if it still matches)
      if (currentSub) {
        subSelect.value = currentSub;
      }
    }

    // Initial load (for old values, edit form, or validation fail)
    refreshSubOptions();

    parentSelect.addEventListener('change', function(){
      // when main category changes, clear sub and rebuild
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
  
  let variants = []; // Store variant data in memory
  let editingIndex = null;
  const ckEditorFontCssUrl = 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Lato:wght@300;400;700;900&family=Open+Sans:wght@300;400;600;700;800&family=Source+Sans+3:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&family=EB+Garamond:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Cormorant:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700;800&family=Raleway:wght@300;400;500;600;700;800&display=swap';
  
  // Modal Controls
  function openModal(editIndex = null) {
    editingIndex = editIndex;
    
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

    if (editIndex !== null) {
      modalTitle.textContent = 'Edit Variant';
      const variant = variants[editIndex];
      document.getElementById('variantName').value = variant.name;
      document.getElementById('variantPrice').value = variant.price;
      document.getElementById('variantStock').value = variant.stock;
      document.getElementById('variantSKU').value = variant.sku;
      document.getElementById('variantHSN').value = variant.hsn || '';
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
      
    } else {
      modalTitle.textContent = 'Add Variant';
      clearModalForm();
    }
    variantModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
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
    const variantPrice = document.getElementById('variantPrice');
    
    if (isLinked) {
      linkedSection.classList.remove('hidden');
      customSection.classList.add('hidden');
      // Remove required from hidden fields to prevent validation errors
      if(variantPrice) variantPrice.removeAttribute('required');
    } else {
      linkedSection.classList.add('hidden');
      customSection.classList.remove('hidden');
      // Add required back
      if(variantPrice) variantPrice.setAttribute('required', 'required');
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
      // Search products via AJAX
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
          
          // Add click handlers
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
  
  // Variant FAQs in Modal
  let variantFaqCount = 0;
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
        img.className = 'w-16 h-16 object-cover rounded border';
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
      id: null,
      name: name,
      type: variantType
    };
    
    if (variantType === 'linked') {
      // Linked Product Variant
      const linkedProductId = document.getElementById('linkedProductId').value;
      
      if (!linkedProductId) {
        alert('Please select a product to link!');
        return;
      }
      
      variantData.linked_product_id = parseInt(linkedProductId);
      variantData.hsn = null; // HSN hidden for linked
      
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
      
      variantData.price = parseFloat(price);
      variantData.compare_price = parseFloat(document.getElementById('variantComparePrice').value) || null;
      variantData.discount_percent = parseFloat(document.getElementById('variantDiscount').value) || null;
      variantData.stock = document.getElementById('variantStock').value || 10;
      variantData.sku = document.getElementById('variantSKU').value.trim();
      variantData.hsn = document.getElementById('variantHSN').value.trim();
      variantData.customTitle = document.getElementById('variantCustomTitle').value.trim();
      
      variantData.customDesc = document.getElementById('variantCustomDesc').value.trim();
      variantData.shortDesc = document.getElementById('variantShortDesc').value.trim();
      variantData.ingredients = document.getElementById('variantIngredients').value.trim();
      variantData.howToUse = document.getElementById('variantHowToUse').value.trim();
      
      variantData.metaTitle = document.getElementById('variantMetaTitle').value.trim();
      variantData.metaDesc = document.getElementById('variantMetaDesc').value.trim();
      variantData.images = document.getElementById('variantImages').files;
      variantData.faqs = [];
      
      // Collect FAQs
      document.querySelectorAll('#variantFaqsContainer > div').forEach(row => {
        const q = row.querySelector('.variant-faq-question')?.value.trim();
        const a = row.querySelector('.variant-faq-answer')?.value.trim();
        if (q && a) {
          variantData.faqs.push({ question: q, answer: a });
        }
      });
    }
    
    if (editingIndex !== null) {
      variants[editingIndex] = variantData;
    } else {
      variants.push(variantData);
    }
    
    renderVariants();
    closeModal();
  });
  
  //Render Variants List
  function renderVariants() {
    variantsList.innerHTML = '';
    variants.forEach((v, index) => {
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
            <button type="button" onclick="editVariant(${index})" class="text-indigo-600 hover:underline text-sm">Edit</button>
            <button type="button" onclick="deleteVariant(${index})" class="text-red-600 hover:underline text-sm">Delete</button>
          </div>
        `;
      } else {
        // Custom Variant Display
        card.innerHTML = `
          <div class="flex-1">
            <div class="font-semibold text-gray-900">${v.name} - ₹${v.price.toFixed(2)}</div>
            <div class="text-xs text-gray-500">Stock: ${v.stock} ${v.customTitle ? '• Custom Title' : ''} ${v.customDesc ? '• Custom Desc' : ''} ${v.images.length ? '• ' + v.images.length + ' img(s)' : ''} ${v.faqs.length ? '• ' + v.faqs.length + ' FAQ(s)' : ''}</div>
          </div>
          <div class="flex gap-2">
            <button type="button" onclick="editVariant(${index})" class="text-indigo-600 hover:underline text-sm">Edit</button>
            <button type="button" onclick="deleteVariant(${index})" class="text-red-600 hover:underline text-sm">Delete</button>
          </div>
        `;
      }
      
      variantsList.appendChild(card);
    });
  }
  
  // Global functions for edit/delete
  window.editVariant = function(index) {
    openModal(index);
  };
  
  window.deleteVariant = function(index) {
    if (confirm('Delete this variant?')) {
      variants.splice(index, 1);
      renderVariants();
    }
  };
  
  // Event Listeners
  addVariantBtn.addEventListener('click', () => openModal());
  closeModalBtn.addEventListener('click', closeModal);
  cancelModalBtn.addEventListener('click', closeModal);
  
  // Close modal on outside click
  variantModal.addEventListener('click', function(e) {
    if (e.target === variantModal) {
      closeModal();
    }
  });

  // ====== Form Submission with FormData for Variants ======
  const productForm = document.querySelector('form[action="save_product.php"]');
  if (productForm) {
    productForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      // Remove old variant inputs (if any exist in DOM, though currently none do)
      // This is safe cleanup
      const formInputs = Array.from(formData.keys());
      formInputs.forEach(key => {
        if (key.startsWith('variants[')) {
          formData.delete(key);
        }
      });
      
      // Add variant data to FormData manually since they don't have input fields
      if (typeof variants !== 'undefined' && Array.isArray(variants)) {
          variants.forEach((v, idx) => {
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
              formData.append(`variants[${idx}][compare_price]`, v.compare_price || '');
              formData.append(`variants[${idx}][discount_percent]`, v.discount_percent || '');
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
              
              // Add variant images
              if (v.images && v.images.length > 0) {
                Array.from(v.images).forEach((file, fileIdx) => {
                  formData.append(`variants[${idx}][images][${fileIdx}]`, file);
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
      
      // Add product media files with order information
      if (window.selectedMedia && window.selectedMedia.length > 0) {
        window.selectedMedia.forEach((file, index) => {
          formData.append(`product_media[${index}]`, file);
          formData.append(`product_media_order[${index}]`, index); // Track order
        });
      }
      

      
      // Submit via AJAX
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Saving...';
      
      fetch('save_product.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(html => {

        
        // Check for common error indicators or success redirect
        if (html.includes('success') || html.includes('Success')) {
           window.location.href = 'products.php';
        } else {
          // SHOW ERROR IN ALERT instead of replacing page
          alert('Server returned an error:\n\n' + html.substring(0, 500));
          console.error('Full server response:', html);
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      })
      .catch(error => {
        alert('Error saving product: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      });
    });
  }

  // ====== FAQs Logic ======
  const faqsContainer = document.getElementById('faqs-container');
  const addFaqBtn     = document.getElementById('add-faq-btn');

  if (faqsContainer && addFaqBtn) {
    let faqCount = 0;

    function addFaqRow() {
      const index = faqCount++;
      const row = document.createElement('div');
      row.className = 'border-b pb-3 last:border-0 last:pb-0 mb-2';
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
          <button type="button" class="text-red-500 hover:text-red-700 mt-6" onclick="this.closest('.border-b').remove()">
            &times;
          </button>
        </div>
      `;
      faqsContainer.appendChild(row);
    }

    addFaqBtn.addEventListener('click', addFaqRow);
  }

  // === Related Products Search with Chips ===
  const productSearchInput = document.getElementById('product_search_input');
  const searchResults = document.getElementById('search_results');
  const selectedProductsContainer = document.getElementById('selected_products');
  const relatedProductsInputs = document.getElementById('related_products_inputs');
  
  if (productSearchInput && searchResults && selectedProductsContainer) {
    let selectedProducts = []; // Array to store selected products {id, name}
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
        fetch(`/admin/search_products_api.php?q=${encodeURIComponent(query)}&current=0`)
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
    
    // Initialize with empty state
    renderSelectedProducts();
  }

  // === Product Media Gallery ===
  (function() {
    const mediaFilesInput = document.getElementById('product_media_files');
    const mediaPreview = document.getElementById('media_preview');
    window.selectedMedia = []; // Make global for form submission
    const MAX_MEDIA_FILES = 50; // Increased limit for extensive product galleries
    
    if (!mediaFilesInput || !mediaPreview) return;
    
    mediaFilesInput.addEventListener('change', function(e) {
      const files = Array.from(e.target.files);
      
      if (window.selectedMedia.length + files.length > MAX_MEDIA_FILES) {
        alert(`Maximum ${MAX_MEDIA_FILES} files allowed. You can add ${MAX_MEDIA_FILES - window.selectedMedia.length} more.`);
        return;
      }
      
      files.forEach(file => {
        if (file.size > 50 * 1024 * 1024) { // 50MB limit
          alert(`File ${file.name} is too large. Maximum 50MB per file.`);
          return;
        }
        
        window.selectedMedia.push(file);
      });
      
      renderMediaPreviews();
      e.target.value = ''; // Reset input
    });
    
    function renderMediaPreviews() {
      mediaPreview.innerHTML = window.selectedMedia.map((file, index) => {
        const isVideo = file.type.startsWith('video/');
        const previewUrl = URL.createObjectURL(file);
        
        return `
          <div class="relative border rounded-lg p-2 bg-gray-50 media-item" draggable="true" data-index="${index}">
            <div class="absolute top-1 left-1 bg-gray-800 text-white rounded px-2 py-1 text-xs z-10">
              ${index + 1}
            </div>
            <button type="button" 
                    title="Remove image"
                    class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-700 z-50 shadow-md transition-transform hover:scale-110"
                    onclick="removeMediaFile(${index})">
              <span class="text-xl font-bold">&times;</span>
            </button>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 cursor-move opacity-0 hover:opacity-100 transition-opacity">
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
              </svg>
            </div>
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
      
      // Setup drag and drop for reordering
      setupDragAndDrop();
    }
    
    // Drag and drop reordering functionality
    function setupDragAndDrop() {
      const items = mediaPreview.querySelectorAll('.media-item');
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
          // Reorder the selectedMedia array based on new DOM order
          const newOrder = [];
          mediaPreview.querySelectorAll('.media-item').forEach(item => {
            const oldIndex = parseInt(item.dataset.index);
            newOrder.push(window.selectedMedia[oldIndex]);
          });
          window.selectedMedia = newOrder;
          renderMediaPreviews();
        });
      });
    }
    
    // Make function global
    window.removeMediaFile = function(index) {
      window.selectedMedia.splice(index, 1);
      renderMediaPreviews();
    };
  })();

  // ====== PREVIEW BUTTON HANDLER ======
  const btnPreview = document.getElementById('btnPreview');
  if (btnPreview) {
      btnPreview.addEventListener('click', function(e) {
          e.preventDefault();
          const originalText = btnPreview.innerHTML;
          btnPreview.disabled = true;
          btnPreview.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating Preview...';

          const form = document.querySelector('form[action="save_product.php"]');
          const formData = new FormData(form);

          // 1. Append selected media from global window.selectedMedia
          if (window.selectedMedia && window.selectedMedia.length > 0) {
            window.selectedMedia.forEach((file, index) => {
              formData.append('preview_images[]', file);
            });
          }

          // 2. Append Variants (serialize as JSON)
          // We access the 'variants' variable which is available in this scope
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
              console.log('[Debug] Preview Response:', data); // DEBUG
              btnPreview.disabled = false;
              btnPreview.innerHTML = originalText;
              
              if (data.success && data.preview_url) {
                  // Open in new tab (URL already contains token)
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
      });
  }

})();
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
