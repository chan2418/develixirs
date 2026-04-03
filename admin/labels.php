<?php
// admin/labels.php – Label Manager
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helpers
function flash_get($key) {
    if (!isset($_SESSION[$key])) return null;
    $v = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $v;
}
function flash_set($key, $val) {
    $_SESSION[$key] = $val;
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Invalid form token, please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // CREATE
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $color = trim($_POST['color'] ?? '#000000');
            $text_color = trim($_POST['text_color'] ?? '#FFFFFF');

            if ($name === '') {
                $errors[] = 'Label name is required.';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO product_labels (name, color, text_color, is_active)
                        VALUES (:name, :color, :text_color, 1)
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':color' => $color,
                        ':text_color' => $text_color
                    ]);
                    flash_set('success_msg', 'Label created successfully.');
                    header('Location: labels.php');
                    exit;
                } catch (Throwable $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        } 
        // TOGGLE STATUS
        elseif ($action === 'toggle') {
            $id = $_POST['id'] ?? '';
            if (ctype_digit($id)) {
                try {
                    $stmt = $pdo->prepare("UPDATE product_labels SET is_active = 1 - is_active WHERE id = ?");
                    $stmt->execute([(int)$id]);
                    flash_set('success_msg', 'Label status updated.');
                    header('Location: labels.php');
                    exit;
                } catch (Throwable $e) {
                    $errors[] = 'Failed to update label: ' . $e->getMessage();
                }
            }
        } 
        // DELETE
        elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if (ctype_digit($id)) {
                try {
                    // Update products to remove this label before deleting
                    $stmtP = $pdo->prepare("UPDATE products SET label_id = NULL WHERE label_id = ?");
                    $stmtP->execute([(int)$id]);

                    $stmt = $pdo->prepare("DELETE FROM product_labels WHERE id = ?");
                    $stmt->execute([(int)$id]);

                    flash_set('success_msg', 'Label deleted.');
                    header('Location: labels.php');
                    exit;
                } catch (Throwable $e) {
                    $errors[] = 'Failed to delete label: ' . $e->getMessage();
                }
            }
        }
    }

    if (!empty($errors)) {
        flash_set('form_errors', $errors);
        flash_set('old', $_POST);
        header('Location: labels.php');
        exit;
    }
}

// Load Data
$errors = flash_get('form_errors') ?? [];
$success = flash_get('success_msg');
$old = flash_get('old') ?? [];

$labels = [];
try {
    $labels = $pdo->query("SELECT * FROM product_labels ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errors[] = 'Unable to load labels: ' . $e->getMessage();
}

$page_title = 'Product Labels';
include __DIR__ . '/layout/header.php';
?>
<link rel="stylesheet" href="/assets/css/admin.css">

<div class="max-w-[1100px] mx-auto py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Product Labels</h1>
            <p class="text-sm text-slate-500 mt-1">
                Create labels like "New", "Hot", "Sale" to attach to your products.
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
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="bg-white p-4 rounded-lg border border-green-100 mb-4">
            <div class="font-bold text-green-700"><?php echo htmlspecialchars($success); ?></div>
        </div>
    <?php endif; ?>

    <!-- Create form -->
    <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Create New Label</h2>
        <form action="labels.php" method="post" class="flex flex-col sm:flex-row gap-4 items-end">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="action" value="create">

            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Label Name</label>
                <input type="text" name="name" 
                       class="w-full p-2.5 border rounded-lg text-sm"
                       placeholder="e.g. Best Seller"
                       value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Bg Color</label>
                <div class="flex items-center border rounded-lg overflow-hidden h-[42px]">
                    <input type="color" name="color" 
                           class="w-12 h-full p-0 border-0 cursor-pointer"
                           value="<?php echo htmlspecialchars($old['color'] ?? '#000000'); ?>">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Text Color</label>
                <div class="flex items-center border rounded-lg overflow-hidden h-[42px]">
                    <input type="color" name="text_color" 
                           class="w-12 h-full p-0 border-0 cursor-pointer"
                           value="<?php echo htmlspecialchars($old['text_color'] ?? '#FFFFFF'); ?>">
                </div>
            </div>

            <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold h-[42px]">
                Create Label
            </button>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-800">Existing Labels</h2>
            <span class="text-xs text-slate-500">Total: <?php echo count($labels); ?></span>
        </div>

        <?php if (empty($labels)): ?>
            <p class="text-sm text-slate-500">No labels created yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b bg-slate-50">
                            <th class="text-left py-2 px-2">Preview</th>
                            <th class="text-left py-2 px-2">Name</th>
                            <th class="text-left py-2 px-2">Status</th>
                            <th class="text-right py-2 px-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($labels as $l): ?>
                            <tr class="border-b last:border-0">
                                <td class="py-2 px-2">
                                    <span class="inline-block px-2 py-1 text-xs font-bold rounded"
                                          style="background-color: <?php echo htmlspecialchars($l['color']); ?>; color: <?php echo htmlspecialchars($l['text_color']); ?>;">
                                        <?php echo htmlspecialchars($l['name']); ?>
                                    </span>
                                </td>
                                <td class="py-2 px-2"><?php echo htmlspecialchars($l['name']); ?></td>
                                <td class="py-2 px-2">
                                    <?php if ($l['is_active']): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-2 text-right space-x-2">
                                    <form action="labels.php" method="post" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int)$l['id']; ?>">
                                        <button type="submit" class="text-xs px-2 py-1 rounded border border-slate-200 text-slate-600 hover:bg-slate-50">
                                            <?php echo $l['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <form action="labels.php" method="post" class="inline" onsubmit="return confirm('Delete this label?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$l['id']; ?>">
                                        <button type="submit" class="text-xs px-2 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50">
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
