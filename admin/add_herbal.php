<?php
// admin/add_herbal.php

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$page_title = "Add Herbal Category";
$is_edit = false;
$id = null;
$item = [
    'title' => '',
    'slug' => '',
    'description' => '',
    'image' => ''
];
$errors = [];

// Handle Edit Mode
if (!empty($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM herbals WHERE id = ?");
    $stmt->execute([$id]);
    $fetched = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fetched) {
        $item = $fetched;
        $is_edit = true;
        $page_title = "Edit Herbal Category";
    }
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug  = trim($_POST['slug'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    
    // Simple Validation
    if (!$title) $errors[] = "Title is required.";
    
    // Auto-generate slug if empty
    if (!$slug && $title) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }
    
    // Image Upload
    $imagePath = $item['image']; // keep existing
    
    // 1. Check for file upload
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../assets/uploads/herbals/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $fileName;
        } else {
            $errors[] = "Failed to upload image.";
        }
    } 
    // 2. Check for Media Library selection
    elseif (!empty($_POST['media_image'])) {
        $imagePath = trim($_POST['media_image']);
    }

    if (empty($errors)) {
        try {
            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE herbals SET title=?, slug=?, description=?, image=? WHERE id=?");
                $stmt->execute([$title, $slug, $desc, $imagePath, $id]);
                $_SESSION['success_msg'] = "Herbal category updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO herbals (title, slug, description, image) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $desc, $imagePath]);
                $_SESSION['success_msg'] = "Herbal category added successfully.";
            }
            header("Location: herbals.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "DB Error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[800px] mx-auto py-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-extrabold text-slate-800"><?= $is_edit ? 'Edit Herbal Category' : 'Add New Herbal Category' ?></h2>
        <a href="herbals.php" class="px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50">Cancel</a>
    </div>

    <?php if($errors): ?>
        <div class="p-4 mb-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-gray-200 p-6 rounded-xl shadow-sm">
        <form method="POST" enctype="multipart/form-data">
            
            <!-- Title -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-100 outline-none" required>
            </div>

            <!-- Slug -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Slug (Optional)</label>
                <input type="text" name="slug" value="<?= htmlspecialchars($item['slug']) ?>" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-100 outline-none" placeholder="auto-generated-from-title">
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-100 outline-none"><?= htmlspecialchars($item['description']) ?></textarea>
            </div>

            <!-- Image -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Image</label>
                
                <!-- Hidden Input for Media Library Path -->
                <input type="hidden" name="media_image" id="media_image_input">

                <!-- Preview Container -->
                <?php 
                    $displayImg = '';
                    $hasImage = false;
                    if ($item['image']) {
                        $hasImage = true;
                        // Check if full path or filename
                        if (strpos($item['image'], '/') !== false) {
                            $displayImg = $item['image'];
                        } else {
                            $displayImg = '/assets/uploads/herbals/' . $item['image'];
                        }
                    }
                ?>
                <div id="image_preview_container" class="mb-3 <?= $hasImage ? '' : 'hidden' ?>">
                    <img src="<?= htmlspecialchars($displayImg) ?>" id="preview_img" class="w-24 h-24 object-cover rounded border shadow-sm">
                    <button type="button" onclick="removeImage()" class="text-xs text-red-600 hover:text-red-800 mt-1">Remove Image</button>
                </div>

                <div class="flex flex-col gap-2">
                    <!-- File Upload -->
                    <input type="file" name="image" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    
                    <div class="text-xs text-gray-400 font-medium text-center">- OR -</div>
                    
                    <!-- Media Library Button -->
                    <button type="button" onclick="openMediaModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm w-fit">
                        📁 Select from Media Library
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-lg">
                <?= $is_edit ? 'Update Category' : 'Create Category' ?>
            </button>
        </form>
    </div>
</div>

<script>
  // Media Modal Logic
  function openMediaModal() {
    const modal = document.createElement('div');
    modal.id = 'mediaLibraryModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
    modal.innerHTML = `
      <div class="bg-white rounded-lg shadow-xl w-11/12 h-5/6 max-w-5xl overflow-hidden relative">
        <button onclick="closeMediaModal()" class="absolute top-4 right-4 z-50 bg-white rounded-full w-8 h-8 flex items-center justify-center shadow hover:bg-gray-100 text-gray-600">
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

  // Called by media.php when an image is selected
  window.insertImagesToEditor = function(imagePaths) {
    if (!imagePaths || imagePaths.length === 0) return;
    
    // Take the first image
    const imgPath = imagePaths[0];
    
    // Update Hidden Input
    document.getElementById('media_image_input').value = imgPath;
    
    // Update Preview
    const previewContainer = document.getElementById('image_preview_container');
    const previewImg = document.getElementById('preview_img');
    
    previewImg.src = imgPath;
    previewContainer.classList.remove('hidden');
    
    closeMediaModal();
  };
  
  window.removeImage = function() {
      document.getElementById('media_image_input').value = '';
      document.getElementById('image_preview_container').classList.add('hidden');
      document.getElementById('preview_img').src = '';
  }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
