<?php
// admin/pages/editor.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/db.php';
// Use _auth.php if available for consistent session/auth
if (file_exists(__DIR__ . '/../_auth.php')) {
    require_once __DIR__ . '/../_auth.php';
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

$pageId = $_GET['id'] ?? null;
$page = null;

if ($pageId) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Default content structure
$defaultContent = [];
$currentContent = $page ? json_decode($page['content'], true) : $defaultContent;
$pageTitle = $page ? $page['title'] : '';
$pageSlug = $page ? $page['slug'] : '';
$metaTitle = $page ? $page['meta_title'] : '';
$metaDesc = $page ? $page['meta_description'] : '';
$status = $page ? $page['status'] : 'draft';
$type = $page ? $page['type'] : 'custom';
?>
<?php
// Include Header
// Use standard admin header to ensure sidebar and styles are consistent
$pathToHeader = __DIR__ . '/../layout/header.php';
if (file_exists($pathToHeader)) {
    include $pathToHeader;
} else {
    // Fallback if structure is different
    echo '<div style="color:red; padding:20px;">Error: Layout header not found at ' . htmlspecialchars($pathToHeader) . '</div>';
}
?>

<!-- Extra Styles for Editor -->
<style>
    .block-item { transition: all 0.2s; }
    .block-item:hover { border-color: #6366f1; }
    .drag-handle { cursor: grab; }
    .drag-handle:active { cursor: grabbing; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>

<!-- Content Wrapper -->
<div class="flex-1 flex flex-col h-[calc(100vh-64px)] overflow-hidden">

    <!-- Editor Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center shadow-sm z-20">
        <div class="flex items-center gap-4">
            <a href="index.php" class="text-slate-500 hover:text-slate-800"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <h1 class="text-lg font-bold text-slate-800"><?= $pageId ? 'Edit Page' : 'Create New Page' ?></h1>
            <div id="saveStatus" class="text-xs text-slate-400 italic hidden">Saving...</div>
        </div>
        <div class="flex gap-3">
            <a href="<?= $pageId ? '/page.php?slug=' . $pageSlug . '&preview=1' : '#' ?>" target="_blank" class="px-4 py-2 text-sm font-medium text-slate-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                Preview
            </a>
            <button onclick="savePage()" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow transition-colors flex items-center gap-2">
                <i class="fa-solid fa-save"></i> Save Page
            </button>
        </div>
    </div>

    <!-- Main Editor Layout -->
    <div class="flex flex-1 overflow-hidden">
        
        <!-- Left Sidebar: Settings -->
        <div class="w-80 bg-white border-r border-gray-200 overflow-y-auto p-5 z-10 hidden md:block">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Page Settings</h3>
            
            <form id="pageSettingsForm" class="space-y-4">
                <input type="hidden" name="id" value="<?= htmlspecialchars($pageId ?? '') ?>">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Page Title</label>
                    <input type="text" name="title" id="inputTitle" value="<?= htmlspecialchars($pageTitle) ?>" class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 p-2 border" placeholder="e.g. About Us" onkeyup="generateSlug(this.value)">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">URL Slug</label>
                    <div class="flex items-center">
                        <span class="text-gray-400 text-xs mr-1">/</span>
                        <input type="text" name="slug" id="inputSlug" value="<?= htmlspecialchars($pageSlug) ?>" class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 p-2 border bg-gray-50" placeholder="about-us">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                         <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                         <select name="status" class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 p-2 border">
                             <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                             <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                         </select>
                    </div>
                    <div>
                         <label class="block text-xs font-semibold text-gray-600 mb-1">Type</label>
                         <select name="type" class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 p-2 border">
                             <option value="custom" <?= $type === 'custom' ? 'selected' : '' ?>>Custom</option>
                             <option value="about" <?= $type === 'about' ? 'selected' : '' ?>>About</option>
                             <option value="contact" <?= $type === 'contact' ? 'selected' : '' ?>>Contact</option>
                             <option value="faq" <?= $type === 'faq' ? 'selected' : '' ?>>FAQ</option>
                         </select>
                    </div>
                </div>

                <hr class="my-4 border-gray-100">
                
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">SEO Details</h3>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Meta Title</label>
                    <input type="text" name="meta_title" value="<?= htmlspecialchars($metaTitle) ?>" class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 p-2 border">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Meta Description</label>
                    <textarea name="meta_description" rows="3" class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 p-2 border"><?= htmlspecialchars($metaDesc) ?></textarea>
                </div>
            </form>
        </div>

        <!-- Center: Visual Editor -->
        <div class="flex-1 overflow-y-auto bg-gray-100 p-8 flex justify-center">
            <div class="w-full max-w-4xl">
                <!-- Canvas -->
                <div id="editorCanvas" class="space-y-4 pb-20">
                    <!-- Blocks will be injected here -->
                </div>
                
                <!-- Empty State -->
                <div id="emptyState" class="text-center py-12 <?= !empty($currentContent) ? 'hidden' : '' ?>">
                    <div class="bg-white border-2 border-dashed border-gray-300 rounded-xl p-8 flex flex-col items-center justify-center text-gray-400">
                        <i class="fa-regular fa-clone text-4xl mb-3 opacity-50"></i>
                        <p class="text-sm font-medium">Start building your page</p>
                        <p class="text-xs mt-1 mb-4">Add blocks from the panel on the right (or below)</p>
                    </div>
                </div>
                
                <div class="h-20"></div> <!-- spacer -->
            </div>
        </div>

        <!-- Right/Bottom: Block Library (Floating) -->
        <div class="fixed bottom-6 right-6 z-30 flex flex-col gap-2 items-end">
            <div id="blockMenu" class="bg-white shadow-xl rounded-xl border border-gray-100 p-4 w-64 mb-2 hidden transition-all transform origin-bottom-right">
                <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Add Block</h4>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="addBlock('hero')" class="flex flex-col items-center justify-center p-3 rounded bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600 border border-transparent hover:border-indigo-100 transition">
                        <i class="fa-solid fa-image text-lg mb-1"></i>
                        <span class="text-xs font-medium">Hero</span>
                    </button>
                    <button onclick="addBlock('text')" class="flex flex-col items-center justify-center p-3 rounded bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600 border border-transparent hover:border-indigo-100 transition">
                        <i class="fa-solid fa-paragraph text-lg mb-1"></i>
                        <span class="text-xs font-medium">Text</span>
                    </button>
                    <button onclick="addBlock('image_text')" class="flex flex-col items-center justify-center p-3 rounded bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600 border border-transparent hover:border-indigo-100 transition">
                        <i class="fa-solid fa-grip-lines-vertical text-lg mb-1"></i>
                        <span class="text-xs font-medium">Img + Txt</span>
                    </button>
                    <button onclick="addBlock('faq')" class="flex flex-col items-center justify-center p-3 rounded bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600 border border-transparent hover:border-indigo-100 transition">
                        <i class="fa-solid fa-list text-lg mb-1"></i>
                        <span class="text-xs font-medium">FAQ</span>
                    </button>
                    <button onclick="addBlock('form')" class="flex flex-col items-center justify-center p-3 rounded bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600 border border-transparent hover:border-indigo-100 transition">
                        <i class="fa-solid fa-envelope text-lg mb-1"></i>
                        <span class="text-xs font-medium">Form</span>
                    </button>
                </div>
            </div>
            
            <button onclick="document.getElementById('blockMenu').classList.toggle('hidden')" class="w-14 h-14 bg-indigo-600 text-white rounded-full shadow-lg hover:bg-indigo-700 flex items-center justify-center text-2xl transition-transform hover:scale-110 active:scale-95">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

    </div>
</div>

<!-- Templates for Blocks (Hidden) -->
<template id="tpl-hero">
    <div class="block-item bg-white rounded-lg shadow-sm border border-gray-200 p-0 relative group" data-type="hero">
        <div class="bg-gray-50 p-2 border-b border-gray-100 flex justify-between items-center rounded-t-lg">
            <div class="flex items-center gap-2">
                <span class="drag-handle text-gray-400 hover:text-gray-600 p-1"><i class="fa-solid fa-grip-vertical"></i></span>
                <span class="text-xs font-bold uppercase text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">Hero Section</span>
            </div>
            <button type="button" class="delete-block-btn text-gray-400 hover:text-red-500 p-1 rounded hover:bg-red-50"><i class="fa-solid fa-trash"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <div class="grid grid-cols-2 gap-4">
               <div>
                   <label class="block text-xs font-medium text-gray-500 mb-1">Heading</label>
                   <input type="text" class="block-input w-full p-2 border border-gray-300 rounded text-sm font-bold" data-key="heading" placeholder="Main Headline">
               </div>
               <div>
                   <label class="block text-xs font-medium text-gray-500 mb-1">Subheading</label>
                   <input type="text" class="block-input w-full p-2 border border-gray-300 rounded text-sm" data-key="subheading" placeholder="Subtitle text">
               </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Background Image URL</label>
                <input type="text" class="block-input w-full p-2 border border-gray-300 rounded text-sm" data-key="bg_image" placeholder="https://...">
            </div>
            <div class="flex gap-3">
                <input type="text" class="block-input flex-1 p-2 border border-gray-300 rounded text-sm" data-key="cta_text" placeholder="Button Text (e.g. Shop Now)">
                <input type="text" class="block-input flex-1 p-2 border border-gray-300 rounded text-sm" data-key="cta_link" placeholder="Button Link (e.g. /products)">
            </div>
        </div>
    </div>
</template>

<template id="tpl-text">
    <div class="block-item bg-white rounded-lg shadow-sm border border-gray-200 relative group" data-type="text">
        <div class="bg-gray-50 p-2 border-b border-gray-100 flex justify-between items-center rounded-t-lg">
             <div class="flex items-center gap-2">
                <span class="drag-handle text-gray-400 hover:text-gray-600 p-1"><i class="fa-solid fa-grip-vertical"></i></span>
                <span class="text-xs font-bold uppercase text-gray-600 bg-gray-100 px-2 py-0.5 rounded">Rich Text</span>
            </div>
            <button type="button" class="delete-block-btn text-gray-400 hover:text-red-500 p-1 rounded hover:bg-red-50"><i class="fa-solid fa-trash"></i></button>
        </div>
        <div class="p-4">
            <textarea class="block-input ckeditor-input w-full border border-gray-300 rounded p-2 text-sm" rows="4" data-key="content" placeholder="Enter HTML content or text here..."></textarea>
        </div>
    </div>
</template>

<template id="tpl-image_text">
    <div class="block-item bg-white rounded-lg shadow-sm border border-gray-200 relative group" data-type="image_text">
        <div class="bg-gray-50 p-2 border-b border-gray-100 flex justify-between items-center rounded-t-lg">
             <div class="flex items-center gap-2">
                <span class="drag-handle text-gray-400 hover:text-gray-600 p-1"><i class="fa-solid fa-grip-vertical"></i></span>
                <span class="text-xs font-bold uppercase text-blue-600 bg-blue-50 px-2 py-0.5 rounded">Image + Text</span>
            </div>
            <button type="button" class="delete-block-btn text-gray-400 hover:text-red-500 p-1 rounded hover:bg-red-50"><i class="fa-solid fa-trash"></i></button>
        </div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Image URL</label>
                <input type="text" class="block-input w-full p-2 border border-gray-300 rounded text-sm mb-2" data-key="image" placeholder="Image URL">
                <label class="block text-xs font-medium text-gray-500 mb-1">Image Position</label>
                <select class="block-input w-full p-2 border border-gray-300 rounded text-sm" data-key="position">
                    <option value="left">Left</option>
                    <option value="right">Right</option>
                </select>
            </div>
            <div>
                 <label class="block text-xs font-medium text-gray-500 mb-1">Content</label>
                 <textarea class="block-input ckeditor-input w-full border border-gray-300 rounded p-2 text-sm h-32" data-key="content" placeholder="Text Content..."></textarea>
            </div>
        </div>
    </div>
</template>

<template id="tpl-faq">
    <div class="block-item bg-white rounded-lg shadow-sm border border-gray-200 relative group" data-type="faq">
        <div class="bg-gray-50 p-2 border-b border-gray-100 flex justify-between items-center rounded-t-lg">
             <div class="flex items-center gap-2">
                <span class="drag-handle text-gray-400 hover:text-gray-600 p-1"><i class="fa-solid fa-grip-vertical"></i></span>
                <span class="text-xs font-bold uppercase text-orange-600 bg-orange-50 px-2 py-0.5 rounded">FAQ Group</span>
            </div>
            <button type="button" class="delete-block-btn text-gray-400 hover:text-red-500 p-1 rounded hover:bg-red-50"><i class="fa-solid fa-trash"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <div class="faq-list space-y-2">
                <!-- FAQ items injected here -->
            </div>
            <button onclick="addFaqItem(this)" class="text-xs text-indigo-600 hover:underline"><i class="fa-solid fa-plus"></i> Add Question</button>
        </div>
    </div>
</template>

<template id="tpl-form">
    <div class="block-item bg-white rounded-lg shadow-sm border border-gray-200 relative group" data-type="form">
        <div class="bg-gray-50 p-2 border-b border-gray-100 flex justify-between items-center rounded-t-lg">
             <div class="flex items-center gap-2">
                <span class="drag-handle text-gray-400 hover:text-gray-600 p-1"><i class="fa-solid fa-grip-vertical"></i></span>
                <span class="text-xs font-bold uppercase text-green-600 bg-green-50 px-2 py-0.5 rounded">Contact Form</span>
            </div>
            <button type="button" class="delete-block-btn text-gray-400 hover:text-red-500 p-1 rounded hover:bg-red-50"><i class="fa-solid fa-trash"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                     <label class="block text-xs font-medium text-gray-500 mb-1">Form Type</label>
                     <select class="block-input w-full p-2 border border-gray-300 rounded text-sm bg-gray-50" data-key="form_type">
                         <option value="contact">Generic Contact Form</option>
                     </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Recipient Email (Optional)</label>
                    <input type="email" class="block-input w-full p-2 border border-gray-300 rounded text-sm" data-key="recipient_email" placeholder="admin@example.com (defaults to system admin)">
                </div>
            </div>
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                     <label class="block text-xs font-medium text-gray-500 mb-1">Button Label</label>
                     <input type="text" class="block-input w-full p-2 border border-gray-300 rounded text-sm" data-key="btn_label" placeholder="Send Message" value="Send Message">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Success Message</label>
                    <input type="text" class="block-input w-full p-2 border border-gray-300 rounded text-sm" data-key="success_msg" placeholder="Thanks for contacting us!" value="Thank you! We received your message.">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    // State management
    let blocks = <?= json_encode($currentContent ?: []) ?>;

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('editorCanvas');
        
        // Initialize existing blocks
        if (blocks && blocks.length > 0) {
            renderFromState();
        }

        // Init dragging
        new Sortable(canvas, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {
                // Update state order could be implemented here or just gathered on save
            }
        });
    });

    function generateSlug(val) {
        if (!val) return;
        // Only auto-generate if slug is empty or user hasn't manually edited it significantly yet (simple check)
        const slugInput = document.getElementById('inputSlug');
        if (slugInput.value === '' || slugInput.value === val.toLowerCase().replace(/[^a-z0-9]+/g, '-')) {
             slugInput.value = val.toLowerCase()
                .replace(/[^\w ]+/g, '')
                .replace(/ +/g, '-');
        }
    }

    const ckEditorFontCssUrl = 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Lato:wght@300;400;700;900&family=Open+Sans:wght@300;400;600;700;800&family=Source+Sans+3:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&family=EB+Garamond:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Cormorant:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700;800&family=Raleway:wght@300;400;500;600;700;800&display=swap';
    const ckEditorExtraFontNames =
        'Playfair Display/Playfair Display, serif;' +
        'Cormorant Garamond/Cormorant Garamond, serif;' +
        'Lato/Lato, sans-serif;' +
        'Open Sans/Open Sans, sans-serif;' +
        'Source Sans/Source Sans 3, Source Sans Pro, sans-serif;' +
        'Libre Baskerville/Libre Baskerville, serif;' +
        'EB Garamond/EB Garamond, serif;' +
        'Montserrat/Montserrat, sans-serif;' +
        'Poppins/Poppins, sans-serif;' +
        'Cormorant/Cormorant, serif;' +
        'Nunito/Nunito, sans-serif;' +
        'Raleway/Raleway, sans-serif';

    function initCKEditor(el) {
        const textareas = el.querySelectorAll('.ckeditor-input');
        textareas.forEach(textarea => {
            // Generate unique ID if not exists
            if (!textarea.id) {
                textarea.id = 'editor_' + Math.random().toString(36).substr(2, 9);
            }
            // Init CKEditor if not already
            if (!CKEDITOR.instances[textarea.id]) {
                CKEDITOR.replace(textarea.id, {
                    height: 200,
                    toolbar: [
                        ['Bold', 'Italic', 'Underline', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink', '-', 'Font', 'FontSize', '-', 'Undo', 'Redo']
                    ],
                    versionCheck: false,
                    font_names: (CKEDITOR.config.font_names ? CKEDITOR.config.font_names + ';' : '') + ckEditorExtraFontNames,
                    on: {
                        instanceReady: function() {
                            this.document.appendStyleSheet(ckEditorFontCssUrl);
                        }
                    }
                });
            }
        });
    }

    function attachDeleteHandler(el) {
        const btn = el.querySelector('.delete-block-btn');
        if (btn) {
            btn.onclick = function() {
                if(confirm('Remove this block?')) {
                    // Destroy CKEditors in this block before delete
                    const textareas = el.querySelectorAll('.ckeditor-input');
                    textareas.forEach(textarea => {
                        if (textarea.id && CKEDITOR.instances[textarea.id]) {
                            CKEDITOR.instances[textarea.id].destroy();
                        }
                    });

                    el.remove();
                    if(document.getElementById('editorCanvas').children.length === 0) {
                        document.getElementById('emptyState').classList.remove('hidden');
                    }
                }
            };
        }
    }

    function addBlock(type) {
        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('blockMenu').classList.add('hidden');
        
        const canvas = document.getElementById('editorCanvas');
        const template = document.getElementById(`tpl-${type}`);
        const clone = template.content.cloneNode(true);
        const el = clone.querySelector('.block-item');
        
        attachDeleteHandler(el);
        canvas.appendChild(el);
        
        // Init CKEditor
        initCKEditor(el);
        
        // If type is FAQ, add one initial item
        if(type === 'faq') {
            addFaqItem(el.querySelector('.faq-list').nextElementSibling);
        }
    }

    function addFaqItem(btn) {
        const list = btn.previousElementSibling;
        const itemHtml = `
            <div class="flex gap-2 items-start border-b border-gray-100 pb-2 mb-2">
                <div class="flex-1 space-y-1">
                    <input type="text" class="block-input w-full p-2 border border-gray-200 rounded text-xs font-semibold" data-sub="question" placeholder="Question">
                    <textarea class="block-input w-full p-2 border border-gray-200 rounded text-xs" data-sub="answer" rows="2" placeholder="Answer"></textarea>
                </div>
                <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-red-500"><i class="fa-solid fa-times"></i></button>
            </div>
        `;
        list.insertAdjacentHTML('beforeend', itemHtml);
    }

    // Serialize content to JSON for saving
    function getEditorContent() {
        const canvas = document.getElementById('editorCanvas');
        const items = canvas.querySelectorAll('.block-item');
        const data = [];

        items.forEach(item => {
            const type = item.getAttribute('data-type');
            const blockData = {};

            if (type === 'faq') {
                blockData.faqs = [];
                const faqItems = item.querySelectorAll('.faq-list > div');
                faqItems.forEach(faq => {
                    blockData.faqs.push({
                        question: faq.querySelector('[data-sub="question"]').value,
                        answer: faq.querySelector('[data-sub="answer"]').value
                    });
                });
            } else {
                const inputs = item.querySelectorAll('.block-input');
                inputs.forEach(input => {
                    const key = input.getAttribute('data-key');
                    if(key) {
                       // If is ckeditor, get data from instance
                       if (input.classList.contains('ckeditor-input') && input.id && CKEDITOR.instances[input.id]) {
                           blockData[key] = CKEDITOR.instances[input.id].getData();
                       } else {
                           blockData[key] = input.value;
                       }
                    }
                });
            }

            data.push({ type: type, data: blockData });
        });

        return data;
    }

    function renderFromState() {
        // Clear canvas
        const canvas = document.getElementById('editorCanvas');
        canvas.innerHTML = '';
        
        blocks.forEach(block => {
            const template = document.getElementById(`tpl-${block.type}`);
            if (!template) return;
            const clone = template.content.cloneNode(true);
            const el = clone.querySelector('.block-item');
            
            // Populate data
            if (block.type === 'faq') {
                const list = el.querySelector('.faq-list');
                if (block.data.faqs) {
                    block.data.faqs.forEach(faq => {
                        const itemHtml = `
                            <div class="flex gap-2 items-start border-b border-gray-100 pb-2 mb-2">
                                <div class="flex-1 space-y-1">
                                    <input type="text" class="block-input w-full p-2 border border-gray-200 rounded text-xs font-semibold" data-sub="question" placeholder="Question" value="${faq.question.replace(/"/g, '&quot;')}">
                                    <textarea class="block-input w-full p-2 border border-gray-200 rounded text-xs" data-sub="answer" rows="2" placeholder="Answer">${faq.answer}</textarea>
                                </div>
                                <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-red-500"><i class="fa-solid fa-times"></i></button>
                            </div>
                        `;
                        list.insertAdjacentHTML('beforeend', itemHtml);
                    });
                }
            } else {
                // Populate standard inputs
                for (const key in block.data) {
                    const input = el.querySelector(`[data-key="${key}"]`);
                    if (input) {
                        input.value = block.data[key];
                    }
                }
            }
            
            attachDeleteHandler(el);
            canvas.appendChild(el);
            
            // Init CKEditor
            initCKEditor(el);
        });
        
         document.getElementById('emptyState').classList.add('hidden');
    }

    async function savePage() {
        const saveStatus = document.getElementById('saveStatus');
        saveStatus.innerText = 'Saving...';
        saveStatus.classList.remove('hidden', 'text-green-500', 'text-red-500');
        
        const form = document.getElementById('pageSettingsForm');
        const formData = new FormData(form);
        
        const content = getEditorContent();
        formData.append('content', JSON.stringify(content));

        try {
            const response = await fetch('save_page.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                saveStatus.innerText = 'Saved!';
                saveStatus.classList.add('text-green-500');
                
                // Redirect if new page
                if (!formData.get('id') && result.id) {
                     window.location.href = `editor.php?id=${result.id}`;
                }
                setTimeout(() => saveStatus.classList.add('hidden'), 2000);
            } else {
                throw new Error(result.message || 'Error saving');
            }
        } catch (error) {
            saveStatus.innerText = 'Error!';
            saveStatus.classList.add('text-red-500');
            alert('Failed to save: ' + error.message);
        }
    }
</script>

</body>
</html>
