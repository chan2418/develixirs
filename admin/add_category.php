<?php
// admin/add_category.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$page_title = "Add Category";

// Fetch category list for Parent dropdown
$list = [];
try {
    // We only need id and title for parent selection
    // Also include slug to show hierarchy if needed but simpler is better
    $stmt = $pdo->query("SELECT id, title, parent_id FROM categories ORDER BY title ASC");
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Build Tree for Options
$byId = [];
foreach ($list as $c) $byId[$c['id']] = $c + ['children'=>[]];
$roots=[];
foreach ($byId as $id=>$node) {
    if ($node['parent_id'] && isset($byId[$node['parent_id']])) {
        $byId[$node['parent_id']]['children'][] = &$byId[$id];
    } else $roots[] = &$byId[$id];
}

function render_options($nodes,$level=0,$selected=null,$exclude=null){
    $html='';
    foreach($nodes as $n){
        if($exclude && $exclude==$n['id']) continue;
        $indent=str_repeat("&nbsp;&nbsp;",$level);
        $sel = ($selected==$n['id']) ? "selected" : "";
        $html.="<option value='{$n['id']}' $sel>$indent".htmlspecialchars($n['title'])."</option>";
        if(!empty($n['children'])){
            $html.=render_options($n['children'],$level+1,$selected,$exclude);
        }
    }
    return $html;
}

// Check for Edit Mode
$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
    $page_title = "Edit Category";
}

// Flash Helpers
function flash_get($k) {
    if (!empty($_SESSION[$k])) { $v = $_SESSION[$k]; unset($_SESSION[$k]); return $v; }
    return null;
}
$errors  = flash_get('form_errors') ?: [];
$old     = flash_get('old') ?: [];

function old_val($key,$default='') {
    global $old,$edit;
    if(isset($old[$key])) return htmlspecialchars($old[$key]);
    if($edit && isset($edit[$key])) return htmlspecialchars($edit[$key]);
    return htmlspecialchars($default);
}

