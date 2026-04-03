<?php
// admin/add_blog_tag.php - Add Blog Tag with SEO
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/blog_scope_helper.php';

$scope = admin_blog_scope_from_request();
$tagsLabel = admin_blog_scope_tags_label($scope);
$tagLabel = admin_blog_scope_tag_label($scope);
$pageTitle = 'Add ' . $tagLabel;
$errors = [];
$scopeColumnAvailable = admin_blog_ensure_tag_scope_column($pdo);
$listUrl = admin_blog_scope_url('/admin/blog_tags.php', $scope);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scope = admin_blog_scope_normalize($_POST['blog_scope'] ?? $scope);
    $tagsLabel = admin_blog_scope_tags_label($scope);
    $tagLabel = admin_blog_scope_tag_label($scope);
    $pageTitle = 'Add ' . $tagLabel;
    $listUrl = admin_blog_scope_url('/admin/blog_tags.php', $scope);

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $seoTitle = trim($_POST['seo_title'] ?? '');
    $seoDescription = trim($_POST['seo_description'] ?? '');
    
    // Handle SEO image upload
    $seoImage = '';
    if (isset($_FILES['seo_image']) && $_FILES['seo_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/blog_tags/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = time() . '_' . basename($_FILES['seo_image']['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['seo_image']['tmp_name'], $targetPath)) {
            $seoImage = '/assets/uploads/blog_tags/' . $filename;
        } else {
            $errors[] = 'Failed to upload SEO image';
        }
    }

    // Validation
    if (empty($name)) {
        $errors[] = 'Tag name is required';
    }

    if (empty($slug)) {
        // Auto-generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    if (empty($errors)) {
        try {
            if ($scopeColumnAvailable) {
                $stmt = $pdo->prepare("
                    INSERT INTO blog_tags (name, slug, description, seo_title, seo_description, seo_image, blog_scope)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $slug, $description, $seoTitle, $seoDescription, $seoImage, admin_blog_scope_taxonomy_value($scope)]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO blog_tags (name, slug, description, seo_title, seo_description, seo_image)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $slug, $description, $seoTitle, $seoDescription, $seoImage]);
            }
            header("Location: " . $listUrl);
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/layout/header.php';
?>

<div class="p-8 max-w-4xl mx-auto">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Add <?php echo htmlspecialchars($tagLabel); ?></h1>
    <a href="<?php echo htmlspecialchars($listUrl); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">← Back to <?php echo htmlspecialchars($tagsLabel); ?></a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
      <ul class="list-disc list-inside">
        <?php foreach ($errors as $error): ?>
          <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-6">
    <input type="hidden" name="blog_scope" value="<?php echo htmlspecialchars($scope); ?>">
    <!-- Basic Info Section -->
    <div class="border-b pb-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h2>
      
      <!-- Tag Name -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Tag Name *</label>
        <input type="text" name="name" required 
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="e.g., Ayurvedic Skincare"
               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
      </div>

      <!-- Slug -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL)</label>
        <input type="text" name="slug" 
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="Auto-generated if empty (e.g., ayurvedic-skincare)"
               value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
        <p class="mt-1 text-xs text-gray-500">Used in URL: blog_tag.php?tag=<span class="font-mono">slug</span></p>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Full Description</label>
        <textarea name="description" rows="4"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Full description displayed on the tag archive page..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
      </div>
    </div>

    <!-- SEO Section -->
    <div class="border-b pb-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">SEO Settings</h2>
      <p class="text-sm text-gray-500 mb-4">These fields help optimize the tag archive page for search engines and social media sharing.</p>
      
      <!-- SEO Title -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">SEO Title (Meta Title)</label>
        <input type="text" name="seo_title" maxlength="60"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="Best Ayurvedic Skincare Tips | Your Brand"
               value="<?php echo htmlspecialchars($_POST['seo_title'] ?? ''); ?>">
        <p class="mt-1 text-xs text-gray-500">Max 60 characters. Appears in search results and browser tab.</p>
      </div>

      <!-- SEO Description -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">SEO Meta Description</label>
        <textarea name="seo_description" rows="3" maxlength="160"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Discover our collection of ayurvedic skincare articles..."><?php echo htmlspecialchars($_POST['seo_description'] ?? ''); ?></textarea>
        <p class="mt-1 text-xs text-gray-500">Max 160 characters. Appears in search engine results.</p>
      </div>

      <!-- SEO Image -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">SEO Image (Open Graph)</label>
        <input type="file" name="seo_image" accept="image/*"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
        <p class="mt-1 text-xs text-gray-500">Recommended size: 1200x630px. Used when sharing on social media.</p>
      </div>
    </div>

    <div class="pt-4 flex justify-end gap-3">
      <a href="<?php echo htmlspecialchars($listUrl); ?>" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition">
        Cancel
      </a>
      <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
        Add Tag
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
