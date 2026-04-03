<?php
// admin/edit_author.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Edit Author';
$errors = [];
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: authors.php');
    exit;
}

// Fetch Author
$author = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    $author = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching author: " . $e->getMessage();
}

if (!$author) {
    die("Author not found.");
}

// Fetch Users for linkage dropdown
$users = [];
try {
    $stmtUsers = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC");
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ignore
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $userId = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $profilePic = $author['profile_pic']; // keep existing by default

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
            $stmt = $pdo->prepare("UPDATE authors SET name = ?, profile_pic = ?, user_id = ? WHERE id = ?");
            $stmt->execute([$name, $profilePic, $userId, $id]);
            
            $success = "Author updated successfully.";
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM authors WHERE id = ?");
            $stmt->execute([$id]);
            $author = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
    <h1 class="text-2xl font-bold text-slate-800">Edit Author</h1>
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

  <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
      
      <!-- Name -->
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Author Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" id="name" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="<?php echo htmlspecialchars($author['name']); ?>">
      </div>

      <!-- Profile Pic -->
      <div>
        <label for="profile_pic" class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
        <div class="flex items-center gap-4 mb-2">
            <?php if (!empty($author['profile_pic'])): ?>
                <img src="<?php echo htmlspecialchars($author['profile_pic']); ?>" alt="Current Avatar" class="h-16 w-16 rounded-full object-cover border">
            <?php else: ?>
                <div class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 border">
                    <i class="fa-solid fa-user text-2xl"></i>
                </div>
            <?php endif; ?>
            <div class="flex-1">
                <input type="file" name="profile_pic" id="profile_pic" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-1">Upload new image to replace current one. Square image (200x200px) recommended.</p>
      </div>

      <!-- Linked User (Optional) -->
      <div>
        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Link to System User (Optional)</label>
        <select name="user_id" id="user_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- No linked user --</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($author['user_id'] == $u['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
      </div>

      <div class="pt-4 border-t border-gray-100 flex justify-end">
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition shadow-sm">
            Update Author
        </button>
      </div>

    </form>
  </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
