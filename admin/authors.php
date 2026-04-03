<?php
// admin/authors.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Blog Authors';
$errors = [];
$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Check if author is used in any blogs (Optional safety)
        // For now, we allow delete and set author_id = NULL via FK constraint
        $stmt = $pdo->prepare("DELETE FROM authors WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Author deleted successfully.";
    } catch (PDOException $e) {
        $errors[] = "Error deleting author: " . $e->getMessage();
    }
}

// Fetch Authors
$authors = [];
try {
    // Join with users table to show linked user name if any
    $stmt = $pdo->query("
        SELECT a.*, u.name as linked_user 
        FROM authors a 
        LEFT JOIN users u ON a.user_id = u.id 
        ORDER BY a.name ASC
    ");
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching authors: " . $e->getMessage();
}

include __DIR__ . '/layout/header.php';
?>

<div class="p-8">
  <div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Blog Authors</h1>
        <p class="text-slate-500 text-sm mt-1">Manage authors for your blog posts</p>
    </div>
    <a href="/admin/add_author.php" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
      + Add New Author
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
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Linked User</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php if (empty($authors)): ?>
            <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                    No authors found. Create one to get started.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($authors as $author): ?>
                <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <?php if (!empty($author['profile_pic'])): ?>
                                <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars($author['profile_pic']); ?>" alt="">
                            <?php else: ?>
                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($author['name']); ?></div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php if ($author['linked_user']): ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            <?php echo htmlspecialchars($author['linked_user']); ?>
                        </span>
                    <?php else: ?>
                        <span class="text-gray-400">-</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo date('M d, Y', strtotime($author['created_at'])); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="/admin/edit_author.php?id=<?php echo $author['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                    <a href="/admin/authors.php?delete=<?php echo $author['id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this author?');"
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
