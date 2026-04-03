<?php
// admin/edit_blog_tag.php - Edit Blog Tag with SEO
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/blog_scope_helper.php';

$scope = admin_blog_scope_from_request();
$tagsLabel = admin_blog_scope_tags_label($scope);
$tagLabel = admin_blog_scope_tag_label($scope);
$pageTitle = 'Edit ' . $tagLabel;
$errors = [];
$tagId = (int)($_GET['id'] ?? 0);
$scopeColumnAvailable = admin_blog_ensure_tag_scope_column($pdo);
$listUrl = admin_blog_scope_url('/admin/blog_tags.php', $scope);

// Fetch existing tag
$tag = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM blog_tags WHERE id = ?");
    $stmt->execute([$tagId]);
    $tag = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tag) {
        header('Location: ' . $listUrl);
        exit;
    }

    if ($scopeColumnAvailable) {
        $existingScope = admin_blog_scope_normalize((string)($tag['blog_scope'] ?? ''));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (isset($_GET['scope']) && admin_blog_scope_normalize((string)$_GET['scope']) !== $existingScope) {
                header('Location: ' . admin_blog_scope_url('/admin/edit_blog_tag.php', $existingScope, ['id' => $tagId]));
                exit;
            }
            if (!isset($_GET['scope'])) {
                $scope = $existingScope;
                $tagsLabel = admin_blog_scope_tags_label($scope);
                $tagLabel = admin_blog_scope_tag_label($scope);
                $pageTitle = 'Edit ' . $tagLabel;
                $listUrl = admin_blog_scope_url('/admin/blog_tags.php', $scope);
            }
        }
    }
} catch (PDOException $e) {
    die('Error fetching tag: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scope = admin_blog_scope_normalize($_POST['blog_scope'] ?? $scope);
    $tagsLabel = admin_blog_scope_tags_label($scope);
    $tagLabel = admin_blog_scope_tag_label($scope);
    $pageTitle = 'Edit ' . $tagLabel;
    $listUrl = admin_blog_scope_url('/admin/blog_tags.php', $scope);

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $seoTitle = trim($_POST['seo_title'] ?? '');
    $seoDescription = trim($_POST['seo_description'] ?? '');
    
    // Handle SEO image upload
    $seoImage = $tag['seo_image'];
    if (isset($_FILES['seo_image']) && $_FILES['seo_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/blog_tags/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = time() . '_' . basename($_FILES['seo_image']['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['seo_image']['tmp_name'], $targetPath)) {
            // Delete old image if exists
            if ($seoImage && file_exists(__DIR__ . '/..' . $seoImage)) {
                unlink(__DIR__ . '/..' . $seoImage);
            }
            $seoImage = '/assets/uploads/blog_tags/' . $filename;
        }
    }

    // Validation
    if (empty($name)) {
        $errors[] = 'Tag name is required';
    }

    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    if (empty($errors)) {
        try {
            if ($scopeColumnAvailable) {
                $stmt = $pdo->prepare("
                    UPDATE blog_tags
                    SET name = ?, slug = ?, description = ?, seo_title = ?, seo_description = ?, seo_image = ?, blog_scope = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $slug, $description, $seoTitle, $seoDescription, $seoImage, admin_blog_scope_taxonomy_value($scope), $tagId]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE blog_tags
                    SET name = ?, slug = ?, description = ?, seo_title = ?, seo_description = ?, seo_image = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $slug, $description, $seoTitle, $seoDescription, $seoImage, $tagId]);
            }
            header("Location: " . $listUrl);
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
} else {
    // Pre-fill form
    $_POST = $tag;
}

include __DIR__ . '/layout/header.php';
?>

<div class="p-8 max-w-4xl mx-auto">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Edit <?php echo htmlspecialchars($tagLabel); ?></h1>
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
      
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Tag Name *</label>
        <input type="text" name="name" required 
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL)</label>
        <input type="text" name="slug" 
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
               value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Full Description</label>
        <textarea name="description" rows="4"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
      </div>
    </div>

    <!-- SEO Section -->
    <div class="border-b pb-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">SEO Settings</h2>
      
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">SEO Title</label>
        <input type="text" name="seo_title" maxlength="60"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
               value="<?php echo htmlspecialchars($_POST['seo_title'] ?? ''); ?>">
        <p class="mt-1 text-xs text-gray-500">Max 60 characters</p>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">SEO Meta Description</label>
        <textarea name="seo_description" rows="3" maxlength="160"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($_POST['seo_description'] ?? ''); ?></textarea>
        <p class="mt-1 text-xs text-gray-500">Max 160 characters</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">SEO Image</label>
        <?php if (!empty($tag['seo_image'])): ?>
          <div class="mb-2">
            <img src="<?php echo htmlspecialchars($tag['seo_image']); ?>" alt="Current SEO Image" class="max-w-xs rounded border">
            <p class="text-sm text-gray-500 mt-1">Current image</p>
          </div>
        <?php endif; ?>
        <input type="file" name="seo_image" accept="image/*"
               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
        <p class="mt-1 text-xs text-gray-500">Upload a new image to replace current one</p>
      </div>
    </div>

    <div class="pt-4 flex justify-end gap-3">
      <a href="<?php echo htmlspecialchars($listUrl); ?>" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition">
        Cancel
      </a>
      <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
        Update Tag
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