include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[1000px] mx-auto py-6">

    <!-- HEADER -->
    <div class="flex items-start justify-between mb-6">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800"><?= $edit ? "Edit Category" : "Add New Category" ?></h2>
            <p class="text-sm text-slate-500"><?= $edit ? "Update existing category details" : "Create a new product category" ?></p>
        </div>
        <a href="categories.php" class="px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50">← Back to List</a>
    </div>

    <!-- FLASH ERRORS -->
    <?php if($errors): ?>
        <div class="p-4 mb-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <strong>Errors:</strong>
            <?php foreach($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- FORM CARD -->
    <div class="bg-white border border-gray-200 p-6 rounded-xl shadow-sm">

        <form action="save_category.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php
                if (empty($_SESSION['csrf_token'])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
                }
                echo $_SESSION['csrf_token'];
            ?>">

            <?php if($edit): ?>
                <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- COL 1 -->
                <div>
                    <!-- Title -->
                    <label class="block font-semibold mb-1">Title</label>
                    <input name="title" class="w-full p-2 rounded-lg border border-gray-300 mb-4"
                           value="<?= old_val('title') ?>" required placeholder="e.g. Skin Care">

                    <!-- Parent -->
                    <label class="block font-semibold mb-1">Parent Category</label>
                    <select name="parent_id" class="w-full p-2 rounded-lg border border-gray-300 mb-4">
                        <option value="">-- None (Top Level) --</option>
                        <?= render_options($roots,0, $edit['parent_id'] ?? null, $edit['id'] ?? null) ?>
                    </select>
                </div>

                <!-- COL 2 (Image) -->
                <div>
                    <label class="block font-semibold mb-1">Category Image</label>
                    
                    <!-- Main Image Selection UI -->
                    <div class="space-y-3 mb-4">
                        <div class="flex flex-col gap-2">
                            <!-- Option 1: Upload -->
                            <label class="flex items-center gap-2 cursor-pointer">
                               <input type="file" name="image" class="w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-full file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-indigo-50 file:text-indigo-700
                                  hover:file:bg-indigo-100" accept="image/*" onchange="previewMainImage(this)">
                            </label>
                            
                            <div class="text-center text-xs text-gray-400 font-medium">- OR -</div>

                            <!-- Option 2: Select from Library -->
                            <button type="button" onclick="window.mediaTarget='main'; openMediaModal()" class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition shadow-sm text-sm font-medium">
                                🗃️ Select from Library
                            </button>
                            <input type="hidden" name="image_selected" id="main_image_selected" value="">
                        </div>

                        <!-- Preview Area -->
                        <div id="main_image_preview" class="border rounded p-2 bg-gray-50 flex items-center justify-center min-h-[100px] relative group <?= ($edit && !empty($edit['image'])) ? '' : 'hidden' ?>">
                            <?php 
                              $currentImgUrl = '';
                              if ($edit && !empty($edit['image'])) {
                                  $imgVal = trim($edit['image']);
                                  // Full URL
                                  if (preg_match('#^https?://#i', $imgVal)) {
                                      $currentImgUrl = $imgVal;
                                  }
                                  // Absolute path starting with /
                                  elseif (strpos($imgVal, '/') === 0) {
                                      $currentImgUrl = $imgVal;
                                  }
                                  // Path starting with "assets/" (from media library)
                                  elseif (strpos($imgVal, 'assets/') === 0) {
                                      $currentImgUrl = '/' . $imgVal;
                                  }
                                  // Just filename
                                  else {
                                      $currentImgUrl = '/assets/uploads/categories/' . ltrim($imgVal, '/');
                                  }
                              }
                            ?>
                            <img src="<?= htmlspecialchars($currentImgUrl) ?>" class="max-h-32 object-contain" id="main_img_tag">
                            <button type="button" onclick="clearMainImage()" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow hover:bg-red-600 tool-remove hidden group-hover:block transition">&times;</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description (CKEditor) -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <label class="block font-semibold">Description</label>
                    <button type="button" onclick="window.mediaTarget='editor'; openMediaModal()" class="px-3 py-1 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition flex items-center gap-1">
                        <span class="text-xs">📁</span> Add Media
                    </button>
                </div>
                <!-- Editor Div -->
                <div id="description_editor_div" class="bg-white border rounded"></div>
                <!-- Hidden Input for Form Submission -->
                <textarea name="description" id="hidden_description" class="hidden"><?= old_val('description') ?></textarea>
            </div>

            <!-- SEO Section -->
            <div class="border-t pt-4 mt-4 bg-gray-50 p-4 rounded-lg mb-6">
                <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <span class="text-indigo-500">🔍</span> SEO Settings
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold mb-1 text-sm">Meta Title</label>
                        <input name="meta_title" class="w-full p-2 rounded-lg border border-gray-300"
                               value="<?= old_val('meta_title') ?>" placeholder="SEO Title">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-sm">Meta Description</label>
                        <textarea name="meta_description" rows="2"
                                  class="w-full p-2 rounded-lg border border-gray-300" placeholder="SEO Description"><?= old_val('meta_description') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Media Gallery Section -->
            <div class="border-t pt-4 mt-4 mb-6">
                <h4 class="font-bold text-gray-700 mb-3">Media Gallery</h4>
                
                <div class="space-y-3 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                       <input type="file" name="media_gallery[]" multiple
                          class="w-full text-sm text-gray-500
                          file:mr-4 file:py-2 file:px-4
                          file:rounded-full file:border-0
                          file:text-sm file:font-semibold
                          file:bg-indigo-50 file:text-indigo-700
                          hover:file:bg-indigo-100" accept="image/*,video/*" onchange="previewUploads(this)">
                    </label>
                    
                    <div class="text-center text-xs text-gray-400 font-medium">- OR -</div>

                    <button type="button" onclick="window.mediaTarget='gallery'; openMediaModal()" class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition shadow-sm text-sm font-medium">
                        🗃️ Select from Library
                    </button>
                    <input type="hidden" name="media_gallery_selected" id="media_gallery_selected" value="">
                </div>

                <p class="text-xs text-slate-500 mb-2">Selected Images / Videos:</p>
                <div id="gallery-preview" class="flex flex-wrap gap-2 mt-2">
                    <?php 
                       $gallery = [];
                       if($edit && !empty($edit['media_gallery'])) {
                           $gallery = json_decode($edit['media_gallery'], true) ?: [];
                       }
                       foreach($gallery as $g): 
                           $gUrl = (strpos($g, '/') === 0) ? $g : '/assets/uploads/categories/' . ltrim($g, '/');
                           $ext = strtolower(pathinfo($gUrl, PATHINFO_EXTENSION));
                           $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                    ?>
                        <div class="relative w-24 h-24 border rounded overflow-hidden group bg-gray-100">
                            <?php if($isVideo): ?>
                                <video src="<?= htmlspecialchars($gUrl) ?>" class="w-full h-full object-cover" muted></video>
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <span class="text-white bg-black bg-opacity-50 rounded-full p-1 text-xs">▶</span>
                                </div>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($gUrl) ?>" class="w-full h-full object-cover">
                            <?php endif; ?>
                            
                            <button type="button" class="absolute top-0 right-0 bg-red-500 text-white w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition z-10" 
                                    onclick="removeExistingImage(this, '<?= htmlspecialchars($g) ?>')">×</button>
                            <input type="hidden" name="existing_images[]" value="<?= htmlspecialchars($g) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="border-t pt-4 mt-4 mb-6">
                <h4 class="font-bold text-gray-700 mb-3">FAQs</h4>
                <div id="faq-container">
                    <?php 
                        $faqs = [];
                        if($edit && !empty($edit['faqs'])) {
                            $faqs = json_decode($edit['faqs'], true) ?: [];
                        }
                        foreach ($faqs as $i => $faq): 
                    ?>
                    <div class="faq-item border border-gray-100 p-3 rounded mb-2 bg-gray-50 relative">
                        <button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" onclick="this.parentElement.remove()">×</button>
                        <input name="faq_questions[]" class="w-full p-2 border rounded mb-2 text-sm" placeholder="Question" value="<?= htmlspecialchars($faq['q'] ?? '') ?>">
                        <textarea name="faq_answers[]" class="w-full p-2 border rounded text-sm" rows="2" placeholder="Answer"><?= htmlspecialchars($faq['a'] ?? '') ?></textarea>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="addFaq()" class="text-sm text-indigo-600 font-semibold hover:underline">+ Add FAQ</button>
            </div>

            <div class="flex justify-end gap-3 mt-4 pt-4 border-t">
                <a href="categories.php" class="px-6 py-2 rounded-lg border hover:bg-gray-50">Cancel</a>
                <button type="button" id="btnPreview" class="px-6 py-2 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 shadow shadow-emerald-200 flex items-center gap-2">
                    <span class="text-xl">👁️</span> Preview
                </button>
                <button class="px-8 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 shadow shadow-indigo-200">
                    <?= $edit ? "Update Category" : "Save New Category" ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// PREVIEW HANDLER
document.getElementById('btnPreview').addEventListener('click', function(e) {
    e.preventDefault();
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Generating...';

    const form = document.querySelector('form');
    const formData = new FormData(form);
    
    // Explicitly add preview_type
    formData.append('preview_type', 'category');

    // Sync CKEditor data explicitly
    if (CKEDITOR.instances.description_editor_div) {
        formData.set('description', CKEDITOR.instances.description_editor_div.getData());
    }

    // Handle File Uploads
    // 1. Image (Main)
    const imgInput = document.querySelector('input[name="image"]');
    if (imgInput && imgInput.files[0]) {
        formData.append('image', imgInput.files[0]); // generic handler will pick this up as 'image_paths'
    }

    // 2. Gallery
    const galleryInput = document.querySelector('input[name="media_gallery[]"]');
    if (galleryInput && galleryInput.files.length > 0) {
        // generic handler iterates $_FILES so it will pick up media_gallery[] as 'media_gallery_paths'
        // FormData automatically handles multiple files with same name
    }

    fetch('/admin/handlers/save_preview_data.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.success && data.preview_url) {
            window.open(data.preview_url, '_blank');
        } else {
            alert('Preview failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Error generating preview');
    });
});

// FAQ REPEATER
function addFaq() {
    const div = document.createElement('div');
    div.className = 'faq-item border border-gray-100 p-3 rounded mb-2 bg-gray-50 relative';
    div.innerHTML = `
        <button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" onclick="this.parentElement.remove()">×</button>
        <input name="faq_questions[]" class="w-full p-2 border rounded mb-2 text-sm" placeholder="Question">
        <textarea name="faq_answers[]" class="w-full p-2 border rounded text-sm" rows="2" placeholder="Answer"></textarea>
    `;
    document.getElementById('faq-container').appendChild(div);
}

/* ---------- MEDIA GALLERY / PREVIEW LOGIC Same as before ---------- */
window.mediaTarget = 'gallery'; 

function openMediaModal() {
    const modal = document.createElement('div');
    modal.id = 'mediaLibraryModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
    modal.innerHTML = `
      <div class="bg-white rounded-lg shadow-xl w-11/12 h-5/6 max-w-6xl overflow-hidden relative">
        <button onclick="closeMediaModal()" class="absolute top-4 right-4 z-50 bg-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg hover:bg-gray-100 text-gray-600 hover:text-gray-800" style="font-size: 24px;">
          &times;
        </button>
        <iframe src="/admin/media.php?select=1" class="w-full h-full border-0"></iframe>
      </div>
    `;
    document.body.appendChild(modal);
}

function closeMediaModal() {
    const modal = document.getElementById('mediaLibraryModal');
    if (modal) modal.remove();
    setTimeout(() => { window.mediaTarget = 'gallery'; }, 500);
}

function isVideo(path) {
    return path.match(/\.(mp4|webm|ogg)$/i);
}

window.insertImagesToEditor = function(imagePaths) {
    if (!imagePaths || imagePaths.length === 0) return;

    // === CASE A: EDITOR ===
    if (window.mediaTarget === 'editor') {
        const editor = CKEDITOR.instances.description_editor_div; // Changed ID
        if (!editor) return;
        let html = '';
        imagePaths.forEach(path => {
            const ext = path.split('.').pop().toLowerCase();
            const isVid = ['mp4', 'webm', 'ogg'].includes(ext);
            if (isVid) html += `<p><video src="${path}" controls style="max-width:100%; height:auto; display:block; margin: 10px 0;"></video></p>`;
            else html += `<p><img src="${path}" style="max-width:100%; height:auto; display:block; margin: 10px 0;" /></p>`;
        });
        editor.insertHtml(html);
        closeMediaModal();
        return;
    }

    // === CASE B: MAIN IMAGE SELECTION ===
    if (window.mediaTarget === 'main') {
        const path = imagePaths[0]; 
        document.getElementById('main_image_selected').value = path;
        const previewDiv = document.getElementById('main_image_preview');
        previewDiv.classList.remove('hidden');
        if (isVideo(path)) {
             previewDiv.innerHTML = `
                <video src="${path}" class="max-h-32 object-contain" muted autoplay loop></video>
                <button type="button" onclick="clearMainImage()" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow hover:bg-red-600 tool-remove transition">&times;</button>
             `;
        } else {
             previewDiv.innerHTML = `
                <img src="${path}" class="max-h-32 object-contain" id="main_img_tag">
                <button type="button" onclick="clearMainImage()" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow hover:bg-red-600 tool-remove transition">&times;</button>
             `;
        }
        closeMediaModal();
        return;
    }

    // === CASE C: GALLERY SELECTION ===
    const container = document.getElementById('gallery-preview');
    const hiddenInput = document.getElementById('media_gallery_selected');
    let currentSelected = hiddenInput.value ? JSON.parse(hiddenInput.value) : [];
    
    imagePaths.forEach(path => {
        if (!currentSelected.includes(path)) {
            currentSelected.push(path);
            const div = document.createElement('div');
            div.className = 'relative w-24 h-24 border rounded overflow-hidden group bg-gray-100';
            let contentHtml = isVideo(path) ? 
                `<video src="${path}" class="w-full h-full object-cover" muted></video><div class="absolute inset-0 flex items-center justify-center pointer-events-none"><span class="text-white bg-black bg-opacity-50 rounded-full p-1 text-xs">▶</span></div>` : 
                `<img src="${path}" class="w-full h-full object-cover">`;
            
            div.innerHTML = `
                ${contentHtml}
                <button type="button" class="absolute top-0 right-0 bg-red-500 text-white w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition z-10"
                        onclick="removeSelectedImage(this, '${path}')">×</button>
            `;
            container.appendChild(div);
        }
    });
    hiddenInput.value = JSON.stringify(currentSelected);
    closeMediaModal();
};

function previewMainImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
             const previewDiv = document.getElementById('main_image_preview');
             previewDiv.classList.remove('hidden');
             const isVid = file.type.startsWith('video/');
             if (isVid) {
                 previewDiv.innerHTML = `
                    <video src="${e.target.result}" class="max-h-32 object-contain" muted autoplay loop></video>
                    <div class="absolute bottom-0 bg-black text-white text-xs px-1">New Upload</div>
                    <button type="button" onclick="clearMainImage()" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow hover:bg-red-600 tool-remove transition">&times;</button>
                 `;
             } else {
                 previewDiv.innerHTML = `
                    <img src="${e.target.result}" class="max-h-32 object-contain">
                    <div class="absolute bottom-0 bg-black text-white text-xs px-1">New Upload</div>
                    <button type="button" onclick="clearMainImage()" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow hover:bg-red-600 tool-remove transition">&times;</button>
                 `;
             }
        }
        reader.readAsDataURL(file);
    }
}

