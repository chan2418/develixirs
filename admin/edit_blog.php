<?php
// Admin: Edit Blog Post
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Edit Blog Post';
$errors = [];
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($blogId <= 0) {
    header('Location: /admin/blogs.php');
    exit;
}

// Fetch existing blog
try {
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = :id");
    $stmt->execute([':id' => $blogId]);
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$blog) {
        header('Location: /admin/blogs.php');
        exit;
    }
} catch (PDOException $e) {
    die('Error fetching blog: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $content = $_POST['content'] ?? '';
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if (empty($title)) {
        $errors[] = 'Title is required';
    }

    // Handle image upload
    $featuredImage = $blog['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/blogs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('blog_') . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetPath)) {
            // Delete old image
            if ($featuredImage && file_exists(__DIR__ . '/..' . $featuredImage)) {
                unlink(__DIR__ . '/..' . $featuredImage);
            }
            $featuredImage = '/assets/uploads/blogs/' . $filename;
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE blogs SET 
                    title = :title,
                    slug = :slug,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    content = :content,
                    author = :author,
                    category_id = :category_id,
                    featured_image = :featured_image,
                    is_published = :is_published,
                    updated_at = NOW()
                WHERE id = :id
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
                ':is_published' => $isPublished,
                ':id' => $blogId
            ]);

            header('Location: /admin/blogs.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
} else {
    // Pre-fill form with existing data
    $_POST = $blog;
}

include __DIR__ . '/layout/header.php';
?>

<div class="p-8 max-w-4xl">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Edit Blog Post</h1>
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
    
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
      <input type="text" name="title" required 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL)</label>
      <input type="text" name="slug" 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">SEO Meta Title</label>
      <input type="text" name="meta_title" 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             maxlength="60"
             value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">SEO Meta Description</label>
      <textarea name="meta_description" rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                maxlength="160"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
    </div>

    <!-- Author -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Author</label>
      <input type="text" name="author" 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>">
    </div>

    <!-- Category -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
      <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
        <option value="">-- Select Category --</option>
        <?php
        try {
            $catStmt = $pdo->query("SELECT id, title FROM categories ORDER BY title ASC");
            while ($cat = $catStmt->fetch(PDO::FETCH_ASSOC)) {
                $currentCatId = $_POST['category_id'] ?? $blog['category_id'] ?? '';
                $selected = ($currentCatId == $cat['id']) ? 'selected' : '';
                echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['title']) . '</option>';
            }
        } catch (PDOException $e) {
            // ignore
        }
        ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
      <?php if (!empty($featuredImage)): ?>
        <div class="mb-2">
          <img src="<?php echo htmlspecialchars($featuredImage); ?>" alt="Current Featured Image" class="max-w-xs rounded border">
          <p class="text-sm text-gray-500 mt-1">Current image</p>
        </div>
      <?php endif; ?>
      <input type="file" name="featured_image" accept="image/*"
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
      <p class="mt-1 text-sm text-gray-500">Upload a new image to replace the current one</p>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Content *</label>
      <textarea name="content" rows="15" required
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
    </div>

    <div class="flex items-center">
      <input type="checkbox" name="is_published" id="is_published" value="1"
             class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
             <?php echo (!empty($_POST['is_published']) ? 'checked' : ''); ?>>
      <label for="is_published" class="ml-2 block text-sm text-gray-700">
        Publish
      </label>
    </div>

    <div class="flex items-center justify-between pt-4 border-t">
      <a href="/admin/blogs.php" class="text-gray-600 hover:text-gray-900">← Back to Blog Posts</a>
      <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
        Update Post
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
