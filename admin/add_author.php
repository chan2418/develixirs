<?php
// admin/add_author.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Add Author';
$errors = [];
$success = '';

// Fetch Users for linkage dropdown
$users = [];
try {
    // Only showing users/admins to link. Adjust query as needed (e.g. role='admin').
    // For now showing all users as per request to "allow linking an existing user"
    $stmtUsers = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC");
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // optional: $errors[] = "Error loading users.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $userId = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $profilePic = null;

    // Validation
    if (empty($name)) {
        $errors[] = "Author Name is required.";
    }

    // Image Upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid image format. Allowed: " . implode(', ', $allowed);
        } else {
            $uploadDir = __DIR__ . '/../assets/uploads/authors';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $filename = uniqid('author_') . '.' . $ext;
            $targetPath = $uploadDir . '/' . $filename;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
                $profilePic = '/assets/uploads/authors/' . $filename;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO authors (name, profile_pic, user_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $profilePic, $userId]);
            
            // Redirect
            header('Location: authors.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/layout/header.php';
?>

<div class="p-8">
  <div class="mb-6">
    <a href="/admin/authors.php" class="text-indigo-600 hover:text-indigo-800 mb-2 inline-block">&larr; Back to Authors</a>
    <h1 class="text-2xl font-bold text-slate-800">Add New Author</h1>
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

  <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
      
      <!-- Name -->
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Author Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" id="name" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. John Doe" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
      </div>

      <!-- Profile Pic -->
      <div>
        <label for="profile_pic" class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
        <div class="flex items-center gap-4">
            <input type="file" name="profile_pic" id="profile_pic" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        </div>
        <p class="text-xs text-gray-500 mt-1">Recommended: Square image (200x200px). Max 2MB.</p>
      </div>

      <!-- Linked User (Optional) -->
      <div>
        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Link to System User (Optional)</label>
        <select name="user_id" id="user_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- No linked user --</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= (isset($_POST['user_id']) && $_POST['user_id'] == $u['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-500 mt-1">If linked, this author profile represents a system user (e.g. an Admin).</p>
      </div>

      <div class="pt-4 border-t border-gray-100 flex justify-end">
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition shadow-sm">
            Create Author
        </button>
      </div>

    </form>
  </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
