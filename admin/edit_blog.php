<?php
// Admin: Edit Blog Post - With Quill Rich Text Editor (same as products)
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/blog_scope_helper.php';

$scope = admin_blog_scope_from_request();
$scopePostsLabel = admin_blog_scope_posts_label($scope);
$scopePostLabel = admin_blog_scope_post_label($scope);
$pageTitle = 'Edit ' . $scopePostLabel;
$errors = [];
$scopeColumnAvailable = admin_blog_ensure_scope_column($pdo);
$categoryScopeColumnAvailable = admin_blog_ensure_category_scope_column($pdo);
$tagScopeColumnAvailable = admin_blog_ensure_tag_scope_column($pdo);
$listUrl = admin_blog_scope_url('/admin/blogs.php', $scope);
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($blogId <= 0) {
    header('Location: ' . $listUrl);
    exit;
}

// Fetch existing blog
try {
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = :id");
    $stmt->execute([':id' => $blogId]);
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$blog) {
        header('Location: ' . $listUrl);
        exit;
    }

    if ($scopeColumnAvailable) {
        $existingScope = admin_blog_scope_normalize((string)($blog['blog_type'] ?? ''));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (isset($_GET['scope']) && admin_blog_scope_normalize((string)$_GET['scope']) !== $existingScope) {
                header('Location: ' . admin_blog_scope_url('/admin/edit_blog.php', $existingScope, ['id' => $blogId]));
                exit;
            }
            if (!isset($_GET['scope'])) {
                $scope = $existingScope;
                $scopePostsLabel = admin_blog_scope_posts_label($scope);
                $scopePostLabel = admin_blog_scope_post_label($scope);
                $pageTitle = 'Edit ' . $scopePostLabel;
                $listUrl = admin_blog_scope_url('/admin/blogs.php', $scope);
            }
        }
    }
} catch (PDOException $e) {
    die('Error fetching blog: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scope = admin_blog_scope_normalize($_POST['blog_scope'] ?? $scope);
    $scopePostsLabel = admin_blog_scope_posts_label($scope);
    $scopePostLabel = admin_blog_scope_post_label($scope);
    $pageTitle = 'Edit ' . $scopePostLabel;
    $listUrl = admin_blog_scope_url('/admin/blogs.php', $scope);

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $authorId = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : null;
    $content = $_POST['content'] ?? '';
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $scheduleDate = $_POST['schedule_date'] ?? '';

    // Scheduling Logic
    $publishedAt = null;
    if ($isPublished) {
        if (!empty($scheduleDate)) {
            $publishedAt = date('Y-m-d H:i:s', strtotime($scheduleDate));
        } else {
            // If already published and date is empty, keep original published_at if available, or set NOW
            // But if user cleared the date, maybe they want immediate?
            // Let's rely on: if input empty -> immediate (NOW) or keep existing if it's in the past?
            // To be safe: if input empty -> NOW if it wasn't published before, or keep as is?
            // Simpler: If input empty -> NOW.
            // BUT: if editing an old post, we don't want to reset date to NOW every time.
            // So: if input empty AND it was already published -> keep old date.
            // If input empty AND it was NOT published -> NOW.
            
            // Actually, we'll populate the input with existing date. So if they clear it, they mean NOW/update.
            // But let's check existing DB value.
            if (!empty($blog['published_at'])) {
                 $publishedAt = $blog['published_at'];
            } else {
                 $publishedAt = date('Y-m-d H:i:s');
            }
        }
    } elseif (!empty($scheduleDate)) {
         $publishedAt = date('Y-m-d H:i:s', strtotime($scheduleDate));
         $isPublished = 1;
    }

    if (empty($title)) {
        $errors[] = 'Title is required';
    }

    // Handle image upload or selection
    $featuredImage = $blog['featured_image'];

    // Check for uploaded file first
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
    // If no file uploaded, check for selected media URL
    elseif (!empty($_POST['featured_image_url'])) {
        $featuredImage = $_POST['featured_image_url'];
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
                    author_id = :author_id,
                    blog_category_id = :blog_category_id,
                    " . ($scopeColumnAvailable ? "blog_type = :blog_type," : "") . "
                    featured_image = :featured_image,
                    is_published = :is_published,
                    published_at = :published_at,
                    updated_at = NOW()
                WHERE id = :id
            ");

            $updateBind = [
                ':title' => $title,
                ':slug' => $slug,
                ':meta_title' => $metaTitle,
                ':meta_description' => $metaDescription,
                ':content' => $content,
                ':author_id' => $authorId,
                ':blog_category_id' => !empty($_POST['blog_category_id']) ? $_POST['blog_category_id'] : null,
                ':featured_image' => $featuredImage,
                ':is_published' => $isPublished,
                ':published_at' => $publishedAt,
                ':id' => $blogId
            ];
            if ($scopeColumnAvailable) {
                $updateBind[':blog_type'] = admin_blog_scope_db_value($scope);
            }
            $stmt->execute($updateBind);

            // Handle Related Articles
            $relatedArticles = $_POST['related_articles'] ?? [];
            // Delete existing relations
            $pdo->prepare("DELETE FROM blog_related WHERE blog_id = ?")->execute([$blogId]);
            // Insert new relations
            if (!empty($relatedArticles)) {
                $stmtRelated = $pdo->prepare("INSERT IGNORE INTO blog_related (blog_id, related_blog_id) VALUES (?, ?)");
                foreach ($relatedArticles as $relatedId) {
                    if ($relatedId != $blogId) { // Don't relate to itself
                        $stmtRelated->execute([$blogId, $relatedId]);
                    }
                }
            }

            // Handle Blog Tags  
            $blogTags = $_POST['blog_tags'] ?? [];
            // Delete existing tags
            try {
                $pdo->prepare("DELETE FROM blog_post_tags WHERE blog_id = ?")->execute([$blogId]);
            } catch (PDOException $e) { }
            // Insert new tags
            if (!empty($blogTags)) {
                try {
                    $stmtTags = $pdo->prepare("INSERT IGNORE INTO blog_post_tags (blog_id, tag_id) VALUES (?, ?)");
                    foreach ($blogTags as $tagId) {
                        $stmtTags->execute([$blogId, $tagId]);
                    }
                } catch (PDOException $e) { }
            }

            header('Location: ' . $listUrl);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
} else {
    // Pre-fill form with existing data
    $_POST = $blog;
    // Format published_at for datetime-local input (Y-m-d\TH:i)
    if (!empty($blog['published_at'])) {
        $_POST['schedule_date'] = date('Y-m-d\TH:i', strtotime($blog['published_at']));
    }
}

// Fetch currently selected related articles
$selectedRelated = [];
try {
    $stmtRel = $pdo->prepare("SELECT related_blog_id FROM blog_related WHERE blog_id = ?");
    $stmtRel->execute([$blogId]);
    $selectedRelated = array_column($stmtRel->fetchAll(PDO::FETCH_ASSOC), 'related_blog_id');
} catch (PDOException $e) {
    // Table might not exist yet, ignore
}

// Fetch recent articles for selection (excluding current post)
$recentArticles = [];
try {
    if ($scopeColumnAvailable) {
        [$scopeClause, $scopeParams] = admin_blog_scope_filter_clause($scope, 'blog_type');
        $stmtRecent = $pdo->prepare("SELECT id, title, created_at FROM blogs WHERE id != :current_id AND {$scopeClause} ORDER BY created_at DESC LIMIT 20");
        $scopeParams[':current_id'] = $blogId;
        $stmtRecent->execute($scopeParams);
    } else {
        $stmtRecent = $pdo->prepare("SELECT id, title, created_at FROM blogs WHERE id != ? ORDER BY created_at DESC LIMIT 20");
        $stmtRecent->execute([$blogId]);
    }
    $recentArticles = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }

// Fetch currently selected blog tags
$selectedTags = [];
try {
    $stmtTags = $pdo->prepare("SELECT tag_id FROM blog_post_tags WHERE blog_id = ?");
    $stmtTags->execute([$blogId]);
    $selectedTags = array_column($stmtTags->fetchAll(PDO::FETCH_ASSOC), 'tag_id');
} catch (PDOException $e) { }

// Fetch available blog tags
$availableTags = [];
try {
    if ($tagScopeColumnAvailable) {
        [$tagScopeClause, $tagScopeParams] = admin_blog_scope_taxonomy_filter_clause($scope, 'blog_scope', 'tag_scope');
        $stmtAllTags = $pdo->prepare("SELECT id, name FROM blog_tags WHERE {$tagScopeClause} ORDER BY name ASC");
        $stmtAllTags->execute($tagScopeParams);
    } else {
        $stmtAllTags = $pdo->query("SELECT id, name FROM blog_tags ORDER BY name ASC");
    }
    $availableTags = $stmtAllTags->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }

include __DIR__ . '/layout/header.php';
?>

<div class="p-8 max-w-4xl">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Edit <?php echo htmlspecialchars($scopePostLabel); ?></h1>
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
      <select name="author_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
        <option value="">-- Select Author --</option>
        <?php
        try {
            $stmtAuth = $pdo->query("SELECT id, name FROM authors ORDER BY name ASC");
            while ($auth = $stmtAuth->fetch(PDO::FETCH_ASSOC)) {
                // Check POST first, then DB value
                $currentAuth = $_POST['author_id'] ?? $blog['author_id'] ?? '';
                $selected = ($currentAuth == $auth['id']) ? 'selected' : '';
                echo '<option value="' . $auth['id'] . '" ' . $selected . '>' . htmlspecialchars($auth['name']) . '</option>';
            }
        } catch (PDOException $e) { }
        ?>
      </select>
    </div>

    <!-- Category -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
      <select name="blog_category_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
        <option value="">-- Select Category --</option>
        <?php
        try {
            if ($categoryScopeColumnAvailable) {
                [$catScopeClause, $catScopeParams] = admin_blog_scope_taxonomy_filter_clause($scope, 'blog_scope', 'cat_scope');
                $catStmt = $pdo->prepare("SELECT id, title FROM blog_categories WHERE {$catScopeClause} ORDER BY title ASC");
                $catStmt->execute($catScopeParams);
            } else {
                $catStmt = $pdo->query("SELECT id, title FROM blog_categories ORDER BY title ASC");
            }
            while ($cat = $catStmt->fetch(PDO::FETCH_ASSOC)) {
                $currentCatId = $_POST['blog_category_id'] ?? $blog['blog_category_id'] ?? '';
                $selected = ($currentCatId == $cat['id']) ? 'selected' : '';
                echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['title']) . '</option>';
            }
        } catch (PDOException $e) {
            // ignore
        }
        ?>
      <!-- Featured Image -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
      
      <!-- Input Group -->
      <div class="space-y-3">
        <!-- Option 1: File Upload -->
        <div>
           <input type="file" name="featured_image" accept="image/*"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
           <p class="text-xs text-gray-500 mt-1">Upload to replace current image</p>
        </div>
        
        <div class="text-center text-sm text-gray-500 font-medium">- OR -</div>

        <!-- Option 2: Media Library -->
        <div class="flex items-center gap-4">
           <button type="button" id="selectFeaturedImgBtn" 
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition shadow-sm">
             📁 Select from Library
           </button>
           <input type="hidden" name="featured_image_url" id="featured_image_url" value="<?php echo htmlspecialchars($blog['featured_image'] ?? ''); ?>">
           
           <!-- Preview -->
           <?php $fImg = $blog['featured_image'] ?? ''; ?>
           <div id="featured_image_preview" class="<?php echo empty($fImg) ? 'hidden' : ''; ?> h-20 w-32 border rounded overflow-hidden relative group">
              <img src="<?php echo htmlspecialchars($fImg); ?>" alt="Preview" class="w-full h-full object-cover">
              <button type="button" onclick="removeFeaturedImage()" 
                      class="absolute top-0 right-0 bg-red-500 text-white p-1 rounded-bl text-xs opacity-0 group-hover:opacity-100 transition">
                &times;
              </button>
           </div>
        </div>
      </div>
      <p class="mt-2 text-sm text-gray-500">Recommended size: 1200x630px</p>
    </div>

    <!-- Content with CKEditor -->
    <div>
      <div class="flex justify-between items-center mb-2">
        <label class="block text-sm font-medium text-gray-700">Content *</label>
        <div class="flex gap-2">
          <button type="button" id="toggleEditorBtn" class="px-4 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700 transition">
            Hide Editor
          </button>
          <button type="button" id="addMediaBtn" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition">
            📁 Add Media
          </button>
        </div>
      </div>
      <div id="editorWrapper">
        <div id="editor-content" class="bg-white"></div>
      </div>
      <textarea name="content" id="hidden-content" class="hidden"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
      <p class="mt-1 text-sm text-gray-500 error-message" id="content-error" style="display:none; color: #dc2626;">Content is required</p>
    </div>

    <!-- Schedule Date -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Publish Date</label>
      <input type="datetime-local" name="schedule_date" 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             value="<?php echo htmlspecialchars($_POST['schedule_date'] ?? ''); ?>">
      <p class="mt-1 text-sm text-gray-500">Leave blank to keep existing date or publish immediately.</p>
    </div>

    <!-- Related Articles Selection -->
    <div class="border-t pt-6 mt-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Related Articles (Optional)</h3>
      <p class="text-sm text-gray-600 mb-4">Select up to 3 related articles to display on the blog post page. Leave unchecked to auto-show articles from the same category.</p>
      
      <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-100 sticky top-0">
            <tr>
              <th class="text-left p-2 font-semibold w-12">Select</th>
              <th class="text-left p-2 font-semibold">Article Title</th>
              <th class="text-left p-2 font-semibold w-32">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentArticles as $article): ?>
            <tr class="border-b border-gray-200 hover:bg-white transition">
              <td class="p-2">
                <input type="checkbox" 
                       name="related_articles[]" 
                       value="<?= $article['id'] ?>"
                       class="related-article-checkbox h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                       <?= in_array($article['id'], $selectedRelated) ? 'checked' : '' ?>>
              </td>
              <td class="p-2">
                <span class="text-gray-800"><?= htmlspecialchars($article['title']) ?></span>
              </td>
              <td class="p-2 text-gray-500">
                <?= date('M j, Y', strtotime($article['created_at'])) ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentArticles)): ?>
            <tr>
              <td colspan="3" class="p-4 text-center text-gray-500">No other articles available</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <p class="text-xs text-gray-500 mt-2" id="selection-count">
        <span id="selected-count">0</span> article(s) selected (max 3 recommended)
      </p>
    </div>

    <!-- Blog Tags Selection -->
    <div class="border-t pt-6 mt-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-lg font-semibold text-gray-800">Blog Tags</h3>
          <p class="text-sm text-gray-600 mt-1">Select tags for this blog post.</p>
        </div>
        <?php if (!empty($availableTags)): ?>
          <div class="flex gap-2">
            <button type="button" onclick="selectAllTags(true)" class="px-3 py-1 text-xs bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 transition">
              ✓ Select All
            </button>
            <button type="button" onclick="selectAllTags(false)" class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition">
              ✗ Deselect All
            </button>
          </div>
        <?php endif; ?>
      </div>
      
      <?php if (!empty($availableTags)): ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 p-4 bg-gray-50 rounded-lg max-h-60 overflow-y-auto">
          <?php foreach ($availableTags as $tag): ?>
            <label class="flex items-center space-x-2 p-2 bg-white rounded border border-gray-200 hover:border-indigo-400 hover:bg-indigo-50 cursor-pointer transition">
              <input type="checkbox" name="blog_tags[]" value="<?= $tag['id'] ?>" 
                     class="blog-tag-checkbox h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                     <?= in_array($tag['id'], $selectedTags) ? 'checked' : '' ?>>
              <span class="text-sm text-gray-700"><?= htmlspecialchars($tag['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="text-xs text-gray-500 mt-2">
          <span id="tag-count">0</span> tag(s) selected
        </p>
      <?php else: ?>
        <p class="text-sm text-gray-500 bg-gray-50 p-4 rounded">No tags available.</p>
      <?php endif; ?>
    </div>

    <script>
      function selectAllTags(select) {
        document.querySelectorAll('.blog-tag-checkbox').forEach(cb => {
          cb.checked = select;
        });
        updateTagCount();
      }
      
      function updateTagCount() {
        const count = document.querySelectorAll('.blog-tag-checkbox:checked').length;
        const countEl = document.getElementById('tag-count');
        if (countEl) countEl.textContent = count;
      }
      
      document.querySelectorAll('.blog-tag-checkbox').forEach(cb => {
        cb.addEventListener('change', updateTagCount);
      });
      updateTagCount();
    </script>

    <div class="flex items-center">
      <input type="checkbox" name="is_published" id="is_published" value="1"
             class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
             <?php echo (!empty($_POST['is_published']) ? 'checked' : ''); ?>>
      <label for="is_published" class="ml-2 block text-sm text-gray-700">
        Publish / Scheduled
      </label>
    </div>

    <div class="flex items-center justify-between pt-4 border-t">
      <a href="<?php echo htmlspecialchars($listUrl); ?>" class="text-gray-600 hover:text-gray-900">← Back to <?php echo htmlspecialchars($scopePostsLabel); ?></a>
      <div class="flex gap-3">
          <button type="button" id="btnPreview" class="px-6 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition flex items-center gap-2">
            👁️ Preview
          </button>
          <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
            Update Post
          </button>
      </div>
    </div>
  </form>
</div>

<script>
document.getElementById('btnPreview').addEventListener('click', function(e) {
    e.preventDefault();
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Generating...';

    const form = document.querySelector('form');
    const formData = new FormData(form);
    formData.append('preview_type', 'blog');

    if (CKEDITOR.instances['editor-content']) {
        formData.set('content', CKEDITOR.instances['editor-content'].getData());
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
</script>

<!-- CKEditor 4 - Full Featured (v4.22.1 - Last Free Version) -->
<script src="//cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<style>
  /* Hide CKEditor warning notifications */
  .cke_notification_warning { display: none !important; }
  .cke_notifications_area { display: none !important; }
  /* Increase editor content font size */
  .cke_editable { font-size: 16px !important; line-height: 1.6 !important; }
</style>
<script>
  const ckEditorFontCssUrl = 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Lato:wght@300;400;700;900&family=Open+Sans:wght@300;400;600;700;800&family=Source+Sans+3:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&family=EB+Garamond:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Cormorant:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700;800&family=Raleway:wght@300;400;500;600;700;800&display=swap';
  CKEDITOR.replace('editor-content', {
    height: 450,
    removePlugins: 'easyimage,cloudservices',
    extraPlugins: 'uploadimage', // Removed 'video' to fix CDN error
    allowedContent: true, // Allow all HTML tags/attributes
    extraAllowedContent: 'video[*]{*};source[*]{*};',
    filebrowserUploadUrl: '/admin/upload_blog_image.php',
    uploadUrl: '/admin/upload_blog_image.php',
    font_names: 'Arial/Arial, Helvetica, sans-serif;' +
      'Arial Black/Arial Black, Gadget, sans-serif;' +
      'Baskerville/Baskerville, Times New Roman, serif;' +
      'Book Antiqua/Book Antiqua, Palatino, serif;' +
      'Brush Script MT/Brush Script MT, cursive;' +
      'Calibri/Calibri, Candara, Segoe, sans-serif;' +
      'Cambria/Cambria, Georgia, serif;' +
      'Candara/Candara, Calibri, Segoe, sans-serif;' +
      'Century Gothic/Century Gothic, CenturyGothic, sans-serif;' +
      'Comic Sans MS/Comic Sans MS, cursive;' +
      'Consolas/Consolas, monaco, monospace;' +
      'Copperplate/Copperplate, Copperplate Gothic Light, fantasy;' +
      'Courier/Courier, monospace;' +
      'Courier New/Courier New, Courier, monospace;' +
      'Didot/Didot, Didot LT STD, Hoefler Text, serif;' +
      'Franklin Gothic Medium/Franklin Gothic Medium, sans-serif;' +
      'Futura/Futura, Trebuchet MS, sans-serif;' +
      'Garamond/Garamond, Baskerville, serif;' +
      'Geneva/Geneva, Tahoma, sans-serif;' +
      'Georgia/Georgia, Times, serif;' +
      'Gill Sans/Gill Sans, Gill Sans MT, Calibri, sans-serif;' +
      'Goudy Old Style/Goudy Old Style, Garamond, serif;' +
      'Helvetica/Helvetica, Arial, sans-serif;' +
      'Helvetica Neue/Helvetica Neue, Helvetica, Arial, sans-serif;' +
      'Hoefler Text/Hoefler Text, Baskerville Old Face, serif;' +
      'Impact/Impact, Charcoal, sans-serif;' +
      'Inter/Inter, sans-serif;' +
      'Lato/Lato, sans-serif;' +
      'Lucida Bright/Lucida Bright, Georgia, serif;' +
      'Lucida Console/Lucida Console, Monaco, monospace;' +
      'Lucida Grande/Lucida Grande, Lucida Sans Unicode, sans-serif;' +
      'Lucida Sans/Lucida Sans, Lucida Sans Unicode, sans-serif;' +
      'Merriweather/Merriweather, serif;' +
      'Monaco/Monaco, Consolas, monospace;' +
      'Montserrat/Montserrat, sans-serif;' +
      'MS Serif/MS Serif, New York, serif;' +
      'Nunito/Nunito, sans-serif;' +
      'Open Sans/Open Sans, sans-serif;' +
      'Optima/Optima, Segoe, sans-serif;' +
      'Oswald/Oswald, sans-serif;' +
      'Palatino/Palatino, Palatino Linotype, serif;' +
      'Perpetua/Perpetua, Baskerville, serif;' +
      'Playfair Display/Playfair Display, serif;' +
      'Poppins/Poppins, sans-serif;' +
      'PT Sans/PT Sans, sans-serif;' +
      'Quicksand/Quicksand, sans-serif;' +
      'Raleway/Raleway, sans-serif;' +
      'Roboto/Roboto, sans-serif;' +
      'Rockwell/Rockwell, Courier Bold, serif;' +
      'Segoe UI/Segoe UI, Frutiger, sans-serif;' +
      'Source Sans Pro/Source Sans Pro, sans-serif;' +
      'Tahoma/Tahoma, Geneva, sans-serif;' +
      'Times/Times, Times New Roman, serif;' +
      'Times New Roman/Times New Roman, Times, serif;' +
      'Trebuchet MS/Trebuchet MS, Helvetica, sans-serif;' +
      'Ubuntu/Ubuntu, sans-serif;' +
      'Verdana/Verdana, Geneva, sans-serif;' +
      'Cormorant Garamond/Cormorant Garamond, serif;' +
      'Cormorant/Cormorant, serif;' +
      'EB Garamond/EB Garamond, serif;' +
      'Libre Baskerville/Libre Baskerville, serif;' +
      'Source Sans/Source Sans 3, Source Sans Pro, sans-serif',
    on: {
      change: function() {
        document.getElementById('hidden-content').value = this.getData();
      },
      instanceReady: function() {
        this.document.appendStyleSheet(ckEditorFontCssUrl);
        // Load existing content
        const existingContent = document.getElementById('hidden-content').value;
        if (existingContent) {
          this.setData(existingContent);
        }
        // Hide notification area
        const notificationArea = document.querySelector('.cke_notifications_area');
        if (notificationArea) notificationArea.style.display = 'none';
      }
    }
  });
  
  // Suppress console warnings
  const originalWarn = console.warn;
  console.warn = function(msg) {
    if (typeof msg === 'string' && (msg.includes('CKEditor') || msg.includes('ckeditor'))) {
      return;
    }
    originalWarn.apply(console, arguments);
  };

  // Toggle Editor Functionality
  document.getElementById('toggleEditorBtn').addEventListener('click', function() {
    const editorWrapper = document.getElementById('editorWrapper');
    const btn = this;
    
    if (editorWrapper.style.display === 'none') {
      editorWrapper.style.display = 'block';
      btn.textContent = 'Hide Editor';
    } else {
      editorWrapper.style.display = 'none';
      btn.textContent = 'Show Editor';
    }
  });

  // Media Target State
  window.mediaTarget = 'content'; // 'content' or 'featured'

  // Open Media Modal Helper
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

  // Add Media Button (Content)
  document.getElementById('addMediaBtn').addEventListener('click', function() {
    window.mediaTarget = 'content';
    openMediaModal();
  });

  // Select Featured Image Button
  document.getElementById('selectFeaturedImgBtn').addEventListener('click', function() {
    window.mediaTarget = 'featured';
    openMediaModal();
  });

  // Close media modal function
  window.closeMediaModal = function() {
    const modal = document.getElementById('mediaLibraryModal');
    if (modal) modal.remove();
  };

  // Remove Featured Image
  window.removeFeaturedImage = function() {
    document.getElementById('featured_image_url').value = '';
    document.getElementById('featured_image_preview').classList.add('hidden');
    document.querySelector('#featured_image_preview img').src = '';
  };

  // Insert images from media library
  window.insertImagesToEditor = function(imagePaths) {
    console.log('insertImagesToEditor called with:', imagePaths);
    
    if (!imagePaths || imagePaths.length === 0) {
      alert('No images selected');
      return;
    }

    // Handle Featured Image Selection
    if (window.mediaTarget === 'featured') {
      const firstImage = imagePaths[0]; // Take only the first image
      document.getElementById('featured_image_url').value = firstImage;
      
      const preview = document.getElementById('featured_image_preview');
      preview.classList.remove('hidden');
      preview.querySelector('img').src = firstImage;
      
      closeMediaModal();
      return;
    }

    // Handle Content Editor Insertion
    if (!CKEDITOR.instances['editor-content']) {
      console.error('CKEditor instance not found');
      alert('Editor not ready. Please try again.');
      return;
    }
    
    try {
      const editor = CKEDITOR.instances['editor-content'];
      imagePaths.forEach(imageUrl => {
        console.log('Inserting media:', imageUrl);
        
        // Detect if it's a video based on extension
        const ext = imageUrl.split('.').pop().toLowerCase();
        const isVideo = ['mp4', 'webm', 'ogg', 'mov'].includes(ext);
        
        let mediaHtml = '';
        if (isVideo) {
            // Check if user is resizing or not. wrap in a convenient resizable div
            mediaHtml = `
              <div style="display: inline-block; width: 80%; max-width: 100%; resize: both; overflow: hidden; border: 1px dashed #ccc; vertical-align: top; margin: 10px 0;">
                <video src="${imageUrl}" controls style="width: 100%; height: auto; display: block;"></video>
              </div>
              <p><br/></p>
            `;
        } else {
             mediaHtml = '<img src="' + imageUrl + '" alt="Image" style="max-width: 100%; height: auto; margin: 10px 0;" /><p><br/></p>';
        }
        
        // Insert without triggering getParents error
        setTimeout(() => {
          editor.insertHtml(mediaHtml);
        }, 10);
      });
      console.log('Images inserted successfully');
      
      // Close modal after insertion
      setTimeout(closeMediaModal, 100);
      
    } catch (e) {
      console.error('Error inserting html:', e);
      alert('Error inserting image: ' + e.message);
    }
  };


  // Form validation
  document.querySelector('form').addEventListener('submit', function(e) {
    var content = document.getElementById('hidden-content').value;
    var errorDiv = document.getElementById('content-error');
    
    // Check if content is empty (strip HTML tags for validation)
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    var textContent = tempDiv.textContent || tempDiv.innerText || '';
    
    if (textContent.trim() === '') {
      e.preventDefault();
      errorDiv.style.display = 'block';
      document.getElementById('editor-content').scrollIntoView({ behavior: 'smooth', block: 'center' });
      return false;
    } else {
      errorDiv.style.display = 'none';
    }
  });

  // Related Articles selection counter
  const checkboxes = document.querySelectorAll('.related-article-checkbox');
  const countDisplay = document.getElementById('selected-count');
  
  function updateCount() {
    const checked = document.querySelectorAll('.related-article-checkbox:checked').length;
    if (countDisplay) countDisplay.textContent = checked;
  }
  
  checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
  updateCount(); // Initial count
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
