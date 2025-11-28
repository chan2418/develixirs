<?php
require_once '../includes/db.php';

$error = '';

// 🔹 Load product table columns for dropdown
$productColumns = [];
try {
    $stmtCols = $pdo->query("SHOW COLUMNS FROM products");
    $productColumns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $productColumns = [];
}

// 🔹 Handle form submit BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $paramKey   = trim($_POST['param_key'] ?? '');
    $columnName = trim($_POST['column_name'] ?? '');
    $sortOrder  = (int)($_POST['sort_order'] ?? 0);
    $isActive   = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $paramKey === '' || $columnName === '') {
        $error = 'Name, Param Key and Column Name are required.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO filter_groups (name, param_key, column_name, sort_order, is_active)
            VALUES (:name, :param_key, :column_name, :sort_order, :is_active)
        ");
        $stmt->execute([
            'name'        => $name,
            'param_key'   => $paramKey,
            'column_name' => $columnName,
            'sort_order'  => $sortOrder,
            'is_active'   => $isActive,
        ]);

        // ✅ redirect safely (no output has been sent yet)
        header('Location: filter_groups.php');
        exit;
    }
}

// 🔹 Only now include header (which prints HTML)
include __DIR__ . '/layout/header.php';

// helper: previously selected column (after validation error)
$selectedColumn = $_POST['column_name'] ?? '';
?>
<div class="max-w-4xl mx-auto px-6 py-8">
  <!-- Page header -->
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">Add Filter Group</h1>
      <p class="text-sm text-slate-500 mt-1">
        Define a reusable filter group like <strong>Color</strong>, <strong>Size</strong>, or <strong>Range</strong>.
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
          value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
        >
        <p class="text-xs text-slate-500 mt-1">
          This is the label the customer sees on the shop page. Example: <strong>Color</strong>, <strong>Size</strong>, <strong>Range</strong>.
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
          value="<?php echo htmlspecialchars($_POST['param_key'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
          required
        >
          <option value="">-- Select column from products table --</option>
          <?php foreach ($productColumns as $col): ?>
            <?php
              $field = $col['Field'];      // column name
              $type  = $col['Type'];       // column type (for info only)
              $isSelected = ($field === $selectedColumn) ? 'selected' : '';
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
            value="<?php echo htmlspecialchars($_POST['sort_order'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>"
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
              <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>
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
          Save Filter Group
        </button>
      </div>
    </form>
  </div>

  <!-- Example helper -->
  <div class="mt-6 bg-slate-50 border border-slate-200 rounded-lg p-4 text-xs text-slate-600">
    <div class="font-semibold mb-1">Example setup:</div>
    <ul class="list-disc pl-5 space-y-1">
      <li><strong>Color</strong> → Param Key: <code>color</code>, Column: <code>color</code>, Sort: 1</li>
      <li><strong>Size</strong> → Param Key: <code>size</code>, Column: <code>size</code>, Sort: 2</li>
      <li><strong>Range</strong> → Param Key: <code>range</code>, Column: <code>product_range</code>, Sort: 3</li>
    </ul>
  </div>
</div>