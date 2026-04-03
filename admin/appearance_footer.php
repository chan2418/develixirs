<?php
// admin/appearance_footer.php
session_start();
include __DIR__ . '/../includes/db.php';

$page_title = 'Footer Settings';
$page_subtitle = 'Manage footer links and content';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // We will store everything in a single JSON called 'footer_settings'
    
    // Column 1: About
    $aboutData = [
        'description' => $_POST['about_desc'] ?? '',
        'social_fb' => $_POST['social_fb'] ?? '',
        'social_tw' => $_POST['social_tw'] ?? '',
        'social_insta' => $_POST['social_insta'] ?? '',
        'social_pin' => $_POST['social_pin'] ?? '',
    ];

    // Column 1: About
    $aboutData = [
        'description' => $_POST['about_desc'] ?? '',
        'social_fb' => $_POST['social_fb'] ?? '',
        'social_tw' => $_POST['social_tw'] ?? '',
        'social_insta' => $_POST['social_insta'] ?? '',
        'social_pin' => $_POST['social_pin'] ?? '',
    ];

    // Dynamic Link Columns
    $linkColumns = [];
    // Expecting $_POST['link_cols'] to be an array of columns
    // Structure: link_cols[index][title], link_cols[index][labels][], link_cols[index][urls][]
    if (!empty($_POST['link_cols']) && is_array($_POST['link_cols'])) {
        foreach ($_POST['link_cols'] as $idx => $colRaw) {
            $cTitle = $colRaw['title'] ?? 'Links';
            $cLinks = [];
            if (!empty($colRaw['labels']) && is_array($colRaw['labels'])) {
                foreach ($colRaw['labels'] as $Lk => $lbl) {
                    if (!empty($lbl)) {
                        $cLinks[] = [
                            'label' => $lbl,
                            'url' => $colRaw['urls'][$Lk] ?? '#'
                        ];
                    }
                }
            }
            $linkColumns[] = ['title' => $cTitle, 'links' => $cLinks];
        }
    }

    // Contact Info
    $contactInfo = [
        'address_line1' => $_POST['contact_line1'] ?? '',
        'address_line2' => $_POST['contact_line2'] ?? '',
        'address_line3' => $_POST['contact_line3'] ?? '',
        'address_city' => $_POST['contact_city'] ?? '',
        'address_country' => $_POST['contact_country'] ?? '',
        'email' => $_POST['contact_email'] ?? '',
        'phone' => $_POST['contact_phone'] ?? ''
    ];

    // Column 5: Gallery (Last column always)
    $galleryTitle = $_POST['gallery_title'] ?? 'Gallery';
    $galleryImages = $_POST['gallery_images'] ?? []; // Array of 6 URLs

    $footerData = [
        'about' => $aboutData,
        'link_columns' => $linkColumns, // New dynamic key
        'contact' => $contactInfo,
        'gallery' => ['title' => $galleryTitle, 'images' => $galleryImages]
    ];

    $json = json_encode($footerData);

    try {
        $stmt = $pdo->prepare("REPLACE INTO site_settings (setting_key, setting_value) VALUES ('footer_settings', :val)");
        $stmt->execute([':val' => $json]);
        $success = "Footer settings updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Fetch Current Settings
$footerSettings = [];
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'footer_settings'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $footerSettings = json_decode($row['setting_value'], true) ?: [];
    }
} catch (PDOException $e) { }

// Helper to get data safely
$about = $footerSettings['about'] ?? [
    'description' => 'DevElixir Natural Cosmetics - Pure, natural, and effective skincare solutions for you and your family.',
    'social_fb' => '#',
    'social_tw' => '#',
    'social_insta' => '#',
    'social_pin' => '#'
];

