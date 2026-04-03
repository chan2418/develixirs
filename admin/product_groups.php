<?php
// admin/product_groups.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/image_compressor.php'; // Ensure this exists or use simpler move logic if preferred (Using compressor as it is present in other files)

$page_title = 'Product Groups';
$activeMenu = 'products'; // Keep 'products' active or create 'product_groups' active state if sidebar updated

// Handle Actions
$editGroup = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $imagePath = null;
        
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . '/../assets/uploads/groups/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $tmp = $_FILES['image']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $newInfo = 'group_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $uploadDir . $newInfo;
            
            $moved = false;
            if (function_exists('compressImage')) {
                 $res = compressImage($tmp, $dest); 
                 if (!empty($res['success'])) $moved = true;
            }
            
            if (!$moved) {
                 if (move_uploaded_file($tmp, $dest)) $moved = true;
            }
            
            if ($moved) $imagePath = $newInfo;
        } elseif (!empty($_POST['image_from_media'])) {
            // Handle Media Library Selection
            $imagePath = trim($_POST['image_from_media']);
        }

        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO product_groups (name, image) VALUES (?, ?)");
            $stmt->execute([$name, $imagePath]);
            $_SESSION['success'] = "Group added successfully.";
            header("Location: product_groups.php"); exit;
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        
        if ($id && !empty($name)) {
            $imagePath = null;
            
            // New Image?
            if (!empty($_FILES['image']['name'])) {
                $uploadDir = __DIR__ . '/../assets/uploads/groups/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $tmp = $_FILES['image']['tmp_name'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $newInfo = 'group_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $uploadDir . $newInfo;
                
                $moved = false;
                if (function_exists('compressImage')) {
                     $res = compressImage($tmp, $dest);
                     if (!empty($res['success'])) $moved = true;
                } 
                
                if (!$moved) {
                     if (move_uploaded_file($tmp, $dest)) $moved = true;
                }
                
                if ($moved) {
                    $imagePath = $newInfo;
                    
                    // Remove Old Image
                    $oldStmt = $pdo->prepare("SELECT image FROM product_groups WHERE id = ?");
                    $oldStmt->execute([$id]);
                    $oldRow = $oldStmt->fetch();
                    if ($oldRow && $oldRow['image']) {
                        @unlink(__DIR__ . '/../assets/uploads/groups/' . $oldRow['image']);
                    }
                }
            } elseif (!empty($_POST['image_from_media'])) {
                $imagePath = trim($_POST['image_from_media']);
                // Note: We don't delete the old file if switching to media library because the old file might be used elsewhere or we just want to keep it. 
                // But for cleanliness we could delete if it was a local upload. For now, let's just update the DB.
            }

            if ($imagePath) {
                // Update with image
                $stmt = $pdo->prepare("UPDATE product_groups SET name = ?, image = ? WHERE id = ?");
                $stmt->execute([$name, $imagePath, $id]);
            } else {
                // Update Name Only
                $stmt = $pdo->prepare("UPDATE product_groups SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
            }

            $_SESSION['success'] = "Group updated successfully.";
            header("Location: product_groups.php"); exit;
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            // Delete Image File
            $oldStmt = $pdo->prepare("SELECT image FROM product_groups WHERE id = ?");
            $oldStmt->execute([$id]);
            $oldRow = $oldStmt->fetch();
            if ($oldRow && $oldRow['image']) {
                 @unlink(__DIR__ . '/../assets/uploads/groups/' . $oldRow['image']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM product_groups WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Group deleted successfully.";
            header("Location: product_groups.php"); exit;
        }
    }
}

// Fetch Group for Edit
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM product_groups WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editGroup = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Groups
$groups = $pdo->query("SELECT * FROM product_groups ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[1000px] mx-auto py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Product Groups</h1>
            <p class="text-sm text-slate-500 mt-1">Create collections like "New Arrivals", "Best Sellers", etc.</p>
        </div>
    </div>

    <!-- Add/Edit Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-lg font-semibold mb-4"><?= $editGroup ? 'Edit Group' : 'Add New Group' ?></h2>
        <form method="POST" enctype="multipart/form-data" class="flex gap-4 items-end">
            <input type="hidden" name="action" value="<?= $editGroup ? 'edit' : 'add' ?>">
            <?php if ($editGroup): ?>
                <input type="hidden" name="id" value="<?= $editGroup['id'] ?>">
            <?php endif; ?>
            
            <div class="flex-1">
                <label class="block text-sm font-medium text-slate-700 mb-1">Group Name</label>
                <input type="text" name="name" required value="<?= $editGroup ? htmlspecialchars($editGroup['name']) : '' ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                       placeholder="e.g. Summer Collection">
            </div>
            
            <div class="w-1/3">
                 <label class="block text-sm font-medium text-slate-700 mb-2">Group Image</label>
                 
                 <div class="space-y-3">
                    <!-- Option 1: File Upload -->
                    <div>
                       <input type="file" name="image" accept="image/*" id="group_image_input"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                       <p class="text-xs text-gray-500 mt-1">Upload new image from device</p>
                    </div>
                    
                    <div class="text-center text-sm text-gray-500 font-medium">- OR -</div>

                    <!-- Option 2: Media Library -->
                    <div class="flex items-center gap-4">
                       <button type="button" onclick="openMediaSelector()" 
                               class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
                         <i class="fa-regular fa-folder-open"></i> Select from Library
                       </button>
                       <input type="hidden" name="image_from_media" id="mediaInput" value="">
                       
                       <!-- Preview -->
                       <?php 
                          $hasImage = $editGroup && !empty($editGroup['image']);
                          $imgSrc = $hasImage ? ((strpos($editGroup['image'], 'http') === 0 || strpos($editGroup['image'], '/') === 0) ? $editGroup['image'] : '/assets/uploads/groups/' . htmlspecialchars($editGroup['image'])) : '';
                       ?>
                       <div id="imagePreviewContainer" class="<?= $hasImage ? '' : 'hidden' ?> h-20 w-32 border rounded overflow-hidden relative group bg-gray-50">
                          <img src="<?= $imgSrc ?>" id="imgPreview" alt="Preview" class="w-full h-full object-cover">
                          <button type="button" onclick="removeImage()" 
                                  class="absolute top-0 right-0 bg-red-500 text-white p-1 rounded-bl text-xs opacity-0 group-hover:opacity-100 transition">
                            &times;
                          </button>
                       </div>
                    </div>
                 </div>
            </div>
            
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
                <?= $editGroup ? 'Update Group' : 'Add Group' ?>
            </button>
            <?php if ($editGroup): ?>
                <a href="product_groups.php" class="px-4 py-2 text-slate-600 font-medium hover:text-slate-800">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Groups List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-700 w-16">Image</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-700">Name</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-700 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($groups)): ?>
                    <tr><td colspan="2" class="px-6 py-8 text-center text-slate-500">No groups found. Create one above!</td></tr>
                <?php else: ?>
                    <?php foreach ($groups as $g): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <?php if(!empty($g['image'])): ?>
                                <?php 
                                    $imgSrc = (strpos($g['image'], 'http') === 0 || strpos($g['image'], '/') === 0) 
                                              ? $g['image'] 
                                              : '/assets/uploads/groups/' . $g['image'];
                                ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" class="h-10 w-10 object-cover rounded-full border border-gray-200">
                            <?php else: ?>
                                <div class="h-10 w-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-xs">No Img</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-800"><?= htmlspecialchars($g['name']) ?></td>
                        <td class="px-6 py-4 text-right flex justify-end gap-3">
                            <a href="product_groups.php?edit=<?= $g['id'] ?>" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this group?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<!-- Media Selector Script -->
<script>
// Open Library
function openMediaSelector() {
    // Check if modal already exists
    if(document.getElementById('mediaLibraryModal')) {
        document.getElementById('mediaLibraryModal').remove();
    }

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
}

// Remove Image Function
function removeImage() {
    document.getElementById('mediaInput').value = '';
    document.getElementById('group_image_input').value = ''; // clear file
    document.getElementById('imagePreviewContainer').classList.add('hidden');
    document.getElementById('imgPreview').src = '';
}

// File Input Preview
document.getElementById('group_image_input').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('imgPreview').src = evt.target.result;
            document.getElementById('imagePreviewContainer').classList.remove('hidden');
            // Clear media input priority
            document.getElementById('mediaInput').value = '';
        };
        reader.readAsDataURL(this.files[0]);
    }
});

// CRITICAL: Callback function called by admin/media.php (iframe)
window.insertImagesToEditor = function(imagePaths) {
    console.log('ProductGroups: insertImagesToEditor called', imagePaths);
    
    if (!imagePaths || imagePaths.length === 0) {
        alert('No image selected.');
        return;
    }
    
    const url = imagePaths[0]; // Take the first image
    
    // Update Input & Preview
    document.getElementById('mediaInput').value = url;
    document.getElementById('imgPreview').src = url;
    document.getElementById('imagePreviewContainer').classList.remove('hidden');
    
    // Clear file input
    document.getElementById('group_image_input').value = '';
    
    closeMediaModal();
};
</script>
