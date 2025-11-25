<?php
// admin/categories.php (NEW UI - DEVELIXIR ADMIN)

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// page title for sidebar highlighting
$page_title = "Categories";

// NEW UI HEADER
include __DIR__ . '/layout/header.php';

/* ---------- FLASH MESSAGES ---------- */
function flash_get($k) {
    if (!empty($_SESSION[$k])) { $v = $_SESSION[$k]; unset($_SESSION[$k]); return $v; }
    return null;
}
$errors  = flash_get('form_errors') ?: [];
$success = flash_get('success_msg') ?: null;
$old     = flash_get('old') ?: [];

/* ---------- DETECT LABEL FIELD + ENSURE IMAGE COLUMN ---------- */
try {
    $cols   = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $fields = array_column($cols, 'Field');
} catch (Exception $e) {
    $fields = [];
}

// label field
$labelField = in_array('title',$fields) ? 'title' : (in_array('name',$fields) ? 'name' : null);

// ensure image column exists
if (!in_array('image', $fields)) {
    try {
        // add a nullable image column
        $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(255) NULL");
        $fields[] = 'image';
    } catch (Exception $e) {
        // silently ignore if it fails (no hard crash in UI)
    }
}

/* ---------- FETCH EDIT ---------- */
$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ---------- FETCH CATEGORY LIST ---------- */
$list = [];
try {
    $stmt = $pdo->query("
        SELECT id,
               $labelField AS title,
               slug,
               parent_id,
               description,
               created_at,
               image
        FROM categories
        ORDER BY COALESCE(parent_id,id),
                 parent_id IS NOT NULL,
                 title ASC
    ");
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

/* ---------- BUILD TREE ---------- */
$byId = [];
foreach ($list as $c) $byId[$c['id']] = $c + ['children'=>[]];

$roots=[];
foreach ($byId as $id=>$node) {
    if ($node['parent_id'] && isset($byId[$node['parent_id']])) {
        $byId[$node['parent_id']]['children'][] = &$byId[$id];
    } else $roots[] = &$byId[$id];
}

/* ---------- HELPERS ---------- */
function old_val($key,$default='') {
    global $old,$edit;
    if(isset($old[$key])) return htmlspecialchars($old[$key]);
    if($edit && isset($edit[$key])) return htmlspecialchars($edit[$key]);
    return htmlspecialchars($default);
}

function render_options($nodes,$level=0,$selected=null,$exclude=null){
    $html='';
    foreach($nodes as $n){
        if($exclude && $exclude==$n['id']) continue;
        $indent=str_repeat("&nbsp;&nbsp;",$level);
        $sel = ($selected==$n['id']) ? "selected" : "";
        $html.="<option value='{$n['id']}' $sel>$indent".htmlspecialchars($n['title'])."</option>";
        if(!empty($n['children'])){
            $html.=render_options($n['children'],$level+1,$selected,$exclude);
        }
    }
    return $html;
}
?>

<div class="max-w-[1200px] mx-auto py-6">

    <!-- HEADER -->
    <div class="flex items-start justify-between mb-6">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800">Categories</h2>
            <p class="text-sm text-slate-500">Manage category structure and nesting.</p>
        </div>

        <div class="flex gap-3">
            <a href="products.php" class="px-4 py-2 rounded-lg border border-gray-300 bg-white">← Back to Products</a>
            <a href="add_product.php" class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold">Add Product</a>
        </div>
    </div>


    <!-- FLASH MESSAGES -->
    <?php if($errors): ?>
        <div class="p-4 mb-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <strong>Errors:</strong>
            <?php foreach($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="p-4 mb-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>


    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- FORM CARD -->
        <div class="bg-white border border-gray-200 p-6 rounded-xl shadow-sm">

            <h3 class="text-lg font-bold mb-1"><?= $edit ? "Edit Category" : "Add Category" ?></h3>
            <p class="text-sm text-slate-500 mb-4"><?= $edit ? "Modify this category" : "Create a new category" ?></p>

            <!-- IMPORTANT: enctype for file upload -->
            <form action="save_category.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        echo $_SESSION['csrf_token'];
    ?>">

    <?php if($edit): ?>
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <?php endif; ?>

    <!-- Title -->
    <label class="block font-semibold mb-1">Title</label>
    <input name="title" class="w-full p-2 rounded-lg border border-gray-300 mb-4"
           value="<?= old_val('title') ?>" required>

    <!-- Parent -->
    <label class="block font-semibold mb-1">Parent Category</label>
    <select name="parent_id" class="w-full p-2 rounded-lg border border-gray-300 mb-4">
        <option value="">-- None --</option>
        <?= render_options($roots,0, $edit['parent_id'] ?? null, $edit['id'] ?? null) ?>
    </select>

    <!-- Image -->
    <label class="block font-semibold mb-1">Category Image</label>
    <input type="file" name="image" class="w-full mb-4" accept="image/*">

    <!-- Description -->
    <label class="block font-semibold mb-1">Description</label>
    <textarea name="description" rows="4"
              class="w-full p-2 rounded-lg border border-gray-300"><?= old_val('description') ?></textarea>

    <div class="flex justify-end gap-3 mt-4">
        <?php if($edit): ?>
            <a href="categories.php" class="px-4 py-2 rounded-lg border">Cancel</a>
        <?php endif; ?>
        <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold">
            <?= $edit ? "Update" : "Add Category" ?>
        </button>
    </div>
</form>
        </div>

        <!-- CATEGORY TABLE -->
        <div class="bg-white border border-gray-200 p-6 rounded-xl shadow-sm">

            <div class="flex justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold">All Categories</h3>
                    <p class="text-sm text-slate-500">Nested (children are indented)</p>
                </div>

                <input id="catSearch" type="search"
                       placeholder="Search..."
                       class="p-2 border border-gray-300 rounded-lg w-48" />
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-3 text-left">Image</th> <!-- NEW -->
                            <th class="p-3 text-left">Title</th>
                            <th class="p-3 text-left">Slug</th>
                            <th class="p-3 text-left">Parent</th>
                            <th class="p-3 text-left">Created</th>
                            <th class="p-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="catBody">
                        <?php
                        $map = []; foreach ($list as $c) $map[$c['id']]=$c['title'];

                        $renderRow = function($node,$level=0) use (&$renderRow,$map){
                            $indent = str_repeat("&nbsp;&nbsp;&nbsp;",$level);
                            $parent = $node['parent_id'] ? ($map[$node['parent_id']] ?? '-') : '-';

                            // path for category image
                            $imgSrc = '';
                            if (!empty($node['image'])) {
                                $imgSrc = '/assets/uploads/categories/' . ltrim($node['image'], '/');
                            }

                            echo "<tr class='border-b' data-title='".strtolower($node['title'])."'>";

                            // IMAGE CELL
                            echo "<td class='p-3'>";
                            if ($imgSrc) {
                                echo "<img src='".htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8')."' alt='' class='w-10 h-10 object-cover rounded border border-gray-200'>";
                            } else {
                                echo "<span class='text-xs text-slate-400'>—</span>";
                            }
                            echo "</td>";

                            // TITLE CELL
                            echo "<td class='p-3'>{$indent}<strong>".htmlspecialchars($node['title'])."</strong></td>";
                            echo "<td class='p-3'>".htmlspecialchars($node['slug'])."</td>";
                            echo "<td class='p-3'>".htmlspecialchars($parent)."</td>";
                            echo "<td class='p-3'>".date('d M Y',strtotime($node['created_at']))."</td>";

                            echo "<td class='p-3'>
                                    <a href='categories.php?edit={$node['id']}' class='text-indigo-600 font-semibold'>Edit</a>
                                    <a href='delete_category.php?id={$node['id']}'
                                       onclick=\"return confirm('Delete category?');\"
                                       class='ml-3 text-red-600 font-semibold'>Delete</a>
                                  </td>";
                            echo "</tr>";

                            if(!empty($node['children'])){
                                foreach($node['children'] as $ch){
                                    $renderRow($ch,$level+1);
                                }
                            }
                        };

                        foreach($roots as $r) $renderRow($r);
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>


<script>
// LIVE SEARCH
const input = document.getElementById("catSearch");
input.addEventListener("input", () => {
    const q = input.value.toLowerCase();
    document.querySelectorAll("#catBody tr").forEach(row => {
        const t = row.dataset.title;
        row.style.display = !q || t.includes(q) ? "" : "none";
    });
});
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>