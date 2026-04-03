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
            <h2 class="text-2xl font-extrabold text-slate-800">All Categories</h2>
            <p class="text-sm text-slate-500">Manage category structure and nesting.</p>
        </div>

        <div class="flex gap-3">
            <a href="products.php" class="px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50">← Back to Products</a>
            <a href="add_category.php" class="px-4 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 shadow shadow-green-200">+ Add Category</a>
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


    <!-- CATEGORY TABLE (Full Width) -->
    <div class="bg-white border border-gray-200 p-6 rounded-xl shadow-sm">

        <div class="flex justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold">Category Hierarchy</h3>
                <p class="text-sm text-slate-500">Nested view (children are indented)</p>
            </div>

            <input id="catSearch" type="search"
                   placeholder="Search..."
                   class="p-2 border border-gray-300 rounded-lg w-64 focus:ring-2 focus:ring-indigo-200 outline-none" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-3 text-left w-16">Image</th>
                        <th class="p-3 text-left">Title</th>
                        <th class="p-3 text-left">Slug</th>
                        <th class="p-3 text-left">Parent</th>
                        <th class="p-3 text-left">Created</th>
                        <th class="p-3 text-left w-32">Actions</th>
                    </tr>
                </thead>
                <tbody id="catBody">
                    <?php
                    $map = []; foreach ($list as $c) $map[$c['id']]=$c['title'];

                    $renderRow = function($node,$level=0) use (&$renderRow,$map){
                        $indent = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;",$level);
                        $parent = $node['parent_id'] ? ($map[$node['parent_id']] ?? '-') : '-';

                        // path for category image
                        $imgSrc = '';
                        if (!empty($node['image'])) {
                            $imgVal = trim($node['image']);
                            // Full URL
                            if (preg_match('#^https?://#i', $imgVal)) {
                                $imgSrc = $imgVal;
                            }
                            // Absolute path starting with /
                            elseif (strpos($imgVal, '/') === 0) {
                                $imgSrc = $imgVal;
                            }
                            // Path starting with "assets/" (from media library)
                            elseif (strpos($imgVal, 'assets/') === 0) {
                                $imgSrc = '/' . $imgVal;
                            }
                            // Just filename
                            else {
                                $imgSrc = '/assets/uploads/categories/' . ltrim($imgVal, '/');
                            }
                        }

                        // tree lines visual (optional, simple indent for now)
                        $treeIcon = $level > 0 ? '<span class="text-gray-300 mr-1">└──</span>' : '';

                        echo "<tr class='border-b hover:bg-gray-50 transition' data-title='".strtolower($node['title'])."'>";

                        // IMAGE CELL
                        echo "<td class='p-3'>";
                        if ($imgSrc) {
                            echo "<img src='".htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8')."' alt='' class='w-10 h-10 object-contain rounded border border-gray-200 bg-white'>";
                        } else {
                            echo "<span class='text-xs text-slate-400'>—</span>";
                        }
                        echo "</td>";

                        // TITLE CELL
                        echo "<td class='p-3'>{$indent}{$treeIcon}<strong>".htmlspecialchars($node['title'])."</strong></td>";
                        echo "<td class='p-3 text-gray-500'>".htmlspecialchars($node['slug'])."</td>";
                        echo "<td class='p-3 text-gray-500'>".htmlspecialchars($parent)."</td>";
                        echo "<td class='p-3 text-gray-500'>".date('d M Y',strtotime($node['created_at']))."</td>";

                        echo "<td class='p-3'>
                                <div class='flex gap-3'>
                                    <a href='add_category.php?edit={$node['id']}' class='text-indigo-600 font-semibold hover:text-indigo-800 transition'>Edit</a>
                                    <a href='delete_category.php?id={$node['id']}'
                                       onclick=\"return confirm('Delete category? This might affect products.');\"
                                       class='text-red-600 font-semibold hover:text-red-800 transition'>Delete</a>
                                </div>
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

<script>
// LIVE SEARCH
const input = document.getElementById("catSearch");
input.addEventListener("input", () => {
    const q = input.value.toLowerCase();
    document.querySelectorAll("#catBody tr").forEach(row => {
        const t = row.dataset.title;
        // Simple search: show if title matches. 
        // Note: nesting structure visual might look weird if filtering, but acceptable for admin list.
        row.style.display = !q || t.includes(q) ? "" : "none";
    });
});
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>