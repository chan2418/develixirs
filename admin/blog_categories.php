<?php
// admin/blog_categories.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/blog_scope_helper.php';

$scope = admin_blog_scope_from_request();
$categoriesLabel = admin_blog_scope_categories_label($scope);
$categoryLabel = admin_blog_scope_category_label($scope);
$pageTitle = $categoriesLabel;
$errors = [];
$success = '';
$scopeColumnAvailable = admin_blog_ensure_category_scope_column($pdo);
$listUrl = admin_blog_scope_url('/admin/blog_categories.php', $scope);
$addUrl = admin_blog_scope_url('/admin/add_blog_category.php', $scope);

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        if ($scopeColumnAvailable) {
            [$scopeClause, $scopeParams] = admin_blog_scope_taxonomy_filter_clause($scope, 'blog_scope', 'cat_del_scope');
            $stmt = $pdo->prepare("DELETE FROM blog_categories WHERE id = :id AND {$scopeClause}");
            $stmt->execute(array_merge([':id' => $id], $scopeParams));
        } else {
            $stmt = $pdo->prepare("DELETE FROM blog_categories WHERE id = ?");
            $stmt->execute([$id]);
        }
        $success = "Category deleted successfully.";
    } catch (PDOException $e) {
        $errors[] = "Error deleting category: " . $e->getMessage();
    }
}

// Handle Add/Edit via POST (simple inline handling or redirection can be used)
// We will use separate pages for Add/Edit as per plan, but list them here.

// Fetch Categories
$categories = [];
try {
    if ($scopeColumnAvailable) {
        [$scopeClause, $scopeParams] = admin_blog_scope_taxonomy_filter_clause($scope, 'blog_scope', 'cat_list_scope');
        $stmt = $pdo->prepare("SELECT * FROM blog_categories WHERE {$scopeClause} ORDER BY title ASC");
        $stmt->execute($scopeParams);
    } else {
        $stmt = $pdo->query("SELECT * FROM blog_categories ORDER BY title ASC");
    }
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching categories: " . $e->getMessage();
}

include __DIR__ . '/layout/header.php';
?>

<div class="p-8">
  <div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($categoriesLabel); ?></h1>
        <p class="text-slate-500 text-sm mt-1">Manage categories for your <?php echo admin_blog_scope_is_ayurvedh($scope) ? 'Ayurvedh blog posts' : 'blog posts'; ?></p>
    </div>
    <a href="<?php echo htmlspecialchars($addUrl); ?>" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
      + Add New Category
    </a>
  </div>

  <?php if ($success): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
      <?php echo htmlspecialchars($success); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
      <ul class="list-disc list-inside">
        <?php foreach ($errors as $error): ?>
          <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php if (empty($categories)): ?>
            <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                    No <?php echo htmlspecialchars(strtolower($categoriesLabel)); ?> found. Create one to get started.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
                <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <?php echo htmlspecialchars($cat['title']); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo htmlspecialchars($cat['slug']); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo date('M d, Y', strtotime($cat['created_at'])); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="<?php echo htmlspecialchars(admin_blog_scope_url('/admin/edit_blog_category.php', $scope, ['id' => (int)$cat['id']])); ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                    <a href="<?php echo htmlspecialchars(admin_blog_scope_url('/admin/blog_categories.php', $scope, ['delete' => (int)$cat['id']])); ?>" 
                       onclick="return confirm('Are you sure you want to delete this <?php echo htmlspecialchars(strtolower($categoryLabel)); ?>?');"
                       class="text-red-600 hover:text-red-900">Delete</a>
                </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
