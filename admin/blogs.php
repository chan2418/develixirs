<?php
// Admin: Blog Listing Page
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Blog Posts';

// Fetch all blog posts
$blogs = [];
try {
    $stmt = $pdo->query("
        SELECT * FROM blogs 
        ORDER BY created_at DESC
    ");
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $blogs = [];
    error_log('Blog fetch error: ' . $e->getMessage());
}

include __DIR__ . '/layout/header.php';
?>

<div class="p-8">
  <div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Blog Posts</h1>
    <a href="/admin/add_blog.php" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
      + Add New Post
    </a>
  </div>

  <?php if (empty($blogs)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center text-slate-500">
      <p>No blog posts yet. Create your first post!</p>
    </div>
  <?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php foreach ($blogs as $blog): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  <?php echo htmlspecialchars($blog['title'] ?? 'Untitled'); ?>
                </div>
                <?php if (!empty($blog['meta_title'])): ?>
                  <div class="text-xs text-gray-500">SEO: <?php echo htmlspecialchars(substr($blog['meta_title'], 0, 40)); ?></div>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <?php echo htmlspecialchars($blog['author'] ?? 'Unknown'); ?>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <?php if (!empty($blog['is_published'])): ?>
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Published</span>
                <?php else: ?>
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft</span>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <?php echo date('M d, Y', strtotime($blog['created_at'])); ?>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <a href="/admin/edit_blog.php?id=<?php echo $blog['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                <a href="/admin/delete_blog.php?id=<?php echo $blog['id']; ?>" 
                   onclick="return confirm('Are you sure you want to delete this blog post?');"
                   class="text-red-600 hover:text-red-900">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