// MIGRATION / DEFAULTS for Link Columns
$linkColumns = $footerSettings['link_columns'] ?? [];
// If new structure is empty, check for old keys to migrate on first load
if (empty($linkColumns)) {
    // Check old keys
    if (!empty($footerSettings['col2'])) $linkColumns[] = $footerSettings['col2'];
    if (!empty($footerSettings['col3'])) $linkColumns[] = $footerSettings['col3'];
    if (!empty($footerSettings['col4'])) $linkColumns[] = $footerSettings['col4'];
    else if (!empty($footerSettings['col_extra'])) $linkColumns[] = $footerSettings['col_extra'];
    if (!empty($footerSettings['col5'])) $linkColumns[] = $footerSettings['col5'];
    if (!empty($footerSettings['col6'])) $linkColumns[] = $footerSettings['col6'];

    // If still empty (totally fresh), set defaults
    if (empty($linkColumns)) {
        $linkColumns = [
            ['title' => 'Informations', 'links' => [
                ['label' => 'About Devilixirs', 'url' => 'about.php'],
                ['label' => 'New Arrivals', 'url' => 'product.php?sort=new'],
                ['label' => 'Best Sellers', 'url' => 'product.php?sort=best'],
            ]],
            ['title' => 'Links', 'links' => [
                ['label' => 'Shop All', 'url' => 'product.php'],
                ['label' => 'Track Order', 'url' => 'track-order.php'],
                ['label' => 'Contact', 'url' => 'contact.php'],
                ['label' => 'Ayurvedh Blog', 'url' => 'ayurvedha_blog.php'],
            ]]
        ];
    }
}

// Ensure Ayurvedh blog link appears in editor for existing settings too.
$hasAyurvedhaLink = false;
foreach ($linkColumns as $col) {
    foreach (($col['links'] ?? []) as $lnk) {
        $u = strtolower(trim((string)($lnk['url'] ?? '')));
        if ($u === 'ayurvedha_blog.php' || strpos($u, 'ayurvedha_blog.php') !== false) {
            $hasAyurvedhaLink = true;
            break 2;
        }
    }
}
if (!$hasAyurvedhaLink) {
    if (empty($linkColumns)) {
        $linkColumns[] = [
            'title' => 'Links',
            'links' => [
                ['label' => 'Ayurvedh Blog', 'url' => 'ayurvedha_blog.php']
            ]
        ];
    } else {
        if (!isset($linkColumns[0]['links']) || !is_array($linkColumns[0]['links'])) {
            $linkColumns[0]['links'] = [];
        }
        $linkColumns[0]['links'][] = ['label' => 'Ayurvedh Blog', 'url' => 'ayurvedha_blog.php'];
    }
}

$contact = $footerSettings['contact'] ?? [
    'address_line1' => 'DevElixir Natural Cosmetics ™',
    'address_line2' => 'No:6, 3rd Cross Street,',
    'address_line3' => 'Kamatchiamman Garden, Sethukkarai,',
    'address_city' => 'Gudiyatham-632602, Vellore, Tamilnadu',
    'address_country' => 'INDIA',
    'email' => 'sales@develixirs.com',
    'phone' => '+91 95006 50454'
];

$gallery = $footerSettings['gallery'] ?? [
    'title' => 'Gallery',
    'images' => [
        'assets/uploads/products/1167485b8dbb.jpg',
        'assets/uploads/products/c28997524100.jpg',
        'assets/uploads/products/84b062f7d8d2.jpg',
        'assets/uploads/products/fb15b8e998ea.jpg',
        'assets/uploads/products/8e2202201f76.jpg',
        'assets/uploads/products/459a32ced2ab.jpg'
    ]
];
?>

<?php include 'layout/header.php'; ?>