function clearMainImage() {
    document.querySelector('input[name="image"]').value = '';
    document.getElementById('main_image_selected').value = '';
    const previewDiv = document.getElementById('main_image_preview');
    previewDiv.classList.add('hidden');
    previewDiv.innerHTML = ''; 
}

function removeSelectedImage(btn, path) {
    btn.parentElement.remove();
    const hiddenInput = document.getElementById('media_gallery_selected');
    let currentSelected = hiddenInput.value ? JSON.parse(hiddenInput.value) : [];
    currentSelected = currentSelected.filter(p => p !== path);
    hiddenInput.value = JSON.stringify(currentSelected);
}

function removeExistingImage(btn, filename) {
    btn.parentElement.remove(); 
}

function previewUploads(input) {
    const files = input.files;
    const container = document.getElementById('gallery-preview');
    Array.from(files).forEach(file => {
        const reader = new FileReader();
        reader.onload = function(e) {
             const div = document.createElement('div');
             div.className = 'relative w-24 h-24 border rounded overflow-hidden group bg-gray-50';
             const isVid = file.type.startsWith('video/');
             div.innerHTML = isVid ? 
                `<video src="${e.target.result}" class="w-full h-full object-cover" muted></video><div class="absolute inset-0 flex items-center justify-center pointer-events-none"><span class="text-white bg-black bg-opacity-50 rounded-full p-1 text-xs">▶</span></div><div class="absolute bottom-0 left-0 right-0 bg-blue-600 text-white text-[10px] text-center px-1">Upload</div>` : 
                `<img src="${e.target.result}" class="w-full h-full object-cover"><div class="absolute bottom-0 left-0 right-0 bg-blue-600 text-white text-[10px] text-center px-1">Upload</div>`;
             container.appendChild(div);
        }
        reader.readAsDataURL(file);
    });
}
</script>

