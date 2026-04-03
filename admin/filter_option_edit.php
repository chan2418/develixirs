<?php
// admin/filter_option_edit.php
require_once '../includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if ($id <= 0 || $groupId <= 0) {
    echo '<div style="padding:20px;color:red;">Invalid ID or Group ID.</div>';
    exit;
}

// Fetch Option
$stmt = $pdo->prepare("SELECT * FROM filter_options WHERE id = ? AND group_id = ?");
$stmt->execute([$id, $groupId]);
$option = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$option) {
    echo '<div style="padding:20px;color:red;">Option not found.</div>';
    exit;
}

// Fetch Group
$stmtG = $pdo->prepare("SELECT * FROM filter_groups WHERE id = ?");
$stmtG->execute([$groupId]);
$group = $stmtG->fetch(PDO::FETCH_ASSOC);

// ✅ AUTO FETCH VALUES FROM PRODUCT COLUMN
$columnName = $group['column_name'] ?? ''; 
$columnValues = [];

if (!empty($columnName)) {
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
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label     = trim($_POST['label'] ?? '');
    $value     = trim($_POST['value'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    // If value is empty, default to label
    if ($value === '') {
        $value = $label;
    }

    if ($label === '') {
        $error = 'Label is required.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE filter_options 
            SET label = :label, 
                value = :value, 
                sort_order = :sort_order, 
                is_active = :is_active
            WHERE id = :id AND group_id = :group_id
        ");
        $stmt->execute([
            'label'      => $label,
            'value'      => $value,
            'sort_order' => $sortOrder,
            'is_active'  => $isActive,
            'id'         => $id,
            'group_id'   => $groupId,
        ]);
        
        // Refresh data
        $option['label']      = $label;
        $option['value']      = $value;
        $option['sort_order'] = $sortOrder;
        $option['is_active']  = $isActive;

        $message = "Option updated successfully.";
        // Redirect back
        header('Location: filter_options.php?group_id=' . $groupId);
        exit;
    }
}

include __DIR__ . '/layout/header.php';
?>
<div class="max-w-3xl mx-auto px-6 py-8">

  <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-800">
        Edit Option - <?php echo htmlspecialchars($group['name']); ?>
      </h1>
      <a href="filter_options.php?group_id=<?php echo $groupId; ?>" class="text-sm text-slate-500 hover:text-slate-800">← Back</a>
  </div>

  <?php if ($error): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-md">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>

  <form method="post" class="space-y-5 bg-white p-6 rounded-md border text-left">

    <!-- LABEL -->
    <div>
      <label class="block text-sm font-medium mb-1">Label</label>
      <input
        type="text"
        name="label"
        value="<?php echo htmlspecialchars($option['label']); ?>"
        class="w-full border rounded px-3 py-2"
        required>
    </div>

    <!-- VALUE -->
    <div>
      <label class="block text-sm font-medium mb-1">
        Value <?php echo (!empty($columnName)) ? "(from products.$columnName)" : "(internal val)"; ?>
      </label>

      <?php if (!empty($columnValues)): ?>
          <!-- Dropdown if we have column values -->
          <select
            name="value"
            class="w-full border rounded px-3 py-2">

            <option value="">-- Same as Label (Default) --</option>

            <?php foreach ($columnValues as $val): ?>
              <option value="<?php echo htmlspecialchars($val); ?>"
                <?php echo ($option['value'] === $val) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($val); ?>
              </option>
            <?php endforeach; ?>

          </select>
          <p class="text-xs text-slate-500 mt-1">
            Values from <strong>products.<?php echo htmlspecialchars($columnName); ?></strong>.
          </p>
      <?php else: ?>
          <!-- Free text input if no column or no values -->
          <input
            type="text"
            name="value"
            value="<?php echo htmlspecialchars($option['value']); ?>"
            class="w-full border rounded px-3 py-2"
            placeholder="Optional - will use Label if empty">
      <?php endif; ?>
    </div>

    <!-- SORT -->
    <div>
      <label class="block text-sm font-medium mb-1">Sort Order</label>
      <input
        type="number"
        name="sort_order"
        value="<?php echo $option['sort_order']; ?>"
        class="w-full border rounded px-3 py-2">
    </div>

    <!-- ACTIVE -->
    <div>
      <label class="inline-flex items-center">
        <input type="checkbox" name="is_active" value="1"
          <?php echo $option['is_active'] ? 'checked' : ''; ?>>
        <span class="ml-2">Active</span>
      </label>
    </div>

    <div class="flex gap-3 pt-4">
      <button type="submit"
        class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700">
        Update Option
      </button>

      <a href="filter_options.php?group_id=<?php echo $groupId; ?>"
        class="px-4 py-2 border rounded text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
  </form>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