<div class="max-w-[1000px] mx-auto mt-8 px-4 pb-20">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Footer Settings</h1>
            <p class="text-slate-500 text-sm">Configure all footer columns and content.</p>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="bg-green-50 text-green-700 p-4 rounded-lg mb-6 border border-green-200 flex items-center gap-2">
            <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-8">
        
        <!-- Column 1: About -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Column 1 (About)</h3>
            <div class="mb-4">
                 <label class="block text-sm font-medium text-slate-700 mb-1">Description Text</label>
                 <textarea name="about_desc" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"><?= htmlspecialchars($about['description']) ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1"><i class="fa-brands fa-facebook"></i> Facebook URL</label>
                     <input type="text" name="social_fb" value="<?= htmlspecialchars($about['social_fb']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                 </div>
                 <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1"><i class="fa-brands fa-twitter"></i> Twitter/X URL</label>
                     <input type="text" name="social_tw" value="<?= htmlspecialchars($about['social_tw']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                 </div>
                 <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1"><i class="fa-brands fa-instagram"></i> Instagram URL</label>
                     <input type="text" name="social_insta" value="<?= htmlspecialchars($about['social_insta']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                 </div>
                 <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1"><i class="fa-brands fa-pinterest"></i> Pinterest URL</label>
                     <input type="text" name="social_pin" value="<?= htmlspecialchars($about['social_pin']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                 </div>
            </div>
        </div>

    <div id="dynamic_columns_area" class="space-y-8">
        <?php foreach ($linkColumns as $idx => $col): 
            $colId = "col_" . $idx;
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 column-block" data-index="<?= $idx ?>">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-slate-800">Link Column <span class="col-num"><?= $idx + 1 ?></span></h3>
                <button type="button" onclick="removeColumn(this)" class="text-red-500 hover:text-red-700 text-sm font-medium"><i class="fa-solid fa-trash"></i> Remove Column</button>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Column Title</label>
                <input type="text" name="link_cols[<?= $idx ?>][title]" value="<?= htmlspecialchars($col['title']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>

            <div class="space-y-3 links-container" id="links_container_<?= $idx ?>">
                <label class="block text-sm font-medium text-slate-700">Links</label>
                <?php foreach (($col['links'] ?? []) as $link): ?>
                <div class="flex gap-2 link-row">
                    <input type="text" name="link_cols[<?= $idx ?>][labels][]" value="<?= htmlspecialchars($link['label']) ?>" placeholder="Label" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                    <input type="text" name="link_cols[<?= $idx ?>][urls][]" value="<?= htmlspecialchars($link['url']) ?>" placeholder="URL" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                    <button type="button" class="text-red-500 hover:text-red-700 px-2" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addLinkRowToCol(<?= $idx ?>)" class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 font-medium">+ Add Link</button>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="py-4 text-center border-2 border-dashed border-gray-300 rounded-xl hover:border-indigo-500 transition cursor-pointer bg-slate-50" onclick="addNewColumn()">
        <span class="text-indigo-600 font-medium"><i class="fa-solid fa-plus-circle mr-2"></i> Add New Link Column</span>
    </div>

        <!-- Column 4: Contact Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Column 4 (Contact)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Company Name</label>
                    <input type="text" name="contact_line1" value="<?= htmlspecialchars($contact['address_line1']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">Street Address</label>
                     <input type="text" name="contact_line2" value="<?= htmlspecialchars($contact['address_line2']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                 <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">Area/Locality</label>
                     <input type="text" name="contact_line3" value="<?= htmlspecialchars($contact['address_line3']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                 <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">City & Zip</label>
                     <input type="text" name="contact_city" value="<?= htmlspecialchars($contact['address_city']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                 <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">Country</label>
                     <input type="text" name="contact_country" value="<?= htmlspecialchars($contact['address_country']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                 <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                     <input type="text" name="contact_phone" value="<?= htmlspecialchars($contact['phone']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                 <div class="col-span-1 md:col-span-2">
                     <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                     <input type="text" name="contact_email" value="<?= htmlspecialchars($contact['email']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
        </div>

        <!-- Column 5: Gallery -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Column 5 (Gallery / Images)</h3>
             <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Column Title</label>
                <input type="text" name="gallery_title" value="<?= htmlspecialchars($gallery['title']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php for($i=0; $i<6; $i++): 
                    $imgVal = $gallery['images'][$i] ?? '';
                ?>
                <div>
                     <label class="block text-xs font-medium text-slate-500 mb-1">Image <?= $i+1 ?></label>
                     <div class="flex gap-1">
                        <input type="text" name="gallery_images[]" id="gal_img_<?= $i ?>" value="<?= htmlspecialchars($imgVal) ?>" class="w-full px-2 py-1 text-sm border border-gray-300 rounded">
                        <button type="button" onclick="openMediaModal('gal_img_<?= $i ?>')" class="bg-gray-100 border border-gray-300 px-2 rounded hover:bg-gray-200">📁</button>
                     </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-8 rounded-lg transition shadow-md hover:shadow-lg">
                Save Footer Settings
            </button>
        </div>
    </form>
</div>

<script>
function removeRow(btn) {
    if(confirm('Delete this link?')) {
        btn.closest('.link-row').remove();
    }
}

function removeColumn(btn) {
    if(confirm('Delete this entire column? Setttings will be saved when you click Save.')) {
        btn.closest('.column-block').remove();
        // Optional: Renumber visual "Link Column X" headers
        updateColumnNumbers();
    }
}

function updateColumnNumbers() {
    const cols = document.querySelectorAll('.column-block .col-num');
    cols.forEach((span, i) => {
        span.textContent = i + 1;
    });
}

function addLinkRowToCol(idx) {
    const container = document.getElementById('links_container_' + idx);
    const div = document.createElement('div');
    div.className = 'flex gap-2 link-row';
    div.innerHTML = `
        <input type="text" name="link_cols[${idx}][labels][]" placeholder="Label" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
        <input type="text" name="link_cols[${idx}][urls][]" placeholder="URL" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
        <button type="button" class="text-red-500 hover:text-red-700 px-2" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
    `;
    container.appendChild(div);
}

function addNewColumn() {
    const container = document.getElementById('dynamic_columns_area');
    // Find next index
    let maxIdx = -1;
    document.querySelectorAll('.column-block').forEach(el => {
        const i = parseInt(el.getAttribute('data-index'));
        if(i > maxIdx) maxIdx = i;
    });
    const newIdx = maxIdx + 1;
    const currentCount = document.querySelectorAll('.column-block').length + 1;

    const div = document.createElement('div');
    div.className = 'bg-white rounded-xl shadow-sm border border-gray-200 p-6 column-block';
    div.setAttribute('data-index', newIdx);
    div.innerHTML = `
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-slate-800">Link Column <span class="col-num">${currentCount}</span></h3>
            <button type="button" onclick="removeColumn(this)" class="text-red-500 hover:text-red-700 text-sm font-medium"><i class="fa-solid fa-trash"></i> Remove Column</button>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1">Column Title</label>
            <input type="text" name="link_cols[${newIdx}][title]" value="New Column" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>

        <div class="space-y-3 links-container" id="links_container_${newIdx}">
            <label class="block text-sm font-medium text-slate-700">Links</label>
            <!-- Start with one empty link -->
            <div class="flex gap-2 link-row">
                <input type="text" name="link_cols[${newIdx}][labels][]" placeholder="Label" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                <input type="text" name="link_cols[${newIdx}][urls][]" placeholder="URL" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                <button type="button" class="text-red-500 hover:text-red-700 px-2" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
        <button type="button" onclick="addLinkRowToCol(${newIdx})" class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 font-medium">+ Add Link</button>
    `;
    container.appendChild(div);
    // updateColumnNumbers(); // Not strictly needed as we used currentCount, but consistent
}

// Media Picker
  window.mediaTargetInput = null;

  function openMediaModal(targetInputId) {
    window.mediaTargetInput = targetInputId;
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

  window.closeMediaModal = function() {
    const modal = document.getElementById('mediaLibraryModal');
    if (modal) modal.remove();
  };

  // Callback
  window.insertImagesToEditor = function(imagePaths) {
    if (!imagePaths || imagePaths.length === 0) return;
    if (window.mediaTargetInput) {
        document.getElementById(window.mediaTargetInput).value = imagePaths[0];
        closeMediaModal();
    }
  };
</script>

<?php include 'layout/footer.php'; ?>
