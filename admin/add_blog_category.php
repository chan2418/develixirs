<?php
// admin/add_blog_category.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/blog_scope_helper.php';

$scope = admin_blog_scope_from_request();
$categoriesLabel = admin_blog_scope_categories_label($scope);
$categoryLabel = admin_blog_scope_category_label($scope);
$supportsSubcategories = admin_blog_scope_supports_subcategories($scope);
$pageTitle = 'Add ' . $categoryLabel;
$errors = [];
$scopeColumnAvailable = admin_blog_ensure_category_scope_column($pdo);
$parentColumnAvailable = admin_blog_ensure_category_parent_column($pdo);
$listUrl = admin_blog_scope_url('/admin/blog_categories.php', $scope);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scope = admin_blog_scope_normalize($_POST['blog_scope'] ?? $scope);
    $categoriesLabel = admin_blog_scope_categories_label($scope);
    $categoryLabel = admin_blog_scope_category_label($scope);
    $supportsSubcategories = admin_blog_scope_supports_subcategories($scope);
    $pageTitle = 'Add ' . $categoryLabel;
    $listUrl = admin_blog_scope_url('/admin/blog_categories.php', $scope);

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $parentId = null;

    if ($supportsSubcategories && $parentColumnAvailable && !empty($_POST['parent_id'])) {
        $parentId = (int)$_POST['parent_id'];
        if ($parentId <= 0) {
            $parentId = null;
        }
    }

    if (empty($title)) {
        $errors[] = 'Title is required';
    }

    if (empty($slug)) {
        // Auto-generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }

    if ($parentId !== null) {
        try {
            if ($scopeColumnAvailable) {
                [$parentScopeClause, $parentScopeParams] = admin_blog_scope_taxonomy_filter_clause($scope, 'blog_scope', 'parent_scope');
                $stmtParentCheck = $pdo->prepare("SELECT id FROM blog_categories WHERE id = :id AND {$parentScopeClause} LIMIT 1");
                $stmtParentCheck->execute(array_merge([':id' => $parentId], $parentScopeParams));
            } else {
                $stmtParentCheck = $pdo->prepare("SELECT id FROM blog_categories WHERE id = :id LIMIT 1");
                $stmtParentCheck->execute([':id' => $parentId]);
            }

            if (!$stmtParentCheck->fetch(PDO::FETCH_ASSOC)) {
                $errors[] = 'Selected parent category is invalid.';
                $parentId = null;
            }
        } catch (PDOException $e) {
            $errors[] = 'Could not validate selected parent category.';
        }
    }

    if (empty($errors)) {
        try {
            $insertColumns = ['title', 'slug', 'description'];
            $insertValues = [':title', ':slug', ':description'];
            $insertBind = [
                ':title' => $title,
                ':slug' => $slug,
                ':description' => $description,
            ];

            if ($scopeColumnAvailable) {
                $insertColumns[] = 'blog_scope';
                $insertValues[] = ':blog_scope';
                $insertBind[':blog_scope'] = admin_blog_scope_taxonomy_value($scope);
            }

            if ($parentColumnAvailable) {
                $insertColumns[] = 'parent_id';
                $insertValues[] = ':parent_id';
                $insertBind[':parent_id'] = $supportsSubcategories ? $parentId : null;
            }

            $stmt = $pdo->prepare(
                "INSERT INTO blog_categories (" . implode(', ', $insertColumns) . ")
                 VALUES (" . implode(', ', $insertValues) . ")"
            );
            $stmt->execute($insertBind);

            header("Location: " . $listUrl);
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$parentCategories = [];
if ($supportsSubcategories && $parentColumnAvailable) {
    try {
        if ($scopeColumnAvailable) {
            [$parentScopeClause, $parentScopeParams] = admin_blog_scope_taxonomy_filter_clause($scope, 'blog_scope', 'parent_list_scope');
            $stmtParents = $pdo->prepare(
                "SELECT id, title
                 FROM blog_categories
                 WHERE {$parentScopeClause}
                   AND (parent_id IS NULL OR parent_id = 0)
                 ORDER BY title ASC"
            );
            $stmtParents->execute($parentScopeParams);
        } else {
            $stmtParents = $pdo->query(
                "SELECT id, title
                 FROM blog_categories
                 WHERE parent_id IS NULL OR parent_id = 0
                 ORDER BY title ASC"
            );
        }
        $parentCategories = $stmtParents->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $parentCategories = [];
    }
}

include __DIR__ . '/layout/header.php';
?>

<div class="p-8 max-w-2xl mx-auto">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Add <?php echo htmlspecialchars($categoryLabel); ?></h1>
    <a href="<?php echo htmlspecialchars($listUrl); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">← Back to <?php echo htmlspecialchars($categoriesLabel); ?></a>
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

  <form method="POST" class="bg-white rounded-lg shadow p-6 space-y-6">
    <input type="hidden" name="blog_scope" value="<?php echo htmlspecialchars($scope); ?>">
    <!-- Title -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Category Title *</label>
      <input type="text" name="title" required 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
    </div>

    <!-- Slug -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL)</label>
      <input type="text" name="slug" 
             class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
             placeholder="Auto-generated if empty"
             value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
    </div>

    <?php if ($supportsSubcategories && $parentColumnAvailable): ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category (Optional)</label>
      <select name="parent_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
        <option value="">-- No Parent (Main Category) --</option>
        <?php foreach ($parentCategories as $parentCategory): ?>
          <option value="<?php echo (int)$parentCategory['id']; ?>" <?php echo ((string)($_POST['parent_id'] ?? '') === (string)$parentCategory['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($parentCategory['title']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p class="mt-1 text-sm text-gray-500">Choose a parent only for Ayurvedh subcategories.</p>
    </div>
    <?php endif; ?>

    <!-- Description -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
      <textarea name="description" rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
    </div>

    <div class="pt-4 border-t flex justify-end">
      <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
        Save Category
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
