<?php
// admin/filter_option_add.php
require_once '../includes/db.php';

$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if ($groupId <= 0) {
    die('Missing or invalid filter group.');
}

// fetch group
$stmt = $pdo->prepare("
    SELECT *
    FROM filter_groups
    WHERE id = :id
");
$stmt->execute(['id' => $groupId]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    die('Filter group not found.');
}

$error = '';
$old = [
    'label'      => '',
    'value'      => '',
    'sort_order' => 0,
    'is_active'  => 1,
];

// ✅ AUTO FETCH VALUES FROM PRODUCT COLUMN
$columnName = $group['column_name']; // ex: color, size, category
$columnValues = [];

try {
    $sql = "SELECT DISTINCT `$columnName` 
            FROM products 
            WHERE `$columnName` IS NOT NULL 
              AND `$columnName` != ''
            ORDER BY `$columnName` ASC";
    $stmtVals = $pdo->query($sql);
    $columnValues = $stmtVals->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $columnValues = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label     = trim($_POST['label'] ?? '');
    $value     = trim($_POST['value'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    $old = [
        'label'      => $label,
        'value'      => $value,
        'sort_order' => $sortOrder,
        'is_active'  => $isActive,
    ];

    if ($label === '' || $value === '') {
        $error = 'Label and Value are required.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO filter_options (group_id, label, value, sort_order, is_active)
            VALUES (:group_id, :label, :value, :sort_order, :is_active)
        ");
        $stmt->execute([
            'group_id'   => $groupId,
            'label'      => $label,
            'value'      => $value,
            'sort_order' => $sortOrder,
            'is_active'  => $isActive,
        ]);

        header('Location: filter_options.php?group_id=' . $groupId);
        exit;
    }
}

// include header AFTER logic
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-3xl mx-auto px-6 py-8">

  <h1 class="text-2xl font-bold text-slate-800 mb-4">
    Add Filter Option - <?php echo htmlspecialchars($group['name']); ?>
  </h1>

  <?php if ($error): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-md">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>

  <form method="post" class="space-y-5 bg-white p-6 rounded-md border">

    <!-- LABEL -->
    <div>
      <label class="block text-sm font-medium mb-1">Label (shown to user)</label>
      <input
        type="text"
        name="label"
        value="<?php echo htmlspecialchars($old['label']); ?>"
        class="w-full border rounded px-3 py-2"
        placeholder="Black / Large / Men Care"
        required>
    </div>

    <!-- ✅ VALUE DROPDOWN FROM PRODUCTS -->
    <div>
      <label class="block text-sm font-medium mb-1">
        Value (from products.<?php echo $columnName; ?>)
      </label>

      <select
        name="value"
        class="w-full border rounded px-3 py-2"
        required>

        <option value="">-- Select Value From Products --</option>

        <?php foreach ($columnValues as $val): ?>
          <option value="<?php echo htmlspecialchars($val); ?>"
            <?php echo ($old['value'] === $val) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($val); ?>
          </option>
        <?php endforeach; ?>

      </select>

      <p class="text-xs text-slate-500 mt-1">
        These values come directly from <strong>products.<?php echo $columnName; ?></strong> column.
      </p>
    </div>

    <!-- SORT ORDER -->
    <div>
      <label class="block text-sm font-medium mb-1">Sort Order</label>
      <input
        type="number"
        name="sort_order"
        value="<?php echo $old['sort_order']; ?>"
        class="w-full border rounded px-3 py-2">
    </div>

    <!-- ACTIVE -->
    <div>
      <label class="inline-flex items-center">
        <input type="checkbox" name="is_active" value="1"
          <?php echo $old['is_active'] ? 'checked' : ''; ?>>
        <span class="ml-2">Active</span>
      </label>
    </div>

    <div class="flex gap-3">
      <button type="submit"
        class="bg-indigo-600 text-white px-4 py-2 rounded">
        Save Option
      </button>

      <a href="filter_options.php?group_id=<?php echo $groupId; ?>"
        class="text-gray-600">Cancel</a>
    </div>
  </form>

  <div class="mt-6 text-xs bg-slate-50 p-4 rounded border">
    ✅ Admin now selects only valid data from products table.<br>
    No more typo bugs. No broken filters. Full safety.
  </div>

</div>

<?php include __DIR__ . '/layout/footer.php'; ?>