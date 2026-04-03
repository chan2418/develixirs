<?php
// admin/pages/index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/db.php';
// Use _auth.php if available for consistent session/auth
if (file_exists(__DIR__ . '/../_auth.php')) {
    require_once __DIR__ . '/../_auth.php';
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

$page_title = 'Manage Pages';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
    $stmt->execute([$delId]);
    $_SESSION['flash_success'] = "Page deleted successfully.";
    header("Location: index.php");
    exit;
}

// Fetch Pages
try {
    $stmt = $pdo->query("SELECT * FROM pages ORDER BY created_at DESC");
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If Table doesn't exist (Error 42S02), prompt to run setup
    if ($e->getCode() == '42S02' || strpos($e->getMessage(), "doesn't exist") !== false) {
        die('
        <div style="font-family:sans-serif; max-width:600px; margin:50px auto; padding:20px; border:1px solid #ffcccc; background:#fff5f5; border-radius:8px; color:#cc0000;">
            <h2 style="margin-top:0;">⚠️ Setup Required</h2>
            <p>The <strong>pages</strong> database table is missing.</p>
            <p>Please run the setup script to create it:</p>
            <p>
                <a href="/admin/setup_page_builder.php" style="background:#cc0000; color:#fff; text-decoration:none; padding:10px 20px; border-radius:5px; font-weight:bold;">Run Setup Script</a>
            </p>
            <hr style="border:0; border-top:1px solid #ffcccc; margin:20px 0;">
            <small style="color:#666;">Or run this SQL manually in your database:</small>
            <pre style="background:#fff; border:1px solid #ddd; padding:10px; overflow:auto; color:#333; font-size:12px;">' . htmlspecialchars("CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    type VARCHAR(50) DEFAULT 'custom',
    status VARCHAR(20) DEFAULT 'draft',
    content JSON,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords TEXT,
    is_public BOOLEAN DEFAULT 1,
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;") . '</pre>
        </div>');
    }
    throw $e;
}

// Include Header
// Use standard admin header to ensure sidebar and styles are consistent
$pathToHeader = __DIR__ . '/../layout/header.php';
if (file_exists($pathToHeader)) {
    include $pathToHeader;
} else {
    // Fallback if structure is different
    echo '<div style="color:red; padding:20px;">Error: Layout header not found at ' . htmlspecialchars($pathToHeader) . '</div>';
}
?>

<!-- Content Wrapper -->
<div class="flex-1 flex flex-col">
    <!-- Top bar -->
    <div class="bg-white shadow px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="/admin/dashboard.php" class="text-slate-500 hover:text-slate-800"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
                <h1 class="text-xl font-bold">Manage Pages</h1>
            </div>
            <a href="editor.php" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition">
                <i class="fa-solid fa-plus mr-2"></i> Create New Page
            </a>
    </div>

    <div class="p-8">
        <!-- Messages -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Slug / URL</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($pages)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                No pages found. Create your first page!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pages as $p): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($p['title']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <a href="/page.php?slug=<?= htmlspecialchars($p['slug']) ?>" target="_blank" class="text-indigo-600 hover:underline">
                                        /<?= htmlspecialchars($p['slug']) ?> <i class="fa-solid fa-external-link-alt text-xs ml-1"></i>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded border border-gray-200 uppercase tracking-wide">
                                        <?= htmlspecialchars($p['type']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($p['status'] === 'published'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Published
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Draft
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?= date('M d, Y', strtotime($p['updated_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="editor.php?id=<?= $p['id'] ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</a>
                                    <a href="index.php?delete_id=<?= $p['id'] ?>" onclick="return confirm('Are you sure you want to delete this page?');" class="text-red-600 hover:text-red-900 font-medium">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$pathToFooter = __DIR__ . '/../layout/footer.php';
if (file_exists($pathToFooter)) {
    include $pathToFooter;
} else {
    echo '</body></html>';
}
?>
