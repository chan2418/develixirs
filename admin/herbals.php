<?php
// admin/herbals.php

require_once __DIR__ . '/_auth.php'; 
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// --- SELF-HEALING DB SETUP ---
try {
    // 1. Create herbals table
    $sqlCon = "CREATE TABLE IF NOT EXISTS herbals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        image VARCHAR(255) NULL,
        description TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlCon);

    // 2. Add herbal_id to products if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'herbal_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN herbal_id INT NULL DEFAULT NULL");
    }
} catch (Exception $e) {
    error_log("Herbal DB Setup Error: " . $e->getMessage());
}
// -----------------------------

$page_title = "Shop by Herbal";
include __DIR__ . '/layout/header.php';

/* ---------- FLASH MESSAGES ---------- */
function flash_get($k) {
    if (!empty($_SESSION[$k])) { $v = $_SESSION[$k]; unset($_SESSION[$k]); return $v; }
    return null;
}
$success = flash_get('success_msg');
$error   = flash_get('error_msg');

/* ---------- FETCH LIST ---------- */
$herbals = [];
try {
    $stmt = $pdo->query("SELECT * FROM herbals ORDER BY title ASC");
    $herbals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "DB Error: " . $e->getMessage();
}
?>

<div class="max-w-[1200px] mx-auto py-6">

    <!-- HEADER -->
    <div class="flex items-start justify-between mb-6">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800">Shop by Herbal</h2>
            <p class="text-sm text-slate-500">Manage herbal categories for product filtering.</p>
        </div>

        <div class="flex gap-3">
            <a href="products.php" class="px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50">← Back to Products</a>
            <a href="/admin/add_herbal.php" class="px-4 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 shadow shadow-green-200">+ Add Herbal</a>
        </div>
    </div>

    <!-- MESSAGES -->
    <?php if($success): ?>
        <div class="p-4 mb-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="p-4 mb-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- TABLE -->
    <div class="bg-white border border-gray-200 p-6 rounded-xl shadow-sm">
        
        <?php if(empty($herbals)): ?>
            <div class="text-center py-10 text-gray-400">
                <i class="fa-solid fa-leaf text-4xl mb-3 opacity-50"></i>
                <p>No herbal categories found. Add one to get started.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-3 text-left w-16">Image</th>
                            <th class="p-3 text-left">Title</th>
                            <th class="p-3 text-left">Slug</th>
                            <th class="p-3 text-left w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($herbals as $h): 
                            $imgSrc = '/assets/images/avatar-default.png'; // fallback
                            if(!empty($h['image'])) {
                                if (strpos($h['image'], 'http') === 0 || strpos($h['image'], '/') === 0) {
                                    $imgSrc = $h['image'];
                                } else {
                                    $imgSrc = '/assets/uploads/herbals/' . $h['image']; 
                                }
                            }
                        ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="p-3">
                                <img src="<?= htmlspecialchars($imgSrc) ?>" class="w-10 h-10 object-cover rounded border border-gray-200 bg-white" alt="">
                            </td>
                            <td class="p-3 font-semibold text-slate-700"><?= htmlspecialchars($h['title']) ?></td>
                            <td class="p-3 text-gray-500"><?= htmlspecialchars($h['slug']) ?></td>
                            <td class="p-3">
                                <div class="flex gap-3">
                                    <a href="/admin/edit_herbal.php?id=<?= $h['id'] ?>" class="text-indigo-600 font-semibold hover:text-indigo-800">Edit</a>
                                    <a href="/admin/delete_herbal.php?id=<?= $h['id'] ?>" onclick="return confirm('Delete this herbal category?');" class="text-red-600 font-semibold hover:text-red-800">Delete</a>
                                </div>
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
