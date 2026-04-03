<?php
// admin/blog_tags.php - Blog Tags Management with SEO
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/blog_scope_helper.php';

$scope = admin_blog_scope_from_request();
$tagsLabel = admin_blog_scope_tags_label($scope);
$tagLabel = admin_blog_scope_tag_label($scope);
$pageTitle = $tagsLabel;
$errors = [];
$success = '';
$scopeColumnAvailable = admin_blog_ensure_tag_scope_column($pdo);
$blogScopeColumnAvailable = admin_blog_ensure_scope_column($pdo);
$listUrl = admin_blog_scope_url('/admin/blog_tags.php', $scope);
$addUrl = admin_blog_scope_url('/admin/add_blog_tag.php', $scope);

// Fetch Tags with post count
$tags = [];
try {
    if ($scopeColumnAvailable) {
        [$tagScopeClause, $tagScopeParams] = admin_blog_scope_taxonomy_filter_clause($scope, 'bt.blog_scope', 'tag_scope');

        if ($blogScopeColumnAvailable) {
            [$blogScopeClause, $blogScopeParams] = admin_blog_scope_filter_clause($scope, 'b.blog_type');
            $stmt = $pdo->prepare("
                SELECT bt.*, COUNT(DISTINCT CASE WHEN {$blogScopeClause} THEN bpt.blog_id END) as post_count
                FROM blog_tags bt
                LEFT JOIN blog_post_tags bpt ON bt.id = bpt.tag_id
                LEFT JOIN blogs b ON b.id = bpt.blog_id
                WHERE {$tagScopeClause}
                GROUP BY bt.id
                ORDER BY bt.name ASC
            ");
            $stmt->execute(array_merge($tagScopeParams, $blogScopeParams));
        } else {
            $stmt = $pdo->prepare("
                SELECT bt.*, COUNT(DISTINCT bpt.blog_id) as post_count
                FROM blog_tags bt
                LEFT JOIN blog_post_tags bpt ON bt.id = bpt.tag_id
                WHERE {$tagScopeClause}
                GROUP BY bt.id
                ORDER BY bt.name ASC
            ");
            $stmt->execute($tagScopeParams);
        }
    } else {
        $stmt = $pdo->query("
            SELECT bt.*, COUNT(DISTINCT bpt.blog_id) as post_count
            FROM blog_tags bt
            LEFT JOIN blog_post_tags bpt ON bt.id = bpt.tag_id
            GROUP BY bt.id
            ORDER BY bt.name ASC
        ");
    }
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching tags: " . $e->getMessage();
}

include __DIR__ . '/layout/header.php';
?>

<div class="p-8">
  <div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($tagsLabel); ?></h1>
        <p class="text-slate-500 text-sm mt-1">Manage tags with SEO metadata for <?php echo admin_blog_scope_is_ayurvedh($scope) ? 'Ayurvedh blog posts' : 'blog posts'; ?></p>
    </div>
    <a href="<?php echo htmlspecialchars($addUrl); ?>" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
      + Add New Tag
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

  <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">Tag Name</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SEO Title</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posts</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php if (empty($tags)): ?>
          <tr>
            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
              No <?php echo htmlspecialchars(strtolower($tagsLabel)); ?> found.
              <a href="<?php echo htmlspecialchars($addUrl); ?>" class="text-indigo-600 hover:text-indigo-900">Add your first tag</a>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($tags as $tag): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900 break-words"><?php echo htmlspecialchars($tag['name']); ?></div>
                <?php if (!empty($tag['description'])): ?>
                  <div class="text-xs text-gray-500 mt-1 truncate max-w-xs"><?php echo htmlspecialchars(substr($tag['description'], 0, 60)) . (strlen($tag['description']) > 60 ? '...' : ''); ?></div>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($tag['slug']); ?></code>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900 max-w-xs break-words">
                  <?php echo !empty($tag['seo_title']) ? htmlspecialchars($tag['seo_title']) : '<span class="text-gray-400 italic">Not set</span>'; ?>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                  <?php echo $tag['post_count']; ?> posts
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <a href="<?php echo htmlspecialchars(admin_blog_scope_url('/admin/edit_blog_tag.php', $scope, ['id' => (int)$tag['id']])); ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                <a href="<?php echo htmlspecialchars(admin_blog_scope_url('/admin/delete_blog_tag.php', $scope, ['id' => (int)$tag['id']])); ?>"
                   onclick="return confirm('Are you sure you want to delete this <?php echo htmlspecialchars(strtolower($tagLabel)); ?>?');"
                   style="display: inline-block; padding: 5px 10px; background-color: #fee2e2; color: #b91c1c; border-radius: 4px; text-decoration: none; font-weight: 500;">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($tags)): ?>
    <div class="mt-4 text-sm text-gray-500">
      Total: <?php echo count($tags); ?> <?php echo htmlspecialchars(strtolower($tagLabel)); ?>(s)
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
