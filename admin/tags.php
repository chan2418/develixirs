<?php
// admin/tags.php – simple tag manager (create + list + toggle active/delete)
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// small helpers
function flash_get($key) {
    if (!isset($_SESSION[$key])) return null;
    $v = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $v;
}
function flash_set($key, $val) {
    $_SESSION[$key] = $val;
}
function slugify($text) {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^\\pL\\d]+~u', '-', $text);
    $text = strtolower($text);
    $text = preg_replace('~-+~', '-', $text);
    $text = trim($text, '-');
    if ($text === '') {
        $text = 'tag-' . substr(bin2hex(random_bytes(6)), 0, 8);
    }
    return $text;
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// handle actions (toggle / delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Invalid form token, please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $errors[] = 'Tag name is required.';
            } else {
                try {
                    // build slug
                    $slug = slugify($name);

                    // ensure unique slug
                    $baseSlug = $slug;
                    $i = 1;
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tags WHERE slug = ?");
                    while (true) {
                        $stmtCheck->execute([$slug]);
                        $count = (int)$stmtCheck->fetchColumn();
                        if ($count === 0) break;
                        $slug = $baseSlug . '-' . $i++;
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO tags (name, slug, is_active)
                        VALUES (:name, :slug, 1)
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':slug' => $slug,
                    ]);

                    flash_set('success_msg', 'Tag created successfully.');
                    header('Location: tags.php');
                    exit;
                } catch (Throwable $e) {
                    $errors[] = 'Database error while creating tag: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'toggle') {
            $id = $_POST['id'] ?? '';
            if (ctype_digit($id)) {
                try {
                    // flip is_active
                    $stmt = $pdo->prepare("UPDATE tags SET is_active = 1 - is_active WHERE id = ?");
                    $stmt->execute([(int)$id]);
                    flash_set('success_msg', 'Tag status updated.');
                    header('Location: tags.php');
                    exit;
                } catch (Throwable $e) {
                    $errors[] = 'Failed to update tag: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if (ctype_digit($id)) {
                try {
                    // first remove relations from product_tags
                    $stmtP = $pdo->prepare("DELETE FROM product_tags WHERE tag_id = ?");
                    $stmtP->execute([(int)$id]);

                    $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
                    $stmt->execute([(int)$id]);

                    flash_set('success_msg', 'Tag deleted.');
                    header('Location: tags.php');
                    exit;
                } catch (Throwable $e) {
                    $errors[] = 'Failed to delete tag: ' . $e->getMessage();
                }
            }
        }
    }

    if (!empty($errors)) {
        flash_set('form_errors', $errors);
        flash_set('old', $_POST);
        header('Location: tags.php');
        exit;
    }
}

// load flashes
$errors  = flash_get('form_errors') ?? [];
$success = flash_get('success_msg');
$old     = flash_get('old') ?? [];

// load tags list
$tags = [];
try {
    $tags = $pdo->query("
        SELECT id, name, slug, is_active, created_at
        FROM tags
        ORDER BY created_at DESC, name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errors[] = 'Unable to load tags: ' . $e->getMessage();
}

// header layout
$page_title = 'Product Tags';
include __DIR__ . '/layout/header.php';
?>
<link rel="stylesheet" href="/assets/css/admin.css">

<div class="max-w-[1100px] mx-auto py-6">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-800">Product Tags</h1>
      <p class="text-sm text-slate-500 mt-1">
        Create and manage product tags. These tags can be assigned to products in the
        <strong>Add / Edit Product</strong> screen.
      </p>
    </div>
    <a href="add_product.php" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm">
      + Add Product
    </a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="bg-white p-4 rounded-lg border border-red-100 mb-4">
      <div class="font-bold text-red-700 mb-1">There were some problems</div>
      <ul class="text-sm text-red-600 list-disc pl-5">
        <?php foreach ($errors as $e): ?>
          <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="bg-white p-4 rounded-lg border border-green-100 mb-4">
      <div class="font-bold text-green-700"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
  <?php endif; ?>

  <!-- Create new tag -->
  <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6 shadow-sm">
    <h2 class="text-sm font-semibold text-slate-800 mb-3">Create New Tag</h2>
    <form action="tags.php" method="post" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="action" value="create">

      <div class="flex-1 w-full">
        <label class="block text-xs font-semibold text-slate-600 mb-1">Tag Name</label>
        <input
          type="text"
          name="name"
          class="w-full p-2.5 border rounded-lg text-sm"
          placeholder="e.g. Hair Fall, Anti Dandruff, Skin Glow"
          value="<?php echo isset($old['name']) ? htmlspecialchars($old['name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
          required
        >
      </div>

      <button type="submit"
              class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold">
        Add Tag
      </button>
    </form>
    <p class="text-xs text-slate-500 mt-2">
      Slug will be generated automatically from the name (e.g. <code>Hair Fall</code> → <code>hair-fall</code>).
    </p>
  </div>

  <!-- Tags list -->
  <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-sm font-semibold text-slate-800">Existing Tags</h2>
      <span class="text-xs text-slate-500">
        Total: <?php echo count($tags); ?>
      </span>
    </div>

    <?php if (empty($tags)): ?>
      <p class="text-sm text-slate-500">No tags created yet. Add your first tag above.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b bg-slate-50">
              <th class="text-left py-2 px-2">ID</th>
              <th class="text-left py-2 px-2">Name</th>
              <th class="text-left py-2 px-2">Slug</th>
              <th class="text-left py-2 px-2">Status</th>
              <th class="text-left py-2 px-2">Created</th>
              <th class="text-right py-2 px-2">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($tags as $t): ?>
            <tr class="border-b last:border-0">
              <td class="py-2 px-2 text-xs text-slate-500">
                <?php echo (int)$t['id']; ?>
              </td>
              <td class="py-2 px-2">
                <?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?>
              </td>
              <td class="py-2 px-2 text-xs text-slate-500">
                <?php echo htmlspecialchars($t['slug'], ENT_QUOTES, 'UTF-8'); ?>
              </td>
              <td class="py-2 px-2">
                <?php if (!empty($t['is_active'])): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700">
                    Active
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600">
                    Inactive
                  </span>
                <?php endif; ?>
              </td>
              <td class="py-2 px-2 text-xs text-slate-500">
                <?php echo htmlspecialchars($t['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
              </td>
              <td class="py-2 px-2 text-right space-x-2">
                <!-- Toggle active -->
                <form action="tags.php" method="post" class="inline">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                  <button type="submit"
                          class="text-xs px-2 py-1 rounded border border-slate-200 text-slate-600 hover:bg-slate-50">
                    <?php echo !empty($t['is_active']) ? 'Deactivate' : 'Activate'; ?>
                  </button>
                </form>

                <!-- Delete -->
                <form action="tags.php" method="post" class="inline"
                      onsubmit="return confirm('Delete this tag? It will be removed from all products.');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                  <button type="submit"
                          class="text-xs px-2 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50">
                    Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>