<?php
require_once '../includes/db.php';

$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: filter_groups.php');
    exit;
}

// Fetch existing filter group
$stmt = $pdo->prepare("SELECT * FROM filter_groups WHERE id = ?");
$stmt->execute([$id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    header('Location: filter_groups.php');
    exit;
}

// 🔹 Load product table columns for dropdown
$productColumns = [];
try {
    $stmtCols = $pdo->query("SHOW COLUMNS FROM products");
    $productColumns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $productColumns = [];
}
// 🔹 Load Categories for Dropdown (Top Level Only)
$categories = [];
try {
    $stmtCats = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY name ASC");
    $categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}


// 🔹 Handle form submit BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $paramKey   = trim($_POST['param_key'] ?? '');
    $columnName = trim($_POST['column_name'] ?? '');
    $sortOrder  = (int)($_POST['sort_order'] ?? 0);
    $isActive   = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $paramKey === '') {
        $error = 'Name and Param Key are required.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE filter_groups 
            SET name = :name, 
                param_key = :param_key, 
                column_name = :column_name, 
                sort_order = :sort_order, 
                is_active = :is_active,
                category_id = :category_id
            WHERE id = :id
        ");
        $stmt->execute([
            'name'        => $name,
            'param_key'   => $paramKey,
            'column_name' => $columnName, // Can be empty
            'sort_order'  => $sortOrder,
            'is_active'   => $isActive,
            'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            'id'          => $id,
        ]);

        // ✅ redirect safely (no output has been sent yet)
        header('Location: filter_groups.php');
        exit;
    }
}

// 🔹 Only now include header (which prints HTML)
include __DIR__ . '/layout/header.php';

// Use POST data if validation failed, otherwise use DB data
$name       = $_POST['name'] ?? $group['name'];
$paramKey   = $_POST['param_key'] ?? $group['param_key'];
$columnName = $_POST['column_name'] ?? $group['column_name'];
$sortOrder  = $_POST['sort_order'] ?? $group['sort_order'];
$isActive   = isset($_POST['is_active']) ? $_POST['is_active'] : $group['is_active'];
?>
<div class="max-w-4xl mx-auto px-6 py-8">
  <!-- Page header -->
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">Edit Filter Group</h1>
      <p class="text-sm text-slate-500 mt-1">
        Modify filter group settings for <strong><?php echo htmlspecialchars($group['name']); ?></strong>.
      </p>
    </div>
    <a
      href="filter_groups.php"
      class="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-50"
    >
      ← Back to Filter Groups
    </a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="mb-4 px-4 py-3 rounded-md bg-red-50 border border-red-200 text-sm text-red-700">
      <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php endif; ?>

  <div class="bg-white border border-slate-200 rounded-lg shadow-sm">
    <form method="post" class="p-6 space-y-6">
      <!-- Name -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          Name (shown in filter)
        </label>
        <input
          type="text"
          name="name"
          class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Color / Size / Range"
          value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
        >
        <p class="text-xs text-slate-500 mt-1">
          This is the label the customer sees on the shop page. Example: <strong>Color</strong>, <strong>Size</strong>, <strong>Range</strong>.
        </p>
      </div>

      </div>

      <!-- Category (Generic vs Specific) -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          Applies To (Category)
        </label>
        <select
          name="category_id"
          class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">-- Generic / Common Filter (All Products) --</option>
          <?php 
             $currentCat = $_POST['category_id'] ?? $group['category_id'] ?? null;
             foreach ($categories as $cat): 
          ?>
             <option value="<?= $cat['id'] ?>" <?= ($currentCat == $cat['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
             </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">
          If selected, this filter will <strong>only</strong> appear on that Category's page.
        </p>
      </div>

      <!-- Param Key -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          Param Key (used in URL)
        </label>
        <input
          type="text"
          name="param_key"
          class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="color / size / range"
          value="<?php echo htmlspecialchars($paramKey, ENT_QUOTES, 'UTF-8'); ?>"
        >
        <p class="text-xs text-slate-500 mt-1">
          Used in query string. Example:
          <code class="bg-slate-100 px-1 rounded">?color[]=Black&amp;color[]=White</code>
        </p>
      </div>

      <!-- Column Name (DROPDOWN from products table) -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          Product Column Name
        </label>

        <select
          name="column_name"
          class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">-- Select column from products table --</option>
          <?php foreach ($productColumns as $col): ?>
            <?php
              $field = $col['Field'];      // column name
              $type  = $col['Type'];       // column type (for info only)
              $isSelected = ($field === $columnName) ? 'selected' : '';
            ?>
            <option value="<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSelected; ?>>
              <?php echo htmlspecialchars($field . ' (' . $type . ')', ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <p class="text-xs text-slate-500 mt-1">
          This must match a column in your <code class="bg-slate-100 px-1 rounded">products</code> table.
          <br>Example: <strong>color</strong>, <strong>size</strong>, <strong>skin_type</strong>, <strong>brand</strong>.
        </p>
      </div>

      <!-- Sort + Active in 2-col grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">
            Sort Order
          </label>
          <input
            type="number"
            name="sort_order"
            class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            value="<?php echo htmlspecialchars($sortOrder, ENT_QUOTES, 'UTF-8'); ?>"
          >
          <p class="text-xs text-slate-500 mt-1">
            Lower number appears first in Filter By list. Example: Color (1), Size (2), Range (3).
          </p>
        </div>

        <div class="flex items-end">
          <label class="inline-flex items-center gap-2">
            <input
              type="checkbox"
              name="is_active"
              class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
              <?php echo $isActive ? 'checked' : ''; ?>
            >
            <span class="text-sm text-slate-700">Active</span>
          </label>
        </div>
      </div>

      <!-- Buttons -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <a
          href="filter_groups.php"
          class="inline-flex items-center px-4 py-2 rounded-md border border-slate-200 text-sm text-slate-700 hover:bg-slate-50"
        >
          Cancel
        </a>
        <button
          type="submit"
          class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-700 shadow-sm"
        >
          Update Filter Group
        </button>
      </div>
    </form>
  </div>

  <!-- Options shortcut -->
  <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
    <div class="font-semibold mb-1">Manage Options</div>
    <p class="mb-2">After updating this filter group, you can manage its options (values).</p>
    <a
      href="filter_options.php?group_id=<?php echo $id; ?>"
      class="inline-flex items-center px-3 py-1.5 rounded-md bg-blue-100 text-blue-800 hover:bg-blue-200 text-xs font-medium"
    >
      → Manage Options for this Group
    </a>
  </div>
</div>