<!-- CKEditor 4 CDN -->
<script src="//cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<style>
  .cke_notification_warning { display: none !important; }
  .cke_notifications_area { display: none !important; }
  .cke_editable { font-size: 16px !important; line-height: 1.6 !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ckEditorFontCssUrl = 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Lato:wght@300;400;700;900&family=Open+Sans:wght@300;400;600;700;800&family=Source+Sans+3:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&family=EB+Garamond:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Cormorant:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700;800&family=Raleway:wght@300;400;500;600;700;800&display=swap';
    if (document.getElementById('description_editor_div')) {
        CKEDITOR.replace('description_editor_div', {
            height: 300,
            removePlugins: 'easyimage,cloudservices',
            extraPlugins: 'uploadimage',
            enterMode: CKEDITOR.ENTER_P, // Explicitly force P
            shiftEnterMode: CKEDITOR.ENTER_BR,
            autoParagraph: true,
            fillEmptyBlocks: true,
            font_names: 'Arial/Arial, Helvetica, sans-serif;Comic Sans MS/Comic Sans MS, cursive;Courier New/Courier New, Courier, monospace;Georgia/Georgia, serif;Lucida Sans Unicode/Lucida Sans Unicode, Lucida Grande, sans-serif;Tahoma/Tahoma, Geneva, sans-serif;Times New Roman/Times New Roman, Times, serif;Trebuchet MS/Trebuchet MS, Helvetica, sans-serif;Verdana/Verdana, Geneva, sans-serif;Poppins/Poppins, sans-serif;Playfair Display/Playfair Display, serif;Cormorant Garamond/Cormorant Garamond, serif;Lato/Lato, sans-serif;Open Sans/Open Sans, sans-serif;Source Sans/Source Sans 3, Source Sans Pro, sans-serif;Libre Baskerville/Libre Baskerville, serif;EB Garamond/EB Garamond, serif;Montserrat/Montserrat, sans-serif;Cormorant/Cormorant, serif;Nunito/Nunito, sans-serif;Raleway/Raleway, sans-serif;',
            on: {
                instanceReady: function(evt) {
                    this.document.appendStyleSheet(ckEditorFontCssUrl);
                    this.document.appendStyleSheet('/assets/css/style.css'); 
                    // Load initial content
                    var existing = document.getElementById('hidden_description').value;
                    // If existing content is plain text (no tags), wrap in <p> to assist CKEditor
                    if(existing && !existing.trim().startsWith('<')) {
                         existing = '<p>' + existing + '</p>';
                    }
                    if(existing) this.setData(existing);
                },
                change: function(evt) {
                    // Sync to hidden input
                    document.getElementById('hidden_description').value = this.getData();
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
