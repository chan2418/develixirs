<?php
// Admin: Add Blog Post
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Add New Blog Post';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $content = $_POST['content'] ?? '';
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }

    // Handle image upload
    $featuredImage = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/blogs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('blog_') . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetPath)) {
            $featuredImage = '/assets/uploads/blogs/' . $filename;
        } else {
            $errors[] = 'Failed to upload image';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO blogs (title, slug, meta_title, meta_description, content, author, category_id, featured_image, is_published, created_at)
                VALUES (:title, :slug, :meta_title, :meta_description, :content, :author, :category_id, :featured_image, :is_published, NOW())
            ");
            
            $stmt->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':meta_title' => $metaTitle,
                ':meta_description' => $metaDescription,
                ':content' => $content,
                ':author' => $author,
                ':category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                ':featured_image' => $featuredImage,
                ':is_published' => $isPublished
            ]);

            header('Location: /admin/blogs.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/layout/header.php';
?>

<div class="p-8 max-w-4xl">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Add New Blog Post</h1>
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
    
    <!-- Title -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
      <input type="text" name="title" required 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
    </div>

    <!-- Slug -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL)</label>
      <input type="text" name="slug" 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             placeholder="auto-generated-from-title"
             value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
      <p class="mt-1 text-sm text-gray-500">Leave empty to auto-generate from title</p>
    </div>

    <!-- SEO Meta Title -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">SEO Meta Title</label>
      <input type="text" name="meta_title" 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             maxlength="60"
             value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
      <p class="mt-1 text-sm text-gray-500">Recommended: 50-60 characters</p>
    </div>

    <!-- SEO Meta Description -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">SEO Meta Description</label>
      <textarea name="meta_description" rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                maxlength="160"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
      <p class="mt-1 text-sm text-gray-500">Recommended: 150-160 characters</p>
    </div>

    <!-- Author -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Author</label>
      <input type="text" name="author" 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             value="<?php echo htmlspecialchars($_POST['author'] ?? ($_SESSION['admin_name'] ?? '')); ?>">
    </div>

    <!-- Category -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
      <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
        <option value="">-- Select Category --</option>
        <?php
        // Fetch categories for dropdown
        try {
            $catStmt = $pdo->query("SELECT id, title FROM categories ORDER BY title ASC");
            while ($cat = $catStmt->fetch(PDO::FETCH_ASSOC)) {
                $selected = (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '';
                echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['title']) . '</option>';
            }
        } catch (PDOException $e) {
            // ignore
        }
        ?>
      </select>
    </div>

    <!-- Featured Image -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
      <input type="file" name="featured_image" accept="image/*"
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
      <p class="mt-1 text-sm text-gray-500">Recommended size: 1200x630px</p>
    </div>

    <!-- Content -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Content *</label>
      <textarea name="content" rows="15" required
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
      <p class="mt-1 text-sm text-gray-500">You can use HTML here</p>
    </div>

    <!-- Published Status -->
    <div class="flex items-center">
      <input type="checkbox" name="is_published" id="is_published" value="1"
             class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
             <?php echo (!empty($_POST['is_published']) ? 'checked' : ''); ?>>
      <label for="is_published" class="ml-2 block text-sm text-gray-700">
        Publish immediately
      </label>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-4 border-t">
      <a href="/admin/blogs.php" class="text-gray-600 hover:text-gray-900">← Back to Blog Posts</a>
      <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
        Create Post
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
