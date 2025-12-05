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
    $tags = [];
}

// ensure CSRF token
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
        <label class="block text-sm font-semibold mb-2">Short Description</label>
        <div id="editor-short-desc" class="bg-white"></div>
        <textarea name="short_desc" id="hidden-short-desc" class="hidden"><?php echo $short_desc_val; ?></textarea>
      </div>

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

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Full Description</label>
        <div id="editor-description" class="bg-white"></div>
        <textarea name="description" id="hidden-description" class="hidden"><?php echo $description_val; ?></textarea>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Ingredients</label>
        <div id="editor-ingredients" class="bg-white"></div>
        <textarea name="ingredients" id="hidden-ingredients" class="hidden"><?php echo $old['ingredients'] ?? ''; ?></textarea>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">How to Use</label>
        <div id="editor-how-to-use" class="bg-white"></div>
        <textarea name="how_to_use" id="hidden-how-to-use" class="hidden"><?php echo $old['how_to_use'] ?? ''; ?></textarea>
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
        <p class="text-xs text-gray-600 mb-3">Upload images or videos to showcase this product (Max 10 files)</p>
        
        <div class="mb-3">
          <input type="file" 
                 id="product_media_files" 
                 class="w-full p-3 border rounded-lg" 
                 accept="image/jpeg,image/jpg,image/png,image/webp,video/mp4,video/webm"
                 multiple>
          <p class="text-xs text-gray-500 mt-1">Supported: JPG, PNG, WEBP (images) | MP4, WEBM (videos)</p>
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

<!-- image preview + subcategory filtering script -->
<script>
(function(){
  const input   = document.getElementById('images_input');
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

  if (input) {
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
  }

  const dropBox = document.querySelector('.group');
  if (dropBox && input) {
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
  
  // Modal Controls
  function openModal(editIndex = null) {
    editingIndex = editIndex;
    if (editIndex !== null) {
      modalTitle.textContent = 'Edit Variant';
      const variant = variants[editIndex];
      document.getElementById('variantName').value = variant.name;
      document.getElementById('variantPrice').value = variant.price;
      document.getElementById('variantStock').value = variant.stock;
      document.getElementById('variantSKU').value = variant.sku;
      document.getElementById('variantCustomTitle').value = variant.customTitle || '';
      document.getElementById('variantCustomDesc').value = variant.customDesc || '';
      document.getElementById('variantShortDesc').value = variant.shortDesc || '';
      // Note: Files and FAQs need special handling for edit mode
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
  }
  
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
    const name = document.getElementById('variantName').value.trim();
    const price = document.getElementById('variantPrice').value;
    
    if (!name || !price) {
      alert('Variant Name and Price are required!');
      return;
    }
    
    const variantData = {
      name: name,
      price: parseFloat(price),
      stock: parseInt(document.getElementById('variantStock').value) || 10,
      sku: document.getElementById('variantSKU').value.trim(),
      customTitle: document.getElementById('variantCustomTitle').value.trim(),
      customDesc: document.getElementById('variantCustomDesc').value.trim(),
      shortDesc: document.getElementById('variantShortDesc').value.trim(),
      ingredients: document.getElementById('variantIngredients').value.trim(),
      howToUse: document.getElementById('variantHowToUse').value.trim(),
      metaTitle: document.getElementById('variantMetaTitle').value.trim(),
      metaDesc: document.getElementById('variantMetaDesc').value.trim(),
      images: document.getElementById('variantImages').files,
      faqs: []
    };
    
    // Collect FAQs
    document.querySelectorAll('#variantFaqsContainer > div').forEach(row => {
      const q = row.querySelector('.variant-faq-question')?.value.trim();
      const a = row.querySelector('.variant-faq-answer')?.value.trim();
      if (q && a) {
        variantData.faqs.push({ question: q, answer: a });
      }
    });
    
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
      
      // Remove old variant inputs before adding new ones
      const formInputs = Array.from(formData.keys());
      formInputs.forEach(key => {
        if (key.startsWith('variants[')) {
          formData.delete(key);
        }
      });
      
      // Add variant data to FormData
      variants.forEach((v, idx) => {
        formData.append(`variants[${idx}][name]`, v.name);
        formData.append(`variants[${idx}][price]`, v.price);
        formData.append(`variants[${idx}][stock]`, v.stock);
        formData.append(`variants[${idx}][sku]`, v.sku);
        formData.append(`variants[${idx}][custom_title]`, v.customTitle || '');
        formData.append(`variants[${idx}][custom_description]`, v.customDesc || '');
        formData.append(`variants[${idx}][short_description]`, v.shortDesc || '');
        formData.append(`variants[${idx}][ingredients]`, v.ingredients || '');
        formData.append(`variants[${idx}][how_to_use]`, v.howToUse || '');
        formData.append(`variants[${idx}][meta_title]`, v.metaTitle || '');
        formData.append(`variants[${idx}][meta_description]`, v.metaDesc || '');
        
        // Add variant images (multiple files)
        if (v.images && v.images.length > 0) {
          Array.from(v.images).forEach((file, fileIdx) => {
            formData.append(`variants[${idx}][images][${fileIdx}]`, file);
          });
        }
        
        // Add variant FAQs
        v.faqs.forEach((faq, faqIdx) => {
          formData.append(`variants[${idx}][faqs][${faqIdx}][question]`, faq.question);
          formData.append(`variants[${idx}][faqs][${faqIdx}][answer]`, faq.answer);
        });
      });
      
      // Add product media files
      if (window.selectedMedia && window.selectedMedia.length > 0) {
        window.selectedMedia.forEach((file, index) => {
          formData.append(`product_media[${index}]`, file);
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
        // Check if success or error
        if (html.includes('success') || html.includes('Success')) {
          window.location.href = 'products.php';
        } else {
          // Show the response (might have errors)
          document.body.innerHTML = html;
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
  const mediaFilesInput = document.getElementById('product_media_files');
  const mediaPreview = document.getElementById('media_preview');
  window.selectedMedia = []; // Make global for form submission
  const MAX_MEDIA_FILES = 10;
  
  if (mediaFilesInput && mediaPreview) {
    mediaFilesInput.addEventListener('change', function(e) {
      const files = Array.from(e.target.files);
      
      if (selectedMedia.length + files.length > MAX_MEDIA_FILES) {
        alert(`Maximum ${MAX_MEDIA_FILES} files allowed. You can add ${MAX_MEDIA_FILES - selectedMedia.length} more.`);
        return;
      }
      
      files.forEach(file => {
        if (file.size > 50 * 1024 * 1024) { // 50MB limit
          alert(`File ${file.name} is too large. Maximum 50MB per file.`);
          return;
        }
        
        selectedMedia.push(file);
      });
      
      renderMediaPreviews();
      e.target.value = ''; // Reset input
    });
    
    function renderMediaPreviews() {
      mediaPreview.innerHTML = selectedMedia.map((file, index) => {
        const isVideo = file.type.startsWith('video/');
        const previewUrl = URL.createObjectURL(file);
        
        return `
          <div class="relative border rounded-lg p-2 bg-gray-50">
            <button type="button" 
                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 z-10"
                    onclick="removeMediaFile(${index})">
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
    
    // Make function global
    window.removeMediaFile = function(index) {
      selectedMedia.splice(index, 1);
      renderMediaPreviews();
    };
    
    // Add files to FormData before submission
    const originalSubmit = document.querySelector('form').onsubmit;
    document.querySelector('form').addEventListener('submit', function(e) {
      // Files will be handled in the FormData construction below
    });
  }

})();
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>